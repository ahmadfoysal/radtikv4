<?php

namespace App\Gateway;

use App\Gateway\Contracts\PaymentGatewayContract;
use App\Models\Invoice;
use App\Models\PaymentGateway;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CryptomusGateway implements PaymentGatewayContract
{
    protected PaymentGateway $gateway;

    public function __construct(PaymentGateway $gateway)
    {
        $this->gateway = $gateway;
    }

    /**
     * Create a payment with Cryptomus
     */
    public function createPayment(User $user, float $amount, array $meta = []): RedirectResponse|string
    {
        $merchantId = $this->gateway->data['merchant_id'] ?? null;
        $apiKey = $this->gateway->data['api_key'] ?? null;
        $network = $this->gateway->data['network'] ?? 'USDT_TRC20';
        $testMode = $this->gateway->data['test_mode'] ?? false;

        if (! $merchantId || ! $apiKey) {
            throw new RuntimeException('Cryptomus gateway credentials not configured');
        }

        // Generate unique order ID
        $orderId = 'PAY-' . uniqid() . '-' . time();
        $callbackUrl = route('payment.cryptomus.callback');

        // Store payment data in session (to be used after successful payment)
        session()->put('pending_payment', [
            'order_id' => $orderId,
            'user_id' => $user->id,
            'gateway_id' => $this->gateway->id,
            'gateway_name' => 'cryptomus',
            'amount' => $amount,
            'currency' => 'USD',
            'network' => $network,
            'meta' => $meta,
            'created_at' => now()->toIso8601String(),
        ]);

        $data = [
            'amount' => (string) $amount,
            'currency' => 'USD',
            'order_id' => $orderId,
            'url_callback' => $callbackUrl,
            'network' => $network,
        ];

        // Generate signature
        $sign = $this->generateSignature($data, $apiKey);

        try {
            // Cryptomus uses the same URL for both test and production
            $baseUrl = 'https://api.cryptomus.com/v1';

            $response = Http::withHeaders([
                'merchant' => $merchantId,
                'sign' => $sign,
                'Content-Type' => 'application/json',
            ])->post($baseUrl . '/payment', $data);

            if (! $response->successful()) {
                Log::error('Cryptomus payment creation failed', [
                    'response' => $response->json(),
                    'status' => $response->status(),
                ]);
                session()->forget('pending_payment');
                throw new RuntimeException('Failed to create Cryptomus payment');
            }

            $result = $response->json();

            // Update session with transaction UUID from Cryptomus
            $transactionId = $result['result']['uuid'] ?? $orderId;
            session()->put('pending_payment.transaction_id', $transactionId);
            session()->put('pending_payment.cryptomus_response', $result);

            // Return payment URL
            $paymentUrl = $result['result']['url'] ?? null;
            if (! $paymentUrl) {
                session()->forget('pending_payment');
                throw new RuntimeException('No payment URL received from Cryptomus');
            }

            return redirect($paymentUrl);
        } catch (\Exception $e) {
            Log::error('Cryptomus payment error', [
                'error' => $e->getMessage(),
                'order_id' => $orderId,
            ]);

            session()->forget('pending_payment');
            throw $e;
        }
    }

    /**
     * Handle Cryptomus callback
     */
    public function handleCallback(Request $request): void
    {
        $data = $request->all();

        // Verify signature
        $receivedSign = $request->header('sign');
        $apiKey = $this->gateway->data['api_key'] ?? null;

        if (! $this->verifySignature($data, $receivedSign, $apiKey)) {
            Log::warning('Invalid Cryptomus callback signature', ['data' => $data]);
            return;
        }

        $orderId = $data['order_id'] ?? null;
        $status = $data['status'] ?? null;
        $uuid = $data['uuid'] ?? null;

        if (! $orderId) {
            Log::warning('No order_id in Cryptomus callback', ['data' => $data]);
            return;
        }

        // Check if invoice already exists for this transaction
        $existingInvoice = Invoice::where('transaction_id', $uuid)->first();

        if ($existingInvoice) {
            Log::info('Invoice already exists for transaction', ['transaction_id' => $uuid]);
            return;
        }

        // Get payment data from session
        $paymentData = session()->get('pending_payment');

        if (! $paymentData || $paymentData['order_id'] !== $orderId) {
            Log::warning('No matching pending payment in session', [
                'order_id' => $orderId,
                'session_order_id' => $paymentData['order_id'] ?? null,
            ]);
            return;
        }

        // Process based on status
        if ($status === 'paid' || $status === 'paid_over') {
            // Get user
            $user = User::find($paymentData['user_id']);

            if (! $user) {
                Log::error('User not found for payment', ['user_id' => $paymentData['user_id']]);
                return;
            }

            // Create invoice and credit balance in a transaction
            DB::transaction(function () use ($user, $paymentData, $uuid, $data) {
                // Credit user's balance
                $invoice = $user->credit(
                    (float) $paymentData['amount'],
                    'payment_gateway',
                    'Payment received via Cryptomus',
                    array_merge($paymentData['meta'] ?? [], [
                        'gateway' => 'cryptomus',
                        'transaction_id' => $uuid,
                        'callback_data' => $data,
                        'completed_at' => now()->toIso8601String(),
                    ])
                );

                // Update invoice with payment gateway details
                $invoice->update([
                    'transaction_id' => $uuid,
                    'payment_gateway_id' => $paymentData['gateway_id'],
                    'currency' => $paymentData['currency'],
                ]);

                Log::info('Cryptomus payment completed', [
                    'invoice_id' => $invoice->id,
                    'transaction_id' => $uuid,
                    'user_id' => $user->id,
                ]);

                // Send payment notification
                $user->notify(new \App\Notifications\Billing\PaymentReceivedNotification(
                    $invoice,
                    $invoice->amount,
                    $invoice->balance_after
                ));
            });

            // Remove payment data from session
            session()->forget('pending_payment');
        } elseif ($status === 'cancel' || $status === 'fail') {
            Log::info('Cryptomus payment failed/cancelled', [
                'order_id' => $orderId,
                'status' => $status,
            ]);

            // Remove payment data from session
            session()->forget('pending_payment');
        }
    }

    /**
     * Generate signature for Cryptomus API
     */
    protected function generateSignature(array $data, string $apiKey): string
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        return md5(base64_encode($json) . $apiKey);
    }

    /**
     * Verify callback signature
     */
    protected function verifySignature(array $data, ?string $receivedSign, ?string $apiKey): bool
    {
        if (! $receivedSign || ! $apiKey) {
            return false;
        }

        unset($data['sign']); // Remove sign from data before verification
        $expectedSign = $this->generateSignature($data, $apiKey);

        return hash_equals($expectedSign, $receivedSign);
    }
}
