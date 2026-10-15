<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (Encapsulated in web middleware for session persistence)
|--------------------------------------------------------------------------
*/

Route::middleware(['web'])->group(function () {
    // Google Socialite Redirect & Callback
    Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

    // Google API token endpoint and Auth state
    Route::post('/api/auth/google', [GoogleAuthController::class, 'authenticateGoogle'])->name('api.auth.google');
    Route::get('/api/auth/me', [GoogleAuthController::class, 'me'])->name('api.auth.me');
    Route::post('/api/auth/profile', [GoogleAuthController::class, 'updateProfile'])->name('api.auth.profile');
    Route::put('/api/auth/profile', [GoogleAuthController::class, 'updateProfile'])->name('api.auth.profile.put');
    Route::post('/api/auth/logout', [GoogleAuthController::class, 'logout'])->name('api.auth.logout');
});

// React / Single-Page Application fallback
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
