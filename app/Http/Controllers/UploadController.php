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
        // 1. Check if file is provided
        if (!$request->hasFile('file')) {
            $maxUpload = ini_get('upload_max_filesize');
            $maxPost = ini_get('post_max_size');
            return response()->json([
                'success' => false,
                'error' => "No file was uploaded or the file exceeded the server limit (PHP upload_max: {$maxUpload}, post_max: {$maxPost}).",
            ], 400);
        }

        $file = $request->file('file');

        // 2. Check if upload was valid
        if (!$file->isValid()) {
            return response()->json([
                'success' => false,
                'error' => 'File upload error: ' . $file->getErrorMessage(),
            ], 400);
        }

        // 3. Determine subfolder & type
        $type = $request->input('type', 'audio');
        $subfolder = $request->input('subfolder') ?? ($type === 'image' || $type === 'cover' ? 'covers' : 'audio');
        if (!in_array($subfolder, ['audio', 'covers', 'misc'])) {
            $subfolder = 'audio';
        }

        // 4. Validate file type and size (up to 120MB)
        if ($type === 'audio' || $subfolder === 'audio') {
            $allowedExts = ['mp3', 'wav', 'm4a', 'aac', 'ogg'];
            $ext = strtolower($file->getClientOriginalExtension());
            if (!in_array($ext, $allowedExts)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid audio file format. Only MP3, WAV, M4A, AAC, and OGG are supported.',
                ], 422);
            }
        }

        try {
            $result = $this->storageService->uploadFile($file, $subfolder);

            return response()->json([
                'success' => true,
                'url' => $result['url'],
                'key' => $result['key'],
                'provider' => $result['provider'],
                'filename' => $result['filename'] ?? basename($result['url']),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Storage upload error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
