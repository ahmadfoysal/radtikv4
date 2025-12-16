<?php

namespace App\Livewire\Admin;

use App\Models\EmailSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Mary\Traits\Toast;

class EmailSettings extends Component
{
    use Toast;

    // SMTP Configuration
    #[Rule(['required', 'string', 'in:smtp,sendmail,mailgun,ses,postmark,log,array,failover'])]
    public string $mail_mailer = 'smtp';

    #[Rule(['required', 'string', 'max:255'])]
    public string $mail_host = '';

    #[Rule(['required', 'integer', 'min:1', 'max:65535'])]
    public int $mail_port = 587;

    #[Rule(['nullable', 'string', 'max:255'])]
    public string $mail_username = '';

    #[Rule(['nullable', 'string', 'max:255'])]
    public string $mail_password = '';

    #[Rule(['nullable', 'string', 'in:tls,ssl,null'])]
    public string $mail_encryption = 'tls';

    #[Rule(['required', 'email', 'max:255'])]
    public string $mail_from_address = '';

    #[Rule(['required', 'string', 'max:255'])]
    public string $mail_from_name = '';

    // Additional Settings
    #[Rule(['boolean'])]
    public bool $notifications_enabled = true;

    #[Rule(['nullable', 'email', 'max:255'])]
    public string $test_email_address = '';

    public bool $isTestingEmail = false;

    public function mount(): void
    {
        // Check if user is superadmin only
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $this->loadSettings();
    }

    public function render(): View
    {
        return view('livewire.admin.email-settings');
    }

    public function loadSettings(): void
    {
        $settings = EmailSetting::where('is_active', true)->get()->keyBy('key');

        $this->mail_mailer = $settings->get('mail_mailer')?->value ?? 'smtp';
        $this->mail_host = $settings->get('mail_host')?->value ?? '';
        $this->mail_port = (int) ($settings->get('mail_port')?->value ?? 587);
        $this->mail_username = $settings->get('mail_username')?->value ?? '';
        $this->mail_password = $settings->get('mail_password')?->value ?? '';
        $this->mail_encryption = $settings->get('mail_encryption')?->value ?? 'tls';
        $this->mail_from_address = $settings->get('mail_from_address')?->value ?? '';
        $this->mail_from_name = $settings->get('mail_from_name')?->value ?? '';
        $this->notifications_enabled = filter_var($settings->get('notifications_enabled')?->value ?? 'true', FILTER_VALIDATE_BOOLEAN);
        $this->test_email_address = $settings->get('test_email_address')?->value ?? '';
    }

    public function saveSettings(): void
    {
        $this->validate();

        try {
            $settings = [
                'mail_mailer' => $this->mail_mailer,
                'mail_host' => $this->mail_host,
                'mail_port' => $this->mail_port,
                'mail_username' => $this->mail_username,
                'mail_password' => $this->mail_password,
                'mail_encryption' => $this->mail_encryption,
                'mail_from_address' => $this->mail_from_address,
                'mail_from_name' => $this->mail_from_name,
                'notifications_enabled' => $this->notifications_enabled,
                'test_email_address' => $this->test_email_address,
            ];

            foreach ($settings as $key => $value) {
                EmailSetting::setValue($key, $value);
            }

            // Apply settings to Laravel config
            EmailSetting::applyToConfig();

            $this->success('Email settings saved successfully!');
        } catch (\Exception $e) {
            $this->error('Failed to save email settings: ' . $e->getMessage());
        }
    }

    public function testEmailConnection(): void
    {
        if (empty($this->test_email_address)) {
            $this->warning('Please enter a test email address first.');
            return;
        }

        if (!filter_var($this->test_email_address, FILTER_VALIDATE_EMAIL)) {
            $this->error('Please enter a valid email address.');
            return;
        }

        $this->isTestingEmail = true;

        try {
            // Temporarily apply current settings to config
            config([
                'mail.default' => $this->mail_mailer,
                'mail.mailers.smtp.host' => $this->mail_host,
                'mail.mailers.smtp.port' => $this->mail_port,
                'mail.mailers.smtp.username' => $this->mail_username,
                'mail.mailers.smtp.password' => $this->mail_password,
                'mail.mailers.smtp.encryption' => $this->mail_encryption ?: null,
                'mail.from.address' => $this->mail_from_address,
                'mail.from.name' => $this->mail_from_name,
            ]);

            // Send test email using raw method (simpler for testing)
            Mail::raw(
                "RADTik v4 - SMTP Configuration Test Email\n\n" .
                    "This is a test email sent from RADTik v4 Email Settings.\n\n" .
                    "If you received this email, your SMTP configuration is working correctly!\n\n" .
                    "Configuration Details:\n" .
                    "- Sent at: " . now()->format('Y-m-d H:i:s') . "\n" .
                    "- Mail Driver: " . $this->mail_mailer . "\n" .
                    "- SMTP Host: " . $this->mail_host . "\n" .
                    "- SMTP Port: " . $this->mail_port . "\n" .
                    "- Encryption: " . $this->mail_encryption . "\n\n" .
                    "This email was automatically generated by the Email Settings page in the Super Admin panel.\n\n" .
                    "Thank you for using RADTik v4!",
                function ($message) {
                    $message->to($this->test_email_address)
                        ->subject('RADTik v4 - SMTP Configuration Test');
                }
            );

            $this->success('Test email sent successfully to ' . $this->test_email_address);
        } catch (\Exception $e) {
            $this->error('Failed to send test email: ' . $e->getMessage());
        } finally {
            $this->isTestingEmail = false;
        }
    }

    public function resetToDefaults(): void
    {
        $this->mail_mailer = 'smtp';
        $this->mail_host = 'smtp.mailtrap.io';
        $this->mail_port = 587;
        $this->mail_username = '';
        $this->mail_password = '';
        $this->mail_encryption = 'tls';
        $this->mail_from_address = 'noreply@radtik.local';
        $this->mail_from_name = 'RADTik System';
        $this->notifications_enabled = true;
        $this->test_email_address = '';

        $this->info('Settings reset to defaults. Don\'t forget to save!');
    }
}
