<?php

return [
    
    'razorpay' => [
        'key_id' => (!empty(env('RAZORPAY_KEY')) && !str_contains(env('RAZORPAY_KEY'), 'YOUR_'))
            ? env('RAZORPAY_KEY')
            : (
                (!empty(env('RAZORPAY_KEY_ID')) && !str_contains(env('RAZORPAY_KEY_ID'), 'YOUR_'))
                ? env('RAZORPAY_KEY_ID')
                : (env('RAZORPAY_KEY') ?: env('RAZORPAY_KEY_ID', env('NEXT_PUBLIC_RAZORPAY_KEY_ID', 'rzp_test_lovetalk2026')))
            ),
        'key_secret' => (!empty(env('RAZORPAY_SECRET')) && !str_contains(env('RAZORPAY_SECRET'), 'YOUR_'))
            ? env('RAZORPAY_SECRET')
            : (
                (!empty(env('RAZORPAY_KEY_SECRET')) && !str_contains(env('RAZORPAY_KEY_SECRET'), 'YOUR_'))
                ? env('RAZORPAY_KEY_SECRET')
                : (env('RAZORPAY_SECRET') ?: env('RAZORPAY_KEY_SECRET', 'test_secret_key_lovetalk'))
            ),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET', 'test_webhook_secret'),
    ],
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
        'ca_bundle' => env('HTTP_CA_BUNDLE', 'C:/xampp/apache/bin/curl-ca-bundle.crt'),
    ],
];
