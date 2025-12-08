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

        // Generate unique order ID
        $orderId = 'PAY-'.uniqid().'-'.time();
        $callbackUrl = route('payment.paystation.callback');

        // Store payment data in session (to be used after successful payment)
        session()->put('pending_payment', [
            'order_id' => $orderId,
            'user_id' => $user->id,
            'gateway_id' => $this->gateway->id,
            'gateway_name' => 'paystation',
            'amount' => $amount,
            'currency' => 'BDT',
            'meta' => $meta,
            'created_at' => now()->toIso8601String(),
        ]);

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

        // Build payment URL
        $paymentUrl = $baseUrl.'/payment?'.http_build_query($params);

        Log::info('PayStation payment initiated', [
            'order_id' => $orderId,
            'user_id' => $user->id,
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

        // Check if invoice already exists for this transaction
        $existingInvoice = Invoice::where('transaction_id', $transactionId)->first();
        
        if ($existingInvoice) {
            Log::info('Invoice already exists for transaction', ['transaction_id' => $transactionId]);
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
        if ($status === 'success' || $status === 'paid') {
            // Get user
            $user = User::find($paymentData['user_id']);
            
            if (! $user) {
                Log::error('User not found for payment', ['user_id' => $paymentData['user_id']]);
                return;
            }

            // Create invoice and credit balance in a transaction
            DB::transaction(function () use ($user, $paymentData, $transactionId, $request) {
                // Credit user's balance
                $invoice = $user->credit(
                    (float) $paymentData['amount'],
                    'payment_gateway',
                    'Payment received via PayStation',
                    array_merge($paymentData['meta'] ?? [], [
                        'gateway' => 'paystation',
                        'transaction_id' => $transactionId,
                        'callback_data' => $request->all(),
                        'completed_at' => now()->toIso8601String(),
                    ])
                );

                // Update invoice with payment gateway details
                $invoice->update([
                    'transaction_id' => $transactionId,
                    'payment_gateway_id' => $paymentData['gateway_id'],
                    'currency' => $paymentData['currency'],
                ]);

                Log::info('PayStation payment completed', [
                    'invoice_id' => $invoice->id,
                    'transaction_id' => $transactionId,
                    'user_id' => $user->id,
                ]);
            });

            // Remove payment data from session
            session()->forget('pending_payment');
        } elseif ($status === 'failed' || $status === 'cancelled') {
            Log::info('PayStation payment failed/cancelled', [
                'order_id' => $orderId,
                'status' => $status,
            ]);
            
            // Remove payment data from session
            session()->forget('pending_payment');
        }
    }
}
