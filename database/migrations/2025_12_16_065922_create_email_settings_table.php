<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('email_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, integer, array
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insert default SMTP settings
        DB::table('email_settings')->insert([
            [
                'key' => 'mail_mailer',
                'value' => 'smtp',
                'type' => 'string',
                'description' => 'Email driver (smtp, sendmail, mailgun, ses, postmark, log, array, failover)',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'mail_host',
                'value' => 'smtp.mailtrap.io',
                'type' => 'string',
                'description' => 'SMTP server hostname',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'mail_port',
                'value' => '587',
                'type' => 'integer',
                'description' => 'SMTP server port (usually 587 for TLS, 465 for SSL)',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'mail_username',
                'value' => '',
                'type' => 'string',
                'description' => 'SMTP username/email',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'mail_password',
                'value' => '',
                'type' => 'string',
                'description' => 'SMTP password/app password',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'mail_encryption',
                'value' => 'tls',
                'type' => 'string',
                'description' => 'Email encryption (tls, ssl, null)',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'mail_from_address',
                'value' => 'noreply@radtik.local',
                'type' => 'string',
                'description' => 'Default sender email address',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'mail_from_name',
                'value' => 'RADTik System',
                'type' => 'string',
                'description' => 'Default sender name',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'notifications_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Enable/disable email notifications system-wide',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'test_email_address',
                'value' => '',
                'type' => 'string',
                'description' => 'Email address for testing SMTP configuration',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_settings');
    }
};
