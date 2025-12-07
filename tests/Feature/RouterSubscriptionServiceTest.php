<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Router;
use App\Models\User;
use App\Models\Zone;
use App\Services\Subscriptions\RouterSubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class RouterSubscriptionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RouterSubscriptionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RouterSubscriptionService::class);
    }

    public function test_has_balance_for_package_returns_true_when_sufficient_balance(): void
    {
        $user = User::factory()->create(['balance' => 1000.00]);
        $package = Package::create([
            'name' => 'Basic',
            'price_monthly' => 500.00,
            'price_yearly' => 5000.00,
            'user_limit' => 10,
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);

        $this->assertTrue($this->service->hasBalanceForPackage($user, $package));
    }

    public function test_has_balance_for_package_returns_false_when_insufficient_balance(): void
    {
        $user = User::factory()->create(['balance' => 100.00]);
        $package = Package::create([
            'name' => 'Basic',
            'price_monthly' => 500.00,
            'price_yearly' => 5000.00,
            'user_limit' => 10,
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);

        $this->assertFalse($this->service->hasBalanceForPackage($user, $package));
    }

    public function test_subscribe_new_router_creates_router_and_debits_balance(): void
    {
        $user = User::factory()->create(['balance' => 1000.00]);
        $package = Package::create([
            'name' => 'Basic',
            'price_monthly' => 500.00,
            'price_yearly' => 5000.00,
            'user_limit' => 10,
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);

        $zone = new Zone;
        $zone->name = 'Test Zone';
        $zone->user_id = $user->id;
        $zone->save();

        $routerData = [
            'name' => 'Test Router',
            'address' => '192.168.1.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => encrypt('password'),
            'zone_id' => $zone->id,
        ];

        $router = $this->service->subscribeNewRouter($user, $package, $routerData);

        // Check router was created
        $this->assertInstanceOf(Router::class, $router);
        $this->assertEquals('Test Router', $router->name);
        $this->assertEquals($user->id, $router->user_id);

        // Check package snapshot
        $this->assertIsArray($router->package);
        $this->assertEquals($package->id, $router->package['id']);
        $this->assertEquals($package->name, $router->package['name']);

        // Check subscription dates are in the package JSON
        $this->assertArrayHasKey('start_date', $router->package);
        $this->assertArrayHasKey('end_date', $router->package);
        $this->assertArrayHasKey('auto_renew', $router->package);
        $this->assertArrayHasKey('price', $router->package);

        $startDate = \Carbon\Carbon::parse($router->package['start_date']);
        $endDate = \Carbon\Carbon::parse($router->package['end_date']);
        $this->assertTrue($endDate->greaterThan($startDate));

        // Check balance was debited
        $user->refresh();
        $this->assertEquals(500.00, (float) $user->balance);

        // Check invoice was created
        $this->assertCount(1, $user->invoices);
        $invoice = $user->invoices->first();
        $this->assertEquals('debit', $invoice->type);
        $this->assertEquals('router_subscription', $invoice->category);
        $this->assertEquals(500.00, (float) $invoice->amount);
        $this->assertEquals($router->id, $invoice->router_id);
    }

    public function test_subscribe_new_router_throws_exception_when_insufficient_balance(): void
    {
        $user = User::factory()->create(['balance' => 100.00]);
        $package = Package::create([
            'name' => 'Basic',
            'price_monthly' => 500.00,
            'price_yearly' => 5000.00,
            'user_limit' => 10,
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);

        $routerData = [
            'name' => 'Test Router',
            'address' => '192.168.1.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => encrypt('password'),
        ];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Insufficient balance for package subscription.');

        $this->service->subscribeNewRouter($user, $package, $routerData);
    }

    public function test_subscribe_new_router_with_yearly_package(): void
    {
        $user = User::factory()->create(['balance' => 6000.00]);
        $package = Package::create([
            'name' => 'Premium',
            'price_monthly' => 600.00,
            'price_yearly' => 5000.00,
            'user_limit' => 100,
            'billing_cycle' => 'yearly',
            'is_active' => true,
        ]);

        $zone = new Zone;
        $zone->name = 'Test Zone';
        $zone->user_id = $user->id;
        $zone->save();

        $routerData = [
            'name' => 'Test Router',
            'address' => '192.168.1.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => encrypt('password'),
            'zone_id' => $zone->id,
        ];

        $router = $this->service->subscribeNewRouter($user, $package, $routerData);

        // Check yearly price was charged
        $user->refresh();
        $this->assertEquals(1000.00, (float) $user->balance);

        // Check end date is one year from start
        $startDate = \Carbon\Carbon::parse($router->package['start_date']);
        $endDate = \Carbon\Carbon::parse($router->package['end_date']);
        $this->assertGreaterThanOrEqual(
            365,
            $startDate->diffInDays($endDate)
        );
    }

    public function test_renew_router_extends_subscription(): void
    {
        $user = User::factory()->create(['balance' => 2000.00]);
        $package = Package::create([
            'name' => 'Basic',
            'price_monthly' => 500.00,
            'price_yearly' => 5000.00,
            'user_limit' => 10,
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);

        $zone = new Zone;
        $zone->name = 'Test Zone';
        $zone->user_id = $user->id;
        $zone->save();

        $routerData = [
            'name' => 'Test Router',
            'address' => '192.168.1.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => encrypt('password'),
            'zone_id' => $zone->id,
        ];

        // Create initial subscription
        $router = $this->service->subscribeNewRouter($user, $package, $routerData);
        $firstEndDate = \Carbon\Carbon::parse($router->package['end_date']);

        // Renew the router
        $renewedRouter = $this->service->renewRouter($router);

        // Check balance was debited again
        $user->refresh();
        $this->assertEquals(1000.00, (float) $user->balance);

        // Check end date was extended
        $renewedEndDate = \Carbon\Carbon::parse($renewedRouter->package['end_date']);
        $this->assertTrue($renewedEndDate->greaterThan($firstEndDate));

        // Check invoices
        $this->assertCount(2, $user->invoices);
        $renewalInvoice = $user->invoices()->where('category', 'router_renewal')->first();
        $this->assertNotNull($renewalInvoice);
        $this->assertEquals(500.00, (float) $renewalInvoice->amount);
    }

    public function test_renew_router_throws_exception_when_insufficient_balance(): void
    {
        $user = User::factory()->create(['balance' => 1000.00]);
        $package = Package::create([
            'name' => 'Basic',
            'price_monthly' => 500.00,
            'price_yearly' => 5000.00,
            'user_limit' => 10,
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);

        $zone = new Zone;
        $zone->name = 'Test Zone';
        $zone->user_id = $user->id;
        $zone->save();

        $routerData = [
            'name' => 'Test Router',
            'address' => '192.168.1.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => encrypt('password'),
            'zone_id' => $zone->id,
        ];

        // Create initial subscription (balance: 1000 - 500 = 500)
        $router = $this->service->subscribeNewRouter($user, $package, $routerData);

        // Try to renew (needs 500 but only has 500, which is exactly enough)
        $user->refresh();
        $this->assertEquals(500.00, (float) $user->balance);

        // This should succeed
        $renewedRouter = $this->service->renewRouter($router);
        $this->assertInstanceOf(Router::class, $renewedRouter);

        // Now balance is 0, should fail
        $user->refresh();
        $this->assertEquals(0.00, (float) $user->balance);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Insufficient balance for router renewal.');

        $this->service->renewRouter($router);
    }

    public function test_renew_router_with_different_package(): void
    {
        $user = User::factory()->create(['balance' => 2000.00]);
        $package1 = Package::create([
            'name' => 'Basic',
            'price_monthly' => 500.00,
            'price_yearly' => 5000.00,
            'user_limit' => 10,
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);
        $package2 = Package::create([
            'name' => 'Premium',
            'price_monthly' => 800.00,
            'price_yearly' => 8000.00,
            'user_limit' => 50,
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);

        $zone = new Zone;
        $zone->name = 'Test Zone';
        $zone->user_id = $user->id;
        $zone->save();

        $routerData = [
            'name' => 'Test Router',
            'address' => '192.168.1.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => encrypt('password'),
            'zone_id' => $zone->id,
        ];

        // Create initial subscription with package1
        $router = $this->service->subscribeNewRouter($user, $package1, $routerData);

        // Renew with package2
        $renewedRouter = $this->service->renewRouter($router, $package2);

        // Check balance (2000 - 500 - 800 = 700)
        $user->refresh();
        $this->assertEquals(700.00, (float) $user->balance);

        // Check new package snapshot
        $this->assertEquals($package2->id, $renewedRouter->package['id']);
        $this->assertEquals($package2->name, $renewedRouter->package['name']);
    }

    public function test_user_trait_has_balance_for_package(): void
    {
        $user = User::factory()->create(['balance' => 1000.00]);
        $package = Package::create([
            'name' => 'Basic',
            'price_monthly' => 500.00,
            'price_yearly' => 5000.00,
            'user_limit' => 10,
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);

        $this->assertTrue($user->hasBalanceForPackage($package));

        $user->balance = 100.00;
        $user->save();

        $this->assertFalse($user->hasBalanceForPackage($package));
    }

    public function test_user_trait_subscribe_router_with_package(): void
    {
        $user = User::factory()->create(['balance' => 1000.00]);
        $package = Package::create([
            'name' => 'Basic',
            'price_monthly' => 500.00,
            'price_yearly' => 5000.00,
            'user_limit' => 10,
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);

        $zone = new Zone;
        $zone->name = 'Test Zone';
        $zone->user_id = $user->id;
        $zone->save();

        $routerData = [
            'name' => 'Test Router',
            'address' => '192.168.1.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => encrypt('password'),
            'zone_id' => $zone->id,
        ];

        $router = $user->subscribeRouterWithPackage($routerData, $package);

        $this->assertInstanceOf(Router::class, $router);
        $this->assertEquals($user->id, $router->user_id);
        $this->assertEquals($package->id, $router->package['id']);

        // Check balance was debited
        $user->refresh();
        $this->assertEquals(500.00, (float) $user->balance);
    }
}
