<?php

namespace Database\Seeders;

use App\Models\EmailSetting;
use Illuminate\Database\Seeder;

class EmailSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'mail_mailer',
                'value' => 'smtp',
                'type' => 'string',
                'description' => 'Email driver (smtp, sendmail, mailgun, ses, postmark, log, array, failover)',
                'is_active' => true,
            ],
            [
                'key' => 'mail_host',
                'value' => 'smtp.mailtrap.io',
                'type' => 'string',
                'description' => 'SMTP server hostname',
                'is_active' => true,
            ],
            [
                'key' => 'mail_port',
                'value' => '587',
                'type' => 'integer',
                'description' => 'SMTP server port (usually 587 for TLS, 465 for SSL)',
                'is_active' => true,
            ],
            [
                'key' => 'mail_username',
                'value' => '',
                'type' => 'string',
                'description' => 'SMTP username/email',
                'is_active' => true,
            ],
            [
                'key' => 'mail_password',
                'value' => '',
                'type' => 'string',
                'description' => 'SMTP password/app password',
                'is_active' => true,
            ],
            [
                'key' => 'mail_encryption',
                'value' => 'tls',
                'type' => 'string',
                'description' => 'Email encryption (tls, ssl, null)',
                'is_active' => true,
            ],
            [
                'key' => 'mail_from_address',
                'value' => 'noreply@radtik.local',
                'type' => 'string',
                'description' => 'Default sender email address',
                'is_active' => true,
            ],
            [
                'key' => 'mail_from_name',
                'value' => 'RADTik System',
                'type' => 'string',
                'description' => 'Default sender name',
                'is_active' => true,
            ],
            [
                'key' => 'notifications_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Enable/disable email notifications system-wide',
                'is_active' => true,
            ],
            [
                'key' => 'test_email_address',
                'value' => '',
                'type' => 'string',
                'description' => 'Email address for testing SMTP configuration',
                'is_active' => true,
            ],
        ];

        foreach ($settings as $setting) {
            EmailSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
