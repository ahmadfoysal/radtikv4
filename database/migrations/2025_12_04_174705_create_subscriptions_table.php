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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('package_id')->constrained()->onDelete('restrict');
            
            // Subscription Period
            $table->date('start_date');
            $table->date('end_date');
            $table->string('billing_cycle')->comment('monthly or yearly');
            $table->integer('cycle_count')->default(1)->comment('Number of cycles completed');
            
            // Pricing (snapshot at subscription time)
            $table->decimal('amount', 12, 2)->comment('Amount charged per cycle');
            $table->decimal('original_price', 12, 2)->comment('Package price at subscription time');
            $table->integer('discount_percent')->default(0);
            
            // Status Management
            $table->enum('status', [
                'active',           // Currently active
                'grace_period',     // Expired but within grace period
                'suspended',        // Payment failed, service suspended
                'cancelled',        // User cancelled, active until end_date
                'expired',          // Ended and not renewed
            ])->default('active');
            
            // Payment Tracking
            $table->date('last_payment_date')->nullable();
            $table->date('next_billing_date')->nullable();
            $table->foreignId('last_invoice_id')->nullable()->constrained('invoices')->onDelete('set null');
            
            // Auto-Renewal
            $table->boolean('auto_renew')->default(true);
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            
            // Promotional
            $table->string('promo_code')->nullable();
            
            // Audit
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['user_id', 'status']);
            $table->index('end_date');
            $table->index('next_billing_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
