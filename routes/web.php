<?php

use App\Http\Controllers\Api\MikrotikApiController;
use App\Http\Controllers\PaymentCallbackController;
use App\Http\Controllers\Voucher\SingleVoucherPrintController;
use App\Http\Controllers\Voucher\VoucherPrintController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
// use request
use Illuminate\Support\Facades\Route;

// Landing Page (Public)
Route::get('/', App\Livewire\LandingPage::class)->name('landing');

// Contact Form Submission (Public)
Route::post('/contact', [App\Http\Controllers\ContactMessageController::class, 'store'])->name('contact.store');

// All routes below require login and suspension check
Route::middleware(['auth', 'check.suspended'])->group(function () {

    /* ========================================
     * ALL AUTHENTICATED USERS
     * ======================================== */
    // Dashboard
    Route::get('/dashboard', App\Livewire\Dashboard::class)->name('dashboard');
    Route::get('/home', App\Livewire\Dashboard::class)->name('home');

    // Support & Help
    Route::get('/support/contact', App\Livewire\Tickets\Index::class)->name('tickets.index');
    Route::get('/support/contact/{ticket}', App\Livewire\Tickets\Show::class)->name('tickets.show');

    Route::get('/knowledgebase', App\Livewire\Knowledgebase\Index::class)->name('knowledgebase.index');
    Route::get('/knowledgebase/{slug}', App\Livewire\Knowledgebase\Show::class)->name('knowledgebase.show');

    Route::get('/docs', App\Livewire\Docs\Index::class)->name('docs.index');
    Route::get('/docs/{slug}', App\Livewire\Docs\Show::class)->name('docs.show');

    // User Profile & Settings
    Route::get('/settings/profile', App\Livewire\Settings\Profile::class)->name('settings.profile');

    // Notifications
    Route::get('/notifications', App\Livewire\Components\AllNotifications::class)->name('notifications.index');

    /* ========================================
     * SUPERADMIN ONLY
     * ======================================== */
    Route::middleware(['role:superadmin'])->group(function () {
        // Customer Management
        Route::get('/customers', App\Livewire\Admin\CustomerManagement\Index::class)->name('customers.index');
        Route::get('/customers/{customer}', App\Livewire\Admin\CustomerManagement\Show::class)->name('customers.show');
        Route::get('/customers/{customer}/edit', App\Livewire\Admin\CustomerManagement\Edit::class)->name('customers.edit');

        // Package Management
        Route::get('/packages', App\Livewire\Package\Index::class)->name('packages.index');
        Route::get('/package/add', App\Livewire\Package\Create::class)->name('packages.create');
        Route::get('/package/{package}/edit', App\Livewire\Package\Edit::class)->name('packages.edit');

        // User View (Details)
        Route::get('/user/{user}/view', App\Livewire\User\View::class)->name('users.view');

        // Billing - Revenue Analytics & Manual Adjustment
        Route::get('/billing/revenue-analytics', App\Livewire\Admin\RevenueAnalytics::class)->name('billing.revenue-analytics');
        Route::get('/billing/manual-adjustment', App\Livewire\Billing\ManualAdjustment::class)->name('billing.manual-adjustment');

        // Sales History
        Route::get('/sales', App\Livewire\Admin\Sales::class)->name('sales.index');

        // Superadmin Settings
        Route::get('/superadmin/payment-gateways', App\Livewire\Admin\PaymentGatewaySettings::class)->name('superadmin.payment-gateways');
        Route::get('/superadmin/email-settings', App\Livewire\Admin\EmailSettings::class)->name('superadmin.email-settings');
        Route::get('/admin/theme-settings', App\Livewire\Admin\ThemeSettings::class)->name('admin.theme-settings');

        // Log Management
        Route::get('/superadmin/logs', App\Livewire\Admin\LogManagement::class)->name('superadmin.logs');

        // Contact Messages
        Route::get('/contact-messages', App\Livewire\Admin\ContactMessages::class)->name('contact.messages');
    });

    /* ========================================
     * SUPERADMIN & ADMIN
     * ======================================== */
    Route::middleware(['role:superadmin|admin'])->group(function () {
        // User Management
        Route::get('/users', App\Livewire\User\Index::class)->name('users.index');
        Route::get('/user/add', App\Livewire\User\Create::class)->name('users.create');
        Route::get('/user/{user}/edit', App\Livewire\User\Edit::class)->name('users.edit');

        // Reports
        Route::get('/reports/logs', App\Livewire\ActivityLog\Index::class)->name('activity.logs');

        // Admin Settings
        Route::get('/admin/general-settings', App\Livewire\Admin\GeneralSettings::class)->name('admin.general-settings');
        Route::get('/billing/invoices', App\Livewire\Billing\Invoices::class)->name('billing.invoices');
    });

    /* ========================================
     * ADMIN ONLY
     * ======================================== */
    Route::middleware(['role:admin'])->group(function () {
        // Reseller Management
        Route::get('/reseller/assign-router', App\Livewire\Admin\AssignResellerRouters::class)->name('reseller.assign-router');
        Route::get('/reseller/assign-profile', App\Livewire\Admin\AssignResellerProfiles::class)->name('reseller.assign-profile');
        Route::get('/resellers/permissions', App\Livewire\Admin\ResellerPermissions::class)->name('resellers.permissions');

        // Zone Management
        Route::get('/zones', App\Livewire\Zone\Index::class)->name('zones.index');

        // RADIUS Server Management
        Route::get('/radius', App\Livewire\Radius\Index::class)->name('radius.index');
        Route::get('/radius/create', App\Livewire\Radius\Create::class)->name('radius.create');
        Route::get('/radius/{server}/edit', App\Livewire\Radius\Edit::class)->name('radius.edit');
        Route::get('/radius/setup-guide', App\Livewire\Radius\SetupGuide::class)->name('radius.setup-guide');

        // Subscription Management
        Route::get('/subscription', App\Livewire\Subscription\Index::class)->name('subscription.index');
        Route::get('/subscription/history', App\Livewire\Subscription\History::class)->name('subscription.history');

        Route::get('/router/import', App\Livewire\Router\Import::class)->name('routers.import');


        // Billing
        Route::get('/billing/add-balance', App\Livewire\Billing\AddBalance::class)->name('billing.add-balance');
    });

    /* ========================================
     * ADMIN & RESELLER
     * ======================================== */
    Route::middleware(['role:admin|reseller'])->group(function () {
        // Router Management
        Route::get('/routers', App\Livewire\Router\Index::class)->name('routers.index');
        Route::get('/router/add', App\Livewire\Router\Create::class)->name('routers.create');
        Route::get('/router/{router}/edit', App\Livewire\Router\Edit::class)->name('routers.edit');
        Route::get('/router/{router}', App\Livewire\Router\Show::class)->name('routers.show');

        // Profile Management
        Route::get('/profiles', App\Livewire\Profile\Index::class)->name('profiles');
        Route::get('/profile/add', App\Livewire\Profile\Create::class)->name('profiles.create');
        Route::get('/profile/{profile}/edit', App\Livewire\Profile\Edit::class)->name('profiles.edit');

        // Voucher Management
        Route::get('/vouchers', App\Livewire\Voucher\Index::class)->name('vouchers.index');
        Route::get('/voucher/add', App\Livewire\Voucher\Generate::class)->name('vouchers.create');
        Route::get('/vouchers/generate', App\Livewire\Voucher\Generate::class)->name('vouchers.generate');
        Route::get('/vouchers/bulk-manager', App\Livewire\Voucher\BulkManager::class)->name('vouchers.bulk-manager');
        Route::get('/vouchers/logs', App\Livewire\VoucherLogs\Index::class)->name('vouchers.logs');
        Route::get('/vouchers/print', VoucherPrintController::class)->name('vouchers.print');
        Route::get('/vouchers/{voucher}/print-single', SingleVoucherPrintController::class)->name('vouchers.print.single');

        // Hotspot Users
        Route::get('/hotspot/users/create', App\Livewire\HotspotUsers\Create::class)->name('hotspot.users.create');
        Route::get('/hotspot/sessions', App\Livewire\HotspotUsers\ActiveSessions::class)->name('hotspot.sessions');
        Route::get('/hotspot/session-cookies', App\Livewire\HotspotUsers\SessionCookies::class)->name('hotspot.sessionCookies');
        Route::get('/hotspot/logs', App\Livewire\HotspotUsers\Logs::class)->name('hotspot.logs');

        // Billing - Sales Summary
        Route::get('/billing/sales-summary', App\Livewire\Admin\SalesSummary::class)->name('billing.sales-summary');
    });
});

