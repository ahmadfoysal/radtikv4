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
use Illuminate\Support\Facades\Redirect;
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
        $merchantId = trim($this->gateway->data['merchant_id'] ?? '');
        $password = trim($this->gateway->data['password'] ?? '');
        $baseUrl = $this->gateway->data['base_url'] ?? 'https://api.paystation.com.bd';

        if (! $merchantId || ! $password) {
            throw new RuntimeException('PayStation gateway credentials not configured');
        }

        // Generate unique invoice number
        $invoiceNumber = 'INV-' . uniqid() . '-' . time();
        $callbackUrl = route('payment.paystation.callback');

        // Store payment data in session (to be used after successful payment)
        session()->put('pending_payment', [
            'invoice_number' => $invoiceNumber,
            'user_id' => $user->id,
            'gateway_id' => $this->gateway->id,
            'gateway_name' => 'paystation',
            'amount' => $amount,
            'currency' => 'BDT',
            'meta' => $meta,
            'created_at' => now()->toIso8601String(),
        ]);

        // Format phone number for Bangladesh (remove + and other characters, ensure starts with 01)
        $phone = $user->phone ?? '01000000000';
        $phone = preg_replace('/[^0-9]/', '', $phone); // Remove non-digits
        if (!str_starts_with($phone, '01')) {
            $phone = '01000000000'; // Default if invalid format
        }

        // Prepare API request data according to PayStation docs
        $requestData = [
            'merchantId' => $merchantId,
            'password' => $password,
            'invoice_number' => $invoiceNumber,
            'payment_amount' => (int) $amount,
            'currency' => 'BDT',
            'cust_name' => $user->name,
            'cust_email' => $user->email,
            'cust_phone' => $phone,
            'callback_url' => $callbackUrl,
            'reference' => 'Balance Top-up',
        ];

        // Call PayStation API to initiate payment
        try {
            $response = Http::asForm()->post($baseUrl . '/initiate-payment', $requestData);

            $result = $response->json();

            Log::info('PayStation API response', [
                'status_code' => $response->status(),
                'response_body' => $result,
                'request_data' => array_merge($requestData, ['password' => '***']), // Hide password in logs
            ]);

            if (!$response->successful() || ($result['status'] ?? '') !== 'success') {
                $errorMessage = $result['message'] ?? 'Failed to initiate payment';

                Log::error('PayStation payment initiation failed', [
                    'invoice_number' => $invoiceNumber,
                    'status_code' => $response->status(),
                    'response' => $result,
                    'error_message' => $errorMessage,
                ]);

                throw new RuntimeException($errorMessage);
            }

            $paymentUrl = $result['payment_url'] ?? null;

            if (!$paymentUrl) {
                throw new RuntimeException('No payment URL received from PayStation');
            }

            Log::info('PayStation payment initiated', [
                'invoice_number' => $invoiceNumber,
                'user_id' => $user->id,
                'payment_url' => $paymentUrl,
            ]);

            return $paymentUrl;
        } catch (\Exception $e) {
            Log::error('PayStation API error', [
                'invoice_number' => $invoiceNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new RuntimeException('Failed to create payment: ' . $e->getMessage());
        }
    }

    /**
     * Handle PayStation callback
     */
    public function handleCallback(Request $request): void
    {
        // PayStation sends: status, invoice_number, trx_id (via URL parameters)
        $status = $request->input('status'); // Successful/Failed/Canceled
        $invoiceNumber = $request->input('invoice_number');
        $trxId = $request->input('trx_id'); // Only available for successful payments

        if (! $invoiceNumber) {
            Log::warning('No invoice_number in PayStation callback', ['data' => $request->all()]);
            return;
        }

        Log::info('PayStation callback received', [
            'invoice_number' => $invoiceNumber,
            'status' => $status,
            'trx_id' => $trxId,
        ]);

        // Check if invoice already exists for this transaction
        if ($trxId) {
            $existingInvoice = Invoice::where('transaction_id', $trxId)->first();

            if ($existingInvoice) {
                Log::info('Invoice already exists for transaction', ['trx_id' => $trxId]);
                return;
            }
        }

        // Get payment data from session
        $paymentData = session()->get('pending_payment');

        if (! $paymentData || $paymentData['invoice_number'] !== $invoiceNumber) {
            Log::warning('No matching pending payment in session', [
                'invoice_number' => $invoiceNumber,
                'session_invoice' => $paymentData['invoice_number'] ?? null,
            ]);
            return;
        }

        // Process based on status
        if ($status === 'Successful') {
            // Get user
            $user = User::find($paymentData['user_id']);

            if (! $user) {
                Log::error('User not found for payment', ['user_id' => $paymentData['user_id']]);
                return;
            }

            // Create invoice and credit balance in a transaction
            DB::transaction(function () use ($user, $paymentData, $trxId, $request) {
                // Credit user's balance
                $invoice = $user->credit(
                    (float) $paymentData['amount'],
                    'payment_gateway',
                    'Payment received via PayStation',
                    array_merge($paymentData['meta'] ?? [], [
                        'gateway' => 'paystation',
                        'transaction_id' => $trxId,
                        'callback_data' => $request->all(),
                        'completed_at' => now()->toIso8601String(),
                    ])
                );

                // Update invoice with payment gateway details
                $invoice->update([
                    'transaction_id' => $trxId,
                    'payment_gateway_id' => $paymentData['gateway_id'],
                    'currency' => $paymentData['currency'],
                ]);

                Log::info('PayStation payment completed', [
                    'invoice_id' => $invoice->id,
                    'transaction_id' => $trxId,
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
        } elseif (in_array($status, ['Failed', 'Canceled'])) {
            Log::info('PayStation payment failed/cancelled', [
                'invoice_number' => $invoiceNumber,
                'status' => $status,
            ]);

            // Remove payment data from session
            session()->forget('pending_payment');
        }
    }
}
