<?php

namespace App\Providers;

use App\Models\EmailSetting;
use App\Models\GeneralSetting;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Blade;
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

        // Apply email settings from database to Laravel config
        try {
            if (Schema::hasTable('email_settings')) {
                EmailSetting::applyToConfig();
            }
        } catch (\Exception $e) {
            // Ignore errors during migration or when table doesn't exist yet
        }

        // Apply global platform settings from database
        try {
            if (Schema::hasTable('general_settings')) {
                GeneralSetting::applyToConfig();
            }
        } catch (\Exception $e) {
            // Ignore errors during migration or when table doesn't exist yet
        }

        // Register Blade directives for user-specific formatting
        Blade::directive('userDate', function ($expression) {
            return "<?php echo \App\Models\GeneralSetting::formatDate($expression); ?>";
        });

        Blade::directive('userTime', function ($expression) {
            return "<?php echo \App\Models\GeneralSetting::formatTime($expression); ?>";
        });

        Blade::directive('userDateTime', function ($expression) {
            return "<?php echo \App\Models\GeneralSetting::formatDateTime($expression); ?>";
        });

        Blade::directive('userCurrency', function ($expression) {
            return "<?php echo \App\Models\GeneralSetting::getCurrencySymbol() . number_format($expression, 2); ?>";
        });

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
