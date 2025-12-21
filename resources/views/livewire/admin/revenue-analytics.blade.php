<div class="space-y-6">
    {{-- Header with Period Filter --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold">Revenue & Analytics</h1>
            <p class="mt-1 text-sm text-base-content/70">Platform performance and financial insights</p>
        </div>

        <div class="w-56">
            <x-mary-select wire:model.live="period" :options="[
                ['id' => 'today', 'name' => 'Today'],
                ['id' => 'yesterday', 'name' => 'Yesterday'],
                ['id' => 'last_7_days', 'name' => 'Last 7 Days'],
                ['id' => 'last_30_days', 'name' => 'Last 30 Days'],
                ['id' => 'current_month', 'name' => 'Current Month'],
                ['id' => 'last_month', 'name' => 'Last Month'],
                ['id' => 'current_year', 'name' => 'Current Year'],
            ]" option-label="name" option-value="id" />
        </div>
    </div>

    {{-- Revenue Overview Cards --}}
    <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        <x-mary-card class="border border-base-300 bg-base-100">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <x-mary-icon name="o-currency-dollar" class="w-5 h-5 text-primary" />
                        <span class="text-sm font-medium text-base-content/70">Total Revenue</span>
                    </div>
                    <p class="text-3xl font-bold text-primary">৳{{ number_format($revenueMetrics['total_revenue'], 2) }}
                    </p>
                    @if ($revenueMetrics['growth_rate'] != 0)
                        <p class="text-xs text-base-content/60 mt-1">
                            @if ($revenueMetrics['growth_rate'] > 0)
                                <span class="text-success">↑
                                    {{ number_format($revenueMetrics['growth_rate'], 1) }}%</span>
                            @else
                                <span class="text-error">↓
                                    {{ number_format(abs($revenueMetrics['growth_rate']), 1) }}%</span>
                            @endif
                            vs previous
                        </p>
                    @endif
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="border border-base-300 bg-base-100">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <x-mary-icon name="o-wallet" class="w-5 h-5 text-success" />
                        <span class="text-sm font-medium text-base-content/70">Platform Balance</span>
                    </div>
                    <p class="text-3xl font-bold text-success">
                        ৳{{ number_format($balanceMetrics['total_platform_balance'], 2) }}</p>
                    <p class="text-xs text-base-content/60 mt-1">{{ $balanceMetrics['total_admins'] }} admins</p>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="border border-base-300 bg-base-100">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <x-mary-icon name="o-server" class="w-5 h-5 text-info" />
                        <span class="text-sm font-medium text-base-content/70">Total Routers</span>
                    </div>
                    <p class="text-3xl font-bold">{{ $subscriptionMetrics['active_routers'] }}</p>
                    <p class="text-xs text-base-content/60 mt-1">{{ $subscriptionMetrics['total_routers'] }}
                        subscriptions</p>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="border border-base-300 bg-base-100">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <x-mary-icon name="o-arrow-trending-up" class="w-5 h-5 text-warning" />
                        <span class="text-sm font-medium text-base-content/70">Monthly Revenue</span>
                    </div>
                    <p class="text-3xl font-bold text-warning">
                        ৳{{ number_format($subscriptionMetrics['monthly_recurring_revenue'], 2) }}</p>
                    <p class="text-xs text-base-content/60 mt-1">ARR:
                        ৳{{ number_format($subscriptionMetrics['annual_recurring_revenue'], 0) }}</p>
                </div>
            </div>
        </x-mary-card>
    </div>

    {{-- Additional Metrics --}}
    <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
        <x-mary-card class="border border-base-300 bg-base-100">
            <div class="flex items-center gap-2 mb-2">
                <x-mary-icon name="o-gift" class="w-5 h-5 text-accent" />
                <span class="text-sm font-medium text-base-content/70">Commission Paid</span>
            </div>
            <div class="text-2xl font-bold">৳{{ number_format($commissionMetrics['total_commission_paid'], 2) }}</div>
            <p class="text-xs text-base-content/60 mt-1">{{ $commissionMetrics['commission_count'] }} transactions</p>
        </x-mary-card>

        <x-mary-card class="border border-base-300 bg-base-100">
            <div class="flex items-center gap-2 mb-2">
                <x-mary-icon name="o-banknotes" class="w-5 h-5 text-error" />
                <span class="text-sm font-medium text-base-content/70">Total Expenses</span>
            </div>
            <div class="text-2xl font-bold text-error">৳{{ number_format($revenueMetrics['total_expenses'], 2) }}</div>
            <p class="text-xs text-base-content/60 mt-1">Subscriptions & renewals</p>
        </x-mary-card>

        <x-mary-card class="border border-base-300 bg-base-100">
            <div class="flex items-center gap-2 mb-2">
                <x-mary-icon name="o-chart-bar" class="w-5 h-5 text-info" />
                <span class="text-sm font-medium text-base-content/70">Net Revenue</span>
            </div>
            <div class="text-2xl font-bold text-info">৳{{ number_format($revenueMetrics['net_revenue'], 2) }}</div>
            <p class="text-xs text-base-content/60 mt-1">{{ $revenueMetrics['transaction_count'] }} transactions</p>
        </x-mary-card>
    </div>

    {{-- Charts Section --}}
    <div class="grid gap-4 grid-cols-1 xl:grid-cols-2">
        {{-- Revenue Trend Chart --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <x-slot name="title">
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-chart-bar" class="w-5 h-5 text-primary" />
                    <span>Monthly Revenue Trend</span>
                </div>
            </x-slot>
            <div class="h-64">
                @if (!empty($revenueTrendChart))
                    <x-mary-chart wire:model="revenueTrendChart" style="height: 250px; max-height: 250px;" />
                @else
                    <div class="flex items-center justify-center h-full text-base-content/50">
                        No data available
                    </div>
                @endif
            </div>
        </x-mary-card>

        {{-- Revenue by Package Chart --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <x-slot name="title">
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-cube" class="w-5 h-5 text-success" />
                    <span>Revenue by Package</span>
                </div>
            </x-slot>
            <div class="h-64">
                @if (!empty($packageRevenueChart))
                    <x-mary-chart wire:model="packageRevenueChart" style="height: 250px; max-height: 250px;" />
                @else
                    <div class="flex items-center justify-center h-full text-base-content/50">
                        No data available
                    </div>
                @endif
            </div>
        </x-mary-card>

        {{-- Active Subscriptions by Package --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <x-slot name="title">
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-server-stack" class="w-5 h-5 text-info" />
                    <span>Subscriptions by Package</span>
                </div>
            </x-slot>
            <div class="h-64">
                @if (!empty($subscriptionsByPackageChart))
                    <x-mary-chart wire:model="subscriptionsByPackageChart" style="height: 250px; max-height: 250px;" />
                @else
                    <div class="flex items-center justify-center h-full text-base-content/50">
                        No data available
                    </div>
                @endif
            </div>
        </x-mary-card>

        {{-- Revenue by Gateway Chart --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <x-slot name="title">
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-credit-card" class="w-5 h-5 text-warning" />
                    <span>Revenue by Payment Gateway</span>
                </div>
            </x-slot>
            <div class="h-64">
                @if (!empty($gatewayRevenueChart))
                    <x-mary-chart wire:model="gatewayRevenueChart" style="height: 250px; max-height: 250px;" />
                @else
                    <div class="flex items-center justify-center h-full text-base-content/50">
                        No data available
                    </div>
                @endif
            </div>
        </x-mary-card>
    </div>

    {{-- Top Admins Table --}}
    <x-mary-card class="border border-base-300 bg-base-100">
        <x-slot name="title">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-users" class="w-5 h-5 text-primary" />
                    <span>Top 10 Admins (By Spending)</span>
                </div>
            </div>
        </x-slot>
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead>
                    <tr class="border-b border-base-300">
                        <th class="text-xs text-base-content/70">Name</th>
                        <th class="text-xs text-base-content/70">Email</th>
                        <th class="text-xs text-base-content/70">Current Balance</th>
                        <th class="text-xs text-base-content/70">Commission %</th>
                        <th class="text-xs text-base-content/70">Total Spent (Period)</th>
                        <th class="text-xs text-base-content/70">Transactions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topAdmins as $admin)
                        <tr class="border-b border-base-300 hover:bg-base-200">
                            <td class="font-medium">{{ $admin['name'] }}</td>
                            <td class="text-sm text-base-content/70">{{ $admin['email'] }}</td>
                            <td>৳{{ number_format($admin['balance'], 2) }}</td>
                            <td>{{ number_format($admin['commission'], 2) }}%</td>
                            <td class="font-semibold">৳{{ number_format($admin['total_spent_amount'] ?? 0, 2) }}</td>
                            <td>{{ $admin['total_spent'] ?? 0 }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-base-content/50 py-4">No data available</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-mary-card>

    {{-- Recent Transactions --}}
    <x-mary-card class="border border-base-300 bg-base-100">
        <x-slot name="title">
            <div class="flex items-center gap-2">
                <x-mary-icon name="o-clock" class="w-5 h-5 text-warning" />
                <span>Recent Transactions</span>
            </div>
        </x-slot>
        <div class="overflow-x-auto">
            <table class="table table-sm table-zebra">
                <thead>
                    <tr class="border-b border-base-300">
                        <th class="text-xs text-base-content/70">ID</th>
                        <th class="text-xs text-base-content/70">User</th>
                        <th class="text-xs text-base-content/70">Type</th>
                        <th class="text-xs text-base-content/70">Category</th>
                        <th class="text-xs text-base-content/70">Amount</th>
                        <th class="text-xs text-base-content/70">Balance After</th>
                        <th class="text-xs text-base-content/70">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentTransactions as $transaction)
                        <tr>
                            <td class="text-xs">#{{ $transaction['id'] }}</td>
                            <td class="font-medium">{{ $transaction['user_name'] }}</td>
                            <td>
                                <span
                                    class="badge {{ $transaction['type'] === 'credit' ? 'badge-success' : 'badge-error' }} badge-sm">
                                    {{ ucfirst($transaction['type']) }}
                                </span>
                            </td>
                            <td class="text-sm">{{ str_replace('_', ' ', ucwords($transaction['category'])) }}</td>
                            <td class="font-semibold">৳{{ number_format($transaction['amount'], 2) }}</td>
                            <td class="text-sm">৳{{ number_format($transaction['balance_after'], 2) }}</td>
                            <td class="text-xs text-base-content/70">{{ $transaction['created_at'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-mary-card>
</div>
