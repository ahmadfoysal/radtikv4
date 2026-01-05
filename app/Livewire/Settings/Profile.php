<?php

namespace App\Livewire\Settings;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Rule as LivewireRule;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Mary\Traits\Toast;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class Profile extends Component
{
    use AuthorizesRequests, Toast, WithFileUploads;

    // Profile Information
    #[LivewireRule(['required', 'string', 'max:255'])]
    public string $name = '';

    #[LivewireRule(['required', 'email', 'max:255'])]
    public string $email = '';

    #[LivewireRule(['nullable', 'string', 'max:20'])]
    public ?string $phone = null;

    #[LivewireRule(['nullable', 'string', 'max:500'])]
    public ?string $address = null;

    #[LivewireRule(['nullable', 'string', 'max:100'])]
    public ?string $country = null;

    #[LivewireRule(['nullable', 'image', 'max:2048'])]
    public $profile_image;

    // Password Update
    #[LivewireRule(['nullable', 'string', 'min:8'])]
    public ?string $current_password = null;

    #[LivewireRule(['nullable', 'string', 'min:8'])]
    public ?string $new_password = null;

    #[LivewireRule(['nullable', 'string', 'min:8', 'same:new_password'])]
    public ?string $new_password_confirmation = null;

    // Two Factor Authentication
    public bool $two_factor_enabled = false;
    public bool $is_setting_up_2fa = false;
    public ?string $two_factor_qr = null;
    public ?string $two_factor_secret = null;

    #[LivewireRule(['nullable', 'string', 'size:6'])]
    public ?string $two_factor_code = null;

    #[LivewireRule(['nullable', 'array'])]
    public array $two_factor_recovery_codes = [];

    // Other Tyro-Login Options  
    public bool $email_notifications = true;
    public bool $login_alerts = false;
    public ?string $preferred_language = 'en';

    public function mount(): void
    {
        // Force fresh query from database to avoid caching issues
        $user = \App\Models\User::find(Auth::id());

        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone;
        $this->address = $user->address;
        $this->country = $user->country;

        // Check 2FA status - simplified approach
        $this->two_factor_enabled = !empty($user->two_factor_secret);

        // Debug: Log the values to see what's happening
        \Log::info('2FA Debug Mount', [
            'user_id' => $user->id,
            'secret_exists' => !empty($user->two_factor_secret),
            'secret_is_null' => is_null($user->two_factor_secret),
            'secret_value' => $user->two_factor_secret ? 'EXISTS' : 'NULL',
            'confirmed_at' => $user->two_factor_confirmed_at,
            'final_enabled' => $this->two_factor_enabled
        ]);

        // Load existing recovery codes if 2FA is enabled
        if ($this->two_factor_enabled && $user->two_factor_recovery_codes) {
            try {
                $this->two_factor_recovery_codes = decrypt($user->two_factor_recovery_codes);
            } catch (\Exception $e) {
                // If decryption fails, recovery codes might be stored in plain text (legacy)
                $this->two_factor_recovery_codes = [];
            }
        }

        // Load user preferences if they exist in the database
        $this->email_notifications = $user->email_notifications ?? true;
        $this->login_alerts = $user->login_alerts ?? false;
        $this->preferred_language = $user->preferred_language ?? 'en';
    }

