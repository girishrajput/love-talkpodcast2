<?php

use App\Http\Controllers\AudioStreamController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\EpisodeController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\RazorpayWebhookController;
use App\Http\Controllers\SubscriberController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Auth Check, Google Sign-In & Session
Route::get('/auth/me', [GoogleAuthController::class, 'me']);
Route::post('/auth/google', [GoogleAuthController::class, 'authenticateGoogle']);
Route::put('/auth/profile', [GoogleAuthController::class, 'updateProfile']);
Route::post('/auth/logout', [GoogleAuthController::class, 'logout']);

// Public Episodes & Comments
Route::get('/episodes', [EpisodeController::class, 'index']);
Route::post('/episodes', [EpisodeController::class, 'store']);
Route::put('/episodes', [EpisodeController::class, 'update']);
Route::delete('/episodes', [EpisodeController::class, 'destroy']);
Route::get('/episodes/{slug}', [EpisodeController::class, 'show']);
Route::post('/episodes/{slug}/comments', [EpisodeController::class, 'addComment']);

// Protected Audio Stream & Preview Gate
Route::get('/premium/audio/{id}', [AudioStreamController::class, 'show']);

// Public Membership Plans & Perks
Route::get('/membership-plans', [MembershipController::class, 'plans']);
Route::get('/plans', [MembershipController::class, 'plans']);
Route::get('/benefits', [SuperAdminController::class, 'getBenefits']);
Route::get('/settings', [SuperAdminController::class, 'getSettings']);

// Razorpay One-Time Orders & Subscriptions
Route::post('/membership/create-order', [MembershipController::class, 'createOrder']);
Route::post('/membership/verify-payment', [MembershipController::class, 'verifyPayment']);
Route::post('/membership/create-subscription', [MembershipController::class, 'createSubscription']);
Route::post('/membership/verify-subscription', [MembershipController::class, 'verifySubscription']);

// Compatibility aliases for frontend Razorpay subscription calls
Route::post('/razorpay/subscription/create', [MembershipController::class, 'createSubscription']);
Route::post('/razorpay/subscription/verify', [MembershipController::class, 'verifySubscription']);

// Razorpay Webhooks (HMAC Verified)
Route::post('/webhooks/razorpay', [RazorpayWebhookController::class, 'handle']);

// Media Uploads
Route::post('/upload', [UploadController::class, 'upload']);

// Newsletter Subscription
Route::post('/subscribers', [SubscriberController::class, 'store']);

// Super Admin Management APIs
Route::prefix('super-admin')->group(function () {
    Route::get('/plans', [SuperAdminController::class, 'getPlans']);
    Route::put('/plans/{id}', [SuperAdminController::class, 'updatePlan']);
    Route::get('/benefits', [SuperAdminController::class, 'getBenefits']);
    Route::put('/benefits/{id}', [SuperAdminController::class, 'updateBenefit']);
    Route::get('/users', [SuperAdminController::class, 'getUsers']);
    Route::put('/users/{id}', [SuperAdminController::class, 'updateUserRole']);
    Route::get('/settings', [SuperAdminController::class, 'getSettings']);
    Route::post('/settings', [SuperAdminController::class, 'updateSettings']);
});
