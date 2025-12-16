<?php

namespace App\Livewire\Admin;

use App\Models\GeneralSetting;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

class GeneralSettings extends Component
{
    use Toast, WithFileUploads;

    // Company Information
    #[Rule(['required', 'string', 'max:255'])]
    public string $company_name = '';

    #[Rule(['nullable', 'image', 'max:2048'])]
    public $company_logo;

    public string $current_logo = '';

    #[Rule(['nullable', 'string', 'max:500'])]
    public string $company_address = '';

    #[Rule(['nullable', 'string', 'max:50'])]
    public string $company_phone = '';

    #[Rule(['nullable', 'email', 'max:255'])]
    public string $company_email = '';

    #[Rule(['nullable', 'url', 'max:255'])]
    public string $company_website = '';

    // System Settings
    #[Rule(['required', 'string'])]
    public string $timezone = 'UTC';

    #[Rule(['required', 'string', 'max:50'])]
    public string $date_format = 'Y-m-d';

    #[Rule(['required', 'string', 'max:50'])]
    public string $time_format = 'H:i:s';

    #[Rule(['required', 'string', 'max:10'])]
    public string $currency = 'USD';

    #[Rule(['required', 'string', 'max:10'])]
    public string $currency_symbol = '$';

    #[Rule(['required', 'integer', 'min:5', 'max:100'])]
    public int $items_per_page = 10;

    // Maintenance Mode
    #[Rule(['boolean'])]
    public bool $maintenance_mode = false;

    #[Rule(['nullable', 'string', 'max:500'])]
    public string $maintenance_message = '';

    public array $availableTimezones = [];
    public array $availableDateFormats = [];
    public array $availableTimeFormats = [];
    public array $availableCurrencies = [];

