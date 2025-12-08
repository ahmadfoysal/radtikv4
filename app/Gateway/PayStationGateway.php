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

class PayStationGateway implements PaymentGatewayContract
{
    protected PaymentGateway $gateway;

    public function __construct(PaymentGateway $gateway)
    {
        $this->gateway = $gateway;
    }

    /**
     * Create a payment with PayStation
     */
    public function createPayment(User $user, float $amount, array $meta = []): RedirectResponse|string
    {
        $merchantId = $this->gateway->data['merchant_id'] ?? null;
        $password = $this->gateway->data['password'] ?? null;
        $baseUrl = $this->gateway->data['base_url'] ?? 'https://www.paystation.com.bd';

        if (! $merchantId || ! $password) {
            throw new RuntimeException('PayStation gateway credentials not configured');
        }

        // Create pending invoice locally
        $invoice = Invoice::create([
            'user_id' => $user->id,
            'type' => 'credit',
            'category' => 'payment_gateway',
            'status' => 'pending',
            'payment_gateway_id' => $this->gateway->id,
            'amount' => $amount,
            'currency' => 'BDT',
            'balance_after' => $user->balance,
            'description' => 'Balance top-up via PayStation',
            'meta' => array_merge($meta, [
                'gateway' => 'paystation',
            ]),
        ]);

        // Prepare PayStation request
        $orderId = 'INV-'.$invoice->id.'-'.time();
        $callbackUrl = route('payment.paystation.callback');

        // Build payment URL with parameters
        $params = [
            'merchant_id' => $merchantId,
            'order_id' => $orderId,
            'amount' => $amount,
            'currency' => 'BDT',
            'success_url' => url('/billing/add-balance?status=success'),
            'fail_url' => url('/billing/add-balance?status=failed'),
            'cancel_url' => url('/billing/add-balance?status=cancelled'),
            'callback_url' => $callbackUrl,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_phone' => $user->phone ?? '',
        ];

        // Generate signature (hash)
        $signatureString = $merchantId.$orderId.$amount.$password;
        $params['signature'] = hash('sha256', $signatureString);

        // Update invoice with transaction ID
        $invoice->update([
            'transaction_id' => $orderId,
        ]);

        // Build payment URL
        $paymentUrl = $baseUrl.'/payment?'.http_build_query($params);

        Log::info('PayStation payment initiated', [
            'invoice_id' => $invoice->id,
            'order_id' => $orderId,
        ]);

        return redirect($paymentUrl);
    }

    /**
     * Handle PayStation callback
     */
    public function handleCallback(Request $request): void
    {
        $orderId = $request->input('order_id');
        $status = $request->input('status');
        $transactionId = $request->input('transaction_id');
        $amount = $request->input('amount');
        $signature = $request->input('signature');

        if (! $orderId) {
            Log::warning('No order_id in PayStation callback', ['data' => $request->all()]);
            return;
        }

        // Verify signature
        $password = $this->gateway->data['password'] ?? null;
        $merchantId = $this->gateway->data['merchant_id'] ?? null;
        
        $expectedSignature = hash('sha256', $merchantId.$orderId.$amount.$password);
        
        if (! hash_equals($expectedSignature, $signature)) {
            Log::warning('Invalid PayStation callback signature', [
                'order_id' => $orderId,
                'received_signature' => $signature,
            ]);
            return;
        }

        // Find invoice
        $invoice = Invoice::where('transaction_id', $orderId)
            ->whereNull('deleted_at')
            ->first();

        if (! $invoice) {
            Log::warning('Invoice not found for PayStation callback', ['order_id' => $orderId]);
            return;
        }

        // Process based on status
        if ($status === 'success' || $status === 'paid') {
            if ($invoice->status !== 'completed') {
                // Credit user's balance using the trait
                $user = $invoice->user;
                $user->credit(
                    (float) $invoice->amount,
                    'payment_gateway',
                    'Payment received via PayStation',
                    [
                        'gateway' => 'paystation',
                        'transaction_id' => $transactionId,
                        'pending_invoice_id' => $invoice->id,
                    ]
                );

                // Mark original invoice as completed
                $invoice->markAsCompleted();
                $invoice->update([
                    'meta' => array_merge($invoice->meta ?? [], [
                        'callback_data' => $request->all(),
                        'completed_at' => now(),
                        'transaction_id' => $transactionId,
                    ]),
                ]);

                Log::info('PayStation payment completed', [
                    'invoice_id' => $invoice->id,
                    'transaction_id' => $transactionId,
                ]);
            }
        } elseif ($status === 'failed' || $status === 'cancelled') {
            $invoice->markAsFailed();
            Log::info('PayStation payment failed/cancelled', [
                'invoice_id' => $invoice->id,
                'status' => $status,
            ]);
        }
    }
}
