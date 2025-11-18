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
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('rate_limit')->nullable();
            $table->string('validity')->nullable();
            $table->boolean('mac_binding')->default(false);
            $table->decimal('price', 8, 2)->default(0);
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('description')->nullable();
            $table->unique(['name', 'user_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('user_profiles');
    }
};
