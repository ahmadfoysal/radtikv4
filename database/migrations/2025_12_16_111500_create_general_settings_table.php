<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('general_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('key');
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, integer, array
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Composite unique constraint: user-specific settings can have same key but different user_id
            // Global settings have user_id = null
            $table->unique(['user_id', 'key']);
        });

        // Insert default global settings (for superadmin - platform-wide)
        DB::table('general_settings')->insert([
            // Platform-wide system settings
            [
                'user_id' => null,
                'key' => 'platform_name',
                'value' => 'RADTik v4 Platform',
                'type' => 'string',
                'description' => 'Platform name for superadmin',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => null,
                'key' => 'default_timezone',
                'value' => 'UTC',
                'type' => 'string',
                'description' => 'Default system timezone',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => null,
                'key' => 'default_currency',
                'value' => 'USD',
                'type' => 'string',
                'description' => 'Default platform currency',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => null,
                'key' => 'default_currency_symbol',
                'value' => '$',
                'type' => 'string',
                'description' => 'Default currency symbol',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => null,
                'key' => 'default_date_format',
                'value' => 'Y-m-d',
                'type' => 'string',
                'description' => 'Default date format',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => null,
                'key' => 'default_time_format',
                'value' => 'H:i:s',
                'type' => 'string',
                'description' => 'Default time format',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => null,
                'key' => 'default_items_per_page',
                'value' => '10',
                'type' => 'integer',
                'description' => 'Default items per page in lists',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => null,
                'key' => 'platform_maintenance_mode',
                'value' => 'false',
                'type' => 'boolean',
                'description' => 'Platform-wide maintenance mode',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => null,
                'key' => 'platform_maintenance_message',
                'value' => 'Platform is under maintenance. Please check back later.',
                'type' => 'string',
                'description' => 'Platform maintenance message',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => null,
                'key' => 'registration_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Allow new user registrations',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => null,
                'key' => 'max_routers_per_admin',
                'value' => '10',
                'type' => 'integer',
                'description' => 'Maximum routers per admin user',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => null,
                'key' => 'default_admin_commission',
                'value' => '5.00',
                'type' => 'string',
                'description' => 'Default commission rate for new admins (%)',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('general_settings');
    }
};
