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
        Schema::table('routers', function (Blueprint $table) {
            $table->timestamp('package_start_date')->nullable()->after('package');
            $table->timestamp('package_end_date')->nullable()->after('package_start_date');
            $table->boolean('auto_renew')->default(false)->after('package_end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            $table->dropColumn(['package_start_date', 'package_end_date', 'auto_renew']);
        });
    }
};
