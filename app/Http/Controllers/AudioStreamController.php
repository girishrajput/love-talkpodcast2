<?php

namespace App\Http\Controllers;

use App\Models\Episode;
use App\Services\MediaStorageService;
use App\Services\MembershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AudioStreamController extends Controller
{
    protected MembershipService $membershipService;
    protected MediaStorageService $storageService;

    public function __construct(MembershipService $membershipService, MediaStorageService $storageService)
    {
        $this->membershipService = $membershipService;
        $this->storageService = $storageService;
    }

    /**
     * Protected audio streaming endpoint with 60s preview gatekeeper
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $episode = Episode::where('id', $id)
            ->orWhere('slug', $id)
            ->first();

        if (!$episode) {
            return response()->json(['error' => 'Episode not found'], 404);
        }

        $audioUrl = $this->storageService->getUrl($episode->audio_url);

        // 1. If FREE episode, allow unrestricted access
        if ($episode->isFree()) {
            return response()->json([
                'accessGranted' => true,
                'audioUrl' => $audioUrl,
                'previewOnly' => false,
                'duration' => $episode->audio_duration,
            ]);
        }

        // 2. Check user authorization
        $user = Auth::user();
        if (!$user && $request->has('userId')) {
            $user = \App\Models\User::find($request->query('userId'));
        }

        $hasAccess = $this->membershipService->hasPremiumAccess($user);

        if ($hasAccess) {
            return response()->json([
                'accessGranted' => true,
                'audioUrl' => $audioUrl,
                'previewOnly' => false,
                'duration' => $episode->audio_duration,
            ]);
        }

        // 3. For unauthenticated / non-premium listeners, restrict to preview duration
        $previewLimit = $episode->preview_duration ?: 60;

        return response()->json([
            'accessGranted' => false,
            'audioUrl' => $audioUrl,
            'previewOnly' => true,
            'previewDuration' => $previewLimit,
            'message' => 'Unlock Love Talk Premium to listen to the full episode.',
        ]);
    }
}
