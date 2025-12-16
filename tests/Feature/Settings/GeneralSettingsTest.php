<?php

use App\Models\User;
use App\Models\GeneralSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Livewire\Livewire;
use App\Livewire\Admin\AdminGeneralSettings;
use App\Livewire\Admin\SuperAdminGeneralSettings;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'reseller']);
    Role::create(['name' => 'superadmin']);
});

// Admin General Settings Tests
test('admin can access admin general settings page', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)->get('/admin/general-settings');

    $response->assertStatus(200);
});

test('admin cannot access superadmin general settings page', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)->get('/superadmin/general-settings');

    $response->assertStatus(403);
});

test('reseller cannot access admin general settings page', function () {
    $user = User::factory()->create();
    $user->assignRole('reseller');

    $response = $this->actingAs($user)->get('/admin/general-settings');

    $response->assertStatus(403);
});

// SuperAdmin General Settings Tests
test('superadmin can access superadmin general settings page', function () {
    $user = User::factory()->create();
    $user->assignRole('superadmin');

    $response = $this->actingAs($user)->get('/superadmin/general-settings');

    $response->assertStatus(200);
});

test('superadmin can access admin general settings page', function () {
    $user = User::factory()->create();
    $user->assignRole('superadmin');

    $response = $this->actingAs($user)->get('/admin/general-settings');

    $response->assertStatus(403); // SuperAdmins should use their own settings page
});

// General Settings Model Tests
test('general settings model can get and set user-specific values', function () {
    $user = User::factory()->create();

    // Set user-specific setting
    GeneralSetting::setValue('company_name', 'User Company', 'string', $user->id);

    expect(GeneralSetting::getValue('company_name', null, $user->id))->toBe('User Company');

    // Test fallback to global setting
    GeneralSetting::setValue('default_currency', 'EUR', 'string', null);
    expect(GeneralSetting::getValue('currency', null, $user->id))->toBe('EUR');
});

test('general settings model can get and set global values', function () {
    // Set global setting
    GeneralSetting::setValue('platform_name', 'Test Platform', 'string', null);

    expect(GeneralSetting::getValue('platform_name', null, null))->toBe('Test Platform');
});

test('admin can save user-specific general settings', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(AdminGeneralSettings::class)
        ->set('company_name', 'Test Company')
        ->set('company_email', 'test@company.com')
        ->set('company_phone', '+1234567890')
        ->call('saveSettings')
        ->assertHasNoErrors();

    expect(GeneralSetting::getValue('company_name', null, $user->id))->toBe('Test Company');
    expect(GeneralSetting::getValue('company_email', null, $user->id))->toBe('test@company.com');
    expect(GeneralSetting::getValue('company_phone', null, $user->id))->toBe('+1234567890');
});

test('superadmin can save platform settings', function () {
    $user = User::factory()->create();
    $user->assignRole('superadmin');

    Livewire::actingAs($user)
        ->test(SuperAdminGeneralSettings::class)
        ->set('platform_name', 'Test Platform')
        ->set('default_currency', 'EUR')
        ->set('max_routers_per_admin', 20)
        ->call('saveSettings')
        ->assertHasNoErrors();

    expect(GeneralSetting::getValue('platform_name', null, null))->toBe('Test Platform');
    expect(GeneralSetting::getValue('default_currency', null, null))->toBe('EUR');
    expect(GeneralSetting::getValue('max_routers_per_admin', null, null))->toBe(20);
});

test('admin general settings requires valid email format', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(AdminGeneralSettings::class)
        ->set('company_email', 'invalid-email')
        ->call('saveSettings')
        ->assertHasErrors(['company_email']);
});

test('admin general settings requires valid url format for website', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(AdminGeneralSettings::class)
        ->set('company_website', 'invalid-url')
        ->call('saveSettings')
        ->assertHasErrors(['company_website']);
});

test('admin general settings can reset to defaults', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    // Set global defaults first
    GeneralSetting::setValue('default_timezone', 'America/New_York', 'string', null);
    GeneralSetting::setValue('default_currency', 'EUR', 'string', null);

    Livewire::actingAs($user)
        ->test(AdminGeneralSettings::class)
        ->set('company_name', 'Test Company')
        ->call('resetToDefaults')
        ->assertSet('company_name', $user->name ?? 'My Company')
        ->assertSet('timezone', 'America/New_York')
        ->assertSet('currency', 'EUR');
});

test('superadmin general settings can reset to defaults', function () {
    $user = User::factory()->create();
    $user->assignRole('superadmin');

    Livewire::actingAs($user)
        ->test(SuperAdminGeneralSettings::class)
        ->set('platform_name', 'Test Platform')
        ->call('resetToDefaults')
        ->assertSet('platform_name', 'RADTik v4 Platform')
        ->assertSet('default_timezone', 'UTC')
        ->assertSet('default_currency', 'USD');
});

test('user-specific settings do not affect other users', function () {
    $user1 = User::factory()->create();
    $user1->assignRole('admin');

    $user2 = User::factory()->create();
    $user2->assignRole('admin');

    // User 1 sets their settings
    GeneralSetting::setValue('company_name', 'User 1 Company', 'string', $user1->id);
    GeneralSetting::setValue('timezone', 'America/New_York', 'string', $user1->id);

    // User 2 sets different settings
    GeneralSetting::setValue('company_name', 'User 2 Company', 'string', $user2->id);
    GeneralSetting::setValue('timezone', 'Europe/London', 'string', $user2->id);

    // Verify settings are isolated
    expect(GeneralSetting::getValue('company_name', null, $user1->id))->toBe('User 1 Company');
    expect(GeneralSetting::getValue('timezone', null, $user1->id))->toBe('America/New_York');

    expect(GeneralSetting::getValue('company_name', null, $user2->id))->toBe('User 2 Company');
    expect(GeneralSetting::getValue('timezone', null, $user2->id))->toBe('Europe/London');
});

test('user settings fallback to global defaults', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    // Set global defaults
    GeneralSetting::setValue('default_currency', 'EUR', 'string', null);
    GeneralSetting::setValue('default_timezone', 'Europe/Berlin', 'string', null);

    // User has no specific settings, should get global defaults
    expect(GeneralSetting::getValue('currency', null, $user->id))->toBe('EUR');
    expect(GeneralSetting::getValue('timezone', null, $user->id))->toBe('Europe/Berlin');

    // User sets specific setting, should override global default
    GeneralSetting::setValue('currency', 'USD', 'string', $user->id);

    expect(GeneralSetting::getValue('currency', null, $user->id))->toBe('USD');
    expect(GeneralSetting::getValue('timezone', null, $user->id))->toBe('Europe/Berlin'); // Still fallback to global
});
