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
            $table->string('category'); // 'topup', 'subscription', 'renewal', 'adjustment'
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
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
