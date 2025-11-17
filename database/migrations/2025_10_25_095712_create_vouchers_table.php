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
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('router_profile')->nullable();
            $table->foreignId('radius_profile_id')->nullable()->constrained('radius_profiles')->restrictOnDelete();
            $table->string('username');
            $table->string('password');
            $table->enum('status', ['active', 'inactive', 'expired', 'disabled'])->default('active');
            $table->string('mac_address')->nullable();
            $table->dateTime('activated_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('router_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->bigInteger('bytes_in')->default(0);
            $table->bigInteger('bytes_out')->default(0);
            $table->string('up_time');
            $table->boolean('is_radius')->default(false);
            $table->foreignId('radius_server_id')->nullable()->constrained('radius_servers')->restrictOnDelete();
            $table->string('batch');
            $table->boolean('is_synced')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
