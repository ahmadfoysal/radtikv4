<div class="max-w-6xl mx-auto space-y-6">
    {{-- Profile Information Section --}}
    <x-mary-card title="Profile Information" separator class="bg-base-100">
        <x-mary-form wire:submit="updateProfile">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-mary-input label="Full Name" wire:model.live.debounce.400ms="name" placeholder="Enter your name"
                        required />
                </div>

                <div>
                    <x-mary-input label="Email Address" type="email" wire:model.live.debounce.400ms="email"
                        placeholder="Enter your email" required />
                </div>

                <div>
                    <x-mary-input label="Phone Number" wire:model.live.debounce.400ms="phone"
                        placeholder="Enter your phone number" />
                </div>

                <div>
                    <x-mary-input label="Country" wire:model.live.debounce.400ms="country"
                        placeholder="Enter your country" />
                </div>

                <div class="sm:col-span-2">
                    <x-mary-textarea label="Address" wire:model.live.debounce.400ms="address"
                        placeholder="Enter your address" rows="2" />
                </div>

                <div class="sm:col-span-2">
                    <x-mary-file label="Profile Image" wire:model="profile_image" accept="image/*">
                        <img src="{{ Auth::user()->profile_image ? '/storage/' . Auth::user()->profile_image : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name) . '&background=0ea5e9&color=fff' }}"
                            class="h-24 w-24 rounded-full object-cover" alt="Profile">
                    </x-mary-file>
                    @if ($profile_image)
                        <p class="mt-1 text-sm text-gray-600">New image selected:
                            {{ $profile_image->getClientOriginalName() }}</p>
                    @endif
                </div>
            </div>

            <x-slot:actions>
                <x-mary-button label="Update Profile" class="btn-primary" type="submit" spinner="updateProfile" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-card>

    {{-- Password Update Section --}}
    <x-mary-card title="Change Password" separator class="bg-base-100">
        <x-mary-form wire:submit="updatePassword">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-mary-input label="Current Password" type="password" wire:model="current_password"
                        placeholder="Enter your current password" />
                </div>

                <div>
                    <x-mary-input label="New Password" type="password" wire:model="new_password"
                        placeholder="Enter new password" />
                    <p class="mt-1 text-xs opacity-70">
                        Minimum 8 characters required.
                    </p>
                </div>

                <div>
                    <x-mary-input label="Confirm New Password" type="password" wire:model="new_password_confirmation"
                        placeholder="Confirm new password" />
                </div>
            </div>

            <x-slot:actions>
                <x-mary-button label="Update Password" class="btn-primary" type="submit" spinner="updatePassword" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-card>

    {{-- Two Factor Authentication Section --}}
    <x-mary-card title="Two-Factor Authentication" separator class="bg-base-100">
        @if (!$two_factor_enabled && !$is_setting_up_2fa)
            {{-- Enable 2FA --}}
            <div class="space-y-4">
                <div class="bg-info/10 border border-info/20 rounded-lg p-4">
                    <h4 class="font-semibold text-info mb-2">🔐 Enhanced Security</h4>
                    <p class="text-sm opacity-80 mb-3">
                        Add an extra layer of security to your account using two-factor authentication.
                        You'll need an authenticator app like Google Authenticator, Authy, or Microsoft Authenticator.
                    </p>
                    <ul class="text-xs opacity-70 space-y-1 mb-3">
                        <li>• Protects against password theft</li>
                        <li>• Works even when offline</li>
                        <li>• Industry-standard TOTP security</li>
                    </ul>
                </div>

                <x-mary-button label="Enable Two-Factor Authentication" class="btn-primary" wire:click="enable2FA"
                    spinner="enable2FA" />
            </div>
        @elseif($is_setting_up_2fa)
            {{-- Setup Process --}}
            <div class="space-y-4">
                <div class="text-center">
                    <h3 class="font-semibold mb-2">Setup Two-Factor Authentication</h3>
                    <p class="text-sm opacity-80 mb-4">Scan the QR code with your authenticator app like Google
                        Authenticator or Authy.</p>

                    {{-- QR Code --}}
                    <div class="mb-4">
                        <div class="inline-block p-4 bg-white rounded-lg">
                            {!! $two_factor_qr !!}
                        </div>
                    </div>

                    {{-- Manual Entry --}}
                    <div class="text-xs opacity-60 mb-4">
                        <p>Or enter this code manually: <code
                                class="bg-base-200 px-2 py-1 rounded">{{ $two_factor_secret }}</code></p>
                    </div>
                </div>

                <x-mary-form wire:submit="verify2FA">
                    <div class="max-w-xs mx-auto">
                        <x-mary-input label="6-Digit Verification Code" wire:model="two_factor_code"
                            placeholder="000000" maxlength="6" class="text-center text-2xl font-mono"
                            hint="Enter the 6-digit code from your authenticator app" />
                    </div>

                    <div class="flex justify-center space-x-2 mt-4">
                        <x-mary-button label="Cancel" class="btn-ghost" wire:click="cancelSetup2FA" />
                        <x-mary-button label="Verify & Enable" class="btn-primary" type="submit" spinner="verify2FA" />
                    </div>
                </x-mary-form>
            </div>
        @else
            {{-- 2FA Enabled --}}
            <div class="space-y-4">
                <div class="bg-success/10 border border-success/20 rounded-lg p-4">
                    <div class="flex items-center space-x-3 mb-2">
                        <div class="w-3 h-3 bg-success rounded-full"></div>
                        <h4 class="font-semibold text-success">Two-Factor Authentication Active</h4>
                    </div>
                    <p class="text-sm opacity-80">Your account is protected with two-factor authentication. You'll need
                        to enter a code from your authenticator app when signing in.</p>
                </div>

                @if (!empty($two_factor_recovery_codes))
                    <div class="bg-warning/10 border border-warning/20 rounded-lg p-4">
                        <h4 class="font-semibold text-warning mb-2">Recovery Codes</h4>
                        <p class="text-xs opacity-80 mb-2">Store these recovery codes in a secure location. They can be
                            used to access your account if you lose your authenticator device.</p>
                        <div class="grid grid-cols-2 gap-1 text-xs font-mono">
                            @foreach ($two_factor_recovery_codes as $code)
                                <span class="bg-base-200 px-2 py-1 rounded">{{ $code }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="flex flex-wrap gap-3">
                    <x-mary-button label="Regenerate Recovery Codes" class="btn-outline btn-warning btn-sm"
                        wire:click="regenerateRecoveryCodes"
                        wire:confirm="Are you sure? This will invalidate your existing recovery codes."
                        spinner="regenerateRecoveryCodes" />
                    <x-mary-button label="Disable 2FA" class="btn-error btn-sm" wire:click="disable2FA"
                        wire:confirm="Are you sure you want to disable two-factor authentication? This will make your account less secure."
                        spinner="disable2FA" />
                </div>
            </div>
        @endif
    </x-mary-card>

    {{-- Account Preferences Section --}}
    <x-mary-card title="Account Preferences" separator class="bg-base-100">
        <x-mary-form wire:submit="updatePreferences">
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <label class="label-text font-medium">Email Notifications</label>
                        <p class="text-xs opacity-70">Receive email notifications about account activity</p>
                    </div>
                    <x-mary-toggle wire:model.live="email_notifications" />
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        <label class="label-text font-medium">Login Alerts</label>
                        <p class="text-xs opacity-70">Get notified when someone logs into your account</p>
                    </div>
                    <x-mary-toggle wire:model.live="login_alerts" />
                </div>

                <div>
                    <x-mary-select label="Preferred Language" wire:model.live="preferred_language" :options="[
                        ['id' => 'en', 'name' => 'English'],
                        ['id' => 'es', 'name' => 'Spanish'],
                        ['id' => 'fr', 'name' => 'French'],
                        ['id' => 'de', 'name' => 'German'],
                    ]"
                        option-value="id" option-label="name" />
                </div>
            </div>

            <x-slot:actions>
                <x-mary-button label="Update Preferences" class="btn-primary" type="submit"
                    spinner="updatePreferences" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-card>

    {{-- Account Information (Read Only) --}}
    <x-mary-card title="Account Information" separator class="bg-base-100">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="label-text font-medium">Account Type</label>
                <p class="text-sm opacity-80">
                    @if (Auth::user()->isSuperAdmin())
                        <x-mary-badge value="Super Admin" class="badge-error" />
                    @elseif(Auth::user()->isAdmin())
                        <x-mary-badge value="Admin" class="badge-warning" />
                    @elseif(Auth::user()->isReseller())
                        <x-mary-badge value="Reseller" class="badge-info" />
                    @endif
                </p>
            </div>

            <div>
                <label class="label-text font-medium">Member Since</label>
                <p class="text-sm opacity-80">{{ Auth::user()->created_at->format('M d, Y') }}</p>
            </div>

            <div>
                <label class="label-text font-medium">Last Login</label>
                <p class="text-sm opacity-80">
                    {{ Auth::user()->last_login_at ? Auth::user()->last_login_at->diffForHumans() : 'Never' }}
                </p>
            </div>

            <div>
                <label class="label-text font-medium">Account Balance</label>
                <p class="text-sm opacity-80 font-mono">${{ number_format(Auth::user()->balance, 2) }}</p>
            </div>
        </div>
    </x-mary-card>

    {{-- Account Security Status --}}
    <x-mary-card title="Security Status" separator class="bg-base-100">
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-sm">Password Security</span>
                <x-mary-badge value="Good" class="badge-success" />
            </div>

            <div class="flex items-center justify-between">
                <span class="text-sm">Two-Factor Authentication</span>
                @if ($two_factor_enabled)
                    <x-mary-badge value="Enabled" class="badge-success" />
                @else
                    <x-mary-badge value="Disabled" class="badge-error" />
                @endif
            </div>

            <div class="flex items-center justify-between">
                <span class="text-sm">Email Verification</span>
                @if (Auth::user()->email_verified_at)
                    <x-mary-badge value="Verified" class="badge-success" />
                @else
                    <x-mary-badge value="Unverified" class="badge-warning" />
                @endif
            </div>
        </div>
    </x-mary-card>
</div>
