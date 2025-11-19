<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MikrotikApiController;



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
    Route::get('/router/import', App\Livewire\Router\Import::class)->name('routers.import');

    /* Voucher Routes */
    Route::get('/vouchers', App\Livewire\Voucher\Index::class)->name('vouchers.index');
    Route::get('/voucher/add', App\Livewire\Voucher\Create::class)->name('vouchers.create');
    Route::get('/voucher/{voucher}/edit', App\Livewire\Voucher\Edit::class)->name('vouchers.edit');
    Route::get('/vouchers/generate', App\Livewire\Voucher\Generate::class)->name('vouchers.generate');

    /* User Profile list */
    Route::get('/profiles', App\Livewire\Profile\Index::class)->name('profiles');
    Route::get('/profile/add', App\Livewire\Profile\Create::class)->name('profiles.create');
    Route::get('/profile/{profile}/edit', App\Livewire\Profile\Edit::class)->name('profiles.edit');

    /* Radius Profile Routes */
    Route::get('/radius/profiles', App\Livewire\Radius\Profile\Index::class)->name('radius.profiles');
    Route::get('/radius/profile/add', App\Livewire\Radius\Profile\Create::class)->name('radius.profiles.create');
    Route::get('/radius/profile/{profile}/edit', App\Livewire\Radius\Profile\Edit::class)->name('radius.profiles.edit');



    /* Zone Routes */
    Route::get('/zones', App\Livewire\Zone\Index::class)->name('zones.index');
});

/* Hotspot User sync */
Route::get('/mikrotik/api/pull-inactive-users', [MikrotikApiController::class, 'pullInactiveUsers'])->name('mikrotik.pullInactiveUsers');
Route::get('/mikrotik/pull-active-users', [MikrotikApiController::class, 'pullActiveUsers'])->name('mikrotik.pullActiveUsers');
Route::post('/mikrotik/api/push-active-users', [MikrotikApiController::class, 'pushActiveUsers'])->name('mikrotik.pushActiveUsers');
Route::get('/mikrotik/check-user', [MikrotikApiController::class, 'checkUser'])->name('mikrotik.checkUser');

/* Hotspot profile sync */
Route::get('/mikrotik/pull-profiles', [MikrotikApiController::class, 'pullProfiles'])->name('mikrotik.pullProfiles');
Route::get('/mikrotik/check-profile', [MikrotikApiController::class, 'checkProfile'])->name('mikrotik.checkProfile');
Route::get('/mikrotik/pull-updated-profiles', [MikrotikApiController::class, 'pullUpdatedProfiles'])->name('mikrotik.pullUpdatedProfiles');
