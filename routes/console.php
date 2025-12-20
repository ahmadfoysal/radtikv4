<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('model:prune')->daily();

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
