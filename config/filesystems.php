<?php

return [
    'default' => env('FILESYSTEM_DISK', 'public'),
    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],
        'public' => [
            'driver' => 'local',
            'root' => public_path('uploads'),
            'url' => env('APP_URL').'/uploads',
            'visibility' => 'public',
            'throw' => false,
        ],
        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID', env('S3_ACCESS_KEY_ID')),
            'secret' => env('AWS_SECRET_ACCESS_KEY', env('S3_SECRET_ACCESS_KEY')),
            'region' => env('AWS_DEFAULT_REGION', env('S3_REGION', 'us-east-1')),
            'bucket' => env('AWS_BUCKET', env('S3_BUCKET_NAME')),
            'url' => env('AWS_URL', env('STORAGE_PUBLIC_DOMAIN')),
            'endpoint' => env('AWS_ENDPOINT', env('S3_ENDPOINT')),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],
    ],
    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
