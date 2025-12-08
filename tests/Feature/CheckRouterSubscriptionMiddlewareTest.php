<?php

namespace Tests\Feature;

use App\Models\Router;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckRouterSubscriptionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
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

    public function test_blocks_request_when_router_has_no_package(): void
    {
        $router = Router::factory()->create([
            'user_id' => $this->user->id,
            'package' => null,
        ]);

        $response = $this->get('/mikrotik/api/pull-inactive-users?token=' . $router->app_key);

        $response->assertStatus(403);
        $response->assertJson(['error' => 'No active subscription']);
    }

    public function test_blocks_request_when_package_has_no_end_date(): void
    {
        $router = Router::factory()->create([
            'user_id' => $this->user->id,
            'package' => [
                'id' => 1,
                'name' => 'Basic',
            ],
        ]);

        $response = $this->get('/mikrotik/api/pull-inactive-users?token=' . $router->app_key);

        $response->assertStatus(403);
        $response->assertJson(['error' => 'No active subscription']);
    }

    public function test_blocks_request_when_subscription_is_expired(): void
    {
        $router = Router::factory()->create([
            'user_id' => $this->user->id,
            'package' => [
                'id' => 1,
                'name' => 'Basic',
                'end_date' => Carbon::now()->subDays(1)->toDateTimeString(),
            ],
        ]);

        $response = $this->get('/mikrotik/api/pull-inactive-users?token=' . $router->app_key);

        $response->assertStatus(403);
        $response->assertJson(['error' => 'Subscription expired']);
    }

    public function test_allows_request_when_subscription_is_valid(): void
    {
        $router = Router::factory()->create([
            'user_id' => $this->user->id,
            'package' => [
                'id' => 1,
                'name' => 'Basic',
                'end_date' => Carbon::now()->addDays(30)->toDateTimeString(),
            ],
        ]);

        $response = $this->get('/mikrotik/api/pull-inactive-users?token=' . $router->app_key);

        // Should not be blocked by middleware (may still return other responses from controller)
        $response->assertStatus(200);
    }

    public function test_middleware_applies_to_pull_active_users(): void
    {
        $router = Router::factory()->create([
            'user_id' => $this->user->id,
            'package' => [
                'id' => 1,
                'name' => 'Basic',
                'end_date' => Carbon::now()->subDays(1)->toDateTimeString(),
            ],
        ]);

        $response = $this->get('/mikrotik/api/pull-active-users?token=' . $router->app_key);

        $response->assertStatus(403);
        $response->assertJson(['error' => 'Subscription expired']);
    }

    public function test_middleware_applies_to_push_active_users(): void
    {
        $router = Router::factory()->create([
            'user_id' => $this->user->id,
            'package' => [
                'id' => 1,
                'name' => 'Basic',
                'end_date' => Carbon::now()->subDays(1)->toDateTimeString(),
            ],
        ]);

        $response = $this->post('/mikrotik/api/push-active-users?token=' . $router->app_key);

        $response->assertStatus(403);
        $response->assertJson(['error' => 'Subscription expired']);
    }

    public function test_middleware_applies_to_sync_orphans(): void
    {
        $router = Router::factory()->create([
            'user_id' => $this->user->id,
            'package' => [
                'id' => 1,
                'name' => 'Basic',
                'end_date' => Carbon::now()->subDays(1)->toDateTimeString(),
            ],
        ]);

        $response = $this->get('/mikrotik/api/sync-orphans?token=' . $router->app_key);

        $response->assertStatus(403);
        $response->assertJson(['error' => 'Subscription expired']);
    }

    public function test_middleware_applies_to_pull_profiles(): void
    {
        $router = Router::factory()->create([
            'user_id' => $this->user->id,
            'package' => [
                'id' => 1,
                'name' => 'Basic',
                'end_date' => Carbon::now()->subDays(1)->toDateTimeString(),
            ],
        ]);

        $response = $this->get('/mikrotik/api/pull-profiles?token=' . $router->app_key);

        $response->assertStatus(403);
        $response->assertJson(['error' => 'Subscription expired']);
    }

    public function test_middleware_applies_to_pull_updated_profiles(): void
    {
        $router = Router::factory()->create([
            'user_id' => $this->user->id,
            'package' => [
                'id' => 1,
                'name' => 'Basic',
                'end_date' => Carbon::now()->subDays(1)->toDateTimeString(),
            ],
        ]);

        $response = $this->get('/mikrotik/api/pull-updated-profiles?token=' . $router->app_key);

        $response->assertStatus(403);
        $response->assertJson(['error' => 'Subscription expired']);
    }
}
