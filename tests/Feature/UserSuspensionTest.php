<?php

use App\Models\User;
use App\Models\Router;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create roles
    Role::create(['name' => 'superadmin']);
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'reseller']);
});

test('superadmin can suspend admin users', function () {
    $superadmin = User::factory()->create();
    $admin = User::factory()->create();

    $superadmin->assignRole('superadmin');
    $admin->assignRole('admin');

    expect($admin->isSuspended())->toBeFalse();

    $admin->suspend('Test suspension');

    expect($admin->fresh()->isSuspended())->toBeTrue();
    expect($admin->fresh()->suspension_reason)->toBe('Test suspension');
});

test('suspended user cannot access mikrotik router', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $router = Router::factory()->create(['user_id' => $admin->id]);

    // Suspend the admin
    $admin->suspend('Test suspension');

    $routerClient = new \App\MikroTik\Client\RouterClient();

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Access denied: User account is suspended');

    $routerClient->make($router);
});

test('admin can be unsuspended', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $admin->suspend('Test suspension');
    expect($admin->fresh()->isSuspended())->toBeTrue();

    $admin->unsuspend();
    expect($admin->fresh()->isSuspended())->toBeFalse();
    expect($admin->fresh()->suspension_reason)->toBeNull();
});

test('user suspension methods work correctly', function () {
    $user = User::factory()->create();

    expect($user->isSuspended())->toBeFalse();

    $user->suspend('Testing suspension functionality');

    expect($user->isSuspended())->toBeTrue();
    expect($user->suspension_reason)->toBe('Testing suspension functionality');
    expect($user->suspended_at)->not->toBeNull();

    $user->unsuspend();

    expect($user->isSuspended())->toBeFalse();
    expect($user->suspension_reason)->toBeNull();
    expect($user->suspended_at)->toBeNull();
});
