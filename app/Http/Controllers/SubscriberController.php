<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriberController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'name' => 'nullable|string|max:255',
        ]);

        $subscriber = Subscriber::firstOrCreate(
            ['email' => strtolower($validated['email'])],
            ['name' => $validated['name'] ?? null, 'subscribed_at' => now()]
        );

        return response()->json([
            'success' => true,
            'message' => 'Thank you for subscribing to Love Talk Podcast updates!',
            'data' => $subscriber,
        ]);
    }
}
