<?php

return [
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', env('APP_URL') . '/auth/google/callback'),
    ],
    'razorpay' => [
        'key_id' => env('RAZORPAY_KEY_ID', env('NEXT_PUBLIC_RAZORPAY_KEY_ID', 'rzp_test_lovetalk2026')),
        'key_secret' => env('RAZORPAY_KEY_SECRET', 'test_secret_key_lovetalk'),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET', 'test_webhook_secret'),
    ],
];
