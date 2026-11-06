<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (Encapsulated in web middleware for session persistence)
|--------------------------------------------------------------------------
*/

Route::middleware(['web'])->group(function () {
    // Root route with automatic Google OAuth callback handler if code or error query param is present
    Route::get('/', function (\Illuminate\Http\Request $request) {
        if ($request->filled('code') || $request->filled('error')) {
            return app(\App\Http\Controllers\Auth\GoogleAuthController::class)->callback($request);
        }
        return view('app');
    })->name('home');

    // Google Socialite Redirect & Callback
    Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

    // Google API token endpoint and Auth state
    Route::post('/api/auth/google', [GoogleAuthController::class, 'authenticateGoogle'])->name('api.auth.google');
    Route::get('/api/auth/me', [GoogleAuthController::class, 'me'])->name('api.auth.me');
    Route::post('/api/auth/profile', [GoogleAuthController::class, 'updateProfile'])->name('api.auth.profile');
    Route::put('/api/auth/profile', [GoogleAuthController::class, 'updateProfile'])->name('api.auth.profile.put');
    Route::post('/api/auth/logout', [GoogleAuthController::class, 'logout'])->name('api.auth.logout');

    // Razorpay Web Payment Routes
    Route::get('/razorpay', [\App\Http\Controllers\RazorpayController::class, 'index'])->name('razorpay.index');
    Route::post('/razorpay/payment', [\App\Http\Controllers\RazorpayController::class, 'payment'])->name('razorpay.payment');
    Route::post('/razorpay/success', [\App\Http\Controllers\RazorpayController::class, 'success'])->name('razorpay.success');
});

// React / Single-Page Application fallback
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
