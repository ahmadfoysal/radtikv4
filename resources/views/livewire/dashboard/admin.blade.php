<div class="space-y-6">
    {{-- Top Financial Stats Cards --}}
    <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        {{-- Today's Income --}}
        <x-mary-card class="border border-base-300 bg-gradient-to-br from-success/10 to-base-100">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <x-mary-icon name="o-banknotes" class="w-5 h-5 text-success" />
                        <span class="text-sm font-medium text-base-content/70">Today's Income</span>
                    </div>
                    <p class="text-3xl font-bold text-success">@userCurrency($billingStats['todayIncome'])</p>
                    <p class="text-xs text-base-content/60 mt-1">{{ $billingStats['todayActivations'] }} activations
                        today</p>
                </div>
            </div>
        </x-mary-card>

        {{-- Monthly Income --}}
        <x-mary-card class="border border-base-300 bg-gradient-to-br from-primary/10 to-base-100">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <x-mary-icon name="o-currency-dollar" class="w-5 h-5 text-primary" />
                        <span class="text-sm font-medium text-base-content/70">Monthly Income</span>
                    </div>
                    <p class="text-3xl font-bold text-primary">@userCurrency($billingStats['monthIncome'])</p>
                    <p class="text-xs text-base-content/60 mt-1">From voucher activations</p>
                </div>
            </div>
        </x-mary-card>

        {{-- Monthly Expense --}}
        <x-mary-card class="border border-base-300 bg-gradient-to-br from-warning/10 to-base-100">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <x-mary-icon name="o-arrow-trending-down" class="w-5 h-5 text-warning" />
                        <span class="text-sm font-medium text-base-content/70">Monthly Expense</span>
                    </div>
                    <p class="text-3xl font-bold text-warning">@userCurrency($billingStats['monthlyExpense'])</p>
                    <p class="text-xs text-base-content/60 mt-1">ISP and operational costs</p>
                </div>
            </div>
        </x-mary-card>

        {{-- Net Profit --}}
        <x-mary-card
            class="border border-base-300 bg-gradient-to-br {{ $billingStats['netProfit'] >= 0 ? 'from-success/10' : 'from-error/10' }} to-base-100">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <x-mary-icon name="o-chart-bar"
                            class="w-5 h-5 {{ $billingStats['netProfit'] >= 0 ? 'text-success' : 'text-error' }}" />
                        <span class="text-sm font-medium text-base-content/70">Net Profit</span>
                    </div>
                    <p class="text-3xl font-bold {{ $billingStats['netProfit'] >= 0 ? 'text-success' : 'text-error' }}">
                        @userCurrency($billingStats['netProfit'])</p>
                    <p class="text-xs text-base-content/60 mt-1">Income - Expenses</p>
                </div>
            </div>
        </x-mary-card>
    </div>

    {{-- Secondary Stats --}}
    <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        {{-- Wallet Balance --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <x-mary-icon name="o-wallet" class="w-5 h-5 text-info" />
                        <span class="text-sm font-medium text-base-content/70">Wallet Balance</span>
                    </div>
                    <p class="text-2xl font-bold">@userCurrency($balance)</p>
                </div>
                <x-mary-button icon="o-plus" class="btn-sm btn-circle btn-primary"
                    href="{{ route('billing.add-balance') }}" wire:navigate />
            </div>
        </x-mary-card>

        {{-- Routers --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <x-mary-icon name="o-server-stack" class="w-5 h-5 text-info" />
                        <span class="text-sm font-medium text-base-content/70">Routers</span>
                    </div>
                    <p class="text-2xl font-bold">{{ number_format($routerStats['total']) }}</p>
                    <p class="text-xs text-base-content/60 mt-1">Total active routers</p>
                </div>
            </div>
        </x-mary-card>

        {{-- Vouchers --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <x-mary-icon name="o-ticket" class="w-5 h-5 text-success" />
                        <span class="text-sm font-medium text-base-content/70">Vouchers</span>
                    </div>
                    <p class="text-2xl font-bold">{{ number_format($voucherStats['total']) }}</p>
                    <p class="text-xs text-success mt-1">{{ $voucherStats['active'] }} active</p>
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
        {{-- Income Trend Chart --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <x-slot name="title">
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-arrow-trending-up" class="w-5 h-5 text-success" />
                    <span>Income Trend (Last 7 Days)</span>
                </div>
            </x-slot>
            <div class="h-64">
                <x-mary-chart wire:model="incomeChart" />
            </div>
        </x-mary-card>

        {{-- Activation Trend Chart --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <x-slot name="title">
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-ticket" class="w-5 h-5 text-info" />
                    <span>Voucher Activations (Last 7 Days)</span>
                </div>
            </x-slot>
            <div class="h-64">
                <x-mary-chart wire:model="activationChart" />
            </div>
        </x-mary-card>
    </div>

    {{-- Tables Section --}}
    <div class="grid gap-4 grid-cols-1 lg:grid-cols-2">
        {{-- Income by Profile Table --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <x-slot name="title">
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-banknotes" class="w-5 h-5 text-success" />
                    <span>Top Selling Profiles</span>
                </div>
            </x-slot>
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Profile</th>
                            <th class="text-right">Activations</th>
                            <th class="text-right">Income</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($incomeByProfile as $item)
                            <tr>
                                <td class="font-medium">{{ $item->profile }}</td>
                                <td class="text-right">{{ number_format($item->activations) }}</td>
                                <td class="text-right font-semibold text-success">@userCurrency($item->total_income)</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-base-content/60">No activations yet</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-mary-card>

        {{-- Top Routers by Income --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <x-slot name="title">
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-server-stack" class="w-5 h-5 text-info" />
                    <span>Top Routers by Income</span>
                </div>
            </x-slot>
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Router</th>
                            <th class="text-right">Income</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topRouters as $router)
                            <tr>
                                <td class="font-medium">{{ $router->name }}</td>
                                <td class="text-right font-semibold text-success">@userCurrency($router->total_income)</td>
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
                            <th class="text-right">Price</th>
                            <th class="text-right">Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentActivations as $activation)
                            <tr>
                                <td class="font-medium">{{ $activation->username }}</td>
                                <td><span class="badge badge-sm badge-info">{{ $activation->profile }}</span></td>
                                <td class="text-sm">{{ $activation->router_name }}</td>
                                <td class="text-right font-semibold text-success">@userCurrency($activation->price)</td>
                                <td class="text-right text-xs text-base-content/60">
                                    {{ \Carbon\Carbon::parse($activation->created_at)->diffForHumans() }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-base-content/60">No recent activations</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-mary-card>
    </div>

    {{-- Router & Voucher Management --}}
    <div class="grid gap-4 grid-cols-1 xl:grid-cols-2">
        {{-- Router Health --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <x-slot name="title">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-mary-icon name="o-server-stack" class="w-5 h-5 text-info" />
                        <span>Router Health</span>
                    </div>
                    <x-mary-button icon="o-arrow-right" class="btn-ghost btn-xs" href="{{ route('routers.index') }}"
                        wire:navigate />
                </div>
            </x-slot>
            <div class="grid grid-cols-2 gap-4">
                <div class="text-center p-3 bg-base-200">
                    <p class="text-xs text-base-content/60 mb-1">Total Routers</p>
                    <p class="text-2xl font-bold">{{ number_format($routerStats['total']) }}</p>
                </div>
                <div class="text-center p-3 bg-success/10">
                    <p class="text-xs text-base-content/60 mb-1">Active Vouchers</p>
                    <p class="text-2xl font-bold text-success">{{ number_format($voucherStats['active']) }}</p>
                </div>
                <div class="text-center p-3 bg-warning/10">
                    <p class="text-xs text-base-content/60 mb-1">Expired Vouchers</p>
                    <p class="text-2xl font-bold text-warning">{{ number_format($voucherStats['expired']) }}</p>
                </div>
                <div class="text-center p-3 bg-base-200">
                    <p class="text-xs text-base-content/60 mb-1">Total Vouchers</p>
                    <p class="text-2xl font-bold">{{ number_format($voucherStats['total']) }}</p>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-base-300">
                <p class="text-xs text-base-content/60">Monthly ISP Cost: <strong
                        class="text-base-content">@userCurrency($routerStats['monthlyExpense'])</strong></p>
            </div>
        </x-mary-card>

        {{-- Voucher Statistics --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <x-slot name="title">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-mary-icon name="o-ticket" class="w-5 h-5 text-success" />
                        <span>Voucher Statistics</span>
                    </div>
                    <x-mary-button icon="o-arrow-right" class="btn-ghost btn-xs"
                        href="{{ route('vouchers.index') }}" wire:navigate />
                </div>
            </x-slot>
            <div class="grid grid-cols-2 gap-4">
                <div class="text-center p-3 bg-base-200">
                    <p class="text-xs text-base-content/60 mb-1">Total Vouchers</p>
                    <p class="text-2xl font-bold">{{ number_format($voucherStats['total']) }}</p>
                </div>
                <div class="text-center p-3 bg-success/10">
                    <p class="text-xs text-base-content/60 mb-1">Active</p>
                    <p class="text-2xl font-bold text-success">{{ number_format($voucherStats['active']) }}</p>
                </div>
                <div class="text-center p-3 bg-warning/10">
                    <p class="text-xs text-base-content/60 mb-1">Expired</p>
                    <p class="text-2xl font-bold text-warning">{{ number_format($voucherStats['expired']) }}</p>
                </div>
                <div class="text-center p-3 bg-base-200">
                    <p class="text-xs text-base-content/60 mb-1">Generated Today</p>
                    <p class="text-2xl font-bold">{{ number_format($voucherStats['generatedToday']) }}</p>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-base-300">
                <p class="text-xs text-base-content/60">This Week: <strong
                        class="text-base-content">{{ number_format($voucherStats['generatedThisWeek']) }}</strong>
                    vouchers generated</p>
            </div>
        </x-mary-card>
    </div>

    {{-- Invoice Management & Package Distribution --}}
    <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 xl:grid-cols-3">
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
                <div class="pt-3 border-t border-base-300">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-base-content/60">Generated Today</span>
                        <span
                            class="text-sm font-semibold text-primary">{{ number_format($voucherStats['generatedToday']) }}</span>
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

        {{-- Recent Activity Summary --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <x-slot name="title">
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-chart-bar" class="w-5 h-5 text-info" />
                    <span>Quick Stats</span>
                </div>
            </x-slot>
            <div class="space-y-3">
                <div class="flex items-center justify-between p-2 bg-base-200">
                    <span class="text-sm text-base-content/70">Today's Activations</span>
                    <span class="font-bold text-success">{{ number_format($billingStats['todayActivations']) }}</span>
                </div>
                <div class="flex items-center justify-between p-2 bg-base-200">
                    <span class="text-sm text-base-content/70">Vouchers Generated Today</span>
                    <span class="font-bold text-primary">{{ number_format($voucherStats['generatedToday']) }}</span>
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

        {{-- Recent Invoices --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <x-slot name="title">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-mary-icon name="o-document-text" class="w-5 h-5 text-warning" />
                        <span>Recent Invoices</span>
                    </div>
                    <x-mary-button icon="o-arrow-right" class="btn-ghost btn-xs"
                        href="{{ route('billing.invoices') }}" wire:navigate />
                </div>
            </x-slot>
            <div class="space-y-3">
                @forelse ($recentInvoices as $invoice)
                    <div class="flex items-center justify-between p-3 border border-base-300 bg-base-200/50"
                        wire:key="invoice-{{ $invoice->id }}">
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-sm">#{{ $invoice->id }}</p>
                            <p class="text-xs text-base-content/60 mt-1">{{ ucfirst($invoice->category) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-sm">BDT {{ number_format($invoice->amount, 2) }}</p>
                            <x-mary-badge value="{{ ucfirst($invoice->status) }}"
                                class="badge-sm mt-1 {{ $invoice->status === 'completed' ? 'badge-success' : ($invoice->status === 'pending' ? 'badge-warning' : 'badge-error') }}" />
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-base-content/70 text-center py-4">No invoices yet.</p>
                @endforelse
            </div>
        </x-mary-card>
    </div>
</div>
