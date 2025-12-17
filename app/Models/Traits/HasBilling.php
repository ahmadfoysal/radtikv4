<?php

namespace App\Models\Traits;

use App\Models\Invoice;
use App\Models\Router;
use Illuminate\Support\Facades\DB;
use RuntimeException;

trait HasBilling
{
    /**
     * Credit the user's balance and create an invoice record.
     *
     * @param  float  $amount  The amount to credit (must be positive)
     * @param  string  $category  The category of the transaction (e.g., 'topup', 'adjustment')
     * @param  string|null  $description  Optional description for the invoice
     * @param  array  $meta  Optional metadata to store with the invoice
     * @param  Router|null  $router  Optional router associated with the transaction
     * @return Invoice The created invoice
     *
     * @throws RuntimeException If the amount is not positive
     */
    public function credit(
        float $amount,
        string $category,
        ?string $description = null,
        array $meta = [],
        ?Router $router = null
    ): Invoice {
        if ($amount <= 0) {
            throw new RuntimeException('Credit amount must be positive.');
        }

        return DB::transaction(function () use ($amount, $category, $description, $meta, $router) {
            // Lock the user row for update to prevent race conditions
            $lockedUser = static::where('id', $this->id)->lockForUpdate()->first();

            $oldBalance = (float) $lockedUser->balance;
            $newBalance = $oldBalance + $amount;

            // Update user balance with actual credit amount
            $lockedUser->balance = $newBalance;
            $lockedUser->save();

            // Refresh the current user model
            $this->refresh();

            // Create the invoice record for actual credit
            $invoice = Invoice::create([
                'user_id' => $this->id,
                'router_id' => $router?->id,
                'type' => 'credit',
                'category' => $category,
                'status' => 'completed',
                'amount' => $amount,
                'currency' => 'BDT',
                'balance_after' => $newBalance,
                'description' => $description,
                'meta' => ! empty($meta) ? $meta : null,
            ]);

            // Check if user has commission percentage and add commission credit
            if ($this->commission > 0) {
                $commissionAmount = round(($amount * $this->commission) / 100, 2);

                if ($commissionAmount > 0) {
                    // Update balance again with commission
                    $lockedUser = static::where('id', $this->id)->lockForUpdate()->first();
                    $commissionNewBalance = (float) $lockedUser->balance + $commissionAmount;
                    $lockedUser->balance = $commissionNewBalance;
                    $lockedUser->save();

                    $this->refresh();

                    // Create commission invoice
                    Invoice::create([
                        'user_id' => $this->id,
                        'router_id' => $router?->id,
                        'type' => 'credit',
                        'category' => 'commission',
                        'status' => 'completed',
                        'amount' => $commissionAmount,
                        'currency' => 'BDT',
                        'balance_after' => $commissionNewBalance,
                        'description' => "Commission ({$this->commission}%) on {$category}",
                        'meta' => [
                            'commission_percentage' => $this->commission,
                            'original_amount' => $amount,
                            'original_category' => $category,
                            'source_invoice_id' => $invoice->id,
                        ],
                    ]);
                }
            }

            return $invoice;
        });
    }

    /**
     * Debit the user's balance and create an invoice record.
     *
     * @param  float  $amount  The amount to debit (must be positive)
     * @param  string  $category  The category of the transaction (e.g., 'subscription', 'renewal')
     * @param  string|null  $description  Optional description for the invoice
     * @param  array  $meta  Optional metadata to store with the invoice
     * @param  Router|null  $router  Optional router associated with the transaction
     * @return Invoice The created invoice
     *
     * @throws RuntimeException If the amount is not positive or if insufficient balance
     */
    public function debit(
        float $amount,
        string $category,
        ?string $description = null,
        array $meta = [],
        ?Router $router = null
    ): Invoice {
        if ($amount <= 0) {
            throw new RuntimeException('Debit amount must be positive.');
        }

        return DB::transaction(function () use ($amount, $category, $description, $meta, $router) {
            // Lock the user row for update to prevent race conditions
            $lockedUser = static::where('id', $this->id)->lockForUpdate()->first();

            $oldBalance = (float) $lockedUser->balance;

            // Check if user has sufficient balance
            if ($oldBalance < $amount) {
                throw new RuntimeException('Insufficient balance for transaction.');
            }

            $newBalance = $oldBalance - $amount;

            // Update user balance
            $lockedUser->balance = $newBalance;
            $lockedUser->save();

            // Refresh the current user model
            $this->refresh();

            // Create the invoice record
            return Invoice::create([
                'user_id' => $this->id,
                'router_id' => $router?->id,
                'type' => 'debit',
                'category' => $category,
                'status' => 'completed',
                'amount' => $amount,
                'currency' => 'BDT',
                'balance_after' => $newBalance,
                'description' => $description,
                'meta' => ! empty($meta) ? $meta : null,
            ]);
        });
    }
}
