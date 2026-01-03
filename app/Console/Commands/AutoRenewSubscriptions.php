<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoRenewSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:auto-renew {--dry-run : Run without actually renewing subscriptions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically renew subscriptions within early payment period if user has sufficient balance';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('🔍 Running in DRY RUN mode - no subscriptions will be renewed');
        }

        $this->info('🔄 Starting automatic subscription renewal process...');
        $startTime = microtime(true);

        try {
            $renewedCount = 0;
            $failedCount = 0;
            $insufficientBalanceCount = 0;

            // Get subscriptions eligible for renewal
            $eligibleSubscriptions = $this->getEligibleSubscriptions();

            $this->info("📋 Found {$eligibleSubscriptions->count()} eligible subscriptions for renewal");

            if ($eligibleSubscriptions->isEmpty()) {
                $this->info('✓ No subscriptions to renew at this time');
                return self::SUCCESS;
            }

            foreach ($eligibleSubscriptions as $subscription) {
                $user = $subscription->user;
                $package = $subscription->package;

                // Skip if auto-renew is disabled
                if (!$subscription->auto_renew) {
                    $this->line("  ⊘ Skipping {$user->email} - auto-renew disabled");
                    continue;
                }

                // Calculate renewal amount (with commission discount if applicable)
                $renewalAmount = $this->calculateRenewalAmount($subscription, $user);

                // Check if user has sufficient balance
                if ($user->balance < $renewalAmount) {
                    $insufficientBalanceCount++;
                    $this->line("  ⚠ {$user->email} - Insufficient balance ($" . number_format($user->balance, 2) . " < $" . number_format($renewalAmount, 2) . ")");

                    Log::info('Auto-renewal skipped: Insufficient balance', [
                        'user_id' => $user->id,
                        'subscription_id' => $subscription->id,
                        'required_amount' => $renewalAmount,
                        'current_balance' => $user->balance,
                    ]);
                    continue;
                }

                // Perform renewal
                if (!$isDryRun) {
                    try {
                        $this->renewSubscription($subscription, $user, $renewalAmount);
                        $renewedCount++;
                        $this->info("  ✓ Renewed subscription for {$user->email} - Package: {$package->name} - Amount: $" . number_format($renewalAmount, 2));
                    } catch (\Exception $e) {
                        $failedCount++;
                        $this->error("  ✗ Failed to renew {$user->email}: {$e->getMessage()}");

                        Log::error('Auto-renewal failed', [
                            'user_id' => $user->id,
                            'subscription_id' => $subscription->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                } else {
                    $this->info("  [DRY RUN] Would renew {$user->email} - Package: {$package->name} - Amount: $" . number_format($renewalAmount, 2));
                    $renewedCount++;
                }
            }

            $duration = round(microtime(true) - $startTime, 2);

            $this->newLine();
            $this->info("✅ Auto-renewal process completed in {$duration} seconds");
            $this->info("📊 Summary:");
            $this->info("  - Renewed: {$renewedCount}");
            $this->info("  - Insufficient balance: {$insufficientBalanceCount}");
            $this->info("  - Failed: {$failedCount}");

            Log::info('Auto-renewal process completed', [
                'renewed' => $renewedCount,
                'insufficient_balance' => $insufficientBalanceCount,
                'failed' => $failedCount,
                'duration' => $duration,
                'dry_run' => $isDryRun,
            ]);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error during auto-renewal: ' . $e->getMessage());
            Log::error('Auto-renewal process failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return self::FAILURE;
        }
    }

    /**
     * Get subscriptions eligible for early renewal
     */
    protected function getEligibleSubscriptions()
    {
        $today = now()->startOfDay();

        return Subscription::with(['user', 'package'])
            ->where('status', 'active')
            ->where('auto_renew', true)
            ->whereHas('package', function ($query) use ($today) {
                // Case 1: Subscriptions within early payment window (will expire soon)
                // If early_pay_days = 5, renew subscriptions expiring within the next 5 days
                $query->where(function ($q) use ($today) {
                    $q->where('early_pay_days', '>', 0)
                        ->whereHas('subscriptions', function ($subQuery) use ($today) {
                            $subQuery->whereDate('end_date', '>=', $today)
                                ->whereRaw('end_date <= DATE_ADD(?, INTERVAL (SELECT early_pay_days FROM packages WHERE id = subscriptions.package_id) DAY)', [$today->toDateString()]);
                        });
                })
                    // Case 2: Subscriptions that expired but within grace period
                    // If grace_period_days = 3, renew subscriptions that expired up to 3 days ago
                    ->orWhere(function ($q) use ($today) {
                        $q->where('grace_period_days', '>', 0)
                            ->whereHas('subscriptions', function ($subQuery) use ($today) {
                                $subQuery->whereDate('end_date', '<', $today)
                                    ->whereRaw('end_date >= DATE_SUB(?, INTERVAL (SELECT grace_period_days FROM packages WHERE id = subscriptions.package_id) DAY)', [$today->toDateString()]);
                            });
                    });
            })
            ->where(function ($query) use ($today) {
                // Case 1 & 2 direct on subscription
                $query->where(function ($q) use ($today) {
                    $q->whereDate('end_date', '>=', $today)
                        ->whereRaw('end_date <= DATE_ADD(?, INTERVAL (SELECT early_pay_days FROM packages WHERE id = subscriptions.package_id) DAY)', [$today->toDateString()]);
                })
                    ->orWhere(function ($q) use ($today) {
                        $q->whereDate('end_date', '<', $today)
                            ->whereRaw('end_date >= DATE_SUB(?, INTERVAL (SELECT grace_period_days FROM packages WHERE id = subscriptions.package_id) DAY)', [$today->toDateString()]);
                    });
            })
            // Prevent duplicate renewals: Only renew if last payment was before today OR never paid
            // This allows testing with manual date changes while preventing double renewals
            ->where(function ($query) use ($today) {
                $query->whereDate('last_payment_date', '<', $today)
                    ->orWhereNull('last_payment_date');
            })
            ->get();
    }

    /**
     * Calculate renewal amount with commission discount
     */
    protected function calculateRenewalAmount(Subscription $subscription, User $user): float
    {
        $package = $subscription->package;

        $originalAmount = $subscription->billing_cycle === 'yearly'
            ? ($package->price_yearly ?? $package->price_monthly * 12)
            : $package->price_monthly;

        // Apply commission discount for admin users
        $discountPercent = 0;
        if ($originalAmount > 0 && $user->hasRole('admin') && $user->commission > 0) {
            $discountPercent = (int) $user->commission;
        }

        $discount = round(($originalAmount * $discountPercent) / 100, 2);
        return $originalAmount - $discount;
    }

    /**
     * Renew the subscription
     */
    protected function renewSubscription(Subscription $subscription, User $user, float $amount): void
    {
        DB::beginTransaction();

        try {
            $package = $subscription->package;
            $cycle = $subscription->billing_cycle;

            // Calculate new end date
            $newEndDate = $subscription->end_date->copy();
            if ($cycle === 'yearly') {
                $newEndDate->addYear();
            } else {
                $newEndDate->addMonth();
            }

            // Debit user's balance
            $invoice = $user->debit(
                amount: $amount,
                category: 'subscription',
                description: "Auto-renewal: {$package->name} ({$cycle})",
                meta: [
                    'subscription_id' => $subscription->id,
                    'package_id' => $package->id,
                    'billing_cycle' => $cycle,
                    'auto_renewal' => true,
                    'original_end_date' => $subscription->end_date->toDateString(),
                    'new_end_date' => $newEndDate->toDateString(),
                ]
            );

            // Update subscription
            $subscription->update([
                'end_date' => $newEndDate,
                'last_payment_date' => now(),
                'next_billing_date' => $newEndDate,
                'last_invoice_id' => $invoice->id,
                'cycle_count' => $subscription->cycle_count + 1,
            ]);

            // Send notification
            $user->notify(new \App\Notifications\Billing\SubscriptionRenewalNotification(
                $subscription,
                $invoice,
                true // Auto-renewal
            ));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
