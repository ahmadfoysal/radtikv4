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
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Channel preferences
            $table->boolean('email_enabled')->default(true);
            $table->boolean('database_enabled')->default(true);

            // Event-specific toggles
            $table->boolean('router_offline')->default(true);
            $table->boolean('voucher_expiring')->default(true);
            $table->boolean('low_balance')->default(true);
            $table->boolean('payment_received')->default(true);
            $table->boolean('subscription_renewal')->default(true);
            $table->boolean('invoice_generated')->default(true);

            // Thresholds
            $table->decimal('low_balance_threshold', 12, 2)->default(100); // Currency amount
            $table->integer('voucher_expiry_days')->default(7); // Days before expiry

            $table->timestamps();

            // Ensure one preference record per user
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
