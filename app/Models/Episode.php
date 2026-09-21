<?php

namespace App\Models;

use App\Enums\AccessType;
use App\Enums\EpisodeLanguage;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Episode extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'title',
        'episode_number',
        'slug',
        'description',
        'audio_url',
        'audio_duration',
        'cover_image',
        'language',
        'tags',
        'publish_date',
        'is_published',
        'access_type',
        'preview_duration',
        'transcript_en',
        'transcript_hi',
        'listens_count',
    ];

    protected $casts = [
        'episode_number' => 'integer',
        'audio_duration' => 'integer',
        'preview_duration' => 'integer',
        'listens_count' => 'integer',
        'is_published' => 'boolean',
        'language' => EpisodeLanguage::class,
        'access_type' => AccessType::class,
        'tags' => 'array',
        'publish_date' => 'datetime',
    ];

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function isFree(): bool
    {
        return $this->access_type === AccessType::FREE;
    }

    public function isPremium(): bool
    {
        return $this->access_type === AccessType::PREMIUM;
    }
}
