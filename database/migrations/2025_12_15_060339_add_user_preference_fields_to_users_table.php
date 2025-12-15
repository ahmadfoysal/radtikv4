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
        Schema::table('users', function (Blueprint $table) {
            // User preference fields for the settings component
            $table->boolean('email_notifications')->default(true)->after('remember_token');
            $table->boolean('login_alerts')->default(false)->after('email_notifications');
            $table->string('preferred_language', 10)->default('en')->after('login_alerts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'email_notifications',
                'login_alerts',
                'preferred_language',
            ]);
        });
    }
};
