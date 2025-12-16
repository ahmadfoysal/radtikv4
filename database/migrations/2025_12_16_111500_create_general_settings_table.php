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
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, integer, array
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insert default general settings
        DB::table('general_settings')->insert([
            [
                'key' => 'company_name',
                'value' => 'RADTik v4',
                'type' => 'string',
                'description' => 'Company or organization name',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'company_logo',
                'value' => '',
                'type' => 'string',
                'description' => 'Company logo file path',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'company_address',
                'value' => '',
                'type' => 'string',
                'description' => 'Company physical address',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'company_phone',
                'value' => '',
                'type' => 'string',
                'description' => 'Company contact phone number',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'company_email',
                'value' => '',
                'type' => 'string',
                'description' => 'Company contact email address',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'company_website',
                'value' => '',
                'type' => 'string',
                'description' => 'Company website URL',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'timezone',
                'value' => 'UTC',
                'type' => 'string',
                'description' => 'System default timezone',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'date_format',
                'value' => 'Y-m-d',
                'type' => 'string',
                'description' => 'System date format',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'time_format',
                'value' => 'H:i:s',
                'type' => 'string',
                'description' => 'System time format',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'currency',
                'value' => 'USD',
                'type' => 'string',
                'description' => 'Default currency code',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'currency_symbol',
                'value' => '$',
                'type' => 'string',
                'description' => 'Currency symbol',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'items_per_page',
                'value' => '10',
                'type' => 'integer',
                'description' => 'Default items per page in lists',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'maintenance_mode',
                'value' => 'false',
                'type' => 'boolean',
                'description' => 'Enable maintenance mode',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'maintenance_message',
                'value' => 'System is under maintenance. Please check back later.',
                'type' => 'string',
                'description' => 'Maintenance mode message',
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
