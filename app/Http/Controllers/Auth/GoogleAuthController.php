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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Sign in with a Google Identity Services ID token (the `credential`
     * returned by the "Sign in with Google" button). The token is verified
     * with Google before any user is created or logged in.
     */
    public function authenticateGoogle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'credential' => 'required|string',
        ]);

        $payload = $this->verifyGoogleIdToken($validated['credential']);
        if (!$payload) {
            return response()->json([
                'success' => false,
                'error' => 'Google sign-in could not be verified. Please try again.',
            ], 401);
        }

        $email = strtolower(trim($payload['email']));
        $name = $payload['name'] ?? explode('@', $email)[0];
        $avatarUrl = $payload['picture'] ?? "https://api.dicebear.com/7.x/avataaars/svg?seed=" . urlencode($email);
        $googleId = $payload['sub'];

        $initialSuperAdmin = env('INITIAL_SUPER_ADMIN_EMAIL');
        $isSuperAdminEmail = $initialSuperAdmin && strtolower($email) === strtolower($initialSuperAdmin);

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
        $request->session()->regenerate();

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

        $initialSuperAdmin = env('INITIAL_SUPER_ADMIN_EMAIL');
        $isSuperAdmin = $initialSuperAdmin && strtolower($email) === strtolower($initialSuperAdmin);

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

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
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
     * Verify a Google ID token via Google's tokeninfo endpoint and return its
     * claims, or null if it is invalid, expired, or issued for another client.
     */
    private function verifyGoogleIdToken(string $idToken): ?array
    {
        $clientId = config('services.google.client_id');
        if (empty($clientId)) {
            Log::error('GOOGLE_CLIENT_ID is not configured');
            return null;
        }

        try {
            $response = Http::timeout(10)->get('https://oauth2.googleapis.com/tokeninfo', [
                'id_token' => $idToken,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Google tokeninfo request failed: ' . $e->getMessage());
            return null;
        }

        if (!$response->successful()) {
            return null;
        }

        $claims = $response->json();

        $validIssuer = in_array($claims['iss'] ?? '', ['accounts.google.com', 'https://accounts.google.com'], true);
        $validAudience = ($claims['aud'] ?? '') === $clientId;
        $notExpired = (int) ($claims['exp'] ?? 0) > time();
        $emailVerified = filter_var($claims['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (!$validIssuer || !$validAudience || !$notExpired || !$emailVerified || empty($claims['email']) || empty($claims['sub'])) {
            Log::warning('Rejected Google ID token', ['aud' => $claims['aud'] ?? null, 'iss' => $claims['iss'] ?? null]);
            return null;
        }

        return $claims;
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
