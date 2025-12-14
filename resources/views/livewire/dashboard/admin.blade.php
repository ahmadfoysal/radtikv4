<div class="space-y-6">
    {{-- Top Stats Cards --}}
    <div class="grid gap-4 grid-cols-1 md:grid-cols-3">
        {{-- Wallet Balance --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <x-mary-icon name="o-banknotes" class="w-5 h-5 text-primary" />
                        <span class="text-sm font-medium text-base-content/70">Wallet Balance</span>
                    </div>
                    <p class="text-3xl font-bold text-primary">BDT {{ number_format($balance, 2) }}</p>
                    <p class="text-xs text-base-content/60 mt-1">Available for subscriptions</p>
                </div>
            </div>
            <div class="mt-4">
                <x-mary-button icon="o-plus" label="Add Balance" class="btn-sm btn-primary btn-block"
                    href="{{ route('billing.add-balance') }}" wire:navigate />
            </div>
        </x-mary-card>

        {{-- Routers Overview --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <x-mary-icon name="o-server-stack" class="w-5 h-5 text-info" />
                        <span class="text-sm font-medium text-base-content/70">Routers</span>
                    </div>
                    <p class="text-3xl font-bold">{{ number_format($routerStats['total']) }}</p>
                    <div class="flex flex-wrap gap-2 mt-2 text-xs">
                        <span class="text-success">Active: {{ $routerStats['total'] - $routerStats['expiringToday'] - $routerStats['expiringWeek'] }}</span>
                        <span class="text-warning">Expiring: {{ $routerStats['expiringWeek'] }}</span>
                    </div>
                </div>
            </div>
        </x-mary-card>

        {{-- Vouchers Overview --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <x-mary-icon name="o-ticket" class="w-5 h-5 text-success" />
                        <span class="text-sm font-medium text-base-content/70">Vouchers</span>
                    </div>
                    <p class="text-3xl font-bold">{{ number_format($voucherStats['total']) }}</p>
                    <div class="flex flex-wrap gap-2 mt-2 text-xs">
                        <span class="text-success">Active: {{ $voucherStats['active'] }}</span>
                        <span class="text-warning">Expired: {{ $voucherStats['expired'] }}</span>
                    </div>
                </div>
            </div>
        </x-mary-card>

        {{-- Invoices Overview --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <x-mary-icon name="o-document-text" class="w-5 h-5 text-warning" />
                        <span class="text-sm font-medium text-base-content/70">Invoices</span>
                    </div>
                    <p class="text-3xl font-bold">{{ number_format($invoiceStats['total']) }}</p>
                    <div class="flex flex-wrap gap-2 mt-2 text-xs">
                        <span class="text-success">Paid: {{ $invoiceStats['paid'] }}</span>
                        <span class="text-warning">Pending: {{ $invoiceStats['pending'] }}</span>
                    </div>
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
                    <p class="text-3xl font-bold">{{ number_format($resellerStats['total']) }}</p>
                    <div class="flex flex-wrap gap-2 mt-2 text-xs">
                        <span class="text-success">Active: {{ $resellerStats['active'] }}</span>
                        <span class="text-base-content/60">With Routers: {{ $resellerStats['withRouters'] }}</span>
                    </div>
                </div>
            </div>
        </x-mary-card>

        {{-- Monthly Revenue --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <x-mary-icon name="o-currency-dollar" class="w-5 h-5 text-success" />
                        <span class="text-sm font-medium text-base-content/70">Monthly Revenue</span>
                    </div>
                    <p class="text-3xl font-bold text-success">BDT {{ number_format($invoiceStats['thisMonthRevenue'], 2) }}</p>
                    <div class="flex flex-wrap gap-2 mt-2 text-xs">
                        <span class="text-base-content/60">Outstanding: BDT {{ number_format($invoiceStats['outstanding'], 2) }}</span>
                    </div>
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
            <x-mary-button icon="o-server" label="Add Router" class="btn-sm btn-info"
                href="{{ route('routers.create') }}" wire:navigate />
            <x-mary-button icon="o-document-plus" label="Create Invoice" class="btn-sm btn-warning"
                href="{{ route('billing.invoices') }}" wire:navigate />
            <x-mary-button icon="o-chart-bar" label="View Reports" class="btn-sm btn-ghost"
                href="#" wire:navigate />
        </div>
    </x-mary-card>

    {{-- Charts Section --}}
    <div class="grid gap-4 lg:grid-cols-2">
        {{-- Revenue Trend Chart --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <x-slot name="title">
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-banknotes" class="w-5 h-5 text-primary" />
                    <span>Revenue Trend (Last 7 Days)</span>
                </div>
            </x-slot>
            <div class="h-64">
                <canvas id="revenueChart" style="height: 250px;"></canvas>
            </div>
        </x-mary-card>

        {{-- Voucher Status Chart --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <x-slot name="title">
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-ticket" class="w-5 h-5 text-success" />
                    <span>Voucher Status Distribution</span>
                </div>
            </x-slot>
            <div class="h-64">
                <canvas id="voucherStatusChart" style="height: 250px;"></canvas>
            </div>
        </x-mary-card>
    </div>

    {{-- Router & Voucher Management --}}
    <div class="grid gap-4 lg:grid-cols-2">
        {{-- Router Health --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <x-slot name="title">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-mary-icon name="o-server-stack" class="w-5 h-5 text-info" />
                        <span>Router Health</span>
                    </div>
                    <x-mary-button icon="o-arrow-right" class="btn-ghost btn-xs"
                        href="{{ route('routers.index') }}" wire:navigate />
                </div>
            </x-slot>
            <div class="grid grid-cols-2 gap-4">
                <div class="text-center p-3 bg-base-200">
                    <p class="text-xs text-base-content/60 mb-1">Total Routers</p>
                    <p class="text-2xl font-bold">{{ number_format($routerStats['total']) }}</p>
                </div>
                <div class="text-center p-3 bg-warning/10">
                    <p class="text-xs text-base-content/60 mb-1">Expiring (7d)</p>
                    <p class="text-2xl font-bold text-warning">{{ number_format($routerStats['expiringWeek']) }}</p>
                </div>
                <div class="text-center p-3 bg-error/10">
                    <p class="text-xs text-base-content/60 mb-1">Expiring Today</p>
                    <p class="text-2xl font-bold text-error">{{ number_format($routerStats['expiringToday']) }}</p>
                </div>
                <div class="text-center p-3 bg-base-200">
                    <p class="text-xs text-base-content/60 mb-1">No Package</p>
                    <p class="text-2xl font-bold">{{ number_format($routerStats['withoutPackage']) }}</p>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-base-300">
                <p class="text-xs text-base-content/60">Monthly Expense: <strong class="text-base-content">BDT {{ number_format($routerStats['monthlyExpense'], 2) }}</strong></p>
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
                <p class="text-xs text-base-content/60">This Week: <strong class="text-base-content">{{ number_format($voucherStats['generatedThisWeek']) }}</strong> vouchers generated</p>
            </div>
        </x-mary-card>
    </div>

    {{-- Invoice Management & Package Distribution --}}
    <div class="grid gap-4 lg:grid-cols-3">
        {{-- Invoice Statistics --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <x-slot name="title">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-mary-icon name="o-document-text" class="w-5 h-5 text-warning" />
                        <span>Invoice Overview</span>
                    </div>
                    <x-mary-button icon="o-arrow-right" class="btn-ghost btn-xs"
                        href="{{ route('billing.invoices') }}" wire:navigate />
                </div>
            </x-slot>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-base-content/70">Total Invoices</span>
                    <span class="font-bold">{{ number_format($invoiceStats['total']) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-base-content/70">Paid</span>
                    <span class="font-bold text-success">{{ number_format($invoiceStats['paid']) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-base-content/70">Pending</span>
                    <span class="font-bold text-warning">{{ number_format($invoiceStats['pending']) }}</span>
                </div>
                <div class="pt-3 border-t border-base-300">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs text-base-content/60">This Month Revenue</span>
                        <span class="text-lg font-bold text-primary">BDT {{ number_format($invoiceStats['thisMonthRevenue'], 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-base-content/60">Outstanding</span>
                        <span class="text-sm font-semibold text-error">BDT {{ number_format($invoiceStats['outstanding'], 2) }}</span>
                    </div>
                </div>
            </div>
        </x-mary-card>

        {{-- Package Distribution --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <x-slot name="title">
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-cube" class="w-5 h-5 text-info" />
                    <span>Packages In Use</span>
                </div>
            </x-slot>
            <div class="space-y-2">
                @forelse ($routerUsage as $package => $count)
                    <div class="flex items-center justify-between text-sm" wire:key="admin-package-{{ \Illuminate\Support\Str::slug($package) }}">
                        <span class="truncate flex-1">{{ $package }}</span>
                        <x-mary-badge value="{{ number_format($count) }}" class="badge-primary badge-sm" />
                    </div>
                @empty
                    <p class="text-sm text-base-content/70 text-center py-4">No package data yet.</p>
                @endforelse
            </div>
        </x-mary-card>

        {{-- Upcoming Renewals --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <x-slot name="title">
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-clock" class="w-5 h-5 text-warning" />
                    <span>Upcoming Renewals</span>
                </div>
            </x-slot>
            <div class="space-y-3 max-h-64 overflow-y-auto">
                @forelse ($routerAlerts as $router)
                    <div class="flex items-start justify-between gap-2 p-2 bg-warning/5 border border-warning/20" wire:key="alert-{{ $router->id }}">
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-sm truncate">{{ $router->name }}</p>
                            @php
                                $endDate = $router->package['end_date'] ?? null;
                            @endphp
                            <p class="text-xs text-base-content/60 mt-1">
                                {{ $endDate ? 'Ending ' . \Carbon\Carbon::parse($endDate)->diffForHumans() : 'No package date' }}
                            </p>
                        </div>
                        <x-mary-badge value="Attention" class="badge-warning badge-sm" />
                    </div>
                @empty
                    <p class="text-sm text-base-content/70 text-center py-4">No upcoming renewals in the next 10 days.</p>
                @endforelse
            </div>
        </x-mary-card>
    </div>

    {{-- Recent Activity Section --}}
    <div class="grid gap-4 lg:grid-cols-3">
        {{-- Recent Routers --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <x-slot name="title">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-mary-icon name="o-server-stack" class="w-5 h-5 text-info" />
                        <span>Recent Routers</span>
                    </div>
                    <x-mary-button icon="o-arrow-right" class="btn-ghost btn-xs"
                        href="{{ route('routers.index') }}" wire:navigate />
                </div>
            </x-slot>
            <div class="space-y-3">
                @forelse ($recentRouters as $router)
                    <div class="p-3 border border-base-300 bg-base-200/50" wire:key="recent-router-{{ $router->id }}">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-sm truncate">{{ $router->name }}</p>
                                <p class="text-xs text-base-content/60 mt-1">{{ $router->address }}</p>
                                <div class="flex items-center gap-3 mt-2 text-xs text-base-content/60">
                                    <span>Zone: {{ $router->zone->name ?? 'N/A' }}</span>
                                    <span>•</span>
                                    <span class="text-success">Active: {{ $router->active_vouchers_count ?? 0 }}</span>
                                    <span>•</span>
                                    <span class="text-warning">Expired: {{ $router->expired_vouchers_count ?? 0 }}</span>
                                </div>
                            </div>
                            <span class="text-xs text-base-content/50 whitespace-nowrap">{{ $router->created_at->diffForHumans() }}</span>
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
                    <div class="flex items-center justify-between p-2 border border-base-300 bg-base-200/50" wire:key="recent-voucher-{{ $voucher->id }}">
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-sm truncate">{{ $voucher->username }}</p>
                            <p class="text-xs text-base-content/60 mt-1">{{ $voucher->router->name ?? 'N/A' }}</p>
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            <x-mary-badge 
                                value="{{ ucfirst($voucher->status) }}" 
                                class="badge-sm {{ $voucher->status === 'active' ? 'badge-success' : ($voucher->status === 'expired' ? 'badge-warning' : 'badge-ghost') }}" />
                            <span class="text-xs text-base-content/50">{{ $voucher->created_at->diffForHumans() }}</span>
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
                    <div class="flex items-center justify-between p-3 border border-base-300 bg-base-200/50" wire:key="invoice-{{ $invoice->id }}">
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-sm">#{{ $invoice->id }}</p>
                            <p class="text-xs text-base-content/60 mt-1">{{ ucfirst($invoice->category) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-sm">BDT {{ number_format($invoice->amount, 2) }}</p>
                            <x-mary-badge 
                                value="{{ ucfirst($invoice->status) }}" 
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

<script>
    document.addEventListener('livewire:init', function() {
        // Initialize charts after Livewire loads
        setTimeout(function() {
            // Revenue Trend Chart
            const revenueCtx = document.getElementById('revenueChart');
            if (revenueCtx && typeof Chart !== 'undefined') {
                const revenueChartData = @json($revenueChart);
                new Chart(revenueCtx, revenueChartData);
            }

            // Voucher Status Chart
            const voucherCtx = document.getElementById('voucherStatusChart');
            if (voucherCtx && typeof Chart !== 'undefined') {
                const voucherChartData = @json($voucherStatusChart);
                new Chart(voucherCtx, voucherChartData);
            }
        }, 100);
    });

    // Also initialize on page load if Livewire is already loaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(initCharts, 100);
        });
    } else {
        setTimeout(initCharts, 100);
    }

    function initCharts() {
        if (typeof Chart === 'undefined') return;

        // Revenue Trend Chart
        const revenueCtx = document.getElementById('revenueChart');
        if (revenueCtx && !revenueCtx.chart) {
            const revenueChartData = @json($revenueChart);
            revenueCtx.chart = new Chart(revenueCtx, revenueChartData);
        }

        // Voucher Status Chart
        const voucherCtx = document.getElementById('voucherStatusChart');
        if (voucherCtx && !voucherCtx.chart) {
            const voucherChartData = @json($voucherStatusChart);
            voucherCtx.chart = new Chart(voucherCtx, voucherChartData);
        }
    }
</script>
