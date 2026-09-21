<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Episode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EpisodeController extends Controller
{
    /**
     * Display a listing of episodes
     */
    public function index(Request $request): JsonResponse
    {
        $query = Episode::query();

        if (!$request->boolean('include_drafts')) {
            $query->where('is_published', true);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('tag') && $request->input('tag') !== 'All') {
            $query->whereJsonContains('tags', $request->input('tag'));
        }

        if ($request->filled('access_type') && $request->input('access_type') !== 'ALL') {
            $query->where('access_type', $request->input('access_type'));
        }

        if ($request->filled('language') && $request->input('language') !== 'all') {
            $query->where('language', $request->input('language'));
        }

        $episodes = $query->orderBy('episode_number', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $episodes,
        ]);
    }

    /**
     * Display single episode by slug with comments
     */
    public function show(string $slug): JsonResponse
    {
        $episode = Episode::where('slug', $slug)
            ->orWhere('id', $slug)
            ->with(['comments' => function ($q) {
                $q->orderBy('created_at', 'desc');
            }])
            ->first();

        if (!$episode) {
            return response()->json(['success' => false, 'message' => 'Episode not found'], 404);
        }

        // Increment listen count
        $episode->increment('listens_count');

        return response()->json([
            'success' => true,
            'data' => $episode,
        ]);
    }

    /**
     * Store a newly created episode
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'episode_number' => 'required|integer|unique:episodes,episode_number',
            'slug' => 'nullable|string|unique:episodes,slug',
            'description' => 'required|string',
            'audio_url' => 'required|string',
            'audio_duration' => 'required|integer',
            'cover_image' => 'nullable|string',
            'language' => 'nullable|string|in:english,hindi,bilingual',
            'access_type' => 'nullable|string|in:FREE,PREMIUM',
            'tags' => 'nullable|array',
            'transcript_en' => 'nullable|string',
            'transcript_hi' => 'nullable|string',
            'is_published' => 'nullable|boolean',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $episode = Episode::create($validated);

        return response()->json([
            'success' => true,
            'data' => $episode,
        ], 201);
    }

    /**
     * Update the specified episode
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $episode = Episode::where('id', $id)->orWhere('slug', $id)->firstOrFail();

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'audio_url' => 'sometimes|required|string',
            'audio_duration' => 'sometimes|required|integer',
            'cover_image' => 'nullable|string',
            'language' => 'nullable|string|in:english,hindi,bilingual',
            'access_type' => 'nullable|string|in:FREE,PREMIUM',
            'tags' => 'nullable|array',
            'transcript_en' => 'nullable|string',
            'transcript_hi' => 'nullable|string',
            'is_published' => 'nullable|boolean',
        ]);

        $episode->update($validated);

        return response()->json([
            'success' => true,
            'data' => $episode,
        ]);
    }

    /**
     * Remove the specified episode
     */
    public function destroy(string $id): JsonResponse
    {
        $episode = Episode::where('id', $id)->orWhere('slug', $id)->firstOrFail();
        $episode->delete();

        return response()->json([
            'success' => true,
            'message' => 'Episode deleted successfully',
        ]);
    }

    /**
     * Post a comment for an episode
     */
    public function addComment(Request $request, string $slug): JsonResponse
    {
        $episode = Episode::where('slug', $slug)->orWhere('id', $slug)->firstOrFail();

        $validated = $request->validate([
            'user_name' => 'required|string|max:255',
            'user_email' => 'required|email|max:255',
            'content' => 'required|string',
            'rating' => 'nullable|integer|min:1|max:5',
            'user_avatar' => 'nullable|string',
        ]);

        $comment = $episode->comments()->create([
            'user_id' => auth()->id(),
            'user_name' => $validated['user_name'],
            'user_email' => $validated['user_email'],
            'content' => $validated['content'],
            'rating' => $validated['rating'] ?? 5,
            'user_avatar' => $validated['user_avatar'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $comment,
        ], 201);
    }
}
