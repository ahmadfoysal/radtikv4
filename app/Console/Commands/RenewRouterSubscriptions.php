<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RenewRouterSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:renew {--days=7 : Number of days before expiration to renew}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically renew admin subscriptions that are about to expire';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoffDate = Carbon::now()->addDays($days);

        $this->info("Looking for subscriptions expiring within {$days} days (before {$cutoffDate->toDateTimeString()})...");

        // Find active subscriptions that are expiring soon
        $subscriptions = Subscription::where('status', 'active')
            ->where('end_date', '>', Carbon::now())
            ->where('end_date', '<=', $cutoffDate)
            ->with(['user', 'package'])
            ->get();

        if ($subscriptions->isEmpty()) {
            $this->info('No subscriptions found that need renewal.');
            return self::SUCCESS;
        }

        $this->info("Found {$subscriptions->count()} subscriptions to process.");

        $successCount = 0;
        $failureCount = 0;

        foreach ($subscriptions as $subscription) {
            try {
                $user = $subscription->user;
                $package = $subscription->package;

                $this->line("Processing subscription #{$subscription->id} for user {$user->name} (Package: {$package->name})...");

                // Check if user has enough balance
                $price = $subscription->billing_cycle === 'yearly'
                    ? $package->price_yearly
                    : $package->price_monthly;

                if ($user->balance < $price) {
                    $this->warn("⚠ User {$user->name} has insufficient balance (₹{$user->balance} < ₹{$price})");
                    $failureCount++;
                    continue;
                }

                // Renew the subscription
                $subscription->renew();

                // Deduct payment
                $user->debit(
                    amount: $price,
                    category: 'subscription_renewal',
                    description: "Subscription renewal: {$package->name} ({$subscription->billing_cycle})",
                    meta: ['subscription_id' => $subscription->id, 'package_id' => $package->id]
                );

                $successCount++;
                $this->info("✓ Successfully renewed subscription #{$subscription->id}");
            } catch (\Exception $e) {
                $failureCount++;
                $this->error("✗ Failed to renew subscription #{$subscription->id}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Renewal complete: {$successCount} succeeded, {$failureCount} failed.");

        return self::SUCCESS;
    }
}
