<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RazorpayService
{
    protected string $keyId;
    protected string $keySecret;
    protected string $webhookSecret;

    public function __construct()
    {
        $this->keyId = config('services.razorpay.key_id', env('RAZORPAY_KEY_ID', ''));
        $this->keySecret = config('services.razorpay.key_secret', env('RAZORPAY_KEY_SECRET', ''));
        $this->webhookSecret = config('services.razorpay.webhook_secret', env('RAZORPAY_WEBHOOK_SECRET', ''));
    }

    public function isConfigured(): bool
    {
        return !empty($this->keyId) && 
               !empty($this->keySecret) && 
               !str_contains($this->keyId, 'YOUR_') && 
               !str_contains($this->keySecret, 'YOUR_');
    }

    /**
     * Create Razorpay One-Time Order
     */
    public function createOrder(int $amountInPaise, string $receiptId, string $currency = 'INR'): array
    {
        if (app()->environment('production') && !$this->isConfigured()) {
            throw new Exception('Razorpay is not configured for production use.');
        }

        if ($this->isConfigured()) {
            $response = Http::withBasicAuth($this->keyId, $this->keySecret)
                ->post('https://api.razorpay.com/v1/orders', [
                    'amount' => $amountInPaise,
                    'currency' => $currency,
                    'receipt' => $receiptId,
                    'payment_capture' => 1,
                ]);

            if ($response->successful()) {
                return ['orderId' => $response->json('id')];
            }

            Log::error('Razorpay order creation failed', ['response' => $response->json()]);
            throw new Exception($response->json('error.description') ?? 'Razorpay order creation failed');
        }

        // Mock Order ID for local development / testing
        return ['orderId' => 'order_' . time() . '_' . substr(md5(uniqid()), 0, 6)];
    }

    /**
     * Create Razorpay Subscription
     */
    public function createSubscription(string $planId, int $totalCount = 12, int $customerNotify = 1): array
    {
        if (app()->environment('production') && !$this->isConfigured()) {
            throw new Exception('Razorpay is not configured for production use.');
        }

        if ($this->isConfigured()) {
            if (empty($planId) || str_contains($planId, 'YOUR_')) {
                throw new Exception("Invalid Razorpay Plan ID: {$planId}");
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

        if (!app()->environment('production') && !$this->isConfigured() && str_starts_with($signature, 'mock_sig_')) {
            return true;
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

        if (!app()->environment('production') && !$this->isConfigured() && str_starts_with($signature, 'mock_sig_')) {
            return true;
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

        if (!app()->environment('production') && !$this->isConfigured() && str_starts_with($signature, 'mock_wh_')) {
            return true;
        }

        $expected = hash_hmac('sha256', $payload, $this->webhookSecret);
        return hash_equals($expected, $signature);
    }
}
