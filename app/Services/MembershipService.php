<?php

namespace App\Services;

use App\Enums\MembershipStatus;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\User;
use Carbon\Carbon;

class MembershipService
{
    /**
     * Check if a user has active premium access
     */
    public function hasPremiumAccess(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $membership = Membership::where('user_id', $user->id)
            ->where('status', MembershipStatus::ACTIVE)
            ->first();

        if (!$membership) {
            return false;
        }

        // Expire membership if end date has passed
        if (now()->gt($membership->end_date)) {
            $membership->update(['status' => MembershipStatus::EXPIRED]);
            return false;
        }

        return true;
    }

    /**
     * Activate or renew user membership
     */
    public function activateMembership(User $user, MembershipPlan $plan, ?string $subscriptionId = null, ?string $customerId = null): Membership
    {
        $durationMonths = $plan->billing_period === 'month' ? 1 : 12;
        $startDate = now();
        $endDate = now()->addMonths($durationMonths);

        // Check for existing active membership
        $existing = Membership::where('user_id', $user->id)->first();

        if ($existing) {
            $existing->update([
                'plan_id' => $plan->id,
                'status' => MembershipStatus::ACTIVE,
                'razorpay_customer_id' => $customerId ?? $existing->razorpay_customer_id,
                'razorpay_subscription_id' => $subscriptionId ?? $existing->razorpay_subscription_id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'auto_renew' => true,
            ]);

            return $existing;
        }

        return Membership::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => MembershipStatus::ACTIVE,
            'razorpay_customer_id' => $customerId,
            'razorpay_subscription_id' => $subscriptionId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'auto_renew' => true,
        ]);
    }
}
