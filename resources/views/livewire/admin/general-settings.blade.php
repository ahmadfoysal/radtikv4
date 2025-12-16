<div class="max-w-7xl mx-auto space-y-6">
    {{-- Page Header --}}
    <x-mary-card title="{{ auth()->user()->isSuperAdmin() ? 'Platform Settings' : 'General Settings' }}" separator
        class="bg-base-100">
        <p class="text-sm text-base-content/70 mb-6">
            @if (auth()->user()->isSuperAdmin())
                <span class="font-semibold text-primary">SuperAdmin Access:</span> Configure platform-wide settings and
                your company information.
            @else
                <span class="font-semibold text-primary">Admin Settings:</span> Configure your personal company
                information and preferences.
            @endif
        </p>
    </x-mary-card>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Main Settings --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Company Information --}}
            <x-mary-card title="Company Information" separator class="bg-base-100">
                <x-mary-form wire:submit="saveSettings">
                    <div class="grid grid-cols-1 gap-4">
                        {{-- Company Name --}}
                        <x-mary-input label="Company Name" wire:model.live="company_name" placeholder="My Company"
                            hint="Your company or organization name" icon="o-building-office-2" required />

                        {{-- Company Logo --}}
                        <div>
                            <label class="block text-sm font-medium mb-2">Company Logo</label>
                            @if ($current_logo)
                                <div class="mb-3">
                                    <img src="{{ Storage::url($current_logo) }}" alt="Current logo"
                                        class="h-16 w-auto rounded-lg border">
                                    <p class="text-xs text-base-content/60 mt-1">Current logo</p>
                                </div>
                            @endif
                            <x-mary-file wire:model="company_logo" label=""
                                hint="PNG, JPG or JPEG. Max file size: 2MB" accept="image/png, image/jpg, image/jpeg" />
                        </div>

                        {{-- Company Address --}}
                        <x-mary-textarea label="Company Address" wire:model.live="company_address"
                            placeholder="123 Business St, City, State 12345" hint="Your business address"
                            rows="3" />

                        {{-- Company Phone --}}
                        <x-mary-input label="Company Phone" wire:model.live="company_phone"
                            placeholder="+1 (555) 123-4567" hint="Your contact phone number" icon="o-phone" />

                        {{-- Company Email --}}
                        <x-mary-input label="Company Email" type="email" wire:model.live="company_email"
                            placeholder="contact@mycompany.com" hint="Your business email address" icon="o-envelope" />

                        {{-- Company Website --}}
                        <x-mary-input label="Company Website" type="url" wire:model.live="company_website"
                            placeholder="https://www.mycompany.com" hint="Your company website" icon="o-globe-alt" />
                    </div>
                </x-mary-form>
            </x-mary-card>

            {{-- Personal Preferences --}}
            <x-mary-card title="Personal Preferences" separator class="bg-base-100">
                <x-mary-form wire:submit="saveSettings">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Timezone --}}
                        <x-mary-select label="Timezone" wire:model.live="timezone" :options="$availableTimezones"
                            placeholder="Select timezone..." icon="o-globe-alt" hint="Your local timezone" required />

                        {{-- Date Format --}}
                        <x-mary-select label="Date Format" wire:model.live="date_format" :options="$availableDateFormats"
                            placeholder="Select format..." icon="o-calendar-days" required />

                        {{-- Time Format --}}
                        <x-mary-select label="Time Format" wire:model.live="time_format" :options="$availableTimeFormats"
                            placeholder="Select format..." icon="o-clock" required />

                        {{-- Items Per Page --}}
                        <x-mary-input label="Items per Page" type="number" wire:model.live="items_per_page"
                            placeholder="10" hint="Number of items shown per page" icon="o-bars-3" min="5"
                            max="100" required />
                    </div>
                </x-mary-form>
            </x-mary-card>

            {{-- Currency Settings --}}
            <x-mary-card title="Currency Preferences" separator class="bg-base-100">
                <x-mary-form wire:submit="saveSettings">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Currency --}}
                        <x-mary-select label="Preferred Currency" wire:model.live="currency" :options="$availableCurrencies"
                            placeholder="Select currency..." icon="o-currency-dollar" required />

                        {{-- Currency Symbol --}}
                        <x-mary-input label="Currency Symbol" wire:model.live="currency_symbol" placeholder="$"
                            hint="Auto-updates when currency changes" icon="o-banknotes" required />
                    </div>
                </x-mary-form>
            </x-mary-card>

            @if (auth()->user()->isSuperAdmin())
                {{-- Maintenance Mode (SuperAdmin Only) --}}
                <x-mary-card title="Platform Maintenance" separator class="bg-base-100">
                    <x-mary-form wire:submit="saveSettings">
                        <div class="space-y-4">
                            {{-- Maintenance Mode --}}
                            <x-mary-toggle label="Platform Maintenance Mode" wire:model.live="maintenance_mode"
                                hint="Enable maintenance mode for the entire platform" />

                            {{-- Maintenance Message --}}
                            <x-mary-textarea label="Maintenance Message" wire:model.live="maintenance_message"
                                placeholder="Platform is under maintenance. Please check back later."
                                hint="Message shown to all users during maintenance" rows="3" />
                        </div>
                    </x-mary-form>
                </x-mary-card>
            @endif

            {{-- Action Buttons --}}
            <x-mary-card class="bg-base-100">
                <div class="flex flex-wrap gap-3">
                    <x-mary-button label="Save Settings" class="btn-primary" wire:click="saveSettings"
                        spinner="saveSettings" icon="o-check" />

                    <x-mary-button label="Reset to Defaults" class="btn-outline" wire:click="resetToDefaults"
                        icon="o-arrow-path" />
                </div>
            </x-mary-card>
        </div>

        {{-- Info Sidebar --}}
        <div class="space-y-6">
            {{-- Current Settings Overview --}}
            <x-mary-card title="Current Settings" class="bg-base-100">
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-base-content/70">Company:</span>
                        <span class="font-medium">{{ $company_name ?: 'Not set' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-base-content/70">Timezone:</span>
                        <span class="font-medium">{{ $timezone ?: 'UTC' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-base-content/70">Currency:</span>
                        <span class="font-medium">{{ $currency ?: 'USD' }} ({{ $currency_symbol ?: '$' }})</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-base-content/70">Items/Page:</span>
                        <span class="font-medium">{{ $items_per_page ?: 10 }}</span>
                    </div>
                </div>
            </x-mary-card>

            @if (auth()->user()->isSuperAdmin())
                {{-- Platform Status --}}
                <x-mary-card title="Platform Status" class="bg-base-100">
                    <div class="space-y-3">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full {{ $maintenance_mode ? 'bg-error' : 'bg-success' }}">
                            </div>
                            <span class="font-medium {{ $maintenance_mode ? 'text-error' : 'text-success' }}">
                                {{ $maintenance_mode ? 'Maintenance Mode' : 'Operational' }}
                            </span>
                        </div>

                        @if ($maintenance_mode && $maintenance_message)
                            <div class="p-3 bg-error/10 rounded-lg">
                                <p class="text-sm text-error">{{ $maintenance_message }}</p>
                            </div>
                        @endif
                    </div>
                </x-mary-card>
            @endif

            {{-- Quick Tips --}}
            <x-mary-card title="Quick Tips" class="bg-base-100">
                <div class="space-y-3 text-sm text-base-content/70">
                    <p>👤 <strong>Personal Settings:</strong> These settings are specific to your account.</p>
                    <p>🏢 <strong>Company Info:</strong> Customize your business information and branding.</p>
                    <p>⚙️ <strong>Preferences:</strong> Set your timezone, date format, and currency.</p>
                    <p>📷 <strong>Logo Upload:</strong> Supports PNG, JPG, and JPEG files up to 2MB.</p>
                </div>
            </x-mary-card>

            {{-- Format Examples --}}
            <x-mary-card title="Format Examples" class="bg-base-100">
                <div class="space-y-2 text-xs text-base-content/60">
                    <p><strong>Date:</strong> {{ now()->format($date_format ?: 'Y-m-d') }}</p>
                    <p><strong>Time:</strong> {{ now()->format($time_format ?: 'H:i:s') }}</p>
                    <p><strong>Currency:</strong> {{ $currency_symbol ?: '$' }}1,234.56</p>
                </div>
            </x-mary-card>
        </div>
    </div>
</div>
