<?php

namespace App\Http\Controllers;

use App\Services\MediaStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    protected MediaStorageService $storageService;

    public function __construct(MediaStorageService $storageService)
    {
        $this->storageService = $storageService;
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:102400', // max 100MB
            'subfolder' => 'nullable|string|in:audio,covers,misc',
        ]);

        $subfolder = $request->input('subfolder', 'audio');
        $file = $request->file('file');

        try {
            $result = $this->storageService->uploadFile($file, $subfolder);

            return response()->json([
                'success' => true,
                'url' => $result['url'],
                'key' => $result['key'],
                'provider' => $result['provider'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
