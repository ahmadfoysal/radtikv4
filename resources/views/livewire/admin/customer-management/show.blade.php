<div class="space-y-6">
    {{-- Header with Actions --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('customers.index') }}" wire:navigate class="btn btn-ghost btn-sm">
                    <x-mary-icon name="o-arrow-left" class="w-5 h-5" />
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-base-content">{{ $customer->name }}</h1>
                    <p class="text-sm text-base-content/70 mt-1">Customer Details & Activity</p>
                </div>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('customers.edit', $customer) }}" wire:navigate class="btn btn-primary btn-sm">
                <x-mary-icon name="o-pencil" class="w-4 h-4" />
                Edit Customer
            </a>
        </div>
    </div>

    {{-- Customer Info Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <x-mary-card class="bg-gradient-to-br from-primary/10 to-base-100">
            <div class="flex items-center gap-3">
                <div class="avatar placeholder">
                    <div class="bg-primary text-primary-content rounded-full w-16">
                        <span class="text-2xl">{{ substr($customer->name, 0, 2) }}</span>
                    </div>
                </div>
                <div>
                    <div class="text-2xl font-bold">{{ $stats['total_routers'] }}</div>
                    <div class="text-sm text-base-content/70">Total Routers</div>
                    <div class="text-xs text-success">{{ $stats['active_routers'] }} active</div>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-br from-success/10 to-base-100">
            <div class="flex items-center gap-3">
                <x-mary-icon name="o-banknotes" class="w-12 h-12 text-success" />
                <div>
                    <div class="text-2xl font-bold text-success">${{ number_format($customer->balance, 2) }}</div>
                    <div class="text-sm text-base-content/70">Current Balance</div>
                    <div class="text-xs text-base-content/60">{{ $customer->commission }}% commission</div>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-br from-info/10 to-base-100">
            <div class="flex items-center gap-3">
                <x-mary-icon name="o-document-text" class="w-12 h-12 text-info" />
                <div>
                    <div class="text-2xl font-bold text-info">{{ $stats['total_invoices'] }}</div>
                    <div class="text-sm text-base-content/70">Total Invoices</div>
                    <div class="text-xs text-base-content/60">${{ number_format($stats['total_spent'], 2) }} spent
                    </div>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-br from-warning/10 to-base-100">
            <div class="flex items-center gap-3">
                <x-mary-icon name="o-cube" class="w-12 h-12 text-warning" />
                <div>
                    <div class="text-2xl font-bold text-warning">{{ $stats['total_subscriptions'] }}</div>
                    <div class="text-sm text-base-content/70">Subscriptions</div>
                    @if ($activeSubscription)
                        <div class="text-xs text-success">Currently active</div>
                    @else
                        <div class="text-xs text-base-content/60">No active</div>
                    @endif
                </div>
            </div>
        </x-mary-card>
    </div>

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Contact Information --}}
            <x-mary-card title="Contact Information">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <div class="text-sm font-semibold text-base-content/70 mb-1">Email</div>
                        <div class="flex items-center gap-2">
                            <x-mary-icon name="o-envelope" class="w-4 h-4" />
                            <a href="mailto:{{ $customer->email }}"
                                class="text-primary hover:underline">{{ $customer->email }}</a>
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-base-content/70 mb-1">Phone</div>
                        <div class="flex items-center gap-2">
                            <x-mary-icon name="o-phone" class="w-4 h-4" />
                            <span>{{ $customer->phone ?? 'N/A' }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-base-content/70 mb-1">Country</div>
                        <div class="flex items-center gap-2">
                            <x-mary-icon name="o-map-pin" class="w-4 h-4" />
                            <span>{{ $customer->country ?? 'N/A' }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-base-content/70 mb-1">Address</div>
                        <div class="flex items-center gap-2">
                            <x-mary-icon name="o-home" class="w-4 h-4" />
                            <span>{{ $customer->address ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </x-mary-card>

            {{-- Active Subscription --}}
            @if ($activeSubscription)
                <x-mary-card title="Active Subscription">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <div class="text-sm font-semibold text-base-content/70 mb-1">Package</div>
                            <div class="text-lg font-bold">{{ $activeSubscription->package->name }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-base-content/70 mb-1">Billing Cycle</div>
                            <x-mary-badge value="{{ ucfirst($activeSubscription->billing_cycle) }}"
                                class="badge-primary" />
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-base-content/70 mb-1">Status</div>
                            <x-mary-badge value="{{ ucfirst($activeSubscription->status) }}" class="badge-success" />
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-base-content/70 mb-1">Start Date</div>
                            <div>{{ $activeSubscription->start_date->format('M d, Y') }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-base-content/70 mb-1">End Date</div>
                            <div>{{ $activeSubscription->end_date->format('M d, Y') }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-base-content/70 mb-1">Amount</div>
                            <div class="text-lg font-bold text-success">
                                ${{ number_format($activeSubscription->amount, 2) }}
                            </div>
                        </div>
                    </div>
                </x-mary-card>
            @endif

            {{-- Recent Invoices --}}
            <x-mary-card title="Recent Invoices">
                @if ($customer->invoices->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Type</th>
                                    <th>Category</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($customer->invoices as $invoice)
                                    <tr>
                                        <td class="font-mono text-xs">#{{ $invoice->id }}</td>
                                        <td>
                                            <x-mary-badge :value="ucfirst($invoice->type)"
                                                class="badge-xs badge-{{ $invoice->type === 'credit' ? 'success' : 'warning' }}" />
                                        </td>
                                        <td class="text-xs">{{ ucfirst($invoice->category) }}</td>
                                        <td>{{ $invoice->created_at->format('M d, Y') }}</td>
                                        <td class="font-semibold">
                                            <span
                                                class="{{ $invoice->type === 'credit' ? 'text-success' : 'text-warning' }}">
                                                {{ $invoice->type === 'credit' ? '+' : '-' }}${{ number_format($invoice->amount, 2) }}
                                            </span>
                                        </td>
                                        <td>
                                            <x-mary-badge :value="ucfirst($invoice->status)"
                                                class="badge-sm badge-{{ $invoice->status === 'completed' ? 'success' : ($invoice->status === 'failed' ? 'error' : 'warning') }}" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-8 text-base-content/60">
                        <x-mary-icon name="o-document-text" class="w-12 h-12 mx-auto mb-2 text-base-content/20" />
                        <div>No invoices found</div>
                    </div>
                @endif
            </x-mary-card>

            {{-- Routers --}}
            <x-mary-card title="Routers ({{ $customer->routers->count() }})">
                @if ($customer->routers->count() > 0)
                    <div class="space-y-2">
                        @foreach ($customer->routers as $router)
                            <div class="flex items-center justify-between p-3 bg-base-200 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <x-mary-icon name="o-server-stack" class="w-5 h-5 text-primary" />
                                    <div>
                                        <div class="font-semibold">{{ $router->name }}</div>
                                        <div class="text-xs text-base-content/60">{{ $router->ip }}</div>
                                    </div>
                                </div>
                                <x-mary-badge :value="$router->is_active ? 'Active' : 'Inactive'"
                                    class="badge-sm badge-{{ $router->is_active ? 'success' : 'ghost' }}" />
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 text-base-content/60">
                        <x-mary-icon name="o-server-stack" class="w-12 h-12 mx-auto mb-2 text-base-content/20" />
                        <div>No routers configured</div>
                    </div>
                @endif
            </x-mary-card>
        </div>

        {{-- Right Column --}}
        <div class="space-y-6">
            {{-- Account Status --}}
            <x-mary-card title="Account Status">
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm">Account Status</span>
                        <x-mary-badge :value="$customer->is_active ? 'Active' : 'Inactive'"
                            class="badge-{{ $customer->is_active ? 'success' : 'error' }}" />
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm">Suspended</span>
                        <x-mary-badge :value="$customer->suspended_at ? 'Yes' : 'No'"
                            class="badge-{{ $customer->suspended_at ? 'error' : 'success' }}" />
                    </div>
                    @if ($customer->suspended_at)
                        <div class="text-xs text-error p-2 bg-error/10 rounded">
                            <div class="font-semibold">Suspension Reason:</div>
                            <div>{{ $customer->suspension_reason ?? 'N/A' }}</div>
                        </div>
                    @endif
                    <div class="flex justify-between items-center">
                        <span class="text-sm">Email Verified</span>
                        <x-mary-badge :value="$customer->email_verified_at ? 'Yes' : 'No'"
                            class="badge-{{ $customer->email_verified_at ? 'success' : 'warning' }}" />
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm">2FA Enabled</span>
                        <x-mary-badge :value="$customer->two_factor_confirmed_at ? 'Yes' : 'No'"
                            class="badge-{{ $customer->two_factor_confirmed_at ? 'success' : 'ghost' }}" />
                    </div>
                </div>
            </x-mary-card>

            {{-- Account Dates --}}
            <x-mary-card title="Account Information">
                <div class="space-y-3">
                    <div>
                        <div class="text-sm font-semibold text-base-content/70">Joined</div>
                        <div class="text-sm">{{ $customer->created_at->format('M d, Y') }}</div>
                        <div class="text-xs text-base-content/60">{{ $customer->created_at->diffForHumans() }}</div>
                    </div>
                    @if ($customer->last_login_at)
                        <div>
                            <div class="text-sm font-semibold text-base-content/70">Last Login</div>
                            <div class="text-sm">{{ $customer->last_login_at->format('M d, Y H:i') }}</div>
                            <div class="text-xs text-base-content/60">{{ $customer->last_login_at->diffForHumans() }}
                            </div>
                        </div>
                    @endif
                    @if ($customer->expiration_date)
                        <div>
                            <div class="text-sm font-semibold text-base-content/70">Expiration Date</div>
                            <div class="text-sm">{{ $customer->expiration_date->format('M d, Y') }}</div>
                        </div>
                    @endif
                </div>
            </x-mary-card>

            {{-- Preferences --}}
            <x-mary-card title="Preferences">
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm">Email Notifications</span>
                        <x-mary-icon :name="$customer->email_notifications ? 'o-check-circle' : 'o-x-circle'"
                            class="w-5 h-5 {{ $customer->email_notifications ? 'text-success' : 'text-base-content/30' }}" />
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm">Login Alerts</span>
                        <x-mary-icon :name="$customer->login_alerts ? 'o-check-circle' : 'o-x-circle'"
                            class="w-5 h-5 {{ $customer->login_alerts ? 'text-success' : 'text-base-content/30' }}" />
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm">Preferred Language</span>
                        <span class="text-sm font-semibold">{{ $customer->preferred_language ?? 'English' }}</span>
                    </div>
                </div>
            </x-mary-card>
        </div>
    </div>
</div>
