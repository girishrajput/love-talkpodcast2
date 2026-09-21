<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaStorageService
{
    /**
     * Upload an uploaded file to storage (S3/R2 or local public disk)
     */
    public function uploadFile(UploadedFile $file, string $subfolder = 'audio'): array
    {
        $extension = $file->getClientOriginalExtension();
        $safeName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $filename = "{$safeName}-" . time() . ".{$extension}";
        $path = "{$subfolder}/{$filename}";

        $disk = config('filesystems.default', 'public');
        if (env('STORAGE_PROVIDER') === 's3' || env('STORAGE_PROVIDER') === 'r2') {
            $disk = 's3';
        }

        $storedPath = Storage::disk($disk)->putFileAs($subfolder, $file, $filename, 'public');
        $url = Storage::disk($disk)->url($storedPath);

        return [
            'url' => $url,
            'key' => $path,
            'provider' => $disk,
            'filename' => $filename,
        ];
    }

    /**
     * Get accessible media URL
     */
    public function getUrl(?string $path): string
    {
        if (empty($path)) {
            return '';
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, '/uploads/')) {
            return $path;
        }

        return Storage::disk(config('filesystems.default', 'public'))->url($path);
    }
}
