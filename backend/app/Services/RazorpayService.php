<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Razorpay client. TEST MODE ONLY in this build: with test_mode on (or the
 * placeholder keys still in config), order creation is simulated locally and
 * no network call or real charge ever happens. With live keys and
 * test_mode=false, createOrder is where the real Razorpay Orders API call
 * goes; signature verification below is already the real algorithm.
 */
class RazorpayService
{
    public function isTestMode(): bool
    {
        return (bool) config('services.razorpay.test_mode', true)
            || ! config('services.razorpay.key')
            || str_starts_with((string) config('services.razorpay.key'), 'rzp_test');
    }

    public function publicKey(): string
    {
        return (string) config('services.razorpay.key', 'rzp_test_placeholder');
    }

    /** @return array{id: string, amount: int, currency: string} */
    public function createOrder(float $amount, string $currency, string $receipt): array
    {
        // Simulated order. Real integration point: POST https://api.razorpay.com/v1/orders
        // with basic auth key:secret and this same payload.
        return [
            'id' => 'order_test_'.Str::lower(Str::random(14)),
            'amount' => (int) round($amount * 100), // paise
            'currency' => $currency,
            'receipt' => $receipt,
        ];
    }

    /**
     * Simulated payment capture standing in for Razorpay checkout in test
     * mode: returns a payment id and a REAL HMAC signature over
     * "order_id|payment_id", exactly what Razorpay's checkout returns.
     */
    public function simulatePayment(string $orderId): array
    {
        $paymentId = 'pay_test_'.Str::lower(Str::random(14));

        return [
            'razorpay_payment_id' => $paymentId,
            'razorpay_signature' => $this->sign($orderId, $paymentId),
        ];
    }

    public function verifySignature(string $orderId, string $paymentId, string $signature): bool
    {
        return hash_equals($this->sign($orderId, $paymentId), $signature);
    }

    private function sign(string $orderId, string $paymentId): string
    {
        return hash_hmac('sha256', $orderId.'|'.$paymentId, (string) config('services.razorpay.secret'));
    }
}
