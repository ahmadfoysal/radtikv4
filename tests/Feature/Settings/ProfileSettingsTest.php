<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'reseller']);
    Role::create(['name' => 'superadmin']);
});

test('superadmin can access settings route', function () {
    $user = User::factory()->create();
    $user->assignRole('superadmin');

    $response = $this->actingAs($user)->get('/settings/profile');

    $response->assertStatus(200);
});

test('admin can access settings route', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)->get('/settings/profile');

    $response->assertStatus(200);
});

test('reseller can access settings route', function () {
    $user = User::factory()->create();
    $user->assignRole('reseller');

    $response = $this->actingAs($user)->get('/settings/profile');

    $response->assertStatus(200);
});

test('user model has new preference fields', function () {
    $user = User::factory()->create([
        'email_notifications' => true,
        'login_alerts' => false,
        'preferred_language' => 'en',
    ]);

    expect($user->email_notifications)->toBe(true);
    expect($user->login_alerts)->toBe(false);
    expect($user->preferred_language)->toBe('en');
});
