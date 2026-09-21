<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Authenticate or register user with Google OAuth credentials / profile
     */
    public function authenticateGoogle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'name' => 'nullable|string|max:255',
            'avatar_url' => 'nullable|string',
            'google_id' => 'nullable|string',
        ]);

        $email = strtolower(trim($validated['email']));
        $name = $validated['name'] ?? explode('@', $email)[0];
        $avatarUrl = $validated['avatar_url'] ?? "https://api.dicebear.com/7.x/avataaars/svg?seed=" . urlencode($email);
        $googleId = $validated['google_id'] ?? 'google_' . uniqid();

        $initialSuperAdmin = env('INITIAL_SUPER_ADMIN_EMAIL', 'superadmin@lovetalkpodcast.in');
        $isSuperAdminEmail = (strtolower($email) === strtolower($initialSuperAdmin)) || str_contains($email, 'superadmin');

        $user = User::where('email', $email)->first();

        if ($user) {
            $updateData = [
                'name' => $name,
                'avatar_url' => $avatarUrl ?: $user->avatar_url,
                'last_login_at' => now(),
            ];

            if ($isSuperAdminEmail && $user->role !== UserRole::SUPER_ADMIN) {
                $updateData['role'] = UserRole::SUPER_ADMIN;
            }

            if (!$user->auth_user_id) {
                $updateData['auth_user_id'] = $googleId;
            }

            $user->update($updateData);
        } else {
            $user = User::create([
                'auth_user_id' => $googleId,
                'email' => $email,
                'name' => $name,
                'avatar_url' => $avatarUrl,
                'role' => $isSuperAdminEmail ? UserRole::SUPER_ADMIN : UserRole::USER,
                'status' => UserStatus::ACTIVE,
                'last_login_at' => now(),
            ]);
        }

        // Refresh user relations
        $user->refresh();
        Auth::login($user, true);

        return response()->json([
            'success' => true,
            'authenticated' => true,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'avatar_url' => $user->avatar_url,
                'role' => $user->role instanceof UserRole ? $user->role->value : (string) $user->role,
                'status' => $user->status instanceof UserStatus ? $user->status->value : (string) $user->status,
                'is_admin' => $user->isAdmin(),
                'is_super_admin' => $user->isSuperAdmin(),
                'has_premium' => $user->hasPremiumAccess(),
            ],
            'membership' => $user->activeMembership,
            'isPremium' => $user->hasPremiumAccess(),
            'payments' => $user->payments()->latest()->take(10)->get(),
        ]);
    }

    /**
     * Redirect to Google OAuth Provider (Socialite)
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth Callback (Socialite)
     */
    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            Log::warning('Socialite Google callback error: ' . $e->getMessage());
            return redirect('/login?error=google_auth_failed');
        }

        $email = strtolower(trim($googleUser->getEmail()));
        $name = $googleUser->getName() ?: explode('@', $email)[0];
        $avatar = $googleUser->getAvatar();
        $sub = $googleUser->getId();

        $initialSuperAdmin = env('INITIAL_SUPER_ADMIN_EMAIL', 'superadmin@lovetalkpodcast.in');
        $isSuperAdmin = (strtolower($email) === strtolower($initialSuperAdmin)) || str_contains($email, 'superadmin');

        $user = User::where('email', $email)->first();

        if ($user) {
            $user->update([
                'name' => $name,
                'avatar_url' => $avatar ?: $user->avatar_url,
                'last_login_at' => now(),
            ]);
        } else {
            $user = User::create([
                'auth_user_id' => $sub,
                'email' => $email,
                'name' => $name,
                'avatar_url' => $avatar,
                'role' => $isSuperAdmin ? UserRole::SUPER_ADMIN : UserRole::USER,
                'status' => UserStatus::ACTIVE,
                'last_login_at' => now(),
            ]);
        }

        Auth::login($user, true);

        return redirect('/');
    }

    /**
     * Get Current Authenticated User & Profile
     */
    public function me(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user && $request->filled('userId')) {
            $user = User::find($request->query('userId'));
        }

        if (!$user && $request->filled('email')) {
            $user = User::where('email', strtolower($request->query('email')))->first();
        }

        if (!$user) {
            return response()->json([
                'success' => false,
                'authenticated' => false,
                'user' => null,
                'membership' => null,
                'isPremium' => false,
                'payments' => [],
            ]);
        }

        return response()->json([
            'success' => true,
            'authenticated' => true,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'avatar_url' => $user->avatar_url,
                'role' => $user->role instanceof UserRole ? $user->role->value : (string) $user->role,
                'status' => $user->status instanceof UserStatus ? $user->status->value : (string) $user->status,
                'is_admin' => $user->isAdmin(),
                'is_super_admin' => $user->isSuperAdmin(),
                'has_premium' => $user->hasPremiumAccess(),
            ],
            'membership' => $user->activeMembership,
            'isPremium' => $user->hasPremiumAccess(),
            'payments' => $user->payments()->latest()->take(10)->get(),
        ]);
    }

    /**
     * Update User Profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user && $request->filled('userId')) {
            $user = User::find($request->input('userId'));
        }

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'avatar_url' => 'nullable|string',
        ]);

        $user->update(array_filter($validated));

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'avatar_url' => $user->avatar_url,
                'role' => $user->role instanceof UserRole ? $user->role->value : (string) $user->role,
                'status' => $user->status instanceof UserStatus ? $user->status->value : (string) $user->status,
                'is_admin' => $user->isAdmin(),
                'is_super_admin' => $user->isSuperAdmin(),
                'has_premium' => $user->hasPremiumAccess(),
            ],
        ]);
    }

    /**
     * User Logout
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }
}
