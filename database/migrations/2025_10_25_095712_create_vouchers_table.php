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
            $table->string('radius_profile')->nullable();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('username');
            $table->string('password');
            $table->dateTime('expires_at');
            $table->foreignId('router_id')->constrained()->restrictOnDelete();
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
