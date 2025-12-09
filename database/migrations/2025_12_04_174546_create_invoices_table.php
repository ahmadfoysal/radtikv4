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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('router_id')->nullable();
            $table->string('type'); // 'credit' or 'debit'
            $table->string('category'); // 'topup', 'subscription', 'renewal', 'adjustment', 'payment_gateway'
            $table->string('status')->default('pending'); // pending, completed, failed, cancelled
            $table->string('transaction_id')->nullable(); // External payment reference
            $table->foreignId('payment_gateway_id')->nullable()->constrained('payment_gateways')->onDelete('set null');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('BDT');
            $table->decimal('balance_after', 12, 2);
            $table->text('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('router_id')->references('id')->on('routers')->onDelete('set null');
            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'category']);
            $table->index('status');
            $table->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('invoices');
    }
};
