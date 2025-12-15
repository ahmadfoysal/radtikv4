<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\UserPolicy;
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

        // Register policies
        Gate::policy(User::class, UserPolicy::class);

        // Gate before callback: Admins can do everything, resellers need specific permissions
        Gate::before(function ($user, $ability) {
            // If user is admin or superadmin, allow all actions
            if ($user->hasRole('admin')) {
                return true;
            }

            // For resellers, check if they have the specific permission assigned
            if ($user->hasRole('reseller')) {
                return $user->hasPermissionTo($ability);
            }

            // For other roles, let normal permission checks proceed
            return null;
        });
    }
}
