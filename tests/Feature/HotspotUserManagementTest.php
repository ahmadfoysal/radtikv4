<?php

use App\Models\Router;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create roles
    \Spatie\Permission\Models\Role::create(['name' => 'superadmin']);
    \Spatie\Permission\Models\Role::create(['name' => 'admin']);
    \Spatie\Permission\Models\Role::create(['name' => 'reseller']);

    // Create users
    $this->admin = User::factory()->create(['email' => 'admin@test.com']);
    $this->admin->assignRole('admin');

    $this->otherAdmin = User::factory()->create(['email' => 'other@test.com']);
    $this->otherAdmin->assignRole('admin');

    // Create routers
    $this->router = Router::factory()->create(['user_id' => $this->admin->id, 'name' => 'Admin Router']);
    $this->otherRouter = Router::factory()->create(['user_id' => $this->otherAdmin->id, 'name' => 'Other Router']);

    // Create user profile
    $this->profile = UserProfile::factory()->create(['user_id' => $this->admin->id]);
});

// ==================== Authorization Tests ====================

test('authenticated user can access hotspot users create page', function () {
    $this->actingAs($this->admin)
        ->get(route('hotspot.users.create'))
        ->assertOk();
});

test('authenticated user can access active sessions page', function () {
    $this->actingAs($this->admin)
        ->get(route('hotspot.sessions'))
        ->assertOk();
});

test('authenticated user can access session cookies page', function () {
    $this->actingAs($this->admin)
        ->get(route('hotspot.sessionCookies'))
        ->assertOk();
});

test('authenticated user can access hotspot logs page', function () {
    $this->actingAs($this->admin)
        ->get(route('hotspot.logs'))
        ->assertOk();
});

test('guest cannot access hotspot management pages', function () {
    $this->get(route('hotspot.users.create'))
        ->assertRedirect(route('login'));

    $this->get(route('hotspot.sessions'))
        ->assertRedirect(route('login'));

    $this->get(route('hotspot.sessionCookies'))
        ->assertRedirect(route('login'));

    $this->get(route('hotspot.logs'))
        ->assertRedirect(route('login'));
});

// ==================== Create Component Tests ====================

test('create component only shows user own routers', function () {
    $this->actingAs($this->admin);

    Livewire::test(\App\Livewire\HotspotUsers\Create::class)
        ->assertSet('router_id', null)
        ->assertViewHas('routers', function ($routers) {
            return $routers->count() === 1 && $routers->first()->id === $this->router->id;
        });
});

test('create component does not show other users routers', function () {
    $this->actingAs($this->admin);

    Livewire::test(\App\Livewire\HotspotUsers\Create::class)
        ->assertViewHas('routers', function ($routers) {
            return $routers->doesntContain('id', $this->otherRouter->id);
        });
});

test('create component requires authentication for router operations', function () {
    $this->actingAs($this->admin);

    // Try to use another user's router should fail
    Livewire::test(\App\Livewire\HotspotUsers\Create::class)
        ->set('router_id', $this->otherRouter->id)
        ->assertSet('available_profiles', []);
});

test('create component shows error when no user profile exists', function () {
    // Delete the profile
    $this->profile->delete();

    $this->actingAs($this->admin);

    Livewire::test(\App\Livewire\HotspotUsers\Create::class)
        ->set('router_id', $this->router->id)
        ->set('username', 'testuser')
        ->set('password', 'testpass')
        ->call('save')
        ->assertNotSet('router_id', null); // Should not reset form on error
});

test('create component validates required fields', function () {
    $this->actingAs($this->admin);

    Livewire::test(\App\Livewire\HotspotUsers\Create::class)
        ->set('router_id', null)
        ->set('username', '')
        ->set('password', '')
        ->call('save')
        ->assertHasErrors(['router_id', 'username', 'password']);
});

test('create component validates username length', function () {
    $this->actingAs($this->admin);

    Livewire::test(\App\Livewire\HotspotUsers\Create::class)
        ->set('router_id', $this->router->id)
        ->set('username', 'ab') // Too short
        ->set('password', 'password')
        ->call('save')
        ->assertHasErrors(['username']);
});

test('create component validates password length', function () {
    $this->actingAs($this->admin);

    Livewire::test(\App\Livewire\HotspotUsers\Create::class)
        ->set('router_id', $this->router->id)
        ->set('username', 'testuser')
        ->set('password', 'ab') // Too short
        ->call('save')
        ->assertHasErrors(['password']);
});

// ==================== Active Sessions Component Tests ====================

test('active sessions component only shows user own routers', function () {
    $this->actingAs($this->admin);

    Livewire::test(\App\Livewire\HotspotUsers\ActiveSessions::class)
        ->assertViewHas('routers', function ($routers) {
            return $routers->count() === 1 && $routers->first()->id === $this->router->id;
        });
});

test('active sessions component does not show other users routers', function () {
    $this->actingAs($this->admin);

    Livewire::test(\App\Livewire\HotspotUsers\ActiveSessions::class)
        ->assertViewHas('routers', function ($routers) {
            return $routers->doesntContain('id', $this->otherRouter->id);
        });
});

test('active sessions component initializes with empty sessions', function () {
    $this->actingAs($this->admin);

    Livewire::test(\App\Livewire\HotspotUsers\ActiveSessions::class)
        ->assertSet('router_id', null)
        ->assertSet('sessions', [])
        ->assertSet('loading', false);
});

// ==================== Session Cookies Component Tests ====================

test('session cookies component only shows user own routers', function () {
    $this->actingAs($this->admin);

    Livewire::test(\App\Livewire\HotspotUsers\SessionCookies::class)
        ->assertViewHas('routers', function ($routers) {
            return $routers->count() === 1 && $routers->first()->id === $this->router->id;
        });
});

test('session cookies component does not show other users routers', function () {
    $this->actingAs($this->admin);

    Livewire::test(\App\Livewire\HotspotUsers\SessionCookies::class)
        ->assertViewHas('routers', function ($routers) {
            return $routers->doesntContain('id', $this->otherRouter->id);
        });
});

test('session cookies component initializes with empty cookies', function () {
    $this->actingAs($this->admin);

    Livewire::test(\App\Livewire\HotspotUsers\SessionCookies::class)
        ->assertSet('router_id', null)
        ->assertSet('cookies', [])
        ->assertSet('loading', false);
});

// ==================== Logs Component Tests ====================

test('logs component only shows user own routers', function () {
    $this->actingAs($this->admin);

    Livewire::test(\App\Livewire\HotspotUsers\Logs::class)
        ->assertViewHas('routers', function ($routers) {
            return $routers->count() === 1 && $routers->first()->id === $this->router->id;
        });
});

test('logs component does not show other users routers', function () {
    $this->actingAs($this->admin);

    Livewire::test(\App\Livewire\HotspotUsers\Logs::class)
        ->assertViewHas('routers', function ($routers) {
            return $routers->doesntContain('id', $this->otherRouter->id);
        });
});

test('logs component initializes with empty logs', function () {
    $this->actingAs($this->admin);

    Livewire::test(\App\Livewire\HotspotUsers\Logs::class)
        ->assertSet('router_id', null)
        ->assertSet('logs', [])
        ->assertSet('loading', false);
});