    public function updateProfile(): void
    {
        // Prevent updates in demo mode
        if (env('DEMO_MODE', false)) {
            $this->warning(title: 'Demo Mode', description: 'Profile updates are disabled in demo mode.');
            return;
        }

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore(Auth::id())],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'country' => ['nullable', 'string', 'max:100'],
        ]);

        $user = Auth::user();
        $updateData = [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'country' => $this->country,
        ];

        // Handle profile image upload
        if ($this->profile_image) {
            $path = $this->profile_image->store('profile-images', 'public');
            $updateData['profile_image'] = $path;
        }

        $user->update($updateData);

        $this->profile_image = null;
        $this->success(title: 'Profile Updated', description: 'Your profile information has been updated successfully.');
    }

    public function updatePassword(): void
    {
        // Prevent password updates in demo mode
        if (env('DEMO_MODE', false)) {
            $this->warning(title: 'Demo Mode', description: 'Password updates are disabled in demo mode.');
            return;
        }

        $this->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8'],
            'new_password_confirmation' => ['required', 'string', 'same:new_password'],
        ]);

        $user = Auth::user();

        if (!Hash::check($this->current_password, $user->password)) {
            $this->addError('current_password', 'The current password is incorrect.');
            return;
        }

        $user->update([
            'password' => Hash::make($this->new_password),
        ]);

        $this->current_password = null;
        $this->new_password = null;
        $this->new_password_confirmation = null;

        $this->success(title: 'Password Updated', description: 'Your password has been changed successfully.');
    }

    public function enable2FA(): void
    {
        $user = Auth::user();

        // Check if 2FA is already enabled
        if (!empty($user->two_factor_secret)) {
            $this->error(title: 'Already Enabled', description: 'Two-factor authentication is already enabled for your account.');
            return;
        }

        $google2fa = new Google2FA();
        $secretKey = $google2fa->generateSecretKey();

        $this->two_factor_secret = $secretKey;

        // Generate QR Code SVG
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secretKey
        );

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $this->two_factor_qr = $writer->writeString($qrCodeUrl);

        $this->is_setting_up_2fa = true;
    }

    public function verify2FA(): void
    {
        $this->validate([
            'two_factor_code' => ['required', 'string', 'size:6'],
        ]);

        $google2fa = new Google2FA();

        $valid = $google2fa->verifyKey($this->two_factor_secret, $this->two_factor_code);

        if (!$valid) {
            $this->addError('two_factor_code', 'The provided two factor authentication code is invalid.');
            return;
        }

        // Generate recovery codes
        $recoveryCodes = collect(range(1, 8))->map(function () {
            return \Illuminate\Support\Str::random(10) . '-' . \Illuminate\Support\Str::random(10);
        });

        $user = Auth::user();
        $user->update([
            'two_factor_secret' => $this->two_factor_secret,
            'two_factor_recovery_codes' => $recoveryCodes->toArray(),
            'two_factor_confirmed_at' => now(),
        ]);

        // Reset setup state
        $this->is_setting_up_2fa = false;
        $this->two_factor_code = null;
        $this->two_factor_secret = null;
        $this->two_factor_qr = null;

        // Refresh component state from database
        $this->refreshTwoFactorState();

        $this->success(title: '2FA Enabled', description: 'Two-factor authentication has been enabled successfully. Please save your recovery codes in a secure location.');
    }

    public function disable2FA(): void
    {
        $user = Auth::user();
        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        // Reset component state
        $this->two_factor_secret = null;
        $this->two_factor_recovery_codes = [];
        $this->is_setting_up_2fa = false;

        // Refresh component state from database
        $this->refreshTwoFactorState();

        $this->success(title: '2FA Disabled', description: 'Two-factor authentication has been disabled for your account.');
    }

    public function regenerateRecoveryCodes(): void
    {
        $user = Auth::user();

        if (empty($user->two_factor_secret)) {
            $this->error(title: 'Error', description: 'Two-factor authentication is not enabled for your account.');
            return;
        }

        // Generate new recovery codes
        $recoveryCodes = collect(range(1, 8))->map(function () {
            return \Illuminate\Support\Str::random(10) . '-' . \Illuminate\Support\Str::random(10);
        });

        $user->update([
            'two_factor_recovery_codes' => $recoveryCodes->toArray(),
        ]);

        $this->two_factor_recovery_codes = $recoveryCodes->toArray();

        $this->success(title: 'Recovery Codes Regenerated', description: 'New recovery codes have been generated. Please save them in a secure location.');
    }

    private function refreshTwoFactorState(): void
    {
        // Force fresh query to get latest 2FA status
        $user = \App\Models\User::find(Auth::id());
        $this->two_factor_enabled = $user->hasEnabledTwoFactorAuthentication();

        // Load recovery codes if 2FA is enabled
        if ($this->two_factor_enabled) {
            $this->two_factor_recovery_codes = $user->recoveryCodes();
        }
    }

    public function cancelSetup2FA(): void
    {
        $this->is_setting_up_2fa = false;
        $this->two_factor_secret = null;
        $this->two_factor_qr = null;
        $this->two_factor_code = null;
    }

    public function updatePreferences(): void
    {
        // Prevent preference updates in demo mode
        if (env('DEMO_MODE', false)) {
            $this->warning(title: 'Demo Mode', description: 'Preference updates are disabled in demo mode.');
            return;
        }

        $user = Auth::user();

        // Note: These fields would need to be added to the users table migration
        // This is a placeholder for the tyro-login preferences
        $user->update([
            'email_notifications' => $this->email_notifications,
            'login_alerts' => $this->login_alerts,
            'preferred_language' => $this->preferred_language,
        ]);

        $this->success(title: 'Preferences Updated', description: 'Your account preferences have been saved.');
    }

    public function render()
    {
        return view('livewire.settings.profile')
            ->layout('components.layouts.app');
    }
}
