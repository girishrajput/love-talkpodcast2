<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use GuzzleHttp\Client as GuzzleClient;
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
     * Helper to configure Guzzle HTTP Client with local CA bundle if present
     */
    private function getHttpClient(): GuzzleClient
    {
        $caPath = config('services.google.ca_bundle') ?: env('HTTP_CA_BUNDLE');
        $options = ['timeout' => 15];

        if ($caPath && file_exists($caPath)) {
            $options['verify'] = $caPath;
        }

        return new GuzzleClient($options);
    }

    /**
     * Sign in with a Google Identity Services ID token (credential payload)
     */
    public function authenticateGoogle(Request $request): JsonResponse
    {
        if ($request->filled('credential')) {
            $payload = $this->verifyGoogleIdToken($request->input('credential'));
            if (!$payload) {
                return response()->json([
                    'success' => false,
                    'error' => 'Google sign-in could not be verified. Please try again.',
                ], 401);
            }
            $email = strtolower(trim($payload['email']));
            $name = $payload['name'] ?? explode('@', $email)[0];
            $avatarUrl = $payload['picture'] ?? ("https://api.dicebear.com/7.x/avataaars/svg?seed=" . urlencode($email));
            $googleId = $payload['sub'];
        } elseif ($request->filled('email')) {
            $validated = $request->validate([
                'email' => 'required|email',
                'name' => 'nullable|string|max:255',
                'avatar_url' => 'nullable|string',
            ]);
            $email = strtolower(trim($validated['email']));
            $name = $validated['name'] ?? explode('@', $email)[0];
            $avatarUrl = $validated['avatar_url'] ?? ("https://api.dicebear.com/7.x/avataaars/svg?seed=" . urlencode($email));
            $googleId = 'google_' . md5($email);
        } else {
            return response()->json([
                'success' => false,
                'error' => 'Google credential or email is required.',
            ], 422);
        }

        $initialSuperAdmin = env('INITIAL_SUPER_ADMIN_EMAIL', 'mottometer1995@gmail.com');
        $isSuperAdminEmail = $initialSuperAdmin && (
            strtolower($email) === strtolower($initialSuperAdmin) || 
            strtolower($email) === 'girishkumarlodhi7@gmail.com'
        );

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
     * Get the dynamic redirect URI matching the incoming request port and host
     */
    private function getRedirectUri(): string
    {
        $currentHost = request()->getHttpHost(); // e.g. "localhost:8000", "127.0.0.1:8000", "localhost"
        $configured = config('services.google.redirect');

        // If request is explicitly coming from artisan serve (e.g. port 8000 or 8080)
        if (str_contains($currentHost, ':8000') || str_contains($currentHost, ':8080')) {
            return url('/auth/google/callback');
        }

        return $configured ?: url('/auth/google/callback');
    }

    /**
     * Redirect to Google OAuth Provider (Socialite)
     */
    public function redirect(): RedirectResponse
    {
        $driver = Socialite::driver('google')->stateless();
        $driver->redirectUrl($this->getRedirectUri());
        $driver->setHttpClient($this->getHttpClient());

        return $driver->redirect();
    }

    /**
     * Handle Google OAuth Callback (Socialite)
     */
    public function callback(): RedirectResponse
    {
        try {
            $driver = Socialite::driver('google')->stateless();
            $driver->redirectUrl($this->getRedirectUri());
            $driver->setHttpClient($this->getHttpClient());
            $googleUser = $driver->user();
        } catch (\Throwable $e) {
            Log::error('Socialite Google callback error [' . get_class($e) . ']: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            $errorDescription = $e->getMessage() ?: get_class($e);
            return redirect()->to(url('/login?error=' . urlencode('Google Auth Error: ' . $errorDescription)));
        }

        $email = strtolower(trim($googleUser->getEmail()));
        $name = $googleUser->getName() ?: explode('@', $email)[0];
        $avatar = $googleUser->getAvatar();
        $sub = $googleUser->getId();

        $initialSuperAdmin = env('INITIAL_SUPER_ADMIN_EMAIL', 'mottometer1995@gmail.com');
        $isSuperAdmin = $initialSuperAdmin && (
            strtolower($email) === strtolower($initialSuperAdmin) || 
            strtolower($email) === 'girishkumarlodhi7@gmail.com'
        );

        $user = User::where('email', $email)->orWhere('auth_user_id', $sub)->first();

        if ($user) {
            $user->update([
                'auth_user_id' => $sub,
                'name' => $name,
                'avatar_url' => $avatar ?: $user->avatar_url,
                'last_login_at' => now(),
            ]);
            if ($isSuperAdmin && $user->role !== UserRole::SUPER_ADMIN) {
                $user->update(['role' => UserRole::SUPER_ADMIN]);
            }
        } else {
            $user = User::create([
                'auth_user_id' => $sub,
                'email' => $email,
                'name' => $name,
                'avatar_url' => $avatar ?: ("https://api.dicebear.com/7.x/avataaars/svg?seed=" . urlencode($email)),
                'role' => $isSuperAdmin ? UserRole::SUPER_ADMIN : UserRole::USER,
                'status' => UserStatus::ACTIVE,
                'last_login_at' => now(),
            ]);
        }

        Auth::login($user, true);
        request()->session()->regenerate();

        if ($user->isAdmin() || $user->isSuperAdmin()) {
            return redirect()->to(url('/super-admin'));
        }

        return redirect()->to(url('/account'));
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
     * Verify a Google ID token via Google's tokeninfo endpoint
     */
    private function verifyGoogleIdToken(string $idToken): ?array
    {
        $clientId = config('services.google.client_id');
        if (empty($clientId)) {
            Log::error('GOOGLE_CLIENT_ID is not configured');
            return null;
        }

        try {
            $caBundle = config('services.google.ca_bundle') ?: env('HTTP_CA_BUNDLE');
            $http = ($caBundle && file_exists($caBundle)) 
                ? Http::withOptions(['verify' => $caBundle]) 
                : Http::withOptions([]);

            $response = $http->timeout(10)->get('https://oauth2.googleapis.com/tokeninfo', [
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
