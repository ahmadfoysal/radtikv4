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
    public function render(): View
    {
        $user = Auth::user();

        abort_unless($user, 401);

        if ($user->isSuperAdmin()) {
            return view('livewire.dashboard.superadmin', $this->dataForSuperAdmin())
                ->title(__('Dashboard'));
        }

        if ($user->isAdmin()) {
            return view('livewire.dashboard.admin', $this->dataForAdmin($user))
                ->title(__('Dashboard'));
        }

        if ($user->isReseller()) {
            $this->authorize('view_dashboard');
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

        $routers = Router::select('id', 'name', 'package', 'user_id', 'monthly_expense', 'created_at')
            ->with('user:id,name')
            ->get();

        $routerOverview = [
            'total' => $routers->count(),
            'withPackage' => $routers->filter(fn($router) => ! empty($router->package))->count(),
            'expiringToday' => $routers->filter(fn($router) => $this->endsOn($router, $today))->count(),
            'expiringWeek' => $routers->filter(fn($router) => $this->endsWithinDays($router, 7))->count(),
        ];

        $packageBreakdown = $routers
            ->groupBy(fn($router) => $router->package['name'] ?? 'Unassigned')
            ->map(fn(Collection $group) => [
                'count' => $group->count(),
                'billing' => $group->first()->package['billing_cycle'] ?? null,
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
                'routers.package',
                'routers.monthly_expense',
                'routers.created_at',
                'routers.login_address',
                'routers.zone_id',
            ]);

        $routerStats = [
            'total' => $routers->count(),
            'expiringToday' => $routers->filter(fn($router) => $this->endsOn($router, Carbon::today()))->count(),
            'expiringWeek' => $routers->filter(fn($router) => $this->endsWithinDays($router, 7))->count(),
            'withoutPackage' => $routers->filter(fn($router) => empty($router->package))->count(),
            'monthlyExpense' => $routers->sum('monthly_expense'),
        ];

        $routerUsage = $routers
            ->groupBy(fn($router) => $router->package['name'] ?? 'Unassigned')
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

        $routerAlerts = $routers
            ->filter(fn($router) => $this->endsWithinDays($router, 10))
            ->sortBy(fn($router) => $this->packageEndDate($router->package ?? []))
            ->take(6);

        $balance = $user->balance ?? 0;

        // Voucher Statistics
        $routerIds = $routers->pluck('id');
        $voucherQuery = Voucher::whereIn('router_id', $routerIds);
        $today = Carbon::today();
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

        // Voucher Status Chart Data
        $voucherStatusChart = [
            'type' => 'doughnut',
            'data' => [
                'labels' => ['Active', 'Expired', 'Inactive', 'Disabled'],
                'datasets' => [[
                    'data' => [
                        $voucherStats['active'],
                        $voucherStats['expired'],
                        $voucherStats['inactive'],
                        (clone $voucherQuery)->where('status', 'disabled')->count(),
                    ],
                    'backgroundColor' => [
                        '#10b981', // success green
                        '#f59e0b', // warning orange
                        '#6b7280', // gray
                        '#ef4444', // error red
                    ],
                ]],
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
            'routerStats',
            'routerUsage',
            'recentRouters',
            'resellerStats',
            'recentInvoices',
            'routerAlerts',
            'voucherStats',
            'recentVouchers',
            'voucherStatusChart',
            'invoiceStats',
            'revenueChart'
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

    protected function endsOn($router, Carbon $day): bool
    {
        $end = $this->packageEndDate($router->package ?? null);

        return $end?->isSameDay($day) ?? false;
    }

    protected function endsWithinDays($router, int $days): bool
    {
        $end = $this->packageEndDate($router->package ?? null);

        if (! $end) {
            return false;
        }

        $now = Carbon::today();

        return $end->isBetween($now, $now->copy()->addDays($days));
    }

    protected function packageEndDate(?array $package): ?Carbon
    {
        if (! is_array($package) || empty($package['end_date'])) {
            return null;
        }

        try {
            return Carbon::parse($package['end_date'])->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
