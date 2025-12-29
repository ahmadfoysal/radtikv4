<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Package;
use App\Models\User;
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

    public function test_custom_logging_works(): void
    {
        ActivityLog::log(
            'maintenance_performed',
            'Performed maintenance on package',
            ['maintenance_type' => 'update']
        );

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->user->id,
            'action' => 'maintenance_performed',
            'description' => 'Performed maintenance on package',
        ]);

        $log = ActivityLog::where('action', 'maintenance_performed')->first();
        $this->assertEquals('Performed maintenance on package', $log->description);
        $this->assertEquals(['maintenance_type' => 'update'], $log->data);
    }

    public function test_custom_logging_without_data(): void
    {
        ActivityLog::log(
            'vouchers_generated',
            'Generated 100 vouchers in batch B12345'
        );

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->user->id,
            'action' => 'vouchers_generated',
            'description' => 'Generated 100 vouchers in batch B12345',
        ]);

        $log = ActivityLog::where('action', 'vouchers_generated')->first();
        $this->assertNull($log->data);
    }

    public function test_activity_log_with_additional_data(): void
    {
        ActivityLog::log(
            'bulk_operation',
            'Processed 50 items',
            [
                'quantity' => 50,
                'batch' => 'B12345',
                'router_id' => 5,
            ]
        );

        $log = ActivityLog::where('action', 'bulk_operation')->first();

        $this->assertEquals(50, $log->data['quantity']);
        $this->assertEquals('B12345', $log->data['batch']);
    }
}