/* MikroTik API Routes - Subscription Required */
// Hotspot User sync
Route::get('/mikrotik/api/pull-inactive-users', [MikrotikApiController::class, 'pullInactiveUsers'])
    ->name('mikrotik.pullInactiveUsers');
Route::get('/mikrotik/api/pull-active-users', [MikrotikApiController::class, 'pullActiveUsers'])
    ->name('mikrotik.pullActiveUsers');
Route::post('/mikrotik/api/push-active-users', [MikrotikApiController::class, 'pushActiveUsers'])
    ->name('mikrotik.pushActiveUsers')
    ->withoutMiddleware([VerifyCsrfToken::class]);
Route::post('/mikrotik/api/sync-orphans', [MikrotikApiController::class, 'syncOrphans'])
    ->name('mikrotik.syncOrphans')
    ->withoutMiddleware([VerifyCsrfToken::class]);

// Hotspot profile sync
Route::get('/mikrotik/api/pull-profiles', [MikrotikApiController::class, 'pullProfiles'])
    ->name('mikrotik.pullProfiles');
Route::get('/mikrotik/api/pull-updated-profiles', [MikrotikApiController::class, 'pullUpdatedProfiles'])
    ->name('mikrotik.pullUpdatedProfiles');


/* Payment Gateway Callbacks (without CSRF) */
Route::post('/payment/cryptomus/callback', [App\Http\Controllers\PaymentCallbackController::class, 'cryptomus'])
    ->withoutMiddleware([VerifyCsrfToken::class])->name('payment.cryptomus.callback');
Route::get('/payment/paystation/callback', [App\Http\Controllers\PaymentCallbackController::class, 'paystation'])
    ->withoutMiddleware([VerifyCsrfToken::class])->name('payment.paystation.callback');

/* Deploy route */
Route::post('/api/deploy', [App\Http\Controllers\Api\DeployController::class, 'deploy'])
    ->withoutMiddleware([VerifyCsrfToken::class])->name('deploy');
