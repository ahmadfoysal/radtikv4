<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'check.suspended' => \App\Http\Middleware\CheckSuspendedUser::class,
            'check.subscription' => \App\Http\Middleware\CheckActiveSubscription::class,
            'check.grace.ended' => \App\Http\Middleware\CheckGracePeriodEnded::class,
            'superadmin' => \App\Http\Middleware\EnsureSuperAdmin::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'validate.registration.email' => \App\Http\Middleware\ValidateRegistrationEmail::class,
        ]);

        // Apply user-specific settings (timezone, etc.) for authenticated users
        $middleware->web(append: [
            \App\Http\Middleware\ApplyUserSettings::class,
            \App\Http\Middleware\ValidateRegistrationEmail::class,
        ]);

        $middleware->redirectGuestsTo('/login');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
