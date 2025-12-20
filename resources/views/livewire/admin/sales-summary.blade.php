<div class="space-y-6">
    {{-- Header with Period Filter --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold">Sales Summary</h1>
            <p class="mt-1 text-sm text-base-content/70">Income from voucher activations and router expenses</p>
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

    {{-- Income & Expense Overview Cards --}}
    <div class="grid gap-4 grid-cols-1 md:grid-cols-2 lg:grid-cols-4">
        <x-mary-card class="border border-base-300 bg-base-100">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <x-mary-icon name="o-currency-dollar" class="w-5 h-5 text-success" />
                        <span class="text-sm font-medium text-base-content/70">Total Income</span>
                    </div>
                    <p class="text-3xl font-bold text-success">৳{{ number_format($incomeMetrics['total_income'], 2) }}
                    </p>
                    @if ($incomeMetrics['growth_rate'] != 0)
                        <p class="text-xs text-base-content/60 mt-1">
                            @if ($incomeMetrics['growth_rate'] > 0)
                                <span class="text-success">↑
                                    {{ number_format($incomeMetrics['growth_rate'], 1) }}%</span>
                            @else
                                <span class="text-error">↓
                                    {{ number_format(abs($incomeMetrics['growth_rate']), 1) }}%</span>
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
                        <x-mary-icon name="o-banknotes" class="w-5 h-5 text-error" />
                        <span class="text-sm font-medium text-base-content/70">Total Expenses</span>
                    </div>
                    <p class="text-3xl font-bold text-error">
                        ৳{{ number_format($expenseMetrics['total_expenses'], 2) }}</p>
                    <p class="text-xs text-base-content/60 mt-1">{{ $expenseMetrics['router_count'] }} routers</p>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="border border-base-300 bg-base-100">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <x-mary-icon name="o-ticket" class="w-5 h-5 text-primary" />
                        <span class="text-sm font-medium text-base-content/70">Total Activations</span>
                    </div>
                    <p class="text-3xl font-bold text-primary">{{ number_format($incomeMetrics['total_activations']) }}
                    </p>
                    <p class="text-xs text-base-content/60 mt-1">Avg:
                        ৳{{ number_format($incomeMetrics['average_per_activation'], 2) }}</p>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="border border-base-300 bg-base-100">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <x-mary-icon name="o-chart-bar" class="w-5 h-5 text-info" />
                        <span class="text-sm font-medium text-base-content/70">Net Profit</span>
                    </div>
                    <p class="text-3xl font-bold text-info">
                        ৳{{ number_format($incomeMetrics['total_income'] - $expenseMetrics['total_expenses'], 2) }}</p>
                    <p class="text-xs text-base-content/60 mt-1">Income - Expenses</p>
                </div>
            </div>
        </x-mary-card>
    </div>

    {{-- Additional Metrics --}}
    <div class="grid gap-4 grid-cols-1 md:grid-cols-3">
        <x-mary-card class="border border-base-300 bg-base-100">
            <div class="flex items-center gap-2 mb-2">
                <x-mary-icon name="o-check-circle" class="w-5 h-5 text-success" />
                <span class="text-sm font-medium text-base-content/70">Today's Activations</span>
            </div>
            <div class="text-2xl font-bold">{{ $activationStats['activated_today'] }}</div>
            <p class="text-xs text-base-content/60 mt-1">{{ $activationStats['activated_week'] }} this week</p>
        </x-mary-card>

        <x-mary-card class="border border-base-300 bg-base-100">
            <div class="flex items-center gap-2 mb-2">
                <x-mary-icon name="o-server" class="w-5 h-5 text-warning" />
                <span class="text-sm font-medium text-base-content/70">Monthly Router Expense</span>
            </div>
            <div class="text-2xl font-bold text-warning">৳{{ number_format($expenseMetrics['monthly_expense'], 2) }}
            </div>
            <p class="text-xs text-base-content/60 mt-1">Avg per router:
                ৳{{ number_format($expenseMetrics['average_per_router'], 2) }}</p>
        </x-mary-card>

        <x-mary-card class="border border-base-300 bg-base-100">
            <div class="flex items-center gap-2 mb-2">
                <x-mary-icon name="o-trash" class="w-5 h-5 text-error" />
                <span class="text-sm font-medium text-base-content/70">Deleted Vouchers</span>
            </div>
            <div class="text-2xl font-bold text-error">{{ $activationStats['deleted_period'] }}</div>
            <p class="text-xs text-base-content/60 mt-1">During this period</p>
        </x-mary-card>
    </div>

    {{-- Charts Section --}}
    <div class="grid gap-4 grid-cols-1 lg:grid-cols-2">
        {{-- Income by Profile Chart --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <x-slot name="title">
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-chart-pie" class="w-5 h-5 text-primary" />
                    <span>Income by Profile</span>
                </div>
            </x-slot>
            <div class="h-64">
                @if (!empty($incomeByProfileChart))
                    <x-mary-chart wire:model="incomeByProfileChart" style="height: 250px; max-height: 250px;" />
                @else
                    <div class="flex items-center justify-center h-full text-base-content/50">
                        No data available
                    </div>
                @endif
            </div>
        </x-mary-card>

        {{-- Activation Trend Chart --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <x-slot name="title">
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-chart-bar" class="w-5 h-5 text-success" />
                    <span>Activation Trend (Last 12 Days)</span>
                </div>
            </x-slot>
            <div class="h-64">
                @if (!empty($activationTrendChart))
                    <x-mary-chart wire:model="activationTrendChart" style="height: 250px; max-height: 250px;" />
                @else
                    <div class="flex items-center justify-center h-full text-base-content/50">
                        No data available
                    </div>
                @endif
            </div>
        </x-mary-card>

        {{-- Expense Breakdown Chart --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <x-slot name="title">
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-server-stack" class="w-5 h-5 text-error" />
                    <span>Top 10 Router Expenses</span>
                </div>
            </x-slot>
            <div class="h-64">
                @if (!empty($expenseBreakdownChart))
                    <x-mary-chart wire:model="expenseBreakdownChart" style="height: 250px; max-height: 250px;" />
                @else
                    <div class="flex items-center justify-center h-full text-base-content/50">
                        No expenses configured
                    </div>
                @endif
            </div>
        </x-mary-card>

        {{-- Income vs Expense Chart --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <x-slot name="title">
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-arrow-trending-up" class="w-5 h-5 text-info" />
                    <span>Income vs Expenses (Last 12 Months)</span>
                </div>
            </x-slot>
            <div class="h-64">
                @if (!empty($incomeVsExpenseChart))
                    <x-mary-chart wire:model="incomeVsExpenseChart" style="height: 250px; max-height: 250px;" />
                @else
                    <div class="flex items-center justify-center h-full text-base-content/50">
                        No data available
                    </div>
                @endif
            </div>
        </x-mary-card>
    </div>

    {{-- Income by Profile Table --}}
    <x-mary-card class="border border-base-300 bg-base-100">
        <x-slot name="title">
            <div class="flex items-center gap-2">
                <x-mary-icon name="o-rectangle-group" class="w-5 h-5 text-primary" />
                <span>Income Breakdown by Profile</span>
            </div>
        </x-slot>
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead>
                    <tr class="border-b border-base-300">
                        <th class="text-xs text-base-content/70">Profile</th>
                        <th class="text-xs text-base-content/70">Activations</th>
                        <th class="text-xs text-base-content/70">Total Income</th>
                        <th class="text-xs text-base-content/70">Avg per Activation</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($incomeByProfile as $profile)
                        <tr class="border-b border-base-300 hover:bg-base-200">
                            <td class="font-medium">{{ $profile['profile'] }}</td>
                            <td>{{ number_format($profile['count']) }}</td>
                            <td class="font-semibold text-success">৳{{ number_format($profile['total'], 2) }}</td>
                            <td>৳{{ number_format($profile['total'] / $profile['count'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-base-content/50 py-4">No activations in this
                                period
                            </td>
                        </tr>
                    @endforelse
                    @if (!empty($incomeByProfile))
                        <tr class="font-bold border-t-2 border-base-300">
                            <td>Total</td>
                            <td>{{ number_format(array_sum(array_column($incomeByProfile, 'count'))) }}</td>
                            <td class="text-success">
                                ৳{{ number_format(array_sum(array_column($incomeByProfile, 'total')), 2) }}</td>
                            <td>-</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </x-mary-card>

    {{-- Top Routers Table --}}
    <x-mary-card class="border border-base-300 bg-base-100">
        <x-slot name="title">
            <div class="flex items-center gap-2">
                <x-mary-icon name="o-server" class="w-5 h-5 text-warning" />
                <span>Top 10 Routers by Income</span>
            </div>
        </x-slot>
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead>
                    <tr class="border-b border-base-300">
                        <th class="text-xs text-base-content/70">Router</th>
                        <th class="text-xs text-base-content/70">Address</th>
                        <th class="text-xs text-base-content/70">Activations</th>
                        <th class="text-xs text-base-content/70">Income</th>
                        <th class="text-xs text-base-content/70">Monthly Expense</th>
                        <th class="text-xs text-base-content/70">Net Profit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topRouters as $router)
                        <tr class="border-b border-base-300 hover:bg-base-200">
                            <td class="font-medium">
                                <a href="{{ route('routers.show', $router['id']) }}" wire:navigate
                                    class="link link-primary">
                                    {{ $router['name'] }}
                                </a>
                            </td>
                            <td class="text-sm text-base-content/70">{{ $router['address'] }}</td>
                            <td>{{ number_format($router['activations']) }}</td>
                            <td class="font-semibold text-success">৳{{ number_format($router['income'], 2) }}</td>
                            <td class="text-error">৳{{ number_format($router['monthly_expense'], 2) }}</td>
                            <td class="font-semibold text-info">
                                ৳{{ number_format($router['income'] - $router['monthly_expense'], 2) }}</td>
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

    {{-- Recent Activations --}}
    <x-mary-card class="border border-base-300 bg-base-100">
        <x-slot name="title">
            <div class="flex items-center gap-2">
                <x-mary-icon name="o-clock" class="w-5 h-5 text-primary" />
                <span>Recent Activations</span>
            </div>
        </x-slot>
        <div class="overflow-x-auto">
            <table class="table table-sm table-zebra">
                <thead>
                    <tr class="border-b border-base-300">
                        <th class="text-xs text-base-content/70">Username</th>
                        <th class="text-xs text-base-content/70">Profile</th>
                        <th class="text-xs text-base-content/70">Router</th>
                        <th class="text-xs text-base-content/70">Price</th>
                        <th class="text-xs text-base-content/70">Validity</th>
                        <th class="text-xs text-base-content/70">Activated At</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentActivations as $activation)
                        <tr>
                            <td class="font-medium">{{ $activation['username'] }}</td>
                            <td class="text-sm">{{ $activation['profile'] }}</td>
                            <td class="text-sm">{{ $activation['router_name'] }}</td>
                            <td class="font-semibold text-success">৳{{ number_format($activation['price'], 2) }}</td>
                            <td>{{ $activation['validity_days'] }} days</td>
                            <td class="text-xs text-base-content/70">{{ $activation['created_at'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-mary-card>
</div>
