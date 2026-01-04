<div class="space-y-6">
    {{-- Subscription Expiry Alert --}}
    @if (isset($subscriptionAlert))
        @if ($subscriptionAlert['gracePeriod'])
            {{-- Grace Period Alert - Red --}}
            <div
                class="bg-gradient-to-r from-error/20 via-error/10 to-error/5 border-l-4 border-error rounded-lg p-4 shadow-lg">
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-error rounded-full flex items-center justify-center animate-pulse">
                            <svg class="w-6 h-6 text-error-content" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-lg font-bold text-error mb-1">⚠️ Subscription Expired!</h3>
                        <p class="text-base-content">{{ $subscriptionAlert['message'] }}</p>
                    </div>
                    @if ($subscriptionAlert['daysLeft'] > 0)
                        <div class="flex-shrink-0">
                            <div class="bg-error text-error-content rounded-xl px-6 py-3 text-center shadow-md">
                                <div class="text-3xl font-bold">{{ $subscriptionAlert['daysLeft'] }}</div>
                                <div class="text-sm font-medium">
                                    {{ $subscriptionAlert['daysLeft'] == 1 ? 'Day' : 'Days' }} Left</div>
                            </div>
                        </div>
                    @endif
                    <div class="flex-shrink-0">
                        <a href="{{ route('subscription.index') }}" wire:navigate class="btn btn-error btn-sm gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Renew Now
                        </a>
                    </div>
                </div>
            </div>
        @else
            {{-- Expiring Soon Alert - Warning --}}
            <div
                class="bg-gradient-to-r from-warning/20 via-warning/10 to-warning/5 border-l-4 border-warning rounded-lg p-4 shadow-lg">
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-warning rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-warning-content" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-lg font-bold text-warning mb-1">⏰ Subscription Expiring Soon!</h3>
                        <p class="text-base-content">{{ $subscriptionAlert['message'] }}</p>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="bg-warning text-warning-content rounded-xl px-6 py-3 text-center shadow-md">
                            <div class="text-3xl font-bold">{{ $subscriptionAlert['daysLeft'] }}</div>
                            <div class="text-sm font-medium">{{ $subscriptionAlert['daysLeft'] == 1 ? 'Day' : 'Days' }}
                                Left</div>
                        </div>
                    </div>
                    <div class="flex-shrink-0">
                        <a href="{{ route('subscription.index') }}" wire:navigate class="btn btn-warning btn-sm gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Renew Now
                        </a>
                    </div>
                </div>
            </div>
        @endif
    @endif

    {{-- Top Operational Stats Cards --}}
    <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Total Routers --}}
        <x-mary-card class="border border-base-300 bg-gradient-to-br from-info/10 to-base-100">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <x-mary-icon name="o-server-stack" class="w-5 h-5 text-info" />
                        <span class="text-sm font-medium text-base-content/70">Total Routers</span>
                    </div>
                    <p class="text-3xl font-bold text-info">{{ number_format($routerStats['total']) }}</p>
                    <p class="text-xs text-base-content/60 mt-1">{{ $routerStats['withZone'] }} with zones</p>
                </div>
            </div>
        </x-mary-card>

        {{-- Total Vouchers --}}
        <x-mary-card class="border border-base-300 bg-gradient-to-br from-success/10 to-base-100">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <x-mary-icon name="o-ticket" class="w-5 h-5 text-success" />
                        <span class="text-sm font-medium text-base-content/70">Total Vouchers</span>
                    </div>
                    <p class="text-3xl font-bold text-success">{{ number_format($voucherStats['total']) }}</p>
                    <p class="text-xs text-success mt-1">{{ number_format($voucherStats['active']) }} active</p>
                </div>
            </div>
        </x-mary-card>

        {{-- Today's Activations --}}
        <x-mary-card class="border border-base-300 bg-gradient-to-br from-primary/10 to-base-100">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <x-mary-icon name="o-check-circle" class="w-5 h-5 text-primary" />
                        <span class="text-sm font-medium text-base-content/70">Today's Activations</span>
                    </div>
                    <p class="text-3xl font-bold text-primary">{{ number_format($operationalStats['todayActivations']) }}</p>
                    <p class="text-xs text-base-content/60 mt-1">Active users today</p>
                </div>
            </div>
        </x-mary-card>

        {{-- Monthly Activations --}}
        <x-mary-card class="border border-base-300 bg-gradient-to-br from-accent/10 to-base-100">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <x-mary-icon name="o-chart-bar" class="w-5 h-5 text-accent" />
                        <span class="text-sm font-medium text-base-content/70">Monthly Activations</span>
                    </div>
                    <p class="text-3xl font-bold text-accent">{{ number_format($operationalStats['monthActivations']) }}</p>
                    <p class="text-xs text-base-content/60 mt-1">This month's usage</p>
                </div>
            </div>
        </x-mary-card>
    </div>

    {{-- Secondary Stats --}}
    <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Active Vouchers --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <x-mary-icon name="o-check-badge" class="w-5 h-5 text-success" />
                        <span class="text-sm font-medium text-base-content/70">Active Vouchers</span>
                    </div>
                    <p class="text-2xl font-bold text-success">{{ number_format($voucherStats['active']) }}</p>
                    <p class="text-xs text-base-content/60 mt-1">Currently active</p>
                </div>
            </div>
        </x-mary-card>

        {{-- Expired Today --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <x-mary-icon name="o-clock" class="w-5 h-5 text-warning" />
                        <span class="text-sm font-medium text-base-content/70">Expired Today</span>
                    </div>
                    <p class="text-2xl font-bold text-warning">{{ number_format($voucherStats['expiredToday']) }}</p>
                    <p class="text-xs text-base-content/60 mt-1">Expired vouchers</p>
                </div>
            </div>
        </x-mary-card>

        {{-- Generated Today --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <x-mary-icon name="o-plus-circle" class="w-5 h-5 text-info" />
                        <span class="text-sm font-medium text-base-content/70">Generated Today</span>
                    </div>
                    <p class="text-2xl font-bold text-info">{{ number_format($voucherStats['generatedToday']) }}</p>
                    <p class="text-xs text-base-content/60 mt-1">New vouchers</p>
                </div>
            </div>
        </x-mary-card>

        {{-- Resellers --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <x-mary-icon name="o-users" class="w-5 h-5 text-accent" />
                        <span class="text-sm font-medium text-base-content/70">Resellers</span>
                    </div>
                    <p class="text-2xl font-bold">{{ number_format($resellerStats['total']) }}</p>
                    <p class="text-xs text-success mt-1">{{ $resellerStats['active'] }} active</p>
                </div>
            </div>
        </x-mary-card>
    </div>

    {{-- Quick Actions Bar --}}
    <x-mary-card class="border border-base-300 bg-base-100">
        <div class="flex flex-wrap items-center gap-3">
            <span class="text-sm font-semibold text-base-content/70">Quick Actions:</span>
            <x-mary-button icon="o-plus-circle" label="Generate Vouchers" class="btn-sm btn-primary"
                href="{{ route('vouchers.generate') }}" wire:navigate />
            <x-mary-button icon="o-chart-bar" label="Sales Summary" class="btn-sm btn-success"
                href="{{ route('billing.sales-summary') }}" wire:navigate />
            <x-mary-button icon="o-clipboard-document-list" label="Voucher Logs" class="btn-sm btn-info"
                href="{{ route('vouchers.logs') }}" wire:navigate />
            <x-mary-button icon="o-server" label="Add Router" class="btn-sm btn-ghost"
                href="{{ route('routers.create') }}" wire:navigate />
        </div>
    </x-mary-card>

    {{-- Charts Section --}}
    <div class="grid gap-4 grid-cols-1 lg:grid-cols-2">
        {{-- Activation Trend Chart --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <x-slot name="title">
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-check-circle" class="w-5 h-5 text-primary" />
                    <span>Voucher Activations (Last 7 Days)</span>
                </div>
            </x-slot>
            <div class="h-64">
                <x-mary-chart wire:model="activationChart" />
            </div>
        </x-mary-card>

        {{-- Generation Trend Chart --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <x-slot name="title">
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-ticket" class="w-5 h-5 text-success" />
                    <span>Vouchers Generated (Last 7 Days)</span>
                </div>
            </x-slot>
            <div class="h-64">
                <x-mary-chart wire:model="generationChart" />
            </div>
        </x-mary-card>
    </div>

    {{-- Tables Section --}}
    <div class="grid gap-4 grid-cols-1 lg:grid-cols-2">
        {{-- Top Profiles by Usage --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <x-slot name="title">
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-fire" class="w-5 h-5 text-warning" />
                    <span>Most Used Profiles</span>
                </div>
            </x-slot>
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Profile</th>
                            <th class="text-right">Activations</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topProfiles as $item)
                            <tr>
                                <td class="font-medium">{{ $item->profile }}</td>
                                <td class="text-right font-semibold text-primary">{{ number_format($item->total_activations) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center text-base-content/60">No activations yet</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-mary-card>

        {{-- Top Routers by Usage --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <x-slot name="title">
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-server-stack" class="w-5 h-5 text-info" />
                    <span>Most Active Routers</span>
                </div>
            </x-slot>
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Router</th>
                            <th class="text-right">Activations</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topRoutersByUsage as $router)
                            <tr>
                                <td class="font-medium">{{ $router->name }}</td>
                                <td class="text-right font-semibold text-info">{{ number_format($router->total_activations) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center text-base-content/60">No data available</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-mary-card>

        {{-- Recent Activations --}}
        <x-mary-card class="border border-base-300 bg-base-100 lg:col-span-2">
            <x-slot name="title">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-mary-icon name="o-clock" class="w-5 h-5 text-primary" />
                        <span>Recent Activations</span>
                    </div>
                    <x-mary-button label="View All" icon="o-arrow-right" class="btn-xs btn-ghost"
                        href="{{ route('vouchers.logs') }}" wire:navigate />
                </div>
            </x-slot>
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Profile</th>
                            <th>Router</th>
                            <th class="text-right">Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentActivations as $activation)
                            <tr>
                                <td class="font-medium">{{ $activation->username }}</td>
                                <td><span class="badge badge-sm badge-info">{{ $activation->profile }}</span></td>
                                <td class="text-sm">{{ $activation->router_name }}</td>
                                <td class="text-right text-xs text-base-content/60">
                                    {{ \Carbon\Carbon::parse($activation->created_at)->diffForHumans() }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-base-content/60">No recent activations</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-mary-card>
    </div>

    {{-- Router & Voucher Management --}}
    <div class="grid gap-4 grid-cols-1 xl:grid-cols-3">
        {{-- Voucher Statistics --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <x-slot name="title">
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-ticket" class="w-5 h-5 text-success" />
                    <span>Voucher Overview</span>
                </div>
            </x-slot>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-base-content/70">Total Vouchers</span>
                    <span class="font-bold">{{ number_format($voucherStats['total']) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-base-content/70">Active</span>
                    <span class="font-bold text-success">{{ number_format($voucherStats['active']) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-base-content/70">Expired</span>
                    <span class="font-bold text-warning">{{ number_format($voucherStats['expired']) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-base-content/70">Inactive</span>
                    <span class="font-bold text-base-content/60">{{ number_format($voucherStats['inactive']) }}</span>
                </div>
                <div class="pt-3 border-t border-base-300">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-base-content/60">Generated This Week</span>
                        <span
                            class="text-sm font-semibold text-primary">{{ number_format($voucherStats['generatedThisWeek']) }}</span>
                    </div>
                </div>
            </div>
        </x-mary-card>

        {{-- Router Zones --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <x-slot name="title">
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-map" class="w-5 h-5 text-info" />
                    <span>Routers by Zone</span>
                </div>
            </x-slot>
            <div class="space-y-2">
                @forelse ($routerUsage as $zone => $count)
                    <div class="flex items-center justify-between text-sm"
                        wire:key="admin-zone-{{ \Illuminate\Support\Str::slug($zone) }}">
                        <span class="truncate flex-1">{{ $zone }}</span>
                        <x-mary-badge value="{{ number_format($count) }}" class="badge-primary badge-sm" />
                    </div>
                @empty
                    <p class="text-sm text-base-content/70 text-center py-4">No zone data yet.</p>
                @endforelse
            </div>
        </x-mary-card>

        {{-- Quick Stats --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <x-slot name="title">
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-chart-bar" class="w-5 h-5 text-info" />
                    <span>Quick Stats</span>
                </div>
            </x-slot>
            <div class="space-y-3">
                <div class="flex items-center justify-between p-2 bg-base-200">
                    <span class="text-sm text-base-content/70">Activated Today</span>
                    <span class="font-bold text-primary">{{ number_format($operationalStats['todayActivations']) }}</span>
                </div>
                <div class="flex items-center justify-between p-2 bg-base-200">
                    <span class="text-sm text-base-content/70">Activated This Week</span>
                    <span class="font-bold text-success">{{ number_format($operationalStats['weekActivations']) }}</span>
                </div>
                <div class="flex items-center justify-between p-2 bg-base-200">
                    <span class="text-sm text-base-content/70">Active Resellers</span>
                    <span class="font-bold text-info">{{ number_format($resellerStats['active']) }}</span>
                </div>
                <div class="flex items-center justify-between p-2 bg-base-200">
                    <span class="text-sm text-base-content/70">Total Routers</span>
                    <span class="font-bold">{{ number_format($routerStats['total']) }}</span>
                </div>
            </div>
        </x-mary-card>
    </div>

    {{-- Recent Activity Section --}}
    <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 xl:grid-cols-3">
        {{-- Recent Routers --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <x-slot name="title">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-mary-icon name="o-server-stack" class="w-5 h-5 text-info" />
                        <span>Recent Routers</span>
                    </div>
                    <x-mary-button icon="o-arrow-right" class="btn-ghost btn-xs" href="{{ route('routers.index') }}"
                        wire:navigate />
                </div>
            </x-slot>
            <div class="space-y-3">
                @forelse ($recentRouters as $router)
                    <div class="p-3 border border-base-300 bg-base-200/50"
                        wire:key="recent-router-{{ $router->id }}">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-sm truncate">{{ $router->name }}</p>
                                <p class="text-xs text-base-content/60 mt-1">{{ $router->address }}</p>
                                <div class="flex items-center gap-3 mt-2 text-xs text-base-content/60">
                                    <span>Zone: {{ $router->zone->name ?? 'N/A' }}</span>
                                    <span>•</span>
                                    <span class="text-success">Active:
                                        {{ $router->active_vouchers_count ?? 0 }}</span>
                                    <span>•</span>
                                    <span class="text-warning">Expired:
                                        {{ $router->expired_vouchers_count ?? 0 }}</span>
                                </div>
                            </div>
                            <span
                                class="text-xs text-base-content/50 whitespace-nowrap">{{ $router->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-base-content/70 text-center py-4">No routers found.</p>
                @endforelse
            </div>
        </x-mary-card>

        {{-- Recent Vouchers --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <x-slot name="title">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-mary-icon name="o-ticket" class="w-5 h-5 text-success" />
                        <span>Recent Vouchers</span>
                    </div>
                    <x-mary-button icon="o-arrow-right" class="btn-ghost btn-xs"
                        href="{{ route('vouchers.index') }}" wire:navigate />
                </div>
            </x-slot>
            <div class="space-y-3">
                @forelse ($recentVouchers as $voucher)
                    <div class="flex items-center justify-between p-2 border border-base-300 bg-base-200/50"
                        wire:key="recent-voucher-{{ $voucher->id }}">
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-sm truncate">{{ $voucher->username }}</p>
                            <p class="text-xs text-base-content/60 mt-1">{{ $voucher->router->name ?? 'N/A' }}</p>
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            <x-mary-badge value="{{ ucfirst($voucher->status) }}"
                                class="badge-sm {{ $voucher->status === 'active' ? 'badge-success' : ($voucher->status === 'expired' ? 'badge-warning' : 'badge-ghost') }}" />
                            <span
                                class="text-xs text-base-content/50">{{ $voucher->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-base-content/70 text-center py-4">No vouchers found.</p>
                @endforelse
            </div>
        </x-mary-card>

        {{-- Operational Summary --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <x-slot name="title">
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-chart-pie" class="w-5 h-5 text-accent" />
                    <span>Operational Summary</span>
                </div>
            </x-slot>
            <div class="space-y-3">
                <div class="p-3 bg-success/10 border border-success/20">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-success">Active Vouchers</span>
                        <span class="text-2xl font-bold text-success">{{ number_format($voucherStats['active']) }}</span>
                    </div>
                    <p class="text-xs text-base-content/60 mt-1">Currently running</p>
                </div>
                <div class="p-3 bg-warning/10 border border-warning/20">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-warning">Expired</span>
                        <span class="text-2xl font-bold text-warning">{{ number_format($voucherStats['expired']) }}</span>
                    </div>
                    <p class="text-xs text-base-content/60 mt-1">Total expired</p>
                </div>
                <div class="p-3 bg-info/10 border border-info/20">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-info">Inactive</span>
                        <span class="text-2xl font-bold text-info">{{ number_format($voucherStats['inactive']) }}</span>
                    </div>
                    <p class="text-xs text-base-content/60 mt-1">Not yet activated</p>
                </div>
            </div>
        </x-mary-card>
    </div>
</div>
