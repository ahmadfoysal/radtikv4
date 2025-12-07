<?php

namespace App\Livewire\Router;

use App\MikroTik\Actions\HotspotProfileManager;
use App\MikroTik\Actions\RouterDiagnostics;
use App\MikroTik\Actions\SchedulerManager;
use App\MikroTik\Client\RouterClient;
use App\MikroTik\Installer\ScriptInstaller;
use App\MikroTik\Scripts\PullProfilesScript;
use App\Models\Router;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Mary\Traits\Toast;
use RouterOS\Query;

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
    public array $schedulerStatuses = [];

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
            $this->schedulerStatuses = $this->loadSchedulerStatuses();
            $this->hotspotUserStats = $this->computeHotspotUserStats();
            $this->activityStats = $this->computeActivityStats();
            if (empty($this->interfaces)) {
                $this->interfaces = $this->fetchInterfaces();
            }
            $this->formattedUptime = $this->formatUptime($this->resource['uptime'] ?? null);

            $this->interface = $this->interface ?: ($this->interfaces[0]['name'] ?? '');
            $this->recordTrafficSample($diag);

            $this->trafficChart = $this->buildChartData();
            $this->lastUpdated = now()->toDateTimeString();
            $this->errorMessage = null;
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
            $this->error('Router refresh failed: ' . $e->getMessage());
        }
    }

    public function refreshTrafficData(): void
    {
        try {
            $this->recordTrafficSample();
            $this->trafficChart = $this->buildChartData();
            $this->lastUpdated = now()->toDateTimeString();
            $this->errorMessage = null;
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
            $this->error('Traffic refresh failed: ' . $e->getMessage());
        }
    }

    /**
     * Reset traffic series when user selects another interface.
     */
    public function updatedInterface(): void
    {
        $this->trafficSeries = [];
        $this->refreshTrafficData();
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

    protected function routerClient(): RouterClient
    {
        return app(RouterClient::class);
    }

    protected function schedulerManager(): SchedulerManager
    {
        return app(SchedulerManager::class);
    }

    protected function buildChartData(): array
    {
        $labels = array_map(fn($row) => $row['label'] ?? '', $this->trafficSeries);
        $rx = array_map(fn($row) => round(($row['rx'] ?? 0) / 1_000_000, 2), $this->trafficSeries);
        $tx = array_map(fn($row) => round(($row['tx'] ?? 0) / 1_000_000, 2), $this->trafficSeries);

        $lastRx = !empty($rx) ? end($rx) : 0;
        $lastTx = !empty($tx) ? end($tx) : 0;

        return [
            'type' => 'line',
            'data' => [
                'labels'   => $labels,
                'datasets' => [
                    [
                        'label'           => 'Rx Mbps ' . ($lastRx !== null ? " ({$lastRx})" : ''),
                        'data'            => $rx,
                        'borderColor'     => '#fe51b6ff',
                        'backgroundColor' => 'rgba(247, 12, 145, 0.2)',
                        'borderWidth'     => 2,
                        'pointRadius'     => 6,  // bigger point
                        'pointHoverRadius' => 8,  // hover size
                        'pointBackgroundColor' => '#fe51b6ff', // filled dot
                        'pointBorderColor' => '#ffffff', // optional border
                        'pointBorderWidth' => 1,
                        'tension'         => 0.3,
                        'fill'            => true,
                    ],

                    [
                        'label'           => 'Tx Mbps' . ($lastTx !== null ? " ({$lastTx})" : ''),
                        'data'            => $tx,
                        'borderColor'     => '#51a7feff',
                        'backgroundColor' => 'rgba(81, 167, 254, 0.2)',
                        'borderWidth'     => 2,
                        'pointRadius'     => 6,  // bigger point
                        'pointHoverRadius' => 8,  // hover size
                        'pointBackgroundColor' => '#51a7feff', // filled dot
                        'pointBorderColor' => '#ffffff', // optional border
                        'pointBorderWidth' => 1,
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
                        'position' => 'top',
                        //mathe legen circle instead of box
                        'labels'  => [
                            'usePointStyle' => true,
                            'pointStyle'   => 'circle',
                        ],
                    ],
                ],
                'scales' => [
                    'x' => [
                        'display' => true,
                    ],
                    'y' => [
                        'display' => true,
                        'ticks'   => [
                            'beginAtZero' => true,
                            'stepSize' => 5,
                        ],
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

    protected function loadSchedulerStatuses(): array
    {
        $definitions = $this->schedulerDefinitions();

        try {
            $names = array_column($definitions, 'name');
            $remote = collect($this->schedulerManager()->list($this->router, $names))->keyBy('name');
        } catch (\Throwable $e) {
            $this->error('Failed to load schedulers: ' . $e->getMessage());
            $remote = collect();
        }

        return array_map(function (array $definition) use ($remote) {
            $status = $remote->get($definition['name']);

            return [
                'name'     => $definition['name'],
                'label'    => $definition['label'] ?? $definition['name'],
                'interval' => $status['interval'] ?? $definition['interval'],
                'next_run' => $status['next_run'] ?? null,
                'last_run' => $status['last_run'] ?? null,
                'on_event' => $status['on_event'] ?? $definition['on_event'],
                'disabled' => $status['disabled'] ?? false,
                'missing'  => $status === null,
            ];
        }, $definitions);
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

    protected function recordTrafficSample(?RouterDiagnostics $diag = null): void
    {
        if (!$this->interface) {
            return;
        }

        $diag = $diag ?? $this->diagnostics();
        $traffic = $diag->interfaceTraffic($this->router, $this->interface);
        if ($traffic) {
            $traffic['label'] = now()->format('H:i:s');
            $this->trafficSeries[] = $traffic;
            $this->trafficSeries = array_slice($this->trafficSeries, -8);
        }
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

            $installer->installAllScriptsAndSchedulers($router);
            $this->schedulerStatuses = $this->loadSchedulerStatuses();
            $this->scriptStatuses = $this->diagnostics()->scriptStatuses($router);

            $this->success('Scripts synced successfully.');
        } catch (\Throwable $e) {
            $this->error('Failed to sync scripts: ' . $e->getMessage());
        }
    }

    public function syncSchedulers(): void
    {
        try {
            /** @var ScriptInstaller $installer */
            $installer = app(ScriptInstaller::class);

            $this->upsertConfiguredSchedulers($installer);
            $this->schedulerStatuses = $this->loadSchedulerStatuses();

            $this->success('Schedulers synced successfully.');
        } catch (\Throwable $e) {
            $this->error('Failed to sync schedulers: ' . $e->getMessage());
        }
    }

    public function runScheduler(string $name): void
    {
        try {
            $this->schedulerManager()->run($this->router, $name);
            $this->schedulerStatuses = $this->loadSchedulerStatuses();
            $this->success("Scheduler {$name} triggered.");
        } catch (\Throwable $e) {
            $this->error('Failed to run scheduler: ' . $e->getMessage());
        }
    }

    /**
     * Return tracked scheduler definitions.
     *
     * @return array<int,array{name:string,label:string,interval:string,on_event:string}>
     */
    protected function schedulerDefinitions(): array
    {
        return ScriptInstaller::schedulerDefinitions();
    }

    protected function upsertConfiguredSchedulers(ScriptInstaller $installer): void
    {
        foreach ($this->schedulerDefinitions() as $scheduler) {
            $installer->upsertScheduler(
                $this->router,
                $scheduler['name'],
                $scheduler['interval'],
                $scheduler['on_event']
            );
        }
    }

    public function syncProfiles(): void
    {
        try {
            /** @var ScriptInstaller $installer */
            $installer = app(ScriptInstaller::class);
            $router = $this->router;

            $pullProfilesUrl = route('mikrotik.pullProfiles');
            $installer->installProfileOnLoginScript($router);
            $installer->installPullProfilesScript($router, $pullProfilesUrl);

            $client = $this->routerClient();
            $ros = $client->make($router);

            $client->safeRead(
                $ros,
                (new Query('/system/script/run'))
                    ->equal('number', PullProfilesScript::name())
            );

            $this->profiles = $this->loadProfiles();
            $this->success('Profiles synced successfully.');
        } catch (\Throwable $e) {
            $this->error('Failed to sync profiles: ' . $e->getMessage());
        }
    }
}
