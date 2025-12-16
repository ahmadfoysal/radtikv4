<?php

use App\Models\User;
use App\Models\GeneralSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Livewire\Livewire;
use App\Livewire\Admin\GeneralSettings as GeneralSettingsComponent;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'reseller']);
    Role::create(['name' => 'superadmin']);
});

test('admin can access general settings page', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)->get('/admin/general-settings');

    $response->assertStatus(200);
});

test('superadmin can access general settings page', function () {
    $user = User::factory()->create();
    $user->assignRole('superadmin');

    $response = $this->actingAs($user)->get('/admin/general-settings');

    $response->assertStatus(200);
});

test('reseller cannot access general settings page', function () {
    $user = User::factory()->create();
    $user->assignRole('reseller');

    $response = $this->actingAs($user)->get('/admin/general-settings');

    $response->assertStatus(403);
});

test('general settings model can get and set values', function () {
    // Create test setting
    $setting = GeneralSetting::create([
        'key' => 'test_setting',
        'value' => 'test_value',
        'type' => 'string',
        'description' => 'Test setting',
        'is_active' => true,
    ]);

    expect(GeneralSetting::getValue('test_setting'))->toBe('test_value');

    // Update value
    GeneralSetting::setValue('test_setting', 'updated_value');
    
    expect(GeneralSetting::getValue('test_setting'))->toBe('updated_value');
});

test('admin can save general settings', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(GeneralSettingsComponent::class)
        ->set('company_name', 'Test Company')
        ->set('company_email', 'test@company.com')
        ->set('company_phone', '+1234567890')
        ->call('saveSettings')
        ->assertHasNoErrors();

    expect(GeneralSetting::getValue('company_name'))->toBe('Test Company');
    expect(GeneralSetting::getValue('company_email'))->toBe('test@company.com');
    expect(GeneralSetting::getValue('company_phone'))->toBe('+1234567890');
});

test('general settings requires valid email format', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(GeneralSettingsComponent::class)
        ->set('company_email', 'invalid-email')
        ->call('saveSettings')
        ->assertHasErrors(['company_email']);
});

test('general settings requires valid url format for website', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(GeneralSettingsComponent::class)
        ->set('company_website', 'invalid-url')
        ->call('saveSettings')
        ->assertHasErrors(['company_website']);
});

test('general settings can reset to defaults', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(GeneralSettingsComponent::class)
        ->set('company_name', 'Test Company')
        ->call('resetToDefaults')
        ->assertSet('company_name', 'RADTik v4')
        ->assertSet('timezone', 'UTC')
        ->assertSet('currency', 'USD');
});
