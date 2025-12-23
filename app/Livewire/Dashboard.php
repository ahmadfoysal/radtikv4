<?php

namespace App\Livewire;

use App\Models\Invoice;
use App\Models\ResellerRouter;
use App\Models\Router;
use App\Models\User;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    // Public chart properties for MaryUI
    public array $incomeChart = [];
    public array $activationChart = [];
    public array $profileIncomeChart = [];

    public function render(): View
    {
        $user = Auth::user();

        abort_unless($user, 401);

        if ($user->isSuperAdmin()) {
            return view('livewire.dashboard.superadmin', $this->dataForSuperAdmin())
                ->title(__('Dashboard'));
        }

        if ($user->isAdmin()) {
            $data = $this->dataForAdmin($user);

            // Set chart data as public properties for MaryUI
            $this->incomeChart = $data['incomeChart'] ?? [];
            $this->activationChart = $data['activationChart'] ?? [];
            $this->profileIncomeChart = $data['profileIncomeChart'] ?? [];

            return view('livewire.dashboard.admin', $data)
                ->title(__('Dashboard'));
        }

        if ($user->isReseller()) {
            return view('livewire.dashboard.reseller', $this->dataForReseller($user))
                ->title(__('Dashboard'));
        }

        return view('livewire.dashboard.guest')
            ->title(__('Dashboard'));
    }

    /**
     * Metrics for super administrators.
     */
    protected function dataForSuperAdmin(): array
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $trendStart = Carbon::now()->subDays(6)->startOfDay();

        $adminQuery = User::role('admin');
        $adminStats = [
            'total' => (clone $adminQuery)->count(),
            'active' => (clone $adminQuery)->where('is_active', true)->count(),
            'registeredToday' => (clone $adminQuery)->whereDate('created_at', $today)->count(),
            'lowBalance' => (clone $adminQuery)->where('balance', '<', 50)->count(),
        ];

        $resellerQuery = User::role('reseller');
        $resellerStats = [
            'total' => (clone $resellerQuery)->count(),
            'active' => (clone $resellerQuery)->where('is_active', true)->count(),
        ];

        $routers = Router::select('id', 'name', 'user_id', 'monthly_isp_cost', 'created_at')
            ->with('user:id,name')
            ->get();

        $routerOverview = [
            'total' => $routers->count(),
            'withSubscription' => $routers->filter(fn($router) => $router->user?->hasActiveSubscription())->count(),
            'totalIspCost' => $routers->sum('monthly_isp_cost'),
        ];

        // Group routers by their owner's subscription package
        $packageBreakdown = $routers
            ->filter(fn($router) => $router->user?->activeSubscription())
            ->groupBy(fn($router) => $router->user->activeSubscription()->package->name ?? 'No Subscription')
            ->map(fn(Collection $group) => [
                'count' => $group->count(),
                'package' => $group->first()->user->activeSubscription()->package->name ?? 'N/A',
            ])
            ->sortByDesc('count')
            ->take(8);

        $salesQuery = Invoice::query()->where('status', 'completed');

        $salesSummary = [
            'today' => (clone $salesQuery)->whereDate('created_at', $today)->sum('amount'),
            'month' => (clone $salesQuery)->where('created_at', '>=', $startOfMonth)->sum('amount'),
            'pending' => Invoice::where('status', 'pending')->sum('amount'),
        ];

        $categoryBreakdown = (clone $salesQuery)
            ->select('category', DB::raw('COUNT(*) as invoices'), DB::raw('SUM(amount) as total_amount'))
            ->groupBy('category')
            ->orderByDesc('total_amount')
            ->get();

        $trendRaw = (clone $salesQuery)
            ->where('created_at', '>=', $trendStart)
            ->selectRaw('DATE(created_at) as day, SUM(amount) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $revenueTrend = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $trendStart->copy()->addDays($i);
            $revenueTrend[] = [
                'label' => $day->format('M d'),
                'value' => (float) ($trendRaw[$day->toDateString()] ?? 0),
            ];
        }

        $recentAdmins = User::role('admin')
            ->latest()
            ->take(5)
            ->get(['id', 'name', 'email', 'is_active', 'balance', 'created_at']);

        $recentInvoices = Invoice::with('user:id,name')
            ->latest()
            ->take(6)
            ->get(['id', 'user_id', 'amount', 'status', 'category', 'created_at']);

        return compact(
            'adminStats',
            'resellerStats',
            'routerOverview',
            'packageBreakdown',
            'salesSummary',
            'categoryBreakdown',
            'revenueTrend',
            'recentAdmins',
            'recentInvoices'
        );
    }

    /**
     * Metrics for admins (router owners).
     */
    protected function dataForAdmin(User $user): array
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $trendStart = Carbon::now()->subDays(6)->startOfDay();

        $routerQuery = $user->routers()
            ->with(['zone:id,name'])
            ->withCount([
                'vouchers as active_vouchers_count' => fn($q) => $q->where('status', 'active'),
                'vouchers as expired_vouchers_count' => fn($q) => $q->where('status', 'expired'),
            ]);

        $routers = $routerQuery
            ->get([
                'routers.id',
                'routers.name',
                'routers.address',
                'routers.monthly_isp_cost',
                'routers.created_at',
                'routers.login_address',
                'routers.zone_id',
            ]);

        $routerIds = $routers->pluck('id');

        $routerStats = [
            'total' => $routers->count(),
            'expiringWeek' => 0, // No longer tracking router expiry
            'expiringToday' => 0, // No longer tracking router expiry
            'withoutPackage' => 0, // No longer tracking router packages
            'monthlyExpense' => $routers->sum('monthly_isp_cost'),
        ];

        // Billing & Accounting Metrics from VoucherLog
        $voucherLogQuery = DB::table('voucher_logs')
            ->whereIn('router_id', $routerIds)
            ->where('event_type', 'activated');

        // Today's income from activations
        $todayIncome = (clone $voucherLogQuery)
            ->whereDate('created_at', $today)
            ->sum('price');

        // This month's income
        $monthIncome = (clone $voucherLogQuery)
            ->where('created_at', '>=', $startOfMonth)
            ->sum('price');

        // Today's activations count
        $todayActivations = (clone $voucherLogQuery)
            ->whereDate('created_at', $today)
            ->count();

        // Monthly expense (ISP costs)
        $monthlyExpense = $routerStats['monthlyExpense'];

        // Net profit (monthly)
        $netProfit = $monthIncome - $monthlyExpense;

        $billingStats = [
            'todayIncome' => $todayIncome,
            'monthIncome' => $monthIncome,
            'todayActivations' => $todayActivations,
            'monthlyExpense' => $monthlyExpense,
            'netProfit' => $netProfit,
        ];

        // Income by profile (top 5)
        $incomeByProfile = (clone $voucherLogQuery)
            ->where('created_at', '>=', $startOfMonth)
            ->select('profile', DB::raw('SUM(price) as total_income'), DB::raw('COUNT(*) as activations'))
            ->groupBy('profile')
            ->orderByDesc('total_income')
            ->limit(5)
            ->get();

        // Activation trend (last 7 days)
        $activationTrend = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $trendStart->copy()->addDays($i);
            $count = DB::table('voucher_logs')
                ->whereIn('router_id', $routerIds)
                ->where('event_type', 'activated')
                ->whereDate('created_at', $day)
                ->count();

            $activationTrend[] = [
                'label' => $day->format('M d'),
                'value' => $count,
            ];
        }

        // Income trend (last 7 days)
        $incomeTrend = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $trendStart->copy()->addDays($i);
            $income = DB::table('voucher_logs')
                ->whereIn('router_id', $routerIds)
                ->where('event_type', 'activated')
                ->whereDate('created_at', $day)
                ->sum('price');

            $incomeTrend[] = [
                'label' => $day->format('M d'),
                'value' => (float) $income,
            ];
        }

        // Top routers by income
        $topRouters = DB::table('voucher_logs')
            ->join('routers', 'voucher_logs.router_id', '=', 'routers.id')
            ->whereIn('voucher_logs.router_id', $routerIds)
            ->where('voucher_logs.event_type', 'activated')
            ->where('voucher_logs.created_at', '>=', $startOfMonth)
            ->select('routers.name', DB::raw('SUM(voucher_logs.price) as total_income'))
            ->groupBy('routers.name', 'routers.id')
            ->orderByDesc('total_income')
            ->limit(5)
            ->get();

        // Group routers by zone
        $routerUsage = $routers
            ->groupBy(fn($router) => $router->zone?->name ?? 'No Zone')
            ->map(fn(Collection $group) => $group->count())
            ->sortByDesc(fn($count) => $count)
            ->take(6);

        $recentRouters = $routers->sortByDesc('created_at')->take(5);

        $resellerQuery = User::role('reseller')->where('admin_id', $user->id);
        $resellerTotal = (clone $resellerQuery)->count();
        $resellerActive = (clone $resellerQuery)->where('is_active', true)->count();
        $resellerIds = (clone $resellerQuery)->pluck('id');
        $assignedResellers = $resellerIds->isEmpty()
            ? 0
            : ResellerRouter::whereIn('reseller_id', $resellerIds)->distinct('reseller_id')->count();

        $resellerStats = [
            'total' => $resellerTotal,
            'active' => $resellerActive,
            'withRouters' => $assignedResellers,
        ];

        $recentInvoices = Invoice::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get(['id', 'amount', 'status', 'category', 'created_at']);

        // No router-specific alerts - subscriptions are now at user level
        $routerAlerts = collect([]);

        $balance = $user->balance ?? 0;

        // Voucher Statistics
        $voucherQuery = Voucher::whereIn('router_id', $routerIds);
        $startOfWeek = Carbon::now()->startOfWeek();

        $voucherStats = [
            'total' => (clone $voucherQuery)->count(),
            'active' => (clone $voucherQuery)->where('status', 'active')->count(),
            'expired' => (clone $voucherQuery)->where('status', 'expired')->count(),
            'inactive' => (clone $voucherQuery)->where('status', 'inactive')->count(),
            'generatedToday' => (clone $voucherQuery)->whereDate('created_at', $today)->count(),
            'generatedThisWeek' => (clone $voucherQuery)->whereBetween('created_at', [$startOfWeek, Carbon::now()])->count(),
        ];

        $recentVouchers = (clone $voucherQuery)
            ->latest()
            ->take(5)
            ->get(['id', 'username', 'status', 'router_id', 'created_at'])
            ->load('router:id,name');

        // Recent activations with income
        $recentActivations = DB::table('voucher_logs')
            ->whereIn('router_id', $routerIds)
            ->where('event_type', 'activated')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['username', 'profile', 'price', 'router_name', 'created_at']);

        // Income Trend Chart
        $incomeChart = [
            'type' => 'line',
            'data' => [
                'labels' => array_column($incomeTrend, 'label'),
                'datasets' => [[
                    'label' => 'Income (BDT)',
                    'data' => array_column($incomeTrend, 'value'),
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                    'pointRadius' => 4,
                    'pointHoverRadius' => 6,
                ]],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['display' => true, 'position' => 'top'],
                ],
                'scales' => [
                    'y' => ['beginAtZero' => true],
                ],
            ],
        ];

        // Activation Trend Chart
        $activationChart = [
            'type' => 'bar',
            'data' => [
                'labels' => array_column($activationTrend, 'label'),
                'datasets' => [[
                    'label' => 'Activations',
                    'data' => array_column($activationTrend, 'value'),
                    'backgroundColor' => '#3b82f6',
                    'borderColor' => '#2563eb',
                    'borderWidth' => 1,
                ]],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['display' => true, 'position' => 'top'],
                ],
                'scales' => [
                    'y' => ['beginAtZero' => true],
                ],
            ],
        ];

        // Income by Profile Pie Chart
        $profileIncomeChart = [];
        if ($incomeByProfile->isNotEmpty()) {
            $profileIncomeChart = [
                'type' => 'pie',
                'data' => [
                    'labels' => $incomeByProfile->pluck('profile')->toArray(),
                    'datasets' => [[
                        'data' => $incomeByProfile->pluck('total_income')->toArray(),
                        'backgroundColor' => [
                            'rgb(16, 185, 129)',
                            'rgb(59, 130, 246)',
                            'rgb(245, 158, 11)',
                            'rgb(239, 68, 68)',
                            'rgb(139, 92, 246)',
                        ],
                    ]],
                ],
                'options' => [
                    'responsive' => true,
                    'maintainAspectRatio' => false,
                    'plugins' => [
                        'legend' => ['display' => true, 'position' => 'bottom'],
                    ],
                ],
            ];
        }

        // Invoice Statistics
        $invoiceQuery = Invoice::where('user_id', $user->id);
        $startOfMonth = Carbon::now()->startOfMonth();

        $invoiceStats = [
            'total' => (clone $invoiceQuery)->count(),
            'paid' => (clone $invoiceQuery)->where('status', 'completed')->count(),
            'pending' => (clone $invoiceQuery)->where('status', 'pending')->count(),
            'thisMonthRevenue' => (clone $invoiceQuery)
                ->where('status', 'completed')
                ->where('created_at', '>=', $startOfMonth)
                ->sum('amount'),
            'outstanding' => (clone $invoiceQuery)
                ->where('status', 'pending')
                ->sum('amount'),
        ];

        // Revenue Trend Chart Data (Last 7 days)
        $trendStart = Carbon::now()->subDays(6)->startOfDay();
        $revenueTrend = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $trendStart->copy()->addDays($i);
            $revenueTrend[] = [
                'label' => $day->format('M d'),
                'value' => (float) Invoice::where('user_id', $user->id)
                    ->where('status', 'completed')
                    ->whereDate('created_at', $day)
                    ->sum('amount'),
            ];
        }

        $revenueChart = [
            'type' => 'line',
            'data' => [
                'labels' => array_column($revenueTrend, 'label'),
                'datasets' => [[
                    'label' => 'Revenue (BDT)',
                    'data' => array_column($revenueTrend, 'value'),
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                    'pointRadius' => 4,
                    'pointHoverRadius' => 6,
                    'pointBackgroundColor' => '#3b82f6',
                    'pointBorderColor' => '#ffffff',
                    'pointBorderWidth' => 2,
                ]],
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
                        'ticks' => [
                            'callback' => 'function(value) { return "BDT " + value.toLocaleString(); }',
                        ],
                    ],
                ],
            ],
        ];

        return compact(
            'balance',
            'billingStats',
            'routerStats',
            'routerUsage',
            'recentRouters',
            'resellerStats',
            'recentInvoices',
            'routerAlerts',
            'voucherStats',
            'recentVouchers',
            'recentActivations',
            'incomeByProfile',
            'topRouters',
            'incomeChart',
            'activationChart',
            'profileIncomeChart'
        );
    }

    /**
     * Metrics for resellers scoped to their assignments.
     */
    protected function dataForReseller(User $user): array
    {
        $assignments = ResellerRouter::where('reseller_id', $user->id)
            ->with([
                'router' => function ($query) {
                    $query->select('id', 'name', 'address', 'login_address', 'zone_id', 'user_id',);
                    $query->with(['zone:id,name', 'user:id,name']);
                },
                'assignedBy:id,name',
            ])
            ->orderByDesc('created_at')
            ->get();

        $routerIds = $assignments->pluck('router_id')->filter()->unique();

        $routerStats = [
            'total' => $assignments->count(),
            'withLogin' => $assignments->filter(fn($assignment) => filled($assignment->router?->login_address))->count(),
        ];

        $zonesBreakdown = $assignments
            ->groupBy(fn($assignment) => $assignment->router?->zone?->name ?? 'Unassigned zone')
            ->map(fn(Collection $group) => $group->count())
            ->sortDesc();

        $voucherStats = [
            'total' => 0,
            'active' => 0,
            'expired' => 0,
        ];
        $recentVouchers = collect();

        if ($routerIds->isNotEmpty()) {
            $voucherBase = Voucher::whereIn('router_id', $routerIds);

            $voucherStats['total'] = (clone $voucherBase)->count();
            $voucherStats['active'] = (clone $voucherBase)->where('status', 'active')->count();
            $voucherStats['expired'] = (clone $voucherBase)->where('status', 'expired')->count();

            $recentVouchers = (clone $voucherBase)
                ->latest()
                ->take(6)
                ->get(['id', 'router_id', 'username', 'status', 'created_at'])
                ->load('router:id,name');
        }

        $recentAssignments = $assignments->take(6);

        return compact(
            'assignments',
            'routerStats',
            'zonesBreakdown',
            'voucherStats',
            'recentVouchers',
            'recentAssignments'
        );
    }

    // Router package methods removed - now using admin subscription system
    // Subscription expiry is tracked at the user level, not router level
}
