<?php

namespace Tests\Feature;

use App\Models\Router;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Voucher;
use App\Models\VoucherLog;
use App\Models\Zone;
use App\Services\VoucherLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoucherLoggerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Router $router;

    protected UserProfile $profile;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Create zone
        $zone = new Zone;
        $zone->name = 'Test Zone';
        $zone->user_id = $this->user->id;
        $zone->save();

        // Create router
        $this->router = new Router;
        $this->router->name = 'Test Router';
        $this->router->address = '192.168.1.1';
        $this->router->port = 8728;
        $this->router->username = 'admin';
        $this->router->password = encrypt('password');
        $this->router->user_id = $this->user->id;
        $this->router->zone_id = $zone->id;
        $this->router->save();

        // Create user profile
        $this->profile = new UserProfile;
        $this->profile->name = '1 Day Package';
        $this->profile->rate_limit = '1M/1M';
        $this->profile->validity = 1;
        $this->profile->price = 50.00;
        $this->profile->user_id = $this->user->id;
        $this->profile->save();
    }

    public function test_log_creates_voucher_log_with_snapshot_data(): void
    {
        $voucher = new Voucher;
        $voucher->username = 'test_user_123';
        $voucher->password = 'test_pass';
        $voucher->user_profile_id = $this->profile->id;
        $voucher->status = 'active';
        $voucher->user_id = $this->user->id;
        $voucher->router_id = $this->router->id;
        $voucher->created_by = $this->user->id;
        $voucher->batch = 'BATCH001';
        $voucher->save();

        $log = VoucherLogger::log($voucher, $this->router, 'activated', ['ip' => '192.168.1.100']);

        $this->assertInstanceOf(VoucherLog::class, $log);
        $this->assertEquals($this->user->id, $log->user_id);
        $this->assertEquals($voucher->id, $log->voucher_id);
        $this->assertEquals($this->router->id, $log->router_id);
        $this->assertEquals('activated', $log->event_type);
        $this->assertEquals('test_user_123', $log->username);
        $this->assertEquals('1 Day Package', $log->profile);
        $this->assertEquals(50.00, (float) $log->price);
        $this->assertEquals(1, $log->validity_days);
        $this->assertEquals('Test Router', $log->router_name);
        $this->assertEquals(['ip' => '192.168.1.100'], $log->meta);
    }

    public function test_log_handles_null_voucher(): void
    {
        $log = VoucherLogger::log(null, $this->router, 'expired', ['reason' => 'auto-cleanup']);

        $this->assertNull($log->voucher_id);
        $this->assertNull($log->username);
        $this->assertNull($log->profile);
        $this->assertNull($log->price);
        $this->assertNull($log->validity_days);
        $this->assertEquals('Test Router', $log->router_name);
        $this->assertEquals('expired', $log->event_type);
    }

    public function test_log_handles_null_router(): void
    {
        $voucher = new Voucher;
        $voucher->username = 'test_user_456';
        $voucher->password = 'test_pass';
        $voucher->user_profile_id = $this->profile->id;
        $voucher->status = 'inactive';
        $voucher->user_id = $this->user->id;
        $voucher->router_id = $this->router->id;
        $voucher->created_by = $this->user->id;
        $voucher->batch = 'BATCH002';
        $voucher->save();

        $log = VoucherLogger::log($voucher, null, 'deleted');

        $this->assertNull($log->router_id);
        $this->assertNull($log->router_name);
        $this->assertEquals('test_user_456', $log->username);
        $this->assertEquals('deleted', $log->event_type);
    }

    public function test_log_records_different_event_types(): void
    {
        $voucher = new Voucher;
        $voucher->username = 'test_user_789';
        $voucher->password = 'test_pass';
        $voucher->user_profile_id = $this->profile->id;
        $voucher->status = 'active';
        $voucher->user_id = $this->user->id;
        $voucher->router_id = $this->router->id;
        $voucher->created_by = $this->user->id;
        $voucher->batch = 'BATCH003';
        $voucher->save();

        $activatedLog = VoucherLogger::log($voucher, $this->router, 'activated');
        $expiredLog = VoucherLogger::log($voucher, $this->router, 'expired');
        $syncedLog = VoucherLogger::log($voucher, $this->router, 'synced');

        $this->assertEquals('activated', $activatedLog->event_type);
        $this->assertEquals('expired', $expiredLog->event_type);
        $this->assertEquals('synced', $syncedLog->event_type);
    }

    public function test_voucher_log_belongs_to_user(): void
    {
        $voucher = new Voucher;
        $voucher->username = 'test_user_rel';
        $voucher->password = 'test_pass';
        $voucher->user_profile_id = $this->profile->id;
        $voucher->status = 'active';
        $voucher->user_id = $this->user->id;
        $voucher->router_id = $this->router->id;
        $voucher->created_by = $this->user->id;
        $voucher->batch = 'BATCH004';
        $voucher->save();

        $log = VoucherLogger::log($voucher, $this->router, 'activated');

        $this->assertTrue($log->user->is($this->user));
    }

    public function test_voucher_log_belongs_to_voucher(): void
    {
        $voucher = new Voucher;
        $voucher->username = 'test_user_rel2';
        $voucher->password = 'test_pass';
        $voucher->user_profile_id = $this->profile->id;
        $voucher->status = 'active';
        $voucher->user_id = $this->user->id;
        $voucher->router_id = $this->router->id;
        $voucher->created_by = $this->user->id;
        $voucher->batch = 'BATCH005';
        $voucher->save();

        $log = VoucherLogger::log($voucher, $this->router, 'activated');

        $this->assertTrue($log->voucher->is($voucher));
    }

    public function test_voucher_log_belongs_to_router(): void
    {
        $voucher = new Voucher;
        $voucher->username = 'test_user_rel3';
        $voucher->password = 'test_pass';
        $voucher->user_profile_id = $this->profile->id;
        $voucher->status = 'active';
        $voucher->user_id = $this->user->id;
        $voucher->router_id = $this->router->id;
        $voucher->created_by = $this->user->id;
        $voucher->batch = 'BATCH006';
        $voucher->save();

        $log = VoucherLogger::log($voucher, $this->router, 'activated');

        $this->assertTrue($log->router->is($this->router));
    }

    public function test_prunable_returns_old_logs(): void
    {
        // Create a log older than 6 months
        $oldLog = new VoucherLog;
        $oldLog->user_id = $this->user->id;
        $oldLog->event_type = 'activated';
        $oldLog->username = 'old_user';
        $oldLog->created_at = now()->subMonths(7);
        $oldLog->save();

        // Create a recent log
        $recentLog = new VoucherLog;
        $recentLog->user_id = $this->user->id;
        $recentLog->event_type = 'activated';
        $recentLog->username = 'recent_user';
        $recentLog->created_at = now()->subMonths(3);
        $recentLog->save();

        $prunableQuery = (new VoucherLog)->prunable();
        $prunableIds = $prunableQuery->pluck('id')->toArray();

        $this->assertContains($oldLog->id, $prunableIds);
        $this->assertNotContains($recentLog->id, $prunableIds);
    }
}
