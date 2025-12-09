<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Router;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BillingService
{
    /**
     * Credit the user's balance and create an invoice record.
     *
     * @param  User  $user  The user to credit
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
        User $user,
        float $amount,
        string $category,
        ?string $description = null,
        array $meta = [],
        ?Router $router = null
    ): Invoice {
        if ($amount <= 0) {
            throw new RuntimeException('Credit amount must be positive.');
        }

        return DB::transaction(function () use ($user, $amount, $category, $description, $meta, $router) {
            // Lock the user row for update to prevent race conditions
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

            $oldBalance = (float) $lockedUser->balance;
            $newBalance = $oldBalance + $amount;

            // Update user balance
            $lockedUser->balance = $newBalance;
            $lockedUser->save();

            // Refresh the original user model
            $user->refresh();

            // Create the invoice record
            return Invoice::create([
                'user_id' => $user->id,
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
        });
    }

    /**
     * Debit the user's balance and create an invoice record.
     *
     * @param  User  $user  The user to debit
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
        User $user,
        float $amount,
        string $category,
        ?string $description = null,
        array $meta = [],
        ?Router $router = null
    ): Invoice {
        if ($amount <= 0) {
            throw new RuntimeException('Debit amount must be positive.');
        }

        return DB::transaction(function () use ($user, $amount, $category, $description, $meta, $router) {
            // Lock the user row for update to prevent race conditions
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

            $oldBalance = (float) $lockedUser->balance;

            // Check if user has sufficient balance
            if ($oldBalance < $amount) {
                throw new RuntimeException('Insufficient balance for transaction.');
            }

            $newBalance = $oldBalance - $amount;

            // Update user balance
            $lockedUser->balance = $newBalance;
            $lockedUser->save();

            // Refresh the original user model
            $user->refresh();

            // Create the invoice record
            return Invoice::create([
                'user_id' => $user->id,
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
