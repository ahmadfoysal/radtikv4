<?php

use App\Livewire\Router\Create;
use App\Models\Package;
use App\Models\Router;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create roles
    Role::create(['name' => 'superadmin']);
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'reseller']);

    // Create admin user with active subscription
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    // Create a package and subscription for the admin
    $package = Package::factory()->create([
        'name' => 'Test Package',
        'max_routers' => 10,
        'price' => 100,
    ]);

    Subscription::create([
        'user_id' => $this->admin->id,
        'package_id' => $package->id,
        'start_date' => now(),
        'end_date' => now()->addMonth(),
        'status' => 'active',
    ]);
});

test('router is created with unique nas identifier in kebab case', function () {
    actingAs($this->admin);

    Livewire::test(Create::class)
        ->set('name', 'My Test Router')
        ->set('address', '192.168.1.1')
        ->set('port', 8728)
        ->set('username', 'admin')
        ->set('password', 'password123')
        ->call('save');

    $router = Router::where('name', 'My Test Router')->first();

    expect($router)->not->toBeNull()
        ->and($router->nas_identifier)->not->toBeNull()
        ->and($router->nas_identifier)->toStartWith('my-test-router-')
        ->and($router->nas_identifier)->toMatch('/^my-test-router-\d{14}(-\d+)?$/');
});

test('router nas identifier is unique for multiple routers with same name', function () {
    actingAs($this->admin);

    // Create first router
    Livewire::test(Create::class)
        ->set('name', 'Test Router')
        ->set('address', '192.168.1.1')
        ->set('port', 8728)
        ->set('username', 'admin')
        ->set('password', 'password123')
        ->call('save');

    sleep(1); // Ensure different timestamp

    // Create second router with same name
    Livewire::test(Create::class)
        ->set('name', 'Test Router')
        ->set('address', '192.168.1.2')
        ->set('port', 8728)
        ->set('username', 'admin')
        ->set('password', 'password123')
        ->call('save');

    $routers = Router::where('name', 'Test Router')->get();

    expect($routers)->toHaveCount(2)
        ->and($routers[0]->nas_identifier)->not->toBe($routers[1]->nas_identifier)
        ->and($routers[0]->nas_identifier)->toStartWith('test-router-')
        ->and($routers[1]->nas_identifier)->toStartWith('test-router-');
});

test('router nas identifier handles special characters in name', function () {
    actingAs($this->admin);

    Livewire::test(Create::class)
        ->set('name', 'Test @ Router #2024!')
        ->set('address', '192.168.1.1')
        ->set('port', 8728)
        ->set('username', 'admin')
        ->set('password', 'password123')
        ->call('save');

    $router = Router::where('name', 'Test @ Router #2024!')->first();

    expect($router)->not->toBeNull()
        ->and($router->nas_identifier)->toStartWith('test-router-2024-')
        ->and($router->nas_identifier)->not->toContain('@')
        ->and($router->nas_identifier)->not->toContain('#')
        ->and($router->nas_identifier)->not->toContain('!');
});

test('router factory creates nas identifier', function () {
    $router = Router::factory()->create(['user_id' => $this->admin->id]);

    expect($router->nas_identifier)->not->toBeNull()
        ->and($router->nas_identifier)->toMatch('/^[a-z0-9\-]+-\d+$/');
});
