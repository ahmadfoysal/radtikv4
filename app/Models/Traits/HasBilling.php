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

            // Update user balance
            $lockedUser->balance = $newBalance;
            $lockedUser->save();

            // Refresh the current user model
            $this->refresh();

            // Create the invoice record
            return Invoice::create([
                'user_id' => $this->id,
                'router_id' => $router?->id,
                'type' => 'credit',
                'category' => $category,
                'amount' => $amount,
                'currency' => 'BDT',
                'balance_after' => $newBalance,
                'description' => $description,
                'meta' => ! empty($meta) ? $meta : null,
            ]);
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
                'amount' => $amount,
                'currency' => 'BDT',
                'balance_after' => $newBalance,
                'description' => $description,
                'meta' => ! empty($meta) ? $meta : null,
            ]);
        });
    }
}
