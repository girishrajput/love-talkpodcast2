<?php

namespace App\Http\Controllers;

use App\Enums\MembershipStatus;
use App\Enums\PaymentStatus;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\User;
use App\Services\RazorpayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RazorpayWebhookController extends Controller
{
    protected RazorpayService $razorpayService;

    public function __construct(RazorpayService $razorpayService)
    {
        $this->razorpayService = $razorpayService;
    }

    public function handle(Request $request): JsonResponse
    {
        $signature = $request->header('X-Razorpay-Signature') ?? $request->header('x-razorpay-signature', '');
        $payload = $request->getContent();

        $isValid = $this->razorpayService->verifyWebhookSignature($payload, $signature);
        if (!$isValid) {
            Log::warning('Invalid Razorpay Webhook Signature');
            return response()->json(['error' => 'Invalid webhook signature'], 400);
        }

        $event = $request->json('event');
        $data = $request->json('payload');

        Log::info('Razorpay Webhook received: ' . $event);

        try {
            switch ($event) {
                case 'payment.captured':
                    $paymentEntity = $data['payment']['entity'] ?? null;
                    if ($paymentEntity) {
                        $orderId = $paymentEntity['order_id'] ?? null;
                        $paymentId = $paymentEntity['id'] ?? null;
                        $amount = ($paymentEntity['amount'] ?? 0) / 100;
                        $email = $paymentEntity['email'] ?? null;

                        if ($email) {
                            $user = User::where('email', $email)->first();
                            if ($user && $paymentId) {
                                Payment::firstOrCreate(
                                    ['razorpay_payment_id' => $paymentId],
                                    [
                                        'user_id' => $user->id,
                                        'razorpay_order_id' => $orderId,
                                        'amount' => $amount,
                                        'currency' => $paymentEntity['currency'] ?? 'INR',
                                        'status' => PaymentStatus::CAPTURED,
                                        'payment_method' => $paymentEntity['method'] ?? 'upi',
                                        'paid_at' => now(),
                                    ]
                                );
                            }
                        }
                    }
                    break;

                case 'subscription.charged':
                    $subEntity = $data['subscription']['entity'] ?? null;
                    $payEntity = $data['payment']['entity'] ?? null;

                    if ($subEntity) {
                        $subId = $subEntity['id'] ?? null;
                        $membership = Membership::where('razorpay_subscription_id', $subId)->first();

                        if ($membership) {
                            $membership->update([
                                'status' => MembershipStatus::ACTIVE,
                                'end_date' => now()->addYear(),
                            ]);

                            if ($payEntity) {
                                Payment::firstOrCreate(
                                    ['razorpay_payment_id' => $payEntity['id']],
                                    [
                                        'user_id' => $membership->user_id,
                                        'membership_id' => $membership->id,
                                        'razorpay_subscription_id' => $subId,
                                        'amount' => ($payEntity['amount'] ?? 0) / 100,
                                        'currency' => $payEntity['currency'] ?? 'INR',
                                        'status' => PaymentStatus::CAPTURED,
                                        'payment_method' => $payEntity['method'] ?? 'card',
                                        'paid_at' => now(),
                                    ]
                                );
                            }
                        }
                    }
                    break;

                case 'subscription.cancelled':
                case 'subscription.halted':
                    $subEntity = $data['subscription']['entity'] ?? null;
                    if ($subEntity && isset($subEntity['id'])) {
                        $membership = Membership::where('razorpay_subscription_id', $subEntity['id'])->first();
                        if ($membership) {
                            $membership->update(['status' => MembershipStatus::CANCELLED]);
                        }
                    }
                    break;
            }

            return response()->json(['status' => 'success', 'event' => $event]);
        } catch (\Exception $e) {
            Log::error('Webhook processing error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
