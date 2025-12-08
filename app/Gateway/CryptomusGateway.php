<?php

namespace App\Gateway;

use App\Gateway\Contracts\PaymentGatewayContract;
use App\Models\Invoice;
use App\Models\PaymentGateway;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
     * Create a payment invoice with Cryptomus
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

        // Create pending invoice locally
        $invoice = Invoice::create([
            'user_id' => $user->id,
            'type' => 'credit',
            'category' => 'payment_gateway',
            'status' => 'pending',
            'payment_gateway_id' => $this->gateway->id,
            'amount' => $amount,
            'currency' => 'USD', // Cryptomus typically uses USD
            'balance_after' => $user->balance,
            'description' => 'Balance top-up via Cryptomus',
            'meta' => array_merge($meta, [
                'gateway' => 'cryptomus',
                'network' => $network,
            ]),
        ]);

        // Prepare Cryptomus API request
        $orderId = 'INV-'.$invoice->id.'-'.time();
        $callbackUrl = route('payment.cryptomus.callback');

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
            ])->post($baseUrl.'/payment', $data);

            if (! $response->successful()) {
                Log::error('Cryptomus payment creation failed', [
                    'response' => $response->json(),
                    'status' => $response->status(),
                ]);
                throw new RuntimeException('Failed to create Cryptomus payment');
            }

            $result = $response->json();

            // Update invoice with transaction ID
            $invoice->update([
                'transaction_id' => $result['result']['uuid'] ?? $orderId,
                'meta' => array_merge($invoice->meta ?? [], [
                    'cryptomus_response' => $result,
                ]),
            ]);

            // Return payment URL
            $paymentUrl = $result['result']['url'] ?? null;
            if (! $paymentUrl) {
                throw new RuntimeException('No payment URL received from Cryptomus');
            }

            return redirect($paymentUrl);
        } catch (\Exception $e) {
            Log::error('Cryptomus payment error', [
                'error' => $e->getMessage(),
                'invoice_id' => $invoice->id,
            ]);
            
            $invoice->markAsFailed();
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

        // Find invoice by transaction_id or order_id pattern
        $invoice = Invoice::where('transaction_id', $uuid)
            ->orWhere('transaction_id', 'LIKE', '%'.$orderId.'%')
            ->first();

        if (! $invoice) {
            Log::warning('Invoice not found for Cryptomus callback', ['order_id' => $orderId, 'uuid' => $uuid]);
            return;
        }

        // Process based on status
        if ($status === 'paid' || $status === 'paid_over') {
            if ($invoice->status !== 'completed') {
                // Credit user's balance using the trait
                $user = $invoice->user;
                $user->credit(
                    (float) $invoice->amount,
                    'payment_gateway',
                    'Payment received via Cryptomus',
                    [
                        'gateway' => 'cryptomus',
                        'transaction_id' => $uuid,
                        'pending_invoice_id' => $invoice->id,
                    ]
                );

                // Mark original invoice as completed
                $invoice->markAsCompleted();
                $invoice->update([
                    'meta' => array_merge($invoice->meta ?? [], [
                        'callback_data' => $data,
                        'completed_at' => now(),
                    ]),
                ]);

                Log::info('Cryptomus payment completed', ['invoice_id' => $invoice->id, 'uuid' => $uuid]);
            }
        } elseif ($status === 'cancel' || $status === 'fail') {
            $invoice->markAsFailed();
            Log::info('Cryptomus payment failed/cancelled', ['invoice_id' => $invoice->id, 'status' => $status]);
        }
    }

    /**
     * Generate signature for Cryptomus API
     */
    protected function generateSignature(array $data, string $apiKey): string
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        return md5(base64_encode($json).$apiKey);
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
