<div class="max-w-7xl mx-auto space-y-6">
    {{-- Page Header --}}
    <x-mary-card title="Email & SMTP Settings" separator class="bg-base-100">
        <p class="text-sm text-base-content/70 mb-6">
            <span class="font-semibold text-warning">Superadmin Access:</span> Configure email delivery settings for the
            entire system.
            All system emails including notifications, password resets, and invoices will use these settings.
        </p>
    </x-mary-card>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- SMTP Configuration --}}
        <div class="lg:col-span-2">
            <x-mary-card title="SMTP Configuration" separator class="bg-base-100">
                <x-mary-form wire:submit="saveSettings">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        {{-- Mail Driver --}}
                        <x-mary-select label="Mail Driver" wire:model.live="mail_mailer" :options="[
                            ['id' => 'smtp', 'name' => 'SMTP'],
                            ['id' => 'sendmail', 'name' => 'Sendmail'],
                            ['id' => 'mailgun', 'name' => 'Mailgun'],
                            ['id' => 'ses', 'name' => 'Amazon SES'],
                            ['id' => 'postmark', 'name' => 'Postmark'],
                            ['id' => 'log', 'name' => 'Log (Development)'],
                        ]"
                            option-value="id" option-label="name" hint="Email delivery method" icon="o-cog-6-tooth" />

                        {{-- SMTP Host --}}
                        <x-mary-input label="SMTP Host" wire:model.live="mail_host" placeholder="smtp.gmail.com"
                            hint="SMTP server hostname" icon="o-server" />

                        {{-- SMTP Port --}}
                        <x-mary-input label="SMTP Port" wire:model.live="mail_port" type="number" min="1"
                            max="65535" placeholder="587" hint="Usually 587 for TLS, 465 for SSL" icon="o-hashtag" />

                        {{-- Encryption --}}
                        <x-mary-select label="Encryption" wire:model.live="mail_encryption" :options="[
                            ['id' => 'tls', 'name' => 'TLS (Recommended)'],
                            ['id' => 'ssl', 'name' => 'SSL'],
                            ['id' => 'null', 'name' => 'None'],
                        ]"
                            option-value="id" option-label="name" hint="Email encryption method" icon="o-lock-closed" />

                        {{-- Username --}}
                        <x-mary-input label="SMTP Username" wire:model.live="mail_username"
                            placeholder="username@example.com" hint="SMTP authentication username" icon="o-user" />

                        {{-- Password --}}
                        <x-mary-input label="SMTP Password" wire:model.live="mail_password" type="password"
                            placeholder="••••••••" hint="SMTP authentication password" icon="o-key" />

                        {{-- From Address --}}
                        <x-mary-input label="From Email Address" wire:model.live="mail_from_address" type="email"
                            placeholder="noreply@yoursite.com" hint="Default sender email address" icon="o-envelope" />

                        {{-- From Name --}}
                        <x-mary-input label="From Name" wire:model.live="mail_from_name" placeholder="RADTik System"
                            hint="Default sender name" icon="o-user-circle" />
                    </div>

                    {{-- Additional Settings --}}
                    <div class="mt-6 pt-6 border-t border-base-300">
                        <h3 class="text-lg font-semibold mb-4">Additional Settings</h3>

                        <div class="flex items-center gap-4 mb-4">
                            <x-mary-toggle label="Enable Email Notifications" wire:model.live="notifications_enabled"
                                hint="Master switch for all email notifications" />
                        </div>

                        <x-mary-input label="Test Email Address" wire:model.live="test_email_address" type="email"
                            placeholder="test@example.com" hint="Email address for testing configuration"
                            icon="o-envelope" class="max-w-md" />
                    </div>

                    {{-- Form Actions --}}
                    <x-slot:actions>
                        <x-mary-button label="Reset to Defaults" wire:click="resetToDefaults" class="btn-outline"
                            icon="o-arrow-path" />

                        <x-mary-button label="Save Settings" class="btn-primary" type="submit" spinner="saveSettings"
                            icon="o-check" />
                    </x-slot:actions>
                </x-mary-form>
            </x-mary-card>
        </div>

        {{-- Test & Status Panel --}}
        <div class="space-y-6">
            {{-- Test Email --}}
            <x-mary-card title="Test Configuration" class="bg-base-100">
                <div class="space-y-4">
                    <p class="text-sm text-base-content/70">
                        Test your SMTP configuration by sending a test email.
                    </p>

                    <x-mary-button label="Send Test Email" wire:click="testEmailConnection"
                        class="btn-secondary btn-block" :spinner="$isTestingEmail" icon="o-paper-airplane" :disabled="empty($test_email_address)" />

                    @if (empty($test_email_address))
                        <p class="text-xs text-warning">
                            Please enter a test email address above.
                        </p>
                    @endif
                </div>
            </x-mary-card>

            {{-- Status Overview --}}
            <x-mary-card title="Current Status" class="bg-base-100">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm">Mail Driver:</span>
                        <x-mary-badge :value="strtoupper($mail_mailer)" class="badge-info" />
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm">SMTP Host:</span>
                        <span class="text-xs text-base-content/70">
                            {{ $mail_host ?: 'Not configured' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm">Port:</span>
                        <span class="text-xs text-base-content/70">{{ $mail_port }}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm">Encryption:</span>
                        <x-mary-badge :value="strtoupper($mail_encryption)" :class="$mail_encryption === 'tls' ? 'badge-success' : ($mail_encryption === 'ssl' ? 'badge-warning' : 'badge-error')" />
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm">Notifications:</span>
                        <x-mary-badge :value="$notifications_enabled ? 'Enabled' : 'Disabled'" :class="$notifications_enabled ? 'badge-success' : 'badge-error'" />
                    </div>
                </div>
            </x-mary-card>

            {{-- Common SMTP Providers --}}
            <x-mary-card title="Common Providers" class="bg-base-100">
                <div class="space-y-3 text-xs">
                    <div>
                        <p class="font-semibold text-primary">Gmail</p>
                        <p>Host: smtp.gmail.com</p>
                        <p>Port: 587 (TLS)</p>
                    </div>

                    <div>
                        <p class="font-semibold text-secondary">Outlook</p>
                        <p>Host: smtp-mail.outlook.com</p>
                        <p>Port: 587 (TLS)</p>
                    </div>

                    <div>
                        <p class="font-semibold text-accent">Mailtrap</p>
                        <p>Host: smtp.mailtrap.io</p>
                        <p>Port: 587 (TLS)</p>
                    </div>
                </div>
            </x-mary-card>
        </div>
    </div>
</div>
