<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::enableForeignKeyConstraints();

        // Gate before callback: Admins can do everything, resellers need specific permissions
        Gate::before(function ($user, $ability) {
            // If user is admin or superadmin, allow all actions
            if ($user->hasRole('admin') || $user->hasRole('superadmin')) {
                return true;
            }
            // For resellers, check if they have the specific permission
            // Return null to continue with normal permission check
            return null;
        });
    }
}
