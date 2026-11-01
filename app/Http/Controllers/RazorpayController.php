<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\User;
use App\Services\MembershipService;
use App\Services\RazorpayService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;

class RazorpayController extends Controller
{
    protected RazorpayService $razorpayService;
    protected MembershipService $membershipService;

    public function __construct(RazorpayService $razorpayService, MembershipService $membershipService)
    {
        $this->razorpayService = $razorpayService;
        $this->membershipService = $membershipService;
    }

    public function index()
    {
        return view('razorpay.index');
    }

    public function payment(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'amount' => 'required|numeric|min:1',
        ]);

        $amountInPaise = (int) round($request->amount * 100);
        $receipt = 'order_' . uniqid();

        try {
            $keyId = config('services.razorpay.key_id') ?: env('RAZORPAY_KEY', 'rzp_test_lovetalk2026');
            $keySecret = config('services.razorpay.key_secret') ?: env('RAZORPAY_SECRET', 'test_secret_key');

            $orderData = $this->razorpayService->createOrder($amountInPaise, $receipt, 'INR');
            $orderId = $orderData['orderId'];

            // Find or associate user
            $user = Auth::user();
            if (!$user) {
                $user = User::firstOrCreate(
                    ['email' => strtolower(trim($request->email))],
                    [
                        'name' => $request->name,
                        'avatar_url' => 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($request->email),
                        'role' => 'user',
                        'status' => 'active',
                    ]
                );
            }

            return view('razorpay.payment', [
                'orderId' => $orderId,
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'amount' => $request->amount,
                'razorpayKey' => $this->razorpayService->getKeyId(),
            ]);
        } catch (Exception $e) {
            Log::error('RazorpayController payment initiation failed: ' . $e->getMessage());
            return back()->with('error', 'Could not initiate payment: ' . $e->getMessage());
        }
    }

    public function success(Request $request)
    {
        $paymentId = $request->input('razorpay_payment_id');
        $orderId = $request->input('razorpay_order_id');
        $signature = $request->input('razorpay_signature');

        if (!$paymentId || !$orderId) {
            return redirect('/razorpay')->with('error', 'Payment failed. Missing payment details.');
        }

        // Verify signature if provided
        if ($signature && $this->razorpayService->isConfigured()) {
            $isValid = $this->razorpayService->verifyPaymentSignature($orderId, $paymentId, $signature);
            if (!$isValid) {
                return redirect('/razorpay')->with('error', 'Invalid payment signature.');
            }
        }

        $user = Auth::user();
        if (!$user && $request->filled('email')) {
            $user = User::where('email', strtolower(trim($request->email)))->first();
        }
        if (!$user) {
            $user = User::first();
        }

        // Check if plan matches amount or default to first plan
        $plan = MembershipPlan::where('discounted_price', $request->amount)
            ->orWhere('price', $request->amount)
            ->first() ?: MembershipPlan::first();

        $membership = null;
        if ($user && $plan) {
            $membership = $this->membershipService->activateMembership($user, $plan);
        }

        $payment = Payment::create([
            'user_id' => $user ? $user->id : User::first()->id,
            'membership_id' => $membership ? $membership->id : null,
            'razorpay_payment_id' => $paymentId,
            'razorpay_order_id' => $orderId,
            'amount' => $request->amount ?? 99.00,
            'currency' => 'INR',
            'status' => PaymentStatus::CAPTURED,
            'payment_method' => 'upi',
            'paid_at' => now(),
        ]);

        return redirect('/razorpay')->with([
            'success' => 'Payment Successful! Membership Activated.',
            'payment' => $payment,
        ]);
    }
}
