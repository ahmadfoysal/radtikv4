<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Package;
use App\Models\Router;
use App\Models\User;
use App\Models\Voucher;
use App\Models\Zone;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLoggerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_model_creation_is_logged(): void
    {
        $package = Package::create([
            'name' => 'Test Package',
            'price_monthly' => 100,
            'user_limit' => 10,
            'billing_cycle' => 'monthly',
            'auto_renew_allowed' => true,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->user->id,
            'action' => 'created',
            'model_type' => Package::class,
            'model_id' => $package->id,
        ]);

        $log = ActivityLog::where('model_id', $package->id)
            ->where('model_type', Package::class)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('created', $log->action);
        $this->assertNotNull($log->new_values);
        $this->assertNull($log->old_values);
    }

    public function test_model_update_is_logged(): void
    {
        $package = Package::create([
            'name' => 'Test Package',
            'price_monthly' => 100,
            'user_limit' => 10,
            'billing_cycle' => 'monthly',
            'auto_renew_allowed' => true,
            'is_active' => true,
        ]);

        // Clear initial creation log
        ActivityLog::query()->delete();

        $package->update([
            'name' => 'Updated Package',
            'price_monthly' => 150,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->user->id,
            'action' => 'updated',
            'model_type' => Package::class,
            'model_id' => $package->id,
        ]);

        $log = ActivityLog::where('model_id', $package->id)
            ->where('action', 'updated')
            ->first();

        $this->assertNotNull($log);
        $this->assertNotNull($log->old_values);
        $this->assertNotNull($log->new_values);
        $this->assertEquals('Test Package', $log->old_values['name']);
        $this->assertEquals('Updated Package', $log->new_values['name']);
    }

    public function test_model_deletion_is_logged(): void
    {
        $package = Package::create([
            'name' => 'Test Package',
            'price_monthly' => 100,
            'user_limit' => 10,
            'billing_cycle' => 'monthly',
            'auto_renew_allowed' => true,
            'is_active' => true,
        ]);

        $packageId = $package->id;

        // Clear initial creation log
        ActivityLog::query()->delete();

        $package->delete();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->user->id,
            'action' => 'deleted',
            'model_type' => Package::class,
            'model_id' => $packageId,
        ]);
    }

    public function test_custom_logging_works(): void
    {
        $package = Package::create([
            'name' => 'Test Package',
            'price_monthly' => 100,
            'user_limit' => 10,
            'billing_cycle' => 'monthly',
            'auto_renew_allowed' => true,
            'is_active' => true,
        ]);

        ActivityLogger::logCustom(
            'maintenance_performed',
            $package,
            'Performed maintenance on package',
            ['maintenance_type' => 'update']
        );

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->user->id,
            'action' => 'maintenance_performed',
            'model_type' => Package::class,
            'model_id' => $package->id,
        ]);

        $log = ActivityLog::where('action', 'maintenance_performed')->first();
        $this->assertEquals('Performed maintenance on package', $log->description);
        $this->assertEquals(['maintenance_type' => 'update'], $log->new_values);
    }

    public function test_sensitive_data_is_sanitized(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('secret_password'),
        ]);

        // Clear initial creation log
        ActivityLog::query()->delete();

        $user->update([
            'password' => bcrypt('new_secret_password'),
        ]);

        $log = ActivityLog::where('model_id', $user->id)
            ->where('action', 'updated')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('[REDACTED]', $log->new_values['password']);
        $this->assertEquals('[REDACTED]', $log->old_values['password']);
    }

    public function test_activity_log_captures_request_details(): void
    {
        $package = Package::create([
            'name' => 'Test Package',
            'price_monthly' => 100,
            'user_limit' => 10,
            'billing_cycle' => 'monthly',
            'auto_renew_allowed' => true,
            'is_active' => true,
        ]);

        $log = ActivityLog::where('model_id', $package->id)
            ->where('action', 'created')
            ->first();

        $this->assertNotNull($log->ip_address);
        $this->assertNotNull($log->user_agent);
    }

    public function test_multiple_crud_operations_create_separate_logs(): void
    {
        $package = Package::create([
            'name' => 'Test Package',
            'price_monthly' => 100,
            'user_limit' => 10,
            'billing_cycle' => 'monthly',
            'auto_renew_allowed' => true,
            'is_active' => true,
        ]);

        $packageId = $package->id;

        $package->update(['name' => 'Updated Package']);
        $package->delete();

        $logs = ActivityLog::where('model_type', Package::class)
            ->where('model_id', $packageId)
            ->orderBy('id')
            ->get();

        $this->assertCount(3, $logs);
        $this->assertEquals('created', $logs[0]->action);
        $this->assertEquals('updated', $logs[1]->action);
        $this->assertEquals('deleted', $logs[2]->action);
    }

    public function test_logging_works_for_multiple_models(): void
    {
        $package1 = Package::create([
            'name' => 'Test Package 1',
            'price_monthly' => 100,
            'user_limit' => 10,
            'billing_cycle' => 'monthly',
            'auto_renew_allowed' => true,
            'is_active' => true,
        ]);

        $package2 = Package::create([
            'name' => 'Test Package 2',
            'price_monthly' => 200,
            'user_limit' => 20,
            'billing_cycle' => 'yearly',
            'auto_renew_allowed' => false,
            'is_active' => true,
        ]);

        $user = User::factory()->create();

        $this->assertDatabaseHas('activity_logs', [
            'model_type' => Package::class,
            'model_id' => $package1->id,
            'action' => 'created',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'model_type' => Package::class,
            'model_id' => $package2->id,
            'action' => 'created',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'model_type' => User::class,
            'model_id' => $user->id,
            'action' => 'created',
        ]);
    }
}
