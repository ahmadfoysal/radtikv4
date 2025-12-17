<?php

namespace App\Livewire\Admin;

use App\Models\GeneralSetting;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class GeneralSettings extends Component
{
    use AuthorizesRequests, Toast, WithFileUploads;

    // Company Information (User-specific)
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

    // User Preferences
    #[Rule(['required', 'string', 'max:50'])]
    public string $timezone = '';

    #[Rule(['required', 'string', 'max:20'])]
    public string $date_format = '';

    #[Rule(['required', 'string', 'max:20'])]
    public string $time_format = '';

    #[Rule(['required', 'string', 'max:10'])]
    public string $currency = '';

    #[Rule(['required', 'string', 'max:5'])]
    public string $currency_symbol = '';

    #[Rule(['required', 'integer', 'min:5', 'max:100'])]
    public int $items_per_page = 10;

    // Maintenance mode (superadmin only)
    #[Rule(['boolean'])]
    public bool $maintenance_mode = false;

    #[Rule(['nullable', 'string', 'max:500'])]
    public string $maintenance_message = '';

    public array $availableTimezones = [];
    public array $availableCurrencies = [];
    public array $availableDateFormats = [];
    public array $availableTimeFormats = [];

    public function mount(): void
    {
        // Check if user is admin, superadmin, or reseller
        $user = auth()->user();
        abort_unless($user && ($user->isAdmin() || $user->isSuperAdmin() || $user->isReseller()), 403);

        $this->loadSettings();
        $this->loadOptions();
    }

    public function render(): View
    {
        return view('livewire.admin.general-settings');
    }

    public function loadSettings(): void
    {
        $userId = auth()->id();

        // Load user-specific settings with fallback to global defaults
        $this->company_name = GeneralSetting::getValue('company_name', auth()->user()->name ?? 'My Company', $userId);
        $this->current_logo = GeneralSetting::getValue('company_logo', '', $userId);
        $this->company_address = GeneralSetting::getValue('company_address', '', $userId);
        $this->company_phone = GeneralSetting::getValue('company_phone', '', $userId);
        $this->company_email = GeneralSetting::getValue('company_email', '', $userId);
        $this->company_website = GeneralSetting::getValue('company_website', '', $userId);

        // System preferences with global defaults
        $this->timezone = GeneralSetting::getValue('timezone', GeneralSetting::getValue('default_timezone', 'UTC', null), $userId);
        $this->date_format = GeneralSetting::getValue('date_format', GeneralSetting::getValue('default_date_format', 'Y-m-d', null), $userId);
        $this->time_format = GeneralSetting::getValue('time_format', GeneralSetting::getValue('default_time_format', 'H:i:s', null), $userId);
        $this->currency = GeneralSetting::getValue('currency', GeneralSetting::getValue('default_currency', 'USD', null), $userId);
        $this->currency_symbol = GeneralSetting::getValue('currency_symbol', GeneralSetting::getValue('default_currency_symbol', '$', null), $userId);
        $this->items_per_page = (int) GeneralSetting::getValue('items_per_page', GeneralSetting::getValue('default_items_per_page', '10', null), $userId);

        // Load maintenance mode for superadmin only
        if (auth()->user()->isSuperAdmin()) {
            $this->maintenance_mode = (bool) GeneralSetting::getValue('maintenance_mode', false, $userId);
            $this->maintenance_message = GeneralSetting::getValue('maintenance_message', 'System is under maintenance. Please check back later.', $userId);
        }
    }

    public function loadOptions(): void
    {
        $this->availableTimezones = [
            ['id' => 'UTC', 'name' => 'UTC'],
            ['id' => 'America/New_York', 'name' => 'Eastern Time (ET)'],
            ['id' => 'America/Chicago', 'name' => 'Central Time (CT)'],
            ['id' => 'America/Denver', 'name' => 'Mountain Time (MT)'],
            ['id' => 'America/Los_Angeles', 'name' => 'Pacific Time (PT)'],
            ['id' => 'Europe/London', 'name' => 'London (GMT)'],
            ['id' => 'Europe/Berlin', 'name' => 'Berlin (CET)'],
            ['id' => 'Asia/Tokyo', 'name' => 'Tokyo (JST)'],
            ['id' => 'Asia/Shanghai', 'name' => 'Shanghai (CST)'],
            ['id' => 'Asia/Dhaka', 'name' => 'Dhaka (BST)'],
            ['id' => 'Asia/Kolkata', 'name' => 'Mumbai (IST)'],
        ];

        $this->availableCurrencies = [
            ['id' => 'USD', 'name' => 'US Dollar ($)'],
            ['id' => 'EUR', 'name' => 'Euro (€)'],
            ['id' => 'GBP', 'name' => 'British Pound (£)'],
            ['id' => 'BDT', 'name' => 'Bangladeshi Taka (৳)'],
            ['id' => 'JPY', 'name' => 'Japanese Yen (¥)'],
            ['id' => 'INR', 'name' => 'Indian Rupee (₹)'],
        ];

        $this->availableDateFormats = [
            ['id' => 'Y-m-d', 'name' => 'YYYY-MM-DD (2024-12-16)'],
            ['id' => 'd/m/Y', 'name' => 'DD/MM/YYYY (16/12/2024)'],
            ['id' => 'm/d/Y', 'name' => 'MM/DD/YYYY (12/16/2024)'],
            ['id' => 'd-M-Y', 'name' => 'DD-MMM-YYYY (16-Dec-2024)'],
            ['id' => 'F j, Y', 'name' => 'Month DD, YYYY (December 16, 2024)'],
        ];

        $this->availableTimeFormats = [
            ['id' => 'H:i:s', 'name' => '24-hour format (14:30:45)'],
            ['id' => 'H:i', 'name' => '24-hour format without seconds (14:30)'],
            ['id' => 'h:i:s A', 'name' => '12-hour format (02:30:45 PM)'],
            ['id' => 'h:i A', 'name' => '12-hour format without seconds (02:30 PM)'],
        ];
    }

    public function saveSettings(): void
    {
        $this->validate();

        try {
            $user = auth()->user();

            // SuperAdmin saves as global (null), Admin/Reseller saves as user-specific
            $userId = $user->isSuperAdmin() ? null : $user->id;

            // Handle file upload for company logo
            if ($this->company_logo) {
                $logoPath = $this->company_logo->store('logos', 'public');
                GeneralSetting::setValue('company_logo', $logoPath, 'string', $userId);
                $this->current_logo = $logoPath;
            }

            // Save settings (global for superadmin, user-specific for admin/reseller)
            GeneralSetting::setValue('company_name', $this->company_name, 'string', $userId);
            GeneralSetting::setValue('company_address', $this->company_address, 'string', $userId);
            GeneralSetting::setValue('company_phone', $this->company_phone, 'string', $userId);
            GeneralSetting::setValue('company_email', $this->company_email, 'string', $userId);
            GeneralSetting::setValue('company_website', $this->company_website, 'string', $userId);
            GeneralSetting::setValue('timezone', $this->timezone, 'string', $userId);
            GeneralSetting::setValue('date_format', $this->date_format, 'string', $userId);
            GeneralSetting::setValue('time_format', $this->time_format, 'string', $userId);
            GeneralSetting::setValue('currency', $this->currency, 'string', $userId);
            GeneralSetting::setValue('currency_symbol', $this->currency_symbol, 'string', $userId);
            GeneralSetting::setValue('items_per_page', $this->items_per_page, 'integer', $userId);

            // Save maintenance mode for superadmin only (always global)
            if ($user->isSuperAdmin()) {
                GeneralSetting::setValue('maintenance_mode', $this->maintenance_mode, 'boolean', null);
                GeneralSetting::setValue('maintenance_message', $this->maintenance_message, 'string', null);
            }

            $this->success('Settings saved successfully!', position: 'toast-top');
            $this->reset(['company_logo']); // Clear file input

        } catch (\Exception $e) {
            $this->error('Failed to save settings: ' . $e->getMessage());
        }
    }

    public function resetToDefaults(): void
    {
        $this->company_name = auth()->user()->name ?? 'My Company';
        $this->company_address = '';
        $this->company_phone = '';
        $this->company_email = '';
        $this->company_website = '';

        // Use global defaults
        $this->timezone = GeneralSetting::getValue('default_timezone', 'UTC', null);
        $this->date_format = GeneralSetting::getValue('default_date_format', 'Y-m-d', null);
        $this->time_format = GeneralSetting::getValue('default_time_format', 'H:i:s', null);
        $this->currency = GeneralSetting::getValue('default_currency', 'USD', null);
        $this->currency_symbol = GeneralSetting::getValue('default_currency_symbol', '$', null);
        $this->items_per_page = (int) GeneralSetting::getValue('default_items_per_page', '10', null);

        // Reset maintenance mode for superadmin only
        if (auth()->user()->isSuperAdmin()) {
            $this->maintenance_mode = false;
            $this->maintenance_message = 'System is under maintenance. Please check back later.';
        }

        $this->info('Settings reset to defaults. Click Save to apply changes.');
    }

    public function updatedCurrency(): void
    {
        // Auto-update currency symbol when currency changes
        $currencySymbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'BDT' => '৳',
            'JPY' => '¥',
            'INR' => '₹',
        ];

        $this->currency_symbol = $currencySymbols[$this->currency] ?? '$';
    }
}
