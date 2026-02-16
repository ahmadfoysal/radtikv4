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
            $table->foreignId('user_profile_id')->constrained()->restrictOnDelete();
            $table->string('username');
            $table->string('password');
            $table->enum('status', ['active', 'inactive', 'expired', 'disabled'])->default('inactive');
            $table->string('mac_address')->nullable();
            $table->dateTime('activated_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('router_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->bigInteger('bytes_in')->default(0);
            $table->bigInteger('bytes_out')->default(0);
            $table->string('up_time')->nullable();
            $table->string('batch');
            
            // RADIUS Sync Fields
            $table->enum('radius_sync_status', ['pending', 'synced', 'failed'])->default('pending');
            $table->timestamp('radius_synced_at')->nullable();
            $table->text('radius_sync_error')->nullable();
            
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
