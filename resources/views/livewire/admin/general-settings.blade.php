<div class="max-w-7xl mx-auto space-y-6">
    {{-- Page Header --}}
    <x-mary-card title="General Settings" separator class="bg-base-100">
        <p class="text-sm text-base-content/70 mb-6">
            <span class="font-semibold text-primary">Admin Access:</span> Configure general system settings including
            company information, system preferences, and maintenance mode.
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
                        <x-mary-input label="Company Name" wire:model.live="company_name"
                            placeholder="RADTik v4" hint="Your company or organization name" icon="o-building-office-2"
                            required />

                        {{-- Company Logo --}}
                        <div>
                            <label class="block text-sm font-medium mb-2">Company Logo</label>
                            @if ($current_logo)
                                <div class="mb-3 flex items-center gap-4">
                                    <img src="{{ asset('storage/' . $current_logo) }}" alt="Company Logo"
                                        class="h-16 w-auto rounded border border-base-300">
                                    <span class="text-xs text-base-content/70">Current logo</span>
                                </div>
                            @endif
                            <x-mary-file wire:model="company_logo" accept="image/png, image/jpeg, image/jpg"
                                hint="Upload a new logo (PNG, JPG - Max 2MB)" />
                            @if ($company_logo)
                                <div class="mt-2">
                                    <img src="{{ $company_logo->temporaryUrl() }}" alt="Preview"
                                        class="h-16 w-auto rounded border border-primary">
                                    <span class="text-xs text-success">New logo preview</span>
                                </div>
                            @endif
                        </div>

                        {{-- Company Address --}}
                        <x-mary-textarea label="Company Address" wire:model.live="company_address"
                            placeholder="123 Main Street, City, Country" hint="Physical address of your company"
                            rows="3" />

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            {{-- Company Phone --}}
                            <x-mary-input label="Company Phone" wire:model.live="company_phone"
                                placeholder="+1 234 567 8900" hint="Contact phone number" icon="o-phone" />

                            {{-- Company Email --}}
                            <x-mary-input label="Company Email" wire:model.live="company_email" type="email"
                                placeholder="contact@company.com" hint="Contact email address" icon="o-envelope" />
                        </div>

                        {{-- Company Website --}}
                        <x-mary-input label="Company Website" wire:model.live="company_website" type="url"
                            placeholder="https://www.company.com" hint="Company website URL" icon="o-globe-alt" />
                    </div>
                </x-mary-form>
            </x-mary-card>

            {{-- System Preferences --}}
            <x-mary-card title="System Preferences" separator class="bg-base-100">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    {{-- Timezone --}}
                    <x-mary-select label="Timezone" wire:model.live="timezone" :options="$availableTimezones"
                        option-value="id" option-label="name" hint="Default system timezone" icon="o-clock" />

                    {{-- Items Per Page --}}
                    <x-mary-input label="Items Per Page" wire:model.live="items_per_page" type="number" min="5"
                        max="100" hint="Default pagination size" icon="o-list-bullet" />

                    {{-- Date Format --}}
                    <x-mary-select label="Date Format" wire:model.live="date_format" :options="$availableDateFormats"
                        option-value="id" option-label="name" hint="System date format" icon="o-calendar" />

                    {{-- Time Format --}}
                    <x-mary-select label="Time Format" wire:model.live="time_format" :options="$availableTimeFormats"
                        option-value="id" option-label="name" hint="System time format" icon="o-clock" />

                    {{-- Currency --}}
                    <x-mary-select label="Currency" wire:model.live="currency" :options="$availableCurrencies"
                        option-value="id" option-label="name" hint="Default currency" icon="o-currency-dollar" />

                    {{-- Currency Symbol --}}
                    <x-mary-input label="Currency Symbol" wire:model.live="currency_symbol" placeholder="$"
                        hint="Currency symbol" icon="o-banknotes" />
                </div>
            </x-mary-card>

            {{-- Maintenance Mode --}}
            <x-mary-card title="Maintenance Mode" separator class="bg-base-100">
                <div class="space-y-4">
                    <div class="flex items-center gap-4">
                        <x-mary-toggle label="Enable Maintenance Mode" wire:model.live="maintenance_mode"
                            hint="When enabled, only admins can access the system" />
                    </div>

                    @if ($maintenance_mode)
                        <x-mary-alert icon="o-exclamation-triangle" class="alert-warning">
                            <span class="font-semibold">Warning:</span> Maintenance mode is enabled. Regular users will
                            not be able to access the system.
                        </x-mary-alert>
                    @endif

                    <x-mary-textarea label="Maintenance Message" wire:model.live="maintenance_message"
                        placeholder="System is under maintenance. Please check back later."
                        hint="Message displayed to users during maintenance" rows="3" />
                </div>
            </x-mary-card>

            {{-- Form Actions --}}
            <x-mary-card class="bg-base-100">
                <div class="flex gap-3 justify-end">
                    <x-mary-button label="Reset to Defaults" wire:click="resetToDefaults" class="btn-outline"
                        icon="o-arrow-path" />

                    <x-mary-button label="Save Settings" wire:click="saveSettings" class="btn-primary"
                        spinner="saveSettings" icon="o-check" />
                </div>
            </x-mary-card>
        </div>

        {{-- Info Panel --}}
        <div class="space-y-6">
            {{-- Current Settings Overview --}}
            <x-mary-card title="Current Settings" class="bg-base-100">
                <div class="space-y-3">
                    <div class="flex items-start justify-between">
                        <span class="text-sm font-medium">Company:</span>
                        <span class="text-xs text-base-content/70 text-right">
                            {{ $company_name ?: 'Not set' }}
                        </span>
                    </div>

                    <div class="divider my-2"></div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm">Timezone:</span>
                        <x-mary-badge :value="$timezone" class="badge-info badge-sm" />
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm">Currency:</span>
                        <x-mary-badge :value="$currency . ' (' . $currency_symbol . ')'" class="badge-success badge-sm" />
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm">Date Format:</span>
                        <span class="text-xs font-mono text-base-content/70">{{ $date_format }}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm">Time Format:</span>
                        <span class="text-xs font-mono text-base-content/70">{{ $time_format }}</span>
                    </div>

                    <div class="divider my-2"></div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm">Maintenance:</span>
                        <x-mary-badge :value="$maintenance_mode ? 'Active' : 'Disabled'" :class="$maintenance_mode ? 'badge-error' : 'badge-success'" />
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm">Items/Page:</span>
                        <span class="text-xs text-base-content/70">{{ $items_per_page }}</span>
                    </div>
                </div>
            </x-mary-card>

            {{-- Quick Tips --}}
            <x-mary-card title="Quick Tips" class="bg-base-100">
                <div class="space-y-3 text-xs text-base-content/70">
                    <div class="flex gap-2">
                        <span class="text-primary">•</span>
                        <span>Company information will be displayed on invoices and emails.</span>
                    </div>

                    <div class="flex gap-2">
                        <span class="text-primary">•</span>
                        <span>Logo should be in PNG or JPG format with transparent background recommended.</span>
                    </div>

                    <div class="flex gap-2">
                        <span class="text-primary">•</span>
                        <span>Date and time formats affect how dates are displayed throughout the system.</span>
                    </div>

                    <div class="flex gap-2">
                        <span class="text-warning">•</span>
                        <span>Use maintenance mode when performing system updates or database migrations.</span>
                    </div>

                    <div class="flex gap-2">
                        <span class="text-error">•</span>
                        <span>Always save settings before navigating away from this page.</span>
                    </div>
                </div>
            </x-mary-card>

            {{-- Example Formats --}}
            <x-mary-card title="Format Examples" class="bg-base-100">
                <div class="space-y-3 text-xs">
                    <div>
                        <p class="font-semibold text-primary mb-1">Current Date/Time:</p>
                        <p class="font-mono">{{ now()->format($date_format) }}</p>
                        <p class="font-mono">{{ now()->format($time_format) }}</p>
                    </div>

                    <div class="divider my-2"></div>

                    <div>
                        <p class="font-semibold text-secondary mb-1">Currency Example:</p>
                        <p>{{ $currency_symbol }}100.00 {{ $currency }}</p>
                    </div>
                </div>
            </x-mary-card>
        </div>
    </div>
</div>
