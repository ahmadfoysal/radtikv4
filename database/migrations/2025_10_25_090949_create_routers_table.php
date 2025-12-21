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
        Schema::create('routers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address');
            $table->string('login_address')->nullable();
            $table->string('port');
            $table->string('ssh_port')->nullable();
            $table->string('username');
            $table->string('password');
            $table->string('note')->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained()->nullOnDelete();
            $table->string('app_key')->nullable();
            $table->decimal('monthly_isp_cost', 10, 2)->default(0)->comment('ISP/provider monthly cost');
            $table->string('logo')->nullable();
            $table->foreignId('voucher_template_id')->nullable()->constrained('voucher_templates')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('routers');
    }
};
