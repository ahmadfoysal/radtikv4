<?php

namespace App\Console\Commands;

use App\Models\Router;
use App\Services\Subscriptions\RouterSubscriptionService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use RuntimeException;

class RenewRouterSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'routers:renew-subscriptions {--days=7 : Number of days before expiration to renew}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically renew router subscriptions that are about to expire';

    /**
     * Execute the console command.
     */
    public function handle(RouterSubscriptionService $subscriptionService): int
    {
        $days = (int) $this->option('days');
        $cutoffDate = Carbon::now()->addDays($days);

        $this->info("Looking for routers expiring within {$days} days (before {$cutoffDate->toDateTimeString()})...");

        // Find routers with auto_renew enabled that are expiring soon
        $routers = Router::query()
            ->whereNotNull('package')
            ->with('user')
            ->get()
            ->filter(function ($router) use ($cutoffDate) {
                // Check if package has auto_renew enabled and is expiring soon
                if (! $router->package || ! isset($router->package['auto_renew']) || ! $router->package['auto_renew']) {
                    return false;
                }

                if (! isset($router->package['end_date'])) {
                    return false;
                }

                $endDate = Carbon::parse($router->package['end_date']);

                return $endDate->isFuture() && $endDate->lte($cutoffDate);
            });

        if ($routers->isEmpty()) {
            $this->info('No routers found that need renewal.');

            return self::SUCCESS;
        }

        $this->info("Found {$routers->count()} routers to renew.");

        $successCount = 0;
        $failureCount = 0;

        foreach ($routers as $router) {
            try {
                $this->line("Renewing router #{$router->id} ({$router->name}) for user #{$router->user_id}...");

                $subscriptionService->renewRouter($router);

                $successCount++;
                $this->info("✓ Successfully renewed router #{$router->id}");
            } catch (RuntimeException $e) {
                $failureCount++;
                $this->error("✗ Failed to renew router #{$router->id}: {$e->getMessage()}");
            } catch (\Exception $e) {
                $failureCount++;
                $this->error("✗ Unexpected error renewing router #{$router->id}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Renewal complete: {$successCount} succeeded, {$failureCount} failed.");

        return self::SUCCESS;
    }
}
