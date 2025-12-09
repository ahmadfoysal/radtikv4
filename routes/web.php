<?php

use App\Http\Controllers\Api\MikrotikApiController;
use App\Http\Controllers\PaymentCallbackController;
use App\Http\Controllers\Voucher\SingleVoucherPrintController;
use App\Http\Controllers\Voucher\VoucherPrintController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
// use request
use Illuminate\Support\Facades\Route;

// All routes below require login
Route::middleware(['auth'])->group(function () {

    /* Dashboard Route */
    Route::get('/', App\Livewire\Dashboard::class)->name('home');
    Route::get('/dashboard', App\Livewire\Dashboard::class)->name('dashboard');

    /* User Routes */
    Route::get('/users', App\Livewire\User\Index::class)->name('users.index');
    Route::get('/user/add', App\Livewire\User\Create::class)->name('users.create');
    Route::get('/user/{user}/edit', App\Livewire\User\Edit::class)->name('users.edit');

    /* Router Routes */
    Route::get('/routers', App\Livewire\Router\Index::class)->name('routers.index');
    Route::get('/router/add', App\Livewire\Router\Create::class)->name('routers.create');
    Route::get('/router/{router}/edit', App\Livewire\Router\Edit::class)->name('routers.edit');
    Route::get('/router/{router}', App\Livewire\Router\Show::class)->name('routers.show');
    Route::get('/router/import', App\Livewire\Router\Import::class)->name('routers.import');

    /* Voucher Routes */
    Route::get('/vouchers', App\Livewire\Voucher\Index::class)->name('vouchers.index');
    Route::get('/voucher/add', App\Livewire\Voucher\Create::class)->name('vouchers.create');
    Route::get('/voucher/{voucher}/edit', App\Livewire\Voucher\Edit::class)->name('vouchers.edit');
    Route::get('/vouchers/generate', App\Livewire\Voucher\Generate::class)->name('vouchers.generate');
    Route::get('/vouchers/bulk-manager', App\Livewire\Voucher\BulkManager::class)->name('vouchers.bulk-manager');
    Route::get('/vouchers/print', VoucherPrintController::class)->name('vouchers.print');
    Route::get('/vouchers/{voucher}/print-single', SingleVoucherPrintController::class)->name('vouchers.print.single');

    /* User Profile list */
    Route::get('/profiles', App\Livewire\Profile\Index::class)->name('profiles');
    Route::get('/profile/add', App\Livewire\Profile\Create::class)->name('profiles.create');
    Route::get('/profile/{profile}/edit', App\Livewire\Profile\Edit::class)->name('profiles.edit');

    /* Zone Routes */
    Route::get('/zones', App\Livewire\Zone\Index::class)->name('zones.index');

    /* Package Routes */
    Route::get('/packages', App\Livewire\Package\Index::class)->name('packages.index');
    Route::get('/package/add', App\Livewire\Package\Create::class)->name('packages.create');
    Route::get('/package/{package}/edit', App\Livewire\Package\Edit::class)->name('packages.edit');

    /* Billing Routes */
    Route::get('/billing/invoices', App\Livewire\Billing\Invoices::class)->name('billing.invoices');
    Route::get('/billing/manual-adjustment', App\Livewire\Billing\ManualAdjustment::class)->name('billing.manual-adjustment');

    /* Ticket Routes */
    Route::get('/tickets', App\Livewire\Tickets\Index::class)->name('tickets.index');
    Route::get('/tickets/{ticket}', App\Livewire\Tickets\Show::class)->name('tickets.show');
    Route::get('/billing/add-balance', App\Livewire\Billing\AddBalance::class)->name('billing.add-balance');

    /* Admin Routes */
    Route::get('/admin/payment-gateways', App\Livewire\Admin\PaymentGatewaySettings::class)->name('admin.payment-gateways');
});

/* Hotspot User sync */
Route::get('/mikrotik/api/pull-inactive-users', [MikrotikApiController::class, 'pullInactiveUsers'])
    ->name('mikrotik.pullInactiveUsers')->middleware('check.router.subscription');
Route::get('/mikrotik/api/pull-active-users', [MikrotikApiController::class, 'pullActiveUsers'])
    ->name('mikrotik.pullActiveUsers')->middleware('check.router.subscription');
Route::post('/mikrotik/api/push-active-users', [MikrotikApiController::class, 'pushActiveUsers'])
    ->name('mikrotik.pushActiveUsers')->withoutMiddleware([VerifyCsrfToken::class])->middleware('check.router.subscription');
Route::get('/mikrotik/api/sync-orphans', [MikrotikApiController::class, 'syncOrphans'])
    ->name('mikrotik.syncOrphans')->withoutMiddleware([VerifyCsrfToken::class])->middleware('check.router.subscription');

/* Hotspot profile sync */
Route::get('/mikrotik/api/pull-profiles', [MikrotikApiController::class, 'pullProfiles'])
    ->name('mikrotik.pullProfiles')->middleware('check.router.subscription');
Route::get('/mikrotik/api/pull-updated-profiles', [MikrotikApiController::class, 'pullUpdatedProfiles'])
    ->name('mikrotik.pullUpdatedProfiles')->middleware('check.router.subscription');

/* Payment Gateway Callbacks (without CSRF) */
Route::post('/payment/cryptomus/callback', [App\Http\Controllers\PaymentCallbackController::class, 'cryptomus'])
    ->withoutMiddleware([VerifyCsrfToken::class])->name('payment.cryptomus.callback');
Route::post('/payment/paystation/callback', [App\Http\Controllers\PaymentCallbackController::class, 'paystation'])
    ->withoutMiddleware([VerifyCsrfToken::class])->name('payment.paystation.callback');

/* Deploy route */
Route::post('/api/deploy', [App\Http\Controllers\Api\DeployController::class, 'deploy'])
    ->withoutMiddleware([VerifyCsrfToken::class])->name('deploy');
