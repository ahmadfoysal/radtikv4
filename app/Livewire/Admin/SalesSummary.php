<?php

namespace App\Livewire\Admin;

use App\Models\Router;
use App\Models\VoucherLog;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class SalesSummary extends Component
{
    use AuthorizesRequests;

    public string $period = 'current_month';
    public array $dateRange = [];

    // Chart data arrays
    public array $incomeByProfileChart = [];
    public array $activationTrendChart = [];
    public array $expenseBreakdownChart = [];
    public array $incomeVsExpenseChart = [];

    public function mount(): void
    {
        $this->authorize('view_sales_summary');
        $this->updateDateRange();
    }

    public function updatedPeriod(): void
    {
        $this->updateDateRange();
    }

    public function render(): View
    {
        $user = auth()->user();
        $accessibleRouterIds = $user->getAccessibleRouters()->pluck('id')->toArray();

        $data = [
            'incomeMetrics' => $this->getIncomeMetrics($accessibleRouterIds),
            'expenseMetrics' => $this->getExpenseMetrics($accessibleRouterIds),
            'activationStats' => $this->getActivationStats($accessibleRouterIds),
            'incomeByProfile' => $this->getIncomeByProfile($accessibleRouterIds),
            'topRouters' => $this->getTopRouters($accessibleRouterIds),
            'recentActivations' => $this->getRecentActivations($accessibleRouterIds),
        ];

        // Build chart data
        $this->incomeByProfileChart = $this->buildIncomeByProfileChart($data['incomeByProfile']);
        $this->activationTrendChart = $this->buildActivationTrendChart($accessibleRouterIds);
        $this->expenseBreakdownChart = $this->buildExpenseBreakdownChart($accessibleRouterIds);
        $this->incomeVsExpenseChart = $this->buildIncomeVsExpenseChart($accessibleRouterIds);

        return view('livewire.admin.sales-summary', $data);
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

    protected function getIncomeMetrics(array $routerIds): array
    {
        // Get total income from activated vouchers
        $activatedLogs = VoucherLog::where('event_type', 'activated')
            ->whereIn('router_id', $routerIds)
            ->whereBetween('created_at', [$this->dateRange['start'], $this->dateRange['end']])
            ->get();

        $totalIncome = $activatedLogs->sum('price');
        $totalActivations = $activatedLogs->count();

        // Previous period for comparison
        $periodLength = $this->dateRange['start']->diffInDays($this->dateRange['end']);
        $previousStart = $this->dateRange['start']->copy()->subDays($periodLength);
        $previousEnd = $this->dateRange['start']->copy();

        $previousIncome = VoucherLog::where('event_type', 'activated')
            ->whereIn('router_id', $routerIds)
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->sum('price');

        $growthRate = $previousIncome > 0
            ? (($totalIncome - $previousIncome) / $previousIncome) * 100
            : 0;

        return [
            'total_income' => $totalIncome,
            'total_activations' => $totalActivations,
            'average_per_activation' => $totalActivations > 0 ? $totalIncome / $totalActivations : 0,
            'growth_rate' => round($growthRate, 2),
        ];
    }

    protected function getExpenseMetrics(array $routerIds): array
    {
        // Get routers with their monthly expenses
        $routers = Router::whereIn('id', $routerIds)->get();

        // Calculate total monthly expenses for the period
        $totalMonthlyExpense = $routers->sum('monthly_isp_cost');

        // For multi-month periods, calculate proportional expenses
        $monthsInPeriod = max(1, $this->dateRange['start']->diffInMonths($this->dateRange['end']));
        $totalExpenses = $totalMonthlyExpense * $monthsInPeriod;

        return [
            'total_expenses' => $totalExpenses,
            'monthly_expense' => $totalMonthlyExpense,
            'router_count' => $routers->count(),
            'average_per_router' => $routers->count() > 0 ? $totalMonthlyExpense / $routers->count() : 0,
        ];
    }

    protected function getActivationStats(array $routerIds): array
    {
        $today = Carbon::today();
        $startWeek = Carbon::now()->startOfWeek();
        $endWeek = Carbon::now()->endOfWeek();

        $activatedToday = VoucherLog::where('event_type', 'activated')
            ->whereIn('router_id', $routerIds)
            ->whereDate('created_at', $today)
            ->count();

        $activatedWeek = VoucherLog::where('event_type', 'activated')
            ->whereIn('router_id', $routerIds)
            ->whereBetween('created_at', [$startWeek, $endWeek])
            ->count();

        $deletedPeriod = VoucherLog::where('event_type', 'deleted')
            ->whereIn('router_id', $routerIds)
            ->whereBetween('created_at', [$this->dateRange['start'], $this->dateRange['end']])
            ->count();

        return [
            'activated_today' => $activatedToday,
            'activated_week' => $activatedWeek,
            'deleted_period' => $deletedPeriod,
        ];
    }

    protected function getIncomeByProfile(array $routerIds): array
    {
        return VoucherLog::where('event_type', 'activated')
            ->whereIn('router_id', $routerIds)
            ->whereBetween('created_at', [$this->dateRange['start'], $this->dateRange['end']])
            ->select('profile', DB::raw('COUNT(*) as count'), DB::raw('SUM(price) as total'))
            ->whereNotNull('profile')
            ->groupBy('profile')
            ->orderByDesc('total')
            ->get()
            ->map(function ($item) {
                return [
                    'profile' => $item->profile,
                    'count' => $item->count,
                    'total' => $item->total,
                ];
            })
            ->toArray();
    }

    protected function getTopRouters(array $routerIds): array
    {
        return Router::whereIn('id', $routerIds)
            ->withCount(['voucherLogs as activations_count' => function ($query) {
                $query->where('event_type', 'activated')
                    ->whereBetween('created_at', [$this->dateRange['start'], $this->dateRange['end']]);
            }])
            ->withSum(['voucherLogs as income_total' => function ($query) {
                $query->where('event_type', 'activated')
                    ->whereBetween('created_at', [$this->dateRange['start'], $this->dateRange['end']]);
            }], 'price')
            ->orderByDesc('income_total')
            ->limit(10)
            ->get()
            ->map(function ($router) {
                return [
                    'id' => $router->id,
                    'name' => $router->name,
                    'address' => $router->address,
                    'monthly_expense' => $router->monthly_isp_cost,
                    'activations' => $router->activations_count ?? 0,
                    'income' => $router->income_total ?? 0,
                ];
            })
            ->toArray();
    }

    protected function getRecentActivations(array $routerIds): array
    {
        return VoucherLog::with('router')
            ->where('event_type', 'activated')
            ->whereIn('router_id', $routerIds)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function ($log) {
                return [
                    'username' => $log->username,
                    'profile' => $log->profile,
                    'price' => $log->price,
                    'validity_days' => $log->validity_days,
                    'router_name' => $log->router?->name ?? $log->router_name,
                    'created_at' => $log->created_at->format('M d, Y H:i'),
                ];
            })
            ->toArray();
    }

    protected function buildIncomeByProfileChart(array $profileData): array
    {
        if (empty($profileData)) {
            return [];
        }

        return [
            'type' => 'pie',
            'data' => [
                'labels' => array_column($profileData, 'profile'),
                'datasets' => [
                    [
                        'data' => array_column($profileData, 'total'),
                        'backgroundColor' => [
                            'rgb(59, 130, 246)',
                            'rgb(16, 185, 129)',
                            'rgb(251, 146, 60)',
                            'rgb(168, 85, 247)',
                            'rgb(236, 72, 153)',
                            'rgb(34, 197, 94)',
                            'rgb(239, 68, 68)',
                            'rgb(245, 158, 11)',
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

    protected function buildActivationTrendChart(array $routerIds): array
    {
        $days = [];
        $activations = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $days[] = $date->format('M d');

            $count = VoucherLog::where('event_type', 'activated')
                ->whereIn('router_id', $routerIds)
                ->whereDate('created_at', $date)
                ->count();

            $activations[] = $count;
        }

        return [
            'type' => 'line',
            'data' => [
                'labels' => $days,
                'datasets' => [
                    [
                        'label' => 'Activations',
                        'data' => $activations,
                        'borderColor' => 'rgb(16, 185, 129)',
                        'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                        'borderWidth' => 2,
                        'pointRadius' => 4,
                        'pointHoverRadius' => 6,
                        'pointBackgroundColor' => 'rgb(16, 185, 129)',
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

    protected function buildExpenseBreakdownChart(array $routerIds): array
    {
        $routers = Router::whereIn('id', $routerIds)
            ->where('monthly_isp_cost', '>', 0)
            ->orderByDesc('monthly_isp_cost')
            ->limit(10)
            ->get();

        if ($routers->isEmpty()) {
            return [];
        }

        return [
            'type' => 'bar',
            'data' => [
                'labels' => $routers->pluck('name')->toArray(),
                'datasets' => [
                    [
                        'label' => 'Monthly Expense (৳)',
                        'data' => $routers->pluck('monthly_isp_cost')->toArray(),
                        'backgroundColor' => 'rgba(239, 68, 68, 0.8)',
                        'borderColor' => 'rgb(239, 68, 68)',
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
                    ],
                ],
            ],
        ];
    }

    protected function buildIncomeVsExpenseChart(array $routerIds): array
    {
        $months = [];
        $income = [];
        $expenses = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            $months[] = $month->format('M Y');

            $monthIncome = VoucherLog::where('event_type', 'activated')
                ->whereIn('router_id', $routerIds)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('price');

            $income[] = $monthIncome;

            // Monthly expenses are constant per month
            $monthExpense = Router::whereIn('id', $routerIds)->sum('monthly_isp_cost');
            $expenses[] = $monthExpense;
        }

        return [
            'type' => 'line',
            'data' => [
                'labels' => $months,
                'datasets' => [
                    [
                        'label' => 'Income (৳)',
                        'data' => $income,
                        'borderColor' => 'rgb(16, 185, 129)',
                        'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                        'borderWidth' => 2,
                        'pointRadius' => 4,
                        'pointHoverRadius' => 6,
                        'pointBackgroundColor' => 'rgb(16, 185, 129)',
                        'tension' => 0.4,
                        'fill' => true,
                    ],
                    [
                        'label' => 'Expenses (৳)',
                        'data' => $expenses,
                        'borderColor' => 'rgb(239, 68, 68)',
                        'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                        'borderWidth' => 2,
                        'pointRadius' => 4,
                        'pointHoverRadius' => 6,
                        'pointBackgroundColor' => 'rgb(239, 68, 68)',
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
}
