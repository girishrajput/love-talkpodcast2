<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\MembershipOrder;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\PremiumBenefit;
use App\Models\User;
use App\Services\MembershipService;
use App\Services\RazorpayService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MembershipController extends Controller
{
    protected RazorpayService $razorpayService;
    protected MembershipService $membershipService;

    public function __construct(RazorpayService $razorpayService, MembershipService $membershipService)
    {
        $this->razorpayService = $razorpayService;
        $this->membershipService = $membershipService;
    }

    /**
     * Get all active plans and benefits
     */
    public function plans(): JsonResponse
    {
        $plans = MembershipPlan::where('is_active', true)->get();
        $benefits = PremiumBenefit::where('is_enabled', true)->orderBy('sort_order')->get();

        return response()->json([
            'success' => true,
            'plans' => $plans,
            'benefits' => $benefits,
        ]);
    }

    /**
     * Create Razorpay one-time order for membership
     */
    public function createOrder(Request $request): JsonResponse
    {
        $planId = $request->input('plan_id') ?? $request->input('planId');
        $userId = $request->input('user_id') ?? $request->input('userId');

        if (!$planId) {
            return response()->json(['success' => false, 'message' => 'plan_id is required'], 422);
        }

        $user = Auth::user() ?? ($userId ? User::find($userId) : null);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User must be authenticated'], 401);
        }

        $plan = MembershipPlan::where('id', $planId)
            ->orWhere('slug', $planId)
            ->first();

        if (!$plan) {
            return response()->json(['success' => false, 'message' => 'Plan not found'], 404);
        }

        $amountInPaise = (int) round(($plan->discounted_price ?? $plan->price) * 100);
        $receiptId = 'rcpt_' . substr($user->id, 0, 8) . '_' . time();

        try {
            $orderData = $this->razorpayService->createOrder($amountInPaise, $receiptId, $plan->currency);

            MembershipOrder::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'razorpay_order_id' => $orderData['orderId'],
                'amount' => $plan->discounted_price ?? $plan->price,
                'currency' => $plan->currency,
                'status' => 'created',
            ]);

            return response()->json([
                'success' => true,
                'orderId' => $orderData['orderId'],
                'amount' => $amountInPaise,
                'currency' => $plan->currency,
                'keyId' => config('services.razorpay.key_id', env('NEXT_PUBLIC_RAZORPAY_KEY_ID', 'rzp_test_lovetalk2026')),
            ]);
        } catch (Exception $e) {
            Log::error('Order creation error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Verify one-time payment and activate membership
     */
    public function verifyPayment(Request $request): JsonResponse
    {
        $orderId = $request->input('razorpay_order_id') ?? $request->input('orderId');
        $paymentId = $request->input('razorpay_payment_id') ?? $request->input('paymentId');
        $signature = $request->input('razorpay_signature') ?? $request->input('signature');
        $planId = $request->input('plan_id') ?? $request->input('planId');
        $userId = $request->input('user_id') ?? $request->input('userId');

        if (!$orderId || !$paymentId || !$signature) {
            return response()->json(['success' => false, 'message' => 'Missing payment credentials'], 422);
        }

        $isValid = $this->razorpayService->verifyPaymentSignature(
            $orderId,
            $paymentId,
            $signature
        );

        if (!$isValid) {
            return response()->json(['success' => false, 'message' => 'Invalid payment signature'], 400);
        }

        $order = MembershipOrder::where('razorpay_order_id', $orderId)->first();
        $user = Auth::user() ?? ($order ? $order->user : ($userId ? User::find($userId) : null));

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found for payment activation'], 404);
        }

        $plan = $order ? $order->plan : ($planId ? (MembershipPlan::where('id', $planId)->orWhere('slug', $planId)->first()) : null);
        if (!$plan) {
            return response()->json(['success' => false, 'message' => 'Plan not found'], 404);
        }

        // Activate membership
        $membership = $this->membershipService->activateMembership($user, $plan);

        // Record payment
        Payment::create([
            'user_id' => $user->id,
            'membership_id' => $membership->id,
            'razorpay_payment_id' => $paymentId,
            'razorpay_order_id' => $orderId,
            'amount' => $order ? $order->amount : ($plan->discounted_price ?? $plan->price),
            'currency' => $plan->currency,
            'status' => PaymentStatus::CAPTURED,
            'payment_method' => 'upi',
            'paid_at' => now(),
        ]);

        if ($order) {
            $order->update(['status' => 'paid']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment verified and membership activated',
            'membership' => $membership,
        ]);
    }

    /**
     * Create recurring subscription
     */
    public function createSubscription(Request $request): JsonResponse
    {
        $planId = $request->input('plan_id') ?? $request->input('planId');
        $userId = $request->input('user_id') ?? $request->input('userId');

        if (!$planId) {
            return response()->json(['success' => false, 'message' => 'plan_id is required'], 422);
        }

        $user = Auth::user() ?? ($userId ? User::find($userId) : null);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Authentication required'], 401);
        }

        $plan = MembershipPlan::where('id', $planId)->orWhere('slug', $planId)->first();
        if (!$plan) {
            return response()->json(['success' => false, 'message' => 'Plan not found'], 404);
        }

        $rzpPlanId = $plan->razorpay_plan_id ?: (
            $plan->slug === 'youth' ? env('NEXT_PUBLIC_RAZORPAY_PLAN_ID_1', 'plan_youth_annual')
                                    : env('NEXT_PUBLIC_RAZORPAY_PLAN_ID_2', 'plan_professional_annual')
        );

        try {
            $subData = $this->razorpayService->createSubscription($rzpPlanId);

            return response()->json([
                'success' => true,
                'subscriptionId' => $subData['subscriptionId'],
                'keyId' => config('services.razorpay.key_id', env('NEXT_PUBLIC_RAZORPAY_KEY_ID', 'rzp_test_lovetalk2026')),
            ]);
        } catch (Exception $e) {
            Log::error('Subscription creation error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Verify subscription signature and activate
     */
    public function verifySubscription(Request $request): JsonResponse
    {
        $subId = $request->input('razorpay_subscription_id') ?? $request->input('subscriptionId');
        $paymentId = $request->input('razorpay_payment_id') ?? $request->input('paymentId');
        $signature = $request->input('razorpay_signature') ?? $request->input('signature');
        $planId = $request->input('plan_id') ?? $request->input('planId');
        $userId = $request->input('user_id') ?? $request->input('userId');

        if (!$subId || !$paymentId || !$signature) {
            return response()->json(['success' => false, 'message' => 'Missing subscription verification parameters'], 422);
        }

        $isValid = $this->razorpayService->verifySubscriptionSignature(
            $subId,
            $paymentId,
            $signature
        );

        if (!$isValid) {
            return response()->json(['success' => false, 'message' => 'Invalid subscription signature'], 400);
        }

        $user = Auth::user() ?? ($userId ? User::find($userId) : null);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $plan = $planId ? (MembershipPlan::where('id', $planId)->orWhere('slug', $planId)->first()) : null;
        if (!$plan) {
            return response()->json(['success' => false, 'message' => 'Plan not found'], 404);
        }

        $membership = $this->membershipService->activateMembership($user, $plan, $subId);

        Payment::create([
            'user_id' => $user->id,
            'membership_id' => $membership->id,
            'razorpay_payment_id' => $paymentId,
            'razorpay_subscription_id' => $subId,
            'amount' => $plan->discounted_price ?? $plan->price,
            'currency' => $plan->currency,
            'status' => PaymentStatus::CAPTURED,
            'payment_method' => 'card',
            'paid_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subscription verified and membership activated',
            'membership' => $membership,
        ]);
    }
}
