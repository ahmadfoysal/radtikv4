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
        Schema::create('voucher_logs', function (Blueprint $table) {
            $table->id();

            // Foreign keys with nullable and nullOnDelete
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('voucher_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('router_id')->nullable()->constrained()->nullOnDelete();

            // Event information
            $table->string('event_type');

            // Snapshot fields (preserve data even if voucher/router deleted)
            $table->string('username')->nullable();
            $table->string('profile')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->integer('validity')->nullable();
            $table->string('router_name')->nullable();

            // Extra metadata
            $table->json('meta')->nullable();

            $table->timestamps();

            // Index for pruning queries
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('voucher_logs');
    }
};
