<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Google OAuth Login
Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
Route::get('/api/auth/google', [GoogleAuthController::class, 'redirect']);
Route::get('/api/auth/callback/google', [GoogleAuthController::class, 'callback']);

// App Single-Page / Inertia fallback
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
