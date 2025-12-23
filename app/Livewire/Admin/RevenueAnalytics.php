<?php

namespace App\Livewire\Admin;

use App\Models\Invoice;
use App\Models\Package;
use App\Models\Router;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class RevenueAnalytics extends Component
{
    public string $period = 'current_month';

    public array $dateRange = [];

    public function mount(): void
    {
        $this->updateDateRange();
    }

    public function updatedPeriod(): void
    {
        $this->updateDateRange();
    }

    // Chart data arrays
    public array $revenueTrendChart = [];
    public array $packageRevenueChart = [];
    public array $subscriptionsByPackageChart = [];
    public array $gatewayRevenueChart = [];

    public function render(): View
    {
        $subscriptionMetrics = $this->getSubscriptionMetrics();

        $data = [
            'revenueMetrics' => $this->getRevenueMetrics(),
            'balanceMetrics' => $this->getBalanceMetrics(),
            'subscriptionMetrics' => $subscriptionMetrics,
            'commissionMetrics' => $this->getCommissionMetrics(),
            'topAdmins' => $this->getTopAdmins(),
            'recentTransactions' => $this->getRecentTransactions(),
        ];

        // Build chart data
        $this->revenueTrendChart = $this->buildRevenueTrendChart();
        $this->packageRevenueChart = $this->buildPackageRevenueChart();
        $this->subscriptionsByPackageChart = $this->buildSubscriptionsChart($subscriptionMetrics['subscriptions_by_package']);
        $this->gatewayRevenueChart = $this->buildGatewayRevenueChart();

        return view('livewire.admin.revenue-analytics', $data);
    }

    protected function updateDateRange(): void
    {
        $this->dateRange = match ($this->period) {
            'today' => [
                'start' => Carbon::today(),
                'end' => Carbon::today()->endOfDay(),
            ],
            'yesterday' => [
                'start' => Carbon::yesterday(),
                'end' => Carbon::yesterday()->endOfDay(),
            ],
            'last_7_days' => [
                'start' => Carbon::now()->subDays(7),
                'end' => Carbon::now(),
            ],
            'last_30_days' => [
                'start' => Carbon::now()->subDays(30),
                'end' => Carbon::now(),
            ],
            'current_month' => [
                'start' => Carbon::now()->startOfMonth(),
                'end' => Carbon::now()->endOfMonth(),
            ],
            'last_month' => [
                'start' => Carbon::now()->subMonth()->startOfMonth(),
                'end' => Carbon::now()->subMonth()->endOfMonth(),
            ],
            'current_year' => [
                'start' => Carbon::now()->startOfYear(),
                'end' => Carbon::now()->endOfYear(),
            ],
            default => [
                'start' => Carbon::now()->startOfMonth(),
                'end' => Carbon::now()->endOfMonth(),
            ],
        };
    }

    protected function getRevenueMetrics(): array
    {
        // Total revenue from payment gateways (credits)
        $totalRevenue = Invoice::where('type', 'credit')
            ->where('category', 'payment_gateway')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$this->dateRange['start'], $this->dateRange['end']])
            ->sum('amount');

        // Total expenses (subscription fees and renewals)
        $totalExpenses = Invoice::where('type', 'debit')
            ->whereIn('category', ['subscription', 'subscription_renewal'])
            ->where('status', 'completed')
            ->whereBetween('created_at', [$this->dateRange['start'], $this->dateRange['end']])
            ->sum('amount');

        // Previous period for comparison
        $periodLength = $this->dateRange['start']->diffInDays($this->dateRange['end']);
        $previousStart = $this->dateRange['start']->copy()->subDays($periodLength);
        $previousEnd = $this->dateRange['start']->copy();

        $previousRevenue = Invoice::where('type', 'credit')
            ->where('category', 'payment_gateway')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->sum('amount');

        $growthRate = $previousRevenue > 0
            ? (($totalRevenue - $previousRevenue) / $previousRevenue) * 100
            : 0;

        return [
            'total_revenue' => $totalRevenue,
            'total_expenses' => $totalExpenses,
            'net_revenue' => $totalRevenue - $totalExpenses,
            'growth_rate' => round($growthRate, 2),
            'transaction_count' => Invoice::where('type', 'credit')
                ->where('category', 'payment_gateway')
                ->whereBetween('created_at', [$this->dateRange['start'], $this->dateRange['end']])
                ->count(),
        ];
    }

    protected function getBalanceMetrics(): array
    {
        $admins = User::role('admin')->get();

        return [
            'total_platform_balance' => $admins->sum('balance'),
            'total_admins' => $admins->count(),
            'average_balance' => $admins->count() > 0 ? $admins->avg('balance') : 0,
            'admins_with_balance' => $admins->where('balance', '>', 0)->count(),
        ];
    }

    protected function getSubscriptionMetrics(): array
    {
        $totalRouters = Router::count();

        // Calculate MRR (Monthly Recurring Revenue) from admin subscriptions
        $mrr = \App\Models\Subscription::where('status', 'active')
            ->with('package')
            ->get()
            ->sum(function ($subscription) {
                return $subscription->package->price_monthly ?? 0;
            });

        // Count subscriptions by package (from user subscriptions, not router package)
        $subscriptionsByPackage = \App\Models\Subscription::where('status', 'active')
            ->with('package')
            ->get()
            ->groupBy(function ($subscription) {
                return $subscription->package->name ?? 'Unknown';
            })
            ->map(function ($group) {
                return $group->count();
            })
            ->toArray();

        return [
            'active_routers' => $totalRouters,
            'total_routers' => $totalRouters,
            'inactive_routers' => 0,
            'monthly_recurring_revenue' => $mrr,
            'annual_recurring_revenue' => $mrr * 12,
            'subscriptions_by_package' => $subscriptionsByPackage,
        ];
    }

    protected function getCommissionMetrics(): array
    {
        $totalCommissionPaid = Invoice::where('type', 'credit')
            ->where('category', 'commission')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$this->dateRange['start'], $this->dateRange['end']])
            ->sum('amount');

        return [
            'total_commission_paid' => $totalCommissionPaid,
            'commission_count' => Invoice::where('category', 'commission')
                ->whereBetween('created_at', [$this->dateRange['start'], $this->dateRange['end']])
                ->count(),
        ];
    }

    protected function getRevenueByGateway(): array
    {
        return Invoice::where('type', 'credit')
            ->where('category', 'payment_gateway')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$this->dateRange['start'], $this->dateRange['end']])
            ->get()
            ->groupBy(function ($invoice) {
                return $invoice->meta['gateway'] ?? 'unknown';
            })
            ->map(function ($group) {
                return [
                    'amount' => $group->sum('amount'),
                    'count' => $group->count(),
                ];
            })
            ->toArray();
    }

    protected function getRevenueByPackage(): array
    {
        return Invoice::where('type', 'debit')
            ->whereIn('category', ['subscription', 'subscription_renewal'])
            ->where('status', 'completed')
            ->whereBetween('created_at', [$this->dateRange['start'], $this->dateRange['end']])
            ->get()
            ->groupBy(function ($invoice) {
                return $invoice->meta['package_id'] ?? 'unknown';
            })
            ->map(function ($group) {
                $firstInvoice = $group->first();
                $packageName = 'Unknown Package';

                if (isset($firstInvoice->meta['package_id'])) {
                    $package = Package::find($firstInvoice->meta['package_id']);
                    $packageName = $package?->name ?? 'Unknown Package';
                }

                return [
                    'name' => $packageName,
                    'amount' => $group->sum('amount'),
                    'count' => $group->count(),
                ];
            })
            ->values()
            ->toArray();
    }

    protected function getMonthlyRevenueTrend(): array
    {
        $months = [];
        $revenue = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            $months[] = $month->format('M Y');
            $revenue[] = Invoice::where('type', 'credit')
                ->where('category', 'payment_gateway')
                ->where('status', 'completed')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('amount');
        }

        return [
            'labels' => $months,
            'data' => $revenue,
        ];
    }

    protected function getTopAdmins(): array
    {
        return User::role('admin')
            ->select('id', 'name', 'email', 'balance', 'commission')
            ->withCount(['invoices as total_spent' => function ($query) {
                $query->where('type', 'debit')
                    ->whereBetween('created_at', [$this->dateRange['start'], $this->dateRange['end']]);
            }])
            ->withSum(['invoices as total_spent_amount' => function ($query) {
                $query->where('type', 'debit')
                    ->whereBetween('created_at', [$this->dateRange['start'], $this->dateRange['end']]);
            }], 'amount')
            ->orderByDesc('total_spent_amount')
            ->limit(10)
            ->get()
            ->toArray();
    }

    protected function getRecentTransactions(): array
    {
        return Invoice::with('user:id,name,email')
            ->whereIn('category', ['payment_gateway', 'subscription', 'subscription_renewal', 'manual_adjustment'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'user_name' => $invoice->user->name ?? 'Unknown',
                    'type' => $invoice->type,
                    'category' => $invoice->category,
                    'amount' => $invoice->amount,
                    'balance_after' => $invoice->balance_after,
                    'created_at' => $invoice->created_at->format('M d, Y H:i'),
                ];
            })
            ->toArray();
    }

    protected function buildRevenueTrendChart(): array
    {
        $trendData = $this->getMonthlyRevenueTrend();

        return [
            'type' => 'line',
            'data' => [
                'labels' => $trendData['labels'],
                'datasets' => [
                    [
                        'label' => 'Revenue (৳)',
                        'data' => $trendData['data'],
                        'borderColor' => 'rgb(59, 130, 246)',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'borderWidth' => 2,
                        'pointRadius' => 4,
                        'pointHoverRadius' => 6,
                        'pointBackgroundColor' => 'rgb(59, 130, 246)',
                        'pointBorderColor' => '#ffffff',
                        'pointBorderWidth' => 2,
                        'tension' => 0.4,
                        'fill' => true,
                    ]
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => [
                        'display' => true,
                        'position' => 'top',
                    ],
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                    ],
                ],
            ],
        ];
    }

    protected function buildPackageRevenueChart(): array
    {
        $packageData = $this->getRevenueByPackage();

        if (empty($packageData)) {
            return [];
        }

        return [
            'type' => 'pie',
            'data' => [
                'labels' => array_column($packageData, 'name'),
                'datasets' => [
                    [
                        'data' => array_column($packageData, 'amount'),
                        'backgroundColor' => [
                            'rgb(59, 130, 246)',
                            'rgb(16, 185, 129)',
                            'rgb(251, 146, 60)',
                            'rgb(168, 85, 247)',
                            'rgb(236, 72, 153)',
                            'rgb(34, 197, 94)',
                        ],
                    ]
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => [
                        'display' => true,
                        'position' => 'right',
                    ],
                ],
            ],
        ];
    }

    protected function buildSubscriptionsChart(array $subscriptionData): array
    {
        if (empty($subscriptionData)) {
            return [];
        }

        return [
            'type' => 'bar',
            'data' => [
                'labels' => array_keys($subscriptionData),
                'datasets' => [
                    [
                        'label' => 'Active Routers',
                        'data' => array_values($subscriptionData),
                        'backgroundColor' => 'rgba(168, 85, 247, 0.8)',
                        'borderColor' => 'rgb(168, 85, 247)',
                        'borderWidth' => 1,
                    ]
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => [
                        'display' => false,
                    ],
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'ticks' => [
                            'stepSize' => 1,
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function buildGatewayRevenueChart(): array
    {
        $gatewayData = $this->getRevenueByGateway();

        if (empty($gatewayData)) {
            return [];
        }

        $labels = array_map('ucfirst', array_keys($gatewayData));
        $amounts = array_map(fn($item) => $item['amount'], $gatewayData);

        return [
            'type' => 'doughnut',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'data' => $amounts,
                        'backgroundColor' => [
                            'rgb(16, 185, 129)',
                            'rgb(251, 146, 60)',
                            'rgb(59, 130, 246)',
                            'rgb(236, 72, 153)',
                        ],
                    ]
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => [
                        'display' => true,
                        'position' => 'bottom',
                    ],
                ],
            ],
        ];
    }
}