    public function mount(): void
    {
        // Check if user is admin or superadmin
        $user = auth()->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->isAdmin()), 403);

        $this->loadSettings();
        $this->loadOptions();
    }

    public function render(): View
    {
        return view('livewire.admin.general-settings');
    }

    public function loadSettings(): void
    {
        $settings = GeneralSetting::where('is_active', true)->get()->keyBy('key');

        $this->company_name = $settings->get('company_name')?->value ?? 'RADTik v4';
        $this->current_logo = $settings->get('company_logo')?->value ?? '';
        $this->company_address = $settings->get('company_address')?->value ?? '';
        $this->company_phone = $settings->get('company_phone')?->value ?? '';
        $this->company_email = $settings->get('company_email')?->value ?? '';
        $this->company_website = $settings->get('company_website')?->value ?? '';

        $this->timezone = $settings->get('timezone')?->value ?? 'UTC';
        $this->date_format = $settings->get('date_format')?->value ?? 'Y-m-d';
        $this->time_format = $settings->get('time_format')?->value ?? 'H:i:s';
        $this->currency = $settings->get('currency')?->value ?? 'USD';
        $this->currency_symbol = $settings->get('currency_symbol')?->value ?? '$';
        $this->items_per_page = (int) ($settings->get('items_per_page')?->value ?? 10);

        $this->maintenance_mode = filter_var($settings->get('maintenance_mode')?->value ?? 'false', FILTER_VALIDATE_BOOLEAN);
        $this->maintenance_message = $settings->get('maintenance_message')?->value ?? 'System is under maintenance. Please check back later.';
    }

    public function loadOptions(): void
    {
        // Common timezones
        $this->availableTimezones = [
            ['id' => 'UTC', 'name' => 'UTC'],
            ['id' => 'America/New_York', 'name' => 'America/New York (EST)'],
            ['id' => 'America/Chicago', 'name' => 'America/Chicago (CST)'],
            ['id' => 'America/Los_Angeles', 'name' => 'America/Los Angeles (PST)'],
            ['id' => 'Europe/London', 'name' => 'Europe/London (GMT)'],
            ['id' => 'Europe/Paris', 'name' => 'Europe/Paris (CET)'],
            ['id' => 'Asia/Dubai', 'name' => 'Asia/Dubai (GST)'],
            ['id' => 'Asia/Dhaka', 'name' => 'Asia/Dhaka (BST)'],
            ['id' => 'Asia/Kolkata', 'name' => 'Asia/Kolkata (IST)'],
            ['id' => 'Asia/Singapore', 'name' => 'Asia/Singapore (SGT)'],
            ['id' => 'Asia/Tokyo', 'name' => 'Asia/Tokyo (JST)'],
            ['id' => 'Australia/Sydney', 'name' => 'Australia/Sydney (AEDT)'],
        ];

        $this->availableDateFormats = [
            ['id' => 'Y-m-d', 'name' => 'YYYY-MM-DD (2025-12-16)'],
            ['id' => 'd-m-Y', 'name' => 'DD-MM-YYYY (16-12-2025)'],
            ['id' => 'm/d/Y', 'name' => 'MM/DD/YYYY (12/16/2025)'],
            ['id' => 'd/m/Y', 'name' => 'DD/MM/YYYY (16/12/2025)'],
            ['id' => 'F j, Y', 'name' => 'Month Day, Year (December 16, 2025)'],
        ];

        $this->availableTimeFormats = [
            ['id' => 'H:i:s', 'name' => '24-hour (14:30:00)'],
            ['id' => 'H:i', 'name' => '24-hour (14:30)'],
            ['id' => 'h:i A', 'name' => '12-hour (02:30 PM)'],
            ['id' => 'h:i:s A', 'name' => '12-hour (02:30:00 PM)'],
        ];

        $this->availableCurrencies = [
            ['id' => 'USD', 'symbol' => '$', 'name' => 'US Dollar (USD)'],
            ['id' => 'EUR', 'symbol' => '€', 'name' => 'Euro (EUR)'],
            ['id' => 'GBP', 'symbol' => '£', 'name' => 'British Pound (GBP)'],
            ['id' => 'BDT', 'symbol' => '৳', 'name' => 'Bangladeshi Taka (BDT)'],
            ['id' => 'INR', 'symbol' => '₹', 'name' => 'Indian Rupee (INR)'],
            ['id' => 'AED', 'symbol' => 'د.إ', 'name' => 'UAE Dirham (AED)'],
            ['id' => 'SAR', 'symbol' => 'ر.س', 'name' => 'Saudi Riyal (SAR)'],
            ['id' => 'JPY', 'symbol' => '¥', 'name' => 'Japanese Yen (JPY)'],
            ['id' => 'CNY', 'symbol' => '¥', 'name' => 'Chinese Yuan (CNY)'],
            ['id' => 'AUD', 'symbol' => 'A$', 'name' => 'Australian Dollar (AUD)'],
        ];
    }

    public function updatedCurrency($value): void
    {
        // Auto-update currency symbol when currency changes
        $currency = collect($this->availableCurrencies)->firstWhere('id', $value);
        if ($currency) {
            $this->currency_symbol = $currency['symbol'];
        }
    }

    public function saveSettings(): void
    {
        $this->validate();

        try {
            // Handle logo upload
            $logoPath = $this->current_logo;
            if ($this->company_logo) {
                $logoPath = $this->company_logo->store('logos', 'public');
            }

            $settings = [
                'company_name' => $this->company_name,
                'company_logo' => $logoPath,
                'company_address' => $this->company_address,
                'company_phone' => $this->company_phone,
                'company_email' => $this->company_email,
                'company_website' => $this->company_website,
                'timezone' => $this->timezone,
                'date_format' => $this->date_format,
                'time_format' => $this->time_format,
                'currency' => $this->currency,
                'currency_symbol' => $this->currency_symbol,
                'items_per_page' => $this->items_per_page,
                'maintenance_mode' => $this->maintenance_mode,
                'maintenance_message' => $this->maintenance_message,
            ];

            foreach ($settings as $key => $value) {
                GeneralSetting::setValue($key, $value);
            }

            // Apply settings to Laravel config
            GeneralSetting::applyToConfig();

            // Reset file upload
            $this->company_logo = null;
            $this->loadSettings();

            $this->success('General settings saved successfully!');
        } catch (\Exception $e) {
            $this->error('Failed to save settings: ' . $e->getMessage());
        }
    }

    public function resetToDefaults(): void
    {
        $this->company_name = 'RADTik v4';
        $this->company_logo = null;
        $this->current_logo = '';
        $this->company_address = '';
        $this->company_phone = '';
        $this->company_email = '';
        $this->company_website = '';
        $this->timezone = 'UTC';
        $this->date_format = 'Y-m-d';
        $this->time_format = 'H:i:s';
        $this->currency = 'USD';
        $this->currency_symbol = '$';
        $this->items_per_page = 10;
        $this->maintenance_mode = false;
        $this->maintenance_message = 'System is under maintenance. Please check back later.';

        $this->info('Settings reset to defaults. Don\'t forget to save!');
    }
}
