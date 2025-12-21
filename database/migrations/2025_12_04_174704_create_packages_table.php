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
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Package name (e.g., Free, Starter, Business)');
            $table->text('description')->nullable();
            $table->decimal('price_monthly', 12, 2)->comment('Monthly subscription fee');
            $table->decimal('price_yearly', 12, 2)->nullable()->comment('Yearly fee with discount');
            $table->integer('max_routers')->comment('Maximum routers admin can manage');
            $table->integer('max_users')->default(100)->comment('Maximum users per router');
            $table->integer('max_zones')->nullable()->comment('Maximum zones admin can create');
            $table->integer('max_vouchers_per_router')->nullable()->comment('Voucher generation limit');
            $table->integer('grace_period_days')->default(3)->comment('Days after expiry before suspension');
            $table->integer('early_pay_days')->nullable();
            $table->integer('early_pay_discount_percent')->nullable();
            $table->boolean('auto_renew_allowed')->default(true);
            $table->json('features')->nullable()->comment('Additional features enabled');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
