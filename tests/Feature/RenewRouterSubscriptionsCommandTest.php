<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Router;
use App\Models\User;
use App\Models\Zone;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RenewRouterSubscriptionsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_renews_expiring_routers_with_auto_renew(): void
    {
        $user = User::factory()->create(['balance' => 2000.00]);
        $package = Package::create([
            'name' => 'Basic',
            'price_monthly' => 500.00,
            'price_yearly' => 5000.00,
            'user_limit' => 10,
            'billing_cycle' => 'monthly',
            'auto_renew_allowed' => true,
            'is_active' => true,
        ]);

        $zone = new Zone;
        $zone->name = 'Test Zone';
        $zone->user_id = $user->id;
        $zone->save();

        // Create a router that expires in 5 days
        $router = Router::create([
            'name' => 'Test Router',
            'address' => '192.168.1.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => encrypt('password'),
            'user_id' => $user->id,
            'zone_id' => $zone->id,
            'package' => [
                'id' => $package->id,
                'name' => $package->name,
                'price_monthly' => $package->price_monthly,
                'billing_cycle' => $package->billing_cycle,
            ],
            'package_start_date' => Carbon::now()->subMonth(),
            'package_end_date' => Carbon::now()->addDays(5),
            'auto_renew' => true,
        ]);

        $originalEndDate = $router->package_end_date;

        $this->artisan('routers:renew-subscriptions', ['--days' => 7])
            ->assertExitCode(0);

        // Check that router was renewed
        $router->refresh();
        $this->assertTrue($router->package_end_date->greaterThan($originalEndDate));

        // Check that balance was debited
        $user->refresh();
        $this->assertEquals(1500.00, (float) $user->balance);

        // Check that invoice was created
        $this->assertCount(1, $user->invoices);
        $invoice = $user->invoices->first();
        $this->assertEquals('debit', $invoice->type);
        $this->assertEquals('router_renewal', $invoice->category);
        $this->assertEquals($router->id, $invoice->router_id);
    }

    public function test_command_skips_routers_without_auto_renew(): void
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

        // Create a router that expires in 5 days but has auto_renew disabled
        $router = Router::create([
            'name' => 'Test Router',
            'address' => '192.168.1.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => encrypt('password'),
            'user_id' => $user->id,
            'zone_id' => $zone->id,
            'package' => [
                'id' => $package->id,
                'name' => $package->name,
                'price_monthly' => $package->price_monthly,
                'billing_cycle' => $package->billing_cycle,
            ],
            'package_start_date' => Carbon::now()->subMonth(),
            'package_end_date' => Carbon::now()->addDays(5),
            'auto_renew' => false,
        ]);

        $originalEndDate = $router->package_end_date;

        $this->artisan('routers:renew-subscriptions', ['--days' => 7])
            ->assertExitCode(0);

        // Check that router was NOT renewed
        $router->refresh();
        $this->assertEquals($originalEndDate->timestamp, $router->package_end_date->timestamp);

        // Check that balance was NOT debited
        $user->refresh();
        $this->assertEquals(2000.00, (float) $user->balance);
    }

    public function test_command_skips_routers_expiring_outside_window(): void
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

        // Create a router that expires in 10 days (outside the 7-day window)
        $router = Router::create([
            'name' => 'Test Router',
            'address' => '192.168.1.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => encrypt('password'),
            'user_id' => $user->id,
            'zone_id' => $zone->id,
            'package' => [
                'id' => $package->id,
                'name' => $package->name,
                'price_monthly' => $package->price_monthly,
                'billing_cycle' => $package->billing_cycle,
            ],
            'package_start_date' => Carbon::now()->subMonth(),
            'package_end_date' => Carbon::now()->addDays(10),
            'auto_renew' => true,
        ]);

        $originalEndDate = $router->package_end_date;

        $this->artisan('routers:renew-subscriptions', ['--days' => 7])
            ->assertExitCode(0);

        // Check that router was NOT renewed
        $router->refresh();
        $this->assertEquals($originalEndDate->timestamp, $router->package_end_date->timestamp);

        // Check that balance was NOT debited
        $user->refresh();
        $this->assertEquals(2000.00, (float) $user->balance);
    }

    public function test_command_handles_insufficient_balance(): void
    {
        $user = User::factory()->create(['balance' => 100.00]); // Not enough
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

        // Create a router that expires in 5 days
        $router = Router::create([
            'name' => 'Test Router',
            'address' => '192.168.1.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => encrypt('password'),
            'user_id' => $user->id,
            'zone_id' => $zone->id,
            'package' => [
                'id' => $package->id,
                'name' => $package->name,
                'price_monthly' => $package->price_monthly,
                'billing_cycle' => $package->billing_cycle,
            ],
            'package_start_date' => Carbon::now()->subMonth(),
            'package_end_date' => Carbon::now()->addDays(5),
            'auto_renew' => true,
        ]);

        $originalEndDate = $router->package_end_date;

        // Command should still succeed but report failure for this router
        $this->artisan('routers:renew-subscriptions', ['--days' => 7])
            ->assertExitCode(0);

        // Check that router was NOT renewed
        $router->refresh();
        $this->assertEquals($originalEndDate->timestamp, $router->package_end_date->timestamp);

        // Check that balance was NOT debited
        $user->refresh();
        $this->assertEquals(100.00, (float) $user->balance);
    }

    public function test_command_with_custom_days_option(): void
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

        // Create a router that expires in 2 days
        $router = Router::create([
            'name' => 'Test Router',
            'address' => '192.168.1.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => encrypt('password'),
            'user_id' => $user->id,
            'zone_id' => $zone->id,
            'package' => [
                'id' => $package->id,
                'name' => $package->name,
                'price_monthly' => $package->price_monthly,
                'billing_cycle' => $package->billing_cycle,
            ],
            'package_start_date' => Carbon::now()->subMonth(),
            'package_end_date' => Carbon::now()->addDays(2),
            'auto_renew' => true,
        ]);

        $originalEndDate = $router->package_end_date;

        // Use 3 days window, should catch this router
        $this->artisan('routers:renew-subscriptions', ['--days' => 3])
            ->assertExitCode(0);

        // Check that router was renewed
        $router->refresh();
        $this->assertTrue($router->package_end_date->greaterThan($originalEndDate));
    }
}
