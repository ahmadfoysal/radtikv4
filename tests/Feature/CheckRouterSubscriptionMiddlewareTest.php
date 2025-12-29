<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Router;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Zone;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CheckRouterSubscriptionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Zone $zone;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin role
        Role::create(['name' => 'admin']);

        $this->user = User::factory()->create();
        $this->user->assignRole('admin');

        // Create zone for routers
        $this->zone = Zone::create([
            'name' => 'Test Zone',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_blocks_request_when_no_token_provided(): void
    {
        $response = $this->get('/mikrotik/api/pull-inactive-users');

        $response->assertStatus(403);
        $response->assertJson(['error' => 'Token is required']);
    }

    public function test_blocks_request_when_invalid_token_provided(): void
    {
        $response = $this->get('/mikrotik/api/pull-inactive-users?token=invalid-token');

        $response->assertStatus(403);
        $response->assertJson(['error' => 'Invalid token']);
    }

    public function test_blocks_request_when_user_has_no_subscription(): void
    {
        $router = Router::create([
            'name' => 'Test Router',
            'address' => '192.168.1.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => encrypt('password'),
            'user_id' => $this->user->id,
            'zone_id' => $this->zone->id,
            'app_key' => 'test-token-123',
        ]);

        $response = $this->get('/mikrotik/api/pull-inactive-users?token=' . $router->app_key);

        $response->assertStatus(403);
        $response->assertJson(['error' => 'No active subscription']);
    }

    public function test_blocks_request_when_subscription_is_expired(): void
    {
        $package = Package::create([
            'name' => 'Basic',
            'price_monthly' => 100,
            'max_routers' => 5,
            'is_active' => true,
        ]);

        // Create expired subscription
        Subscription::create([
            'user_id' => $this->user->id,
            'package_id' => $package->id,
            'start_date' => Carbon::now()->subMonth(),
            'end_date' => Carbon::now()->subDay(),
            'billing_cycle' => 'monthly',
            'amount' => 100,
            'original_price' => 100,
            'status' => 'expired',
        ]);

        $router = Router::create([
            'name' => 'Test Router',
            'address' => '192.168.1.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => encrypt('password'),
            'user_id' => $this->user->id,
            'zone_id' => $this->zone->id,
            'app_key' => 'test-token-123',
        ]);

        $response = $this->get('/mikrotik/api/pull-inactive-users?token=' . $router->app_key);

        $response->assertStatus(403);
        $response->assertJson(['error' => 'No active subscription']);
    }

    public function test_allows_request_when_subscription_is_valid(): void
    {
        $package = Package::create([
            'name' => 'Basic',
            'price_monthly' => 100,
            'max_routers' => 5,
            'is_active' => true,
        ]);

        // Create active subscription
        Subscription::create([
            'user_id' => $this->user->id,
            'package_id' => $package->id,
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addMonth(),
            'billing_cycle' => 'monthly',
            'amount' => 100,
            'original_price' => 100,
            'status' => 'active',
        ]);

        $router = Router::create([
            'name' => 'Test Router',
            'address' => '192.168.1.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => encrypt('password'),
            'user_id' => $this->user->id,
            'zone_id' => $this->zone->id,
            'app_key' => 'test-token-123',
        ]);

        $response = $this->get('/mikrotik/api/pull-inactive-users?token=' . $router->app_key);

        // Should not be blocked by middleware (may still return other responses from controller)
        $response->assertStatus(200);
    }

    public function test_middleware_applies_to_all_mikrotik_api_endpoints(): void
    {
        // No active subscription
        $router = Router::create([
            'name' => 'Test Router',
            'address' => '192.168.1.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => encrypt('password'),
            'user_id' => $this->user->id,
            'zone_id' => $this->zone->id,
            'app_key' => 'test-token-123',
        ]);

        $endpoints = [
            ['method' => 'get', 'uri' => '/mikrotik/api/pull-active-users'],
            ['method' => 'get', 'uri' => '/mikrotik/api/pull-profiles'],
            ['method' => 'get', 'uri' => '/mikrotik/api/pull-updated-profiles'],
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->{$endpoint['method']}($endpoint['uri'] . '?token=' . $router->app_key);

            $response->assertStatus(403);
            $response->assertJson(['error' => 'No active subscription']);
        }
    }
}
