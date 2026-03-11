<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('model:prune')->daily();

// Auto-delete expired vouchers (runs every 5 minutes)
Schedule::command('vouchers:delete-expired')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('Expired voucher cleanup completed at ' . now());
    })
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Expired voucher cleanup failed at ' . now());
    });

// Auto-renew subscriptions within early payment period (runs twice daily)
Schedule::command('subscriptions:auto-renew')
    ->twiceDaily(8, 20) // Run at 8:00 AM and 8:00 PM
    ->withoutOverlapping()
    ->runInBackground()
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('Auto-renewal process completed successfully at ' . now());
    })
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Auto-renewal process failed at ' . now());
    });

// Retry failed RADIUS voucher syncs (runs every hour)
Schedule::command('radius:retry-failed-vouchers')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('Failed voucher retry completed at ' . now());
    })
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Failed voucher retry failed at ' . now());
    });

// Demo Mode: Reset demo data every hour
if (env('DEMO_MODE', false)) {
    Schedule::command('demo:reset --force')
        ->hourly()
        ->withoutOverlapping()
        ->runInBackground()
        ->onSuccess(function () {
            \Illuminate\Support\Facades\Log::info('Demo data reset completed successfully at ' . now());
        })
        ->onFailure(function () {
            \Illuminate\Support\Facades\Log::error('Demo data reset failed at ' . now());
        });
}
