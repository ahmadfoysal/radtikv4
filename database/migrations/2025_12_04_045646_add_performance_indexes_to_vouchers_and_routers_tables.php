<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            // Single column indexes for common filter operations
            $table->index('status');
            $table->index('batch');
            $table->index('is_radius');
            $table->index('username');

            // Composite index for common query patterns in Voucher/Index.php
            // Covers: router_id + status + is_radius filters with id ordering
            $table->index(['router_id', 'status', 'is_radius', 'id'], 'vouchers_router_status_radius_id_idx');
        });

        Schema::table('routers', function (Blueprint $table) {
            // Index for API token lookups (used frequently in MikrotikApiController)
            $table->index('app_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['batch']);
            $table->dropIndex(['is_radius']);
            $table->dropIndex(['username']);
            $table->dropIndex('vouchers_router_status_radius_id_idx');
        });

        Schema::table('routers', function (Blueprint $table) {
            $table->dropIndex(['app_key']);
        });
    }
};
