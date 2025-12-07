<?php

namespace App\Models\Traits;

use App\Models\Package;
use App\Models\Router;
use App\Services\Subscriptions\RouterSubscriptionService;

trait HasRouterBilling
{
    /**
     * Check if the user has enough balance for a given package.
     *
     * @param  Package  $package  The package to check against
     * @return bool True if the user has sufficient balance
     */
    public function hasBalanceForPackage(Package $package): bool
    {
        return app(RouterSubscriptionService::class)
            ->hasBalanceForPackage($this, $package);
    }

    /**
     * Subscribe a new router with a package.
     *
     * @param  array  $routerData  Router data for creation
     * @param  Package  $package  The package to subscribe to
     * @return Router The created router
     *
     * @throws \RuntimeException If insufficient balance
     */
    public function subscribeRouterWithPackage(array $routerData, Package $package): Router
    {
        return app(RouterSubscriptionService::class)
            ->subscribeNewRouter($this, $package, $routerData);
    }
}
