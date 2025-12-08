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
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('status')->default('completed')->after('category'); // pending, completed, failed, cancelled
            $table->string('transaction_id')->nullable()->after('status'); // External payment reference
            $table->foreignId('payment_gateway_id')->nullable()->after('transaction_id')
                ->constrained('payment_gateways')->onDelete('set null');
            
            $table->index('status');
            $table->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['payment_gateway_id']);
            $table->dropColumn(['status', 'transaction_id', 'payment_gateway_id']);
        });
    }
};
