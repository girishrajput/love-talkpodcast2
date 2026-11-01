<?php

namespace App\Services;

use Exception;
use Razorpay\Api\Api;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RazorpayService
{
    protected ?Api $api = null;
    protected string $keyId;
    protected string $keySecret;
    protected string $webhookSecret;

    public function __construct()
    {
        $keyCandidates = [
            config('services.razorpay.key_id'),
            env('RAZORPAY_KEY'),
            env('RAZORPAY_KEY_ID'),
            env('NEXT_PUBLIC_RAZORPAY_KEY_ID'),
        ];
        $resolvedKey = '';
        foreach ($keyCandidates as $candidate) {
            if (!empty($candidate) && !str_contains($candidate, 'YOUR_')) {
                $resolvedKey = (string) $candidate;
                break;
            }
        }
        $this->keyId = $resolvedKey ?: (string) (config('services.razorpay.key_id') ?: env('RAZORPAY_KEY') ?: env('RAZORPAY_KEY_ID') ?: 'rzp_test_lovetalk2026');

        $secretCandidates = [
            config('services.razorpay.key_secret'),
            env('RAZORPAY_SECRET'),
            env('RAZORPAY_KEY_SECRET'),
        ];
        $resolvedSecret = '';
        foreach ($secretCandidates as $candidate) {
            if (!empty($candidate) && !str_contains($candidate, 'YOUR_')) {
                $resolvedSecret = (string) $candidate;
                break;
            }
        }
        $this->keySecret = $resolvedSecret ?: (string) (config('services.razorpay.key_secret') ?: env('RAZORPAY_SECRET') ?: env('RAZORPAY_KEY_SECRET') ?: 'test_secret_key_lovetalk');

        $this->webhookSecret = (string) (
            config('services.razorpay.webhook_secret')
            ?: env('RAZORPAY_WEBHOOK_SECRET', '')
        );

        if ($this->isConfigured()) {
            try {
                $this->api = new Api($this->keyId, $this->keySecret);
            } catch (\Throwable $e) {
                Log::warning('Razorpay Api instantiation failed: ' . $e->getMessage());
                $this->api = null;
            }
        }
    }

    public function isConfigured(): bool
    {
        return !empty($this->keyId) && 
               !empty($this->keySecret) && 
               !str_contains($this->keyId, 'YOUR_') && 
               !str_contains($this->keySecret, 'YOUR_') &&
               !str_contains($this->keyId, 'rzp_test_lovetalk');
    }

    public function getKeyId(): string
    {
        return $this->keyId ?: 'rzp_test_lovetalk2026';
    }

    public function getApi(): ?Api
    {
        return $this->api;
    }

    /**
     * Create Razorpay One-Time Order
     */
    public function createOrder(int $amountInPaise, string $receiptId, string $currency = 'INR'): array
    {
        if ($this->isConfigured()) {
            try {
                if ($this->api) {
                    $order = $this->api->order->create([
                        'receipt' => $receiptId,
                        'amount' => $amountInPaise,
                        'currency' => $currency,
                        'payment_capture' => 1,
                    ]);

                    return [
                        'orderId' => $order['id'],
                        'amount' => $order['amount'],
                        'currency' => $order['currency'],
                    ];
                }

                // Fallback direct HTTP
                $response = Http::withBasicAuth($this->keyId, $this->keySecret)
                    ->post('https://api.razorpay.com/v1/orders', [
                        'amount' => $amountInPaise,
                        'currency' => $currency,
                        'receipt' => $receiptId,
                        'payment_capture' => 1,
                    ]);

                if ($response->successful()) {
                    return [
                        'orderId' => $response->json('id'),
                        'amount' => $response->json('amount') ?? $amountInPaise,
                        'currency' => $response->json('currency') ?? $currency,
                    ];
                }

                Log::error('Razorpay order creation failed', ['response' => $response->json()]);
                throw new Exception($response->json('error.description') ?? 'Razorpay order creation failed');
            } catch (Exception $e) {
                Log::error('Razorpay createOrder exception: ' . $e->getMessage());
                throw $e;
            }
        }

        // Mock Order ID for development / testing without live keys
        return [
            'orderId' => 'order_' . time() . '_' . substr(md5(uniqid()), 0, 6),
            'amount' => $amountInPaise,
            'currency' => $currency,
        ];
    }

    /**
     * Create Razorpay Subscription
     */
    public function createSubscription(string $planId, int $totalCount = 12, int $customerNotify = 1): array
    {
        if ($this->isConfigured()) {
            if (empty($planId) || str_contains($planId, 'YOUR_')) {
                throw new Exception("Invalid Razorpay Plan ID: {$planId}");
            }

            try {
                if ($this->api) {
                    $sub = $this->api->subscription->create([
                        'plan_id' => $planId,
                        'total_count' => $totalCount,
                        'quantity' => 1,
                        'customer_notify' => $customerNotify,
                    ]);

                    return ['subscriptionId' => $sub['id']];
                }

                $response = Http::withBasicAuth($this->keyId, $this->keySecret)
                    ->post('https://api.razorpay.com/v1/subscriptions', [
                        'plan_id' => $planId,
                        'total_count' => $totalCount,
                        'quantity' => 1,
                        'customer_notify' => $customerNotify,
                    ]);

                if ($response->successful()) {
                    return ['subscriptionId' => $response->json('id')];
                }

                Log::error('Razorpay subscription creation failed', ['response' => $response->json()]);
                throw new Exception($response->json('error.description') ?? 'Razorpay subscription creation failed');
            } catch (Exception $e) {
                Log::error('Razorpay createSubscription exception: ' . $e->getMessage());
                throw $e;
            }
        }

        // Mock Subscription ID for local development
        return ['subscriptionId' => 'sub_' . time() . '_' . substr(md5(uniqid()), 0, 6)];
    }

    /**
     * Verify Razorpay Payment Signature
     */
    public function verifyPaymentSignature(string $orderId, string $paymentId, string $signature): bool
    {
        if (empty($signature) || empty($orderId) || empty($paymentId)) {
            return false;
        }

        if (!$this->isConfigured() && str_starts_with($signature, 'mock_sig_')) {
            return true;
        }

        try {
            if ($this->api) {
                $this->api->utility->verifyPaymentSignature([
                    'razorpay_order_id' => $orderId,
                    'razorpay_payment_id' => $paymentId,
                    'razorpay_signature' => $signature,
                ]);
                return true;
            }
        } catch (\Throwable $e) {
            Log::warning('Razorpay SDK signature verification failed: ' . $e->getMessage());
        }

        $expected = hash_hmac('sha256', "{$orderId}|{$paymentId}", $this->keySecret);
        return hash_equals($expected, $signature);
    }

    /**
     * Verify Razorpay Subscription Signature
     */
    public function verifySubscriptionSignature(string $subscriptionId, string $paymentId, string $signature): bool
    {
        if (empty($signature) || empty($subscriptionId) || empty($paymentId)) {
            return false;
        }

        if (!$this->isConfigured() && str_starts_with($signature, 'mock_sig_')) {
            return true;
        }

        try {
            if ($this->api) {
                $this->api->utility->verifySubscriptionSignature([
                    'razorpay_subscription_id' => $subscriptionId,
                    'razorpay_payment_id' => $paymentId,
                    'razorpay_signature' => $signature,
                ]);
                return true;
            }
        } catch (\Throwable $e) {
            Log::warning('Razorpay subscription signature verification failed: ' . $e->getMessage());
        }

        $expected = hash_hmac('sha256', "{$paymentId}|{$subscriptionId}", $this->keySecret);
        return hash_equals($expected, $signature);
    }

    /**
     * Verify Razorpay Webhook Signature
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        if (empty($payload) || empty($signature)) {
            return false;
        }

        if (!$this->isConfigured() && str_starts_with($signature, 'mock_wh_')) {
            return true;
        }

        $expected = hash_hmac('sha256', $payload, $this->webhookSecret);
        return hash_equals($expected, $signature);
    }
}
