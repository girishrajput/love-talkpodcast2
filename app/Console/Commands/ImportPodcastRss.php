<?php

namespace App\Console\Commands;

use App\Models\Episode;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Imports (or re-syncs) episodes from the podcast's public RSS feed.
 *
 * Episodes are numbered by publish date (oldest = 1). Re-running is safe:
 * episodes are matched by slug and updated in place, and only new ones are added.
 * Existing local episodes that are not in the feed but occupy a needed
 * episode number are moved to 1001+ instead of being deleted.
 */
class ImportPodcastRss extends Command
{
    protected $signature = 'podcast:import-rss
        {url=https://feeds.hubhopper.com/22e94e72c9c6f93d6d4ea893fc2ce7b5.rss : Public RSS feed URL or a local .rss file}
        {--access=FREE : Access type for newly created episodes (FREE or PREMIUM)}
        {--dry-run : Show what would be imported without writing to the database}';

    protected $description = 'Import podcast episodes from a public RSS feed (Hubhopper, Apple, etc.)';

    private const ITUNES_NS = 'http://www.itunes.com/dtds/podcast-1.0.dtd';

    public function handle(): int
    {
        $access = strtoupper($this->option('access'));
        if (!in_array($access, ['FREE', 'PREMIUM'], true)) {
            $this->error('--access must be FREE or PREMIUM');
            return self::FAILURE;
        }

        $source = $this->argument('url');
        if (is_file($source)) {
            $this->info('Reading ' . $source);
            $body = file_get_contents($source);
        } else {
            $this->info('Fetching ' . $source);
            $response = Http::timeout(60)->get($source);
            if (!$response->successful()) {
                $this->error('Could not download feed (HTTP ' . $response->status() . ')');
                return self::FAILURE;
            }
            $body = $response->body();
        }

        $xml = @simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);
        if (!$xml || !isset($xml->channel->item)) {
            $this->error('Feed is not valid RSS');
            return self::FAILURE;
        }

        $items = [];
        foreach ($xml->channel->item as $item) {
            $parsed = $this->parseItem($item);
            if ($parsed) {
                $items[] = $parsed;
            }
        }

        // Oldest first so episode numbers follow release order.
        usort($items, fn ($a, $b) => $a['publish_date'] <=> $b['publish_date']);
        foreach ($items as $i => &$data) {
            $data['episode_number'] = $i + 1;
        }
        unset($data);

        $this->info(count($items) . ' episodes found in feed');

        if ($this->option('dry-run')) {
            $this->table(
                ['#', 'Date', 'Lang', 'Duration', 'Title'],
                array_map(fn ($d) => [
                    $d['episode_number'],
                    $d['publish_date']->toDateString(),
                    $d['language'],
                    gmdate('H:i:s', $d['audio_duration']),
                    Str::limit($d['title'], 60),
                ], $items)
            );
            return self::SUCCESS;
        }

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($items, $access, &$created, &$updated) {
            $feedSlugs = array_column($items, 'slug');

            // Move local-only episodes out of the 1..N range so numbers don't clash.
            $nextFree = max(1000, (int) Episode::max('episode_number')) + 1;
            Episode::whereNotIn('slug', $feedSlugs)
                ->where('episode_number', '<=', count($items))
                ->orderBy('episode_number')
                ->get()
                ->each(function (Episode $ep) use (&$nextFree) {
                    $this->line("  Moving local episode #{$ep->episode_number} \"{$ep->title}\" to #{$nextFree}");
                    $ep->update(['episode_number' => $nextFree++]);
                });

            // Park feed episodes on temporary numbers first, so renumbering never
            // trips the unique index mid-way.
            Episode::whereIn('slug', $feedSlugs)->each(function (Episode $ep) {
                $ep->update(['episode_number' => 900000 + $ep->episode_number]);
            });

            foreach ($items as $data) {
                $episode = Episode::where('slug', $data['slug'])->first();
                if ($episode) {
                    // Keep local edits to access type / transcripts / listens.
                    $episode->update($data);
                    $updated++;
                } else {
                    Episode::create($data + [
                        'access_type' => $access,
                        'is_published' => true,
                        'tags' => ['Love Talk Podcast'],
                    ]);
                    $created++;
                }
            }
        });

        $this->info("Done: {$created} created, {$updated} updated.");
        return self::SUCCESS;
    }

    private function parseItem(\SimpleXMLElement $item): ?array
    {
        $itunes = $item->children(self::ITUNES_NS);

        $audioUrl = isset($item->enclosure) ? (string) $item->enclosure['url'] : '';
        $title = $this->cleanText((string) $item->title);
        if ($audioUrl === '' || $title === '') {
            return null;
        }

        $link = (string) $item->link;
        $slugSource = trim((string) parse_url($link, PHP_URL_PATH), '/');
        // Hubhopper links look like /episode/<slug>/<id>; use the slug part.
        $parts = explode('/', $slugSource);
        $slug = Str::slug($parts[1] ?? '') ?: Str::slug($title);
        if ($slug === '') {
            $slug = 'episode-' . substr(md5((string) $item->guid ?: $audioUrl), 0, 10);
        }

        $description = $this->htmlToText((string) ($item->description ?: $itunes->summary));

        $cover = isset($itunes->image) ? (string) $itunes->image->attributes()->href : null;

        return [
            'title' => Str::limit($title, 250, ''),
            'slug' => Str::limit($slug, 250, ''),
            'description' => $description !== '' ? $description : $title,
            'audio_url' => $audioUrl,
            'audio_duration' => $this->parseDuration((string) $itunes->duration),
            'cover_image' => $cover ?: null,
            'language' => $this->detectLanguage($title, $description),
            'publish_date' => Carbon::parse((string) $item->pubDate),
        ];
    }

    private function detectLanguage(string $title, string $description): string
    {
        $t = mb_strtolower($title);
        $hindiInTitle = str_contains($t, 'hindi') || preg_match('/\p{Devanagari}/u', $title);
        $englishInTitle = str_contains($t, 'english');

        if ($hindiInTitle && $englishInTitle) {
            return 'bilingual';
        }
        if ($hindiInTitle) {
            return 'hindi';
        }
        if ($englishInTitle) {
            return 'english';
        }

        // Fall back to the script used in the description.
        $devanagari = preg_match_all('/\p{Devanagari}/u', $description);
        $latin = preg_match_all('/[a-zA-Z]/', $description);
        return $devanagari > $latin ? 'hindi' : 'english';
    }

    private function parseDuration(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }
        if (ctype_digit($value)) {
            return (int) $value;
        }
        // HH:MM:SS or MM:SS
        $seconds = 0;
        foreach (explode(':', $value) as $part) {
            $seconds = $seconds * 60 + (int) $part;
        }
        return $seconds;
    }

    private function cleanText(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Strip stray emoji variation selectors left by the feed, then tidy spaces.
        $text = preg_replace('/[\x{FE0F}\x{200D}]/u', '', $text);
        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    private function htmlToText(string $html): string
    {
        $html = preg_replace('#<br\s*/?>#i', "\n", $html);
        $html = preg_replace('#</(p|div|li|h[1-6])>#i', "\n", $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[\x{FE0F}\x{200D}]/u', '', $text);
        $text = preg_replace("/[ \t\x{00A0}]+/u", ' ', $text);
        $text = preg_replace("/ *\n */", "\n", $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        return trim($text);
    }
}
