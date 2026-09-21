<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\MembershipPlan;
use App\Models\PremiumBenefit;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuperAdminController extends Controller
{
    // -------------------------------------------------------------
    // Plans Management
    // -------------------------------------------------------------
    public function getPlans(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => MembershipPlan::orderBy('created_at', 'asc')->get(),
        ]);
    }

    public function updatePlan(Request $request, string $id): JsonResponse
    {
        $plan = MembershipPlan::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'target_audience' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'discounted_price' => 'nullable|numeric|min:0',
            'billing_period' => 'sometimes|string|in:month,year',
            'is_active' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
            'badge' => 'nullable|string',
            'benefits' => 'nullable|array',
            'razorpay_plan_id' => 'nullable|string',
        ]);

        $plan->update($validated);
        return response()->json(['success' => true, 'data' => $plan]);
    }

    // -------------------------------------------------------------
    // Benefits Management (25 Dynamic Perks)
    // -------------------------------------------------------------
    public function getBenefits(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => PremiumBenefit::orderBy('sort_order', 'asc')->get(),
        ]);
    }

    public function updateBenefit(Request $request, string $id): JsonResponse
    {
        $benefit = PremiumBenefit::findOrFail($id);
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'sometimes|string|max:100',
            'is_enabled' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer',
            'plans' => 'nullable|array',
        ]);

        $benefit->update($validated);
        return response()->json(['success' => true, 'data' => $benefit]);
    }

    // -------------------------------------------------------------
    // Users & Roles Management
    // -------------------------------------------------------------
    public function getUsers(): JsonResponse
    {
        $users = User::with(['activeMembership.plan'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    public function updateUserRole(Request $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $validated = $request->validate([
            'role' => 'sometimes|string|in:user,admin,super_admin',
            'status' => 'sometimes|string|in:active,suspended',
        ]);

        if (isset($validated['role'])) {
            $user->role = UserRole::from($validated['role']);
        }

        if (isset($validated['status'])) {
            $user->status = UserStatus::from($validated['status']);
        }

        $user->save();
        return response()->json(['success' => true, 'data' => $user]);
    }

    // -------------------------------------------------------------
    // Site Settings Management
    // -------------------------------------------------------------
    public function getSettings(): JsonResponse
    {
        $settings = SiteSetting::all()->pluck('setting_value', 'setting_key');
        return response()->json(['success' => true, 'data' => $settings]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $payload = $request->all();
        foreach ($payload as $key => $value) {
            SiteSetting::set($key, $value);
        }

        return response()->json(['success' => true, 'message' => 'Settings saved successfully']);
    }
}
