<?php

namespace App\Livewire\Router;

use App\MikroTik\Actions\HotspotProfileManager;
use App\MikroTik\Actions\RouterDiagnostics;
use App\MikroTik\Installer\ScriptInstaller;
use App\Models\Router;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Mary\Traits\Toast;

class Show extends Component
{
    use Toast;

    public Router $router;

    public array $resource = [];
    public array $clock = [];
    public array $hotspotCounts = [];
    public array $scriptStatuses = [];
    public array $profiles = [];
    public array $interfaces = [];
    public string $interface = '';
    public array $trafficSeries = [];
    public array $trafficChart = [];
    public array $hotspotUserStats = [
        'all' => 0,
        'active' => 0,
        'expiring_today' => 0,
        'expiring_week' => 0,
    ];
    public array $activityStats = [
        'activated_today' => 0,
        'activated_week' => 0,
        'sales_today' => 0,
        'sales_week' => 0,
    ];
    public string $formattedUptime = 'N/A';

    public ?string $lastUpdated = null;
    public ?string $errorMessage = null;

    protected $queryString = [
        'interface' => ['except' => ''],
    ];

    public function mount(Router $router): void
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Unauthorized action.');
        }

        $this->router = $router;

        $this->interfaces = $this->fetchInterfaces();
        $this->interface = $this->interface ?: ($this->interfaces[0]['name'] ?? '');

        $this->refreshRealtimeData();
    }

    public function refreshRealtimeData(): void
    {
        try {
            $diag = $this->diagnostics();

            $this->resource = $diag->systemResource($this->router);
            $this->clock = $diag->systemClock($this->router);
            $this->scriptStatuses = $diag->scriptStatuses($this->router);
            $this->hotspotCounts = $diag->hotspotCounts($this->router);
            // $this->logs = $diag->hotspotLogs($this->router, 10);
            $this->profiles = $this->loadProfiles();
            $this->hotspotUserStats = $this->computeHotspotUserStats();
            $this->activityStats = $this->computeActivityStats();
            $this->interfaces = $this->fetchInterfaces();
            $this->formattedUptime = $this->formatUptime($this->resource['uptime'] ?? null);

            $this->interface = $this->interface ?: ($this->interfaces[0]['name'] ?? '');
            if ($this->interface) {
                $traffic = $diag->interfaceTraffic($this->router, $this->interface);
                if ($traffic) {
                    $traffic['label'] = now()->format('H:i:s');
                    $this->trafficSeries[] = $traffic;
                    $this->trafficSeries = array_slice($this->trafficSeries, -12);
                }
            }

            $this->trafficChart = $this->buildChartData();
            $this->lastUpdated = now()->toDateTimeString();
            $this->errorMessage = null;
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
            $this->error('Router refresh failed: ' . $e->getMessage());
        }
    }

    /**
     * Reset traffic series when user selects another interface.
     */
    public function updatedInterface(): void
    {
        $this->trafficSeries = [];
        $this->refreshRealtimeData();
    }

    protected function fetchInterfaces(): array
    {
        try {
            return $this->diagnostics()->interfaces($this->router);
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
            $this->error('Failed to load interfaces: ' . $e->getMessage());
            return [];
        }
    }

    protected function diagnostics(): RouterDiagnostics
    {
        return app(RouterDiagnostics::class);
    }

    protected function buildChartData(): array
    {
        $labels = array_map(fn($row) => $row['label'] ?? '', $this->trafficSeries);
        $rx = array_map(fn($row) => round(($row['rx'] ?? 0) / 1_000_000, 2), $this->trafficSeries);
        $tx = array_map(fn($row) => round(($row['tx'] ?? 0) / 1_000_000, 2), $this->trafficSeries);

        return [
            'type' => 'line',
            'data' => [
                'labels'   => $labels,
                'datasets' => [
                    [
                        'label'           => 'Rx Mbps',
                        'data'            => $rx,
                        'borderColor'     => '#2563eb',
                        'backgroundColor' => 'rgba(37,99,235,0.2)',
                        'tension'         => 0.3,
                        'fill'            => true,
                    ],
                    [
                        'label'           => 'Tx Mbps',
                        'data'            => $tx,
                        'borderColor'     => '#16a34a',
                        'backgroundColor' => 'rgba(22,163,74,0.2)',
                        'tension'         => 0.3,
                        'fill'            => true,
                    ],
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins'    => [
                    'legend' => [
                        'display' => true,
                        'position' => 'bottom',
                    ],
                ],
                'scales' => [
                    'x' => [
                        'display' => true,
                    ],
                    'y' => [
                        'display' => true,
                        'title'   => [
                            'display' => true,
                            'text'    => 'Mbps',
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function profileManager(): HotspotProfileManager
    {
        return app(HotspotProfileManager::class);
    }

    protected function loadProfiles(): array
    {
        try {
            $profiles = $this->profileManager()->listProfiles($this->router);

            return is_array($profiles) ? $profiles : [];
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
            $this->error('Failed to load profiles: ' . $e->getMessage());
            return [];
        }
    }

    protected function computeHotspotUserStats(): array
    {
        $base = $this->router->vouchers()->where('is_radius', false);
        $today = Carbon::today();
        $startWeek = Carbon::now()->startOfWeek();
        $endWeek = Carbon::now()->endOfWeek();

        return [
            'all'            => (clone $base)->count(),
            'active'         => (clone $base)->where('status', 'active')->count(),
            'expiring_today' => (clone $base)->whereDate('expires_at', $today)->count(),
            'expiring_week'  => (clone $base)->whereBetween('expires_at', [$startWeek, $endWeek])->count(),
        ];
    }

    protected function computeActivityStats(): array
    {
        $base = $this->router->vouchers()->where('is_radius', false);
        $today = Carbon::today();
        $startWeek = Carbon::now()->startOfWeek();
        $endWeek = Carbon::now()->endOfWeek();

        return [
            'activated_today' => (clone $base)->whereDate('activated_at', $today)->count(),
            'activated_week'  => (clone $base)->whereBetween('activated_at', [$startWeek, $endWeek])->count(),
            'sales_today'     => (clone $base)->whereDate('created_at', $today)->count(),
            'sales_week'      => (clone $base)->whereBetween('created_at', [$startWeek, $endWeek])->count(),
        ];
    }

    protected function formatUptime(?string $uptime): string
    {
        if (!$uptime) {
            return 'N/A';
        }

        preg_match_all('/(\d+)([wdhms])/', strtolower($uptime), $matches, PREG_SET_ORDER);

        $segments = [
            'w' => 0,
            'd' => 0,
        ];

        foreach ($matches as $match) {
            $value = (int) ($match[1] ?? 0);
            $unit = $match[2] ?? '';
            if (isset($segments[$unit])) {
                $segments[$unit] += $value;
            }
        }

        $months = intdiv($segments['w'], 4);
        $weeks = $segments['w'] % 4;
        $days = $segments['d'];

        $parts = [];
        if ($months) {
            $parts[] = $months . ' ' . ($months > 1 ? 'months' : 'month');
        }
        if ($weeks) {
            $parts[] = $weeks . ' ' . ($weeks > 1 ? 'weeks' : 'week');
        }
        if ($days || empty($parts)) {
            $parts[] = $days . ' ' . ($days === 1 ? 'day' : 'days');
        }

        return implode(' ', $parts);
    }

    public function render(): View
    {
        return view('livewire.router.show');
    }

    public function syncScripts(): void
    {
        try {
            /** @var ScriptInstaller $installer */
            $installer = app(ScriptInstaller::class);
            $router = $this->router;

            $pullInactiveUrl = route('mikrotik.pullInactiveUsers');
            $pullActiveUrl  = route('mikrotik.pullActiveUsers');
            $pushUrl        = route('mikrotik.pushActiveUsers');
            $orphanUserUrl  = route('mikrotik.checkUser');
            $pullProfiles   = route('mikrotik.pullProfiles');

            $installer->installPullInactiveUsersScript($router, $pullInactiveUrl);
            $installer->installPullActiveUsersScript($router, $pullActiveUrl);
            $installer->installPushActiveUsersScript($router, $pushUrl);
            $installer->installRemoveOrphanUsersScript($router, $orphanUserUrl);
            $installer->installProfileOnLoginScript($router);
            $installer->installPullProfilesScript($router, $pullProfiles);

            $installer->upsertScheduler($router, 'RADTik-PullInactive', '5m', '/system script run "RADTik-pull-inactive-users"');
            $installer->upsertScheduler($router, 'RADTik-PullActiveUsers', '30m', '/system script run "RADTik-pull-active-users"');
            $installer->upsertScheduler($router, 'RADTik-PushActive', '1m', '/system script run "RADTik-push-active-users"');
            $installer->upsertScheduler($router, 'RADTik-RemoveOrphans', '1h', '/system script run "RADTik-remove-orphan-users"');
            $installer->upsertScheduler($router, 'RADTik-PullProfiles', '10m', '/system script run "RADTik-pull-profiles"');
            $installer->upsertScheduler($router, 'RADTik-RemoveOrphanProfiles', '1h', '/system script run "RADTik-remove-orphan-profiles"');

            $this->success('Scripts synced successfully.');
        } catch (\Throwable $e) {
            $this->error('Failed to sync scripts: ' . $e->getMessage());
        }
    }
}
