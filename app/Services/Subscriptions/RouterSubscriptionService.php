<?php

namespace App\Services\Subscriptions;

use App\Models\Package;
use App\Models\Router;
use App\Models\User;
use App\Services\BillingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RouterSubscriptionService
{
    public function __construct(
        protected BillingService $billingService
    ) {}

    /**
     * Check if a user has enough balance for a given package.
     *
     * @param  User  $user  The user to check
     * @param  Package  $package  The package to check against
     * @return bool True if the user has sufficient balance
     */
    public function hasBalanceForPackage(User $user, Package $package): bool
    {
        $price = $this->getPackagePrice($package);

        return (float) $user->balance >= $price;
    }

    /**
     * Subscribe a new router with a package.
     *
     * @param  User  $user  The user subscribing
     * @param  Package  $package  The package to subscribe to
     * @param  array  $routerData  Router data for creation
     * @return Router The created router
     *
     * @throws RuntimeException If insufficient balance or validation fails
     */
    public function subscribeNewRouter(User $user, Package $package, array $routerData): Router
    {
        $price = $this->getPackagePrice($package);

        return DB::transaction(function () use ($user, $package, $routerData, $price) {
            // Check balance
            if (! $this->hasBalanceForPackage($user, $package)) {
                throw new RuntimeException('Insufficient balance for package subscription.');
            }

            // Calculate subscription dates
            $startDate = Carbon::now();
            $endDate = $this->calculateEndDate($startDate, $package->billing_cycle);

            // Create the router with package snapshot
            $router = Router::create(array_merge($routerData, [
                'user_id' => $user->id,
                'package' => $this->createPackageSnapshot($package),
                'package_start_date' => $startDate,
                'package_end_date' => $endDate,
                'auto_renew' => $package->auto_renew_allowed ?? false,
            ]));

            // Debit the user's balance with router reference
            $this->billingService->debit(
                $user,
                $price,
                'router_subscription',
                "Router subscription: {$package->name}",
                ['package_id' => $package->id],
                $router
            );

            return $router;
        });
    }

    /**
     * Renew an existing router subscription.
     *
     * @param  Router  $router  The router to renew
     * @param  Package|null  $package  Optional package to upgrade/change to, defaults to current package
     * @return Router The updated router
     *
     * @throws RuntimeException If insufficient balance or package not found
     */
    public function renewRouter(Router $router, ?Package $package = null): Router
    {
        return DB::transaction(function () use ($router, $package) {
            $user = $router->user;

            // Use provided package or fall back to current package
            if ($package === null) {
                // Try to find current package from snapshot
                if (! $router->package || ! isset($router->package['id'])) {
                    throw new RuntimeException('No package information found for router renewal.');
                }

                $package = Package::find($router->package['id']);

                if (! $package) {
                    throw new RuntimeException('Package not found for router renewal.');
                }
            }

            $price = $this->getPackagePrice($package);

            // Check balance
            if (! $this->hasBalanceForPackage($user, $package)) {
                throw new RuntimeException('Insufficient balance for router renewal.');
            }

            // Debit the user's balance
            $this->billingService->debit(
                $user,
                $price,
                'router_renewal',
                "Router renewal: {$package->name}",
                ['package_id' => $package->id],
                $router
            );

            // Calculate new subscription dates
            $startDate = $this->calculateRenewalStartDate($router);
            $endDate = $this->calculateEndDate($startDate, $package->billing_cycle);

            // Update router with new package snapshot and dates
            $router->update([
                'package' => $this->createPackageSnapshot($package),
                'package_start_date' => $startDate,
                'package_end_date' => $endDate,
                'auto_renew' => $package->auto_renew_allowed ?? false,
            ]);

            return $router;
        });
    }

    /**
     * Get the price for a package based on its billing cycle.
     *
     * @param  Package  $package  The package
     * @return float The price
     */
    protected function getPackagePrice(Package $package): float
    {
        return match ($package->billing_cycle) {
            'yearly' => (float) $package->price_yearly,
            'monthly' => (float) $package->price_monthly,
            default => (float) $package->price_monthly,
        };
    }

    /**
     * Create a snapshot of the package data to store with the router.
     *
     * @param  Package  $package  The package
     * @return array The package snapshot
     */
    protected function createPackageSnapshot(Package $package): array
    {
        return [
            'id' => $package->id,
            'name' => $package->name,
            'price_monthly' => $package->price_monthly,
            'price_yearly' => $package->price_yearly,
            'user_limit' => $package->user_limit,
            'billing_cycle' => $package->billing_cycle,
            'auto_renew_allowed' => $package->auto_renew_allowed,
            'description' => $package->description,
        ];
    }

    /**
     * Calculate the renewal start date for a router subscription.
     * If not expired, extends from current end date, otherwise starts from now.
     *
     * @param  Router  $router  The router being renewed
     * @return Carbon The start date for the renewal
     */
    protected function calculateRenewalStartDate(Router $router): Carbon
    {
        if ($router->package_end_date && $router->package_end_date->isFuture()) {
            return $router->package_end_date;
        }

        return Carbon::now();
    }

    /**
     * Calculate the end date based on billing cycle.
     *
     * @param  Carbon  $startDate  The start date
     * @param  string  $billingCycle  The billing cycle (monthly or yearly)
     * @return Carbon The end date
     */
    protected function calculateEndDate(Carbon $startDate, string $billingCycle): Carbon
    {
        return match ($billingCycle) {
            'yearly' => $startDate->copy()->addYear(),
            'monthly' => $startDate->copy()->addMonth(),
            default => $startDate->copy()->addMonth(),
        };
    }
}
