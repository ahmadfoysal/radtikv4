<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('customers.show', $customer) }}" wire:navigate class="btn btn-ghost btn-sm">
                    <x-mary-icon name="o-arrow-left" class="w-5 h-5" />
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-base-content">Edit Customer</h1>
                    <p class="text-sm text-base-content/70 mt-1">Update customer information and settings</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Edit Form --}}
    <x-mary-form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Information --}}
            <div class="lg:col-span-2 space-y-6">
                <x-mary-card title="Basic Information">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-mary-input label="Full Name" wire:model="name" placeholder="Enter customer name"
                            icon="o-user" required />

                        <x-mary-input label="Email Address" wire:model="email" type="email"
                            placeholder="customer@example.com" icon="o-envelope" required />

                        <x-mary-input label="Phone" wire:model="phone" placeholder="+1234567890" icon="o-phone" />

                        <x-mary-input label="Country" wire:model="country" placeholder="Enter country"
                            icon="o-map-pin" />

                        <div class="md:col-span-2">
                            <x-mary-textarea label="Address" wire:model="address" placeholder="Enter full address"
                                rows="2" />
                        </div>
                    </div>
                </x-mary-card>

                <x-mary-card title="Financial Information">
                    <div class="space-y-4">
                        <div>
                            <label class="label">
                                <span class="label-text">Current Balance</span>
                            </label>
                            <div class="flex items-center gap-2 p-3 bg-base-200 rounded-lg">
                                <x-mary-icon name="o-banknotes" class="w-5 h-5 text-success" />
                                <span
                                    class="text-lg font-bold text-success">${{ number_format($customer->balance, 2) }}</span>
                                <span class="text-xs text-base-content/60 ml-2">(Read-only)</span>
                            </div>
                        </div>

                        <x-mary-input label="Commission Rate" wire:model="commission" type="number" step="0.01"
                            min="0" max="100" placeholder="0.00" icon="o-percent-badge" required>
                            <x-slot:suffix>
                                <span class="text-base-content/70">%</span>
                            </x-slot:suffix>
                        </x-mary-input>

                        <x-mary-alert icon="o-information-circle" class="alert-info">
                            Commission rate determines the discount this customer receives on packages and services.
                        </x-mary-alert>
                    </div>
                </x-mary-card>

                <x-mary-card title="Security">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-mary-input label="New Password" wire:model="password" type="password"
                            placeholder="Leave blank to keep current" icon="o-lock-closed">
                            <x-slot:hint>
                                Minimum 8 characters
                            </x-slot:hint>
                        </x-mary-input>

                        <x-mary-input label="Expiration Date" wire:model="expiration_date" type="date"
                            icon="o-calendar">
                            <x-slot:hint>
                                Optional account expiration
                            </x-slot:hint>
                        </x-mary-input>
                    </div>
                </x-mary-card>
            </div>

            {{-- Sidebar Settings --}}
            <div class="space-y-6">
                <x-mary-card title="Account Status">
                    <div class="space-y-4">
                        <x-mary-toggle label="Account Active" wire:model="is_active" />

                        <div class="divider my-2"></div>

                        <div class="space-y-2">
                            @if ($customer->suspended_at)
                                <button type="button" wire:click="unsuspend" class="btn btn-success btn-block btn-sm">
                                    <x-mary-icon name="o-check-circle" class="w-4 h-4" />
                                    Unsuspend Account
                                </button>
                            @else
                                <button type="button" wire:click="openSuspendModal"
                                    class="btn btn-error btn-block btn-sm">
                                    <x-mary-icon name="o-x-circle" class="w-4 h-4" />
                                    Suspend Account
                                </button>
                            @endif
                        </div>

                        @if ($customer->suspended_at)
                            <x-mary-alert icon="o-exclamation-triangle" class="alert-error">
                                <div class="text-sm">
                                    <div class="font-semibold">Account Suspended</div>
                                    <div class="text-xs text-error/70 mt-1">
                                        Since {{ \Carbon\Carbon::parse($customer->suspended_at)->format('M d, Y') }}
                                    </div>
                                    @if ($customer->suspension_reason)
                                        <div class="divider my-1"></div>
                                        <div class="font-semibold text-xs">Reason:</div>
                                        <div class="text-xs mt-1">{{ $customer->suspension_reason }}</div>
                                    @endif
                                </div>
                            </x-mary-alert>
                        @endif
                    </div>
                </x-mary-card>

                <x-mary-card title="Preferences">
                    <div class="space-y-4">
                        <x-mary-toggle label="Email Notifications" wire:model="email_notifications" />
                        <x-mary-toggle label="Login Alerts" wire:model="login_alerts" />
                    </div>
                </x-mary-card>

                <x-mary-card title="Account Info">
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-base-content/70">Customer ID</span>
                            <span class="font-mono">#{{ $customer->id }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-base-content/70">Joined</span>
                            <span>{{ $customer->created_at->format('M d, Y') }}</span>
                        </div>
                        @if ($customer->last_login_at)
                            <div class="flex justify-between">
                                <span class="text-base-content/70">Last Login</span>
                                <span>{{ $customer->last_login_at->diffForHumans() }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between">
                            <span class="text-base-content/70">Total Routers</span>
                            <x-mary-badge value="{{ $customer->routers()->count() }}"
                                class="badge-sm badge-primary" />
                        </div>
                    </div>
                </x-mary-card>
            </div>
        </div>

        {{-- Action Buttons --}}
        <x-slot:actions>
            <div class="flex justify-end gap-3">
                <a href="{{ route('customers.show', $customer) }}" wire:navigate class="btn btn-ghost">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <x-mary-icon name="o-check" class="w-4 h-4" />
                    Save Changes
                </button>
            </div>
        </x-slot:actions>
    </x-mary-form>

    {{-- Suspend Modal --}}
    <x-mary-modal wire:model="showSuspendModal" title="Suspend Customer Account"
        subtitle="Enter reason for suspension">
        <div class="space-y-4">
            <x-mary-alert icon="o-exclamation-triangle" class="alert-warning">
                This will suspend the customer's account. They won't be able to access the system until unsuspended.
            </x-mary-alert>

            <x-mary-textarea label="Suspension Reason" wire:model="suspension_reason"
                placeholder="Enter the reason for suspending this account..." rows="4"
                hint="This reason will be visible to superadmins" required />
        </div>

        <x-slot:actions>
            <x-mary-button label="Cancel" wire:click="$set('showSuspendModal', false)" />
            <x-mary-button label="Suspend Account" class="btn-error" wire:click="suspend" />
        </x-slot:actions>
    </x-mary-modal>
</div>
