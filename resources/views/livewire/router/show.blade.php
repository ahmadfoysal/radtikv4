<section class="w-full space-y-5">
    @if ($errorMessage)
        <x-mary-alert icon="o-exclamation-triangle" class="alert-error">
            <span>{{ $errorMessage }}</span>
        </x-mary-alert>
    @endif

    @php
        $totalMem = (float) ($resource['total-memory'] ?? 0);
        $freeMem = (float) ($resource['free-memory'] ?? 0);
        $usedMem = max($totalMem - $freeMem, 0);
        $totalHdd = (float) ($resource['total-hdd-space'] ?? 0);
        $freeHdd = (float) ($resource['free-hdd-space'] ?? 0);
        $cpuLoad = (float) ($resource['cpu-load'] ?? 0);

        $formatSize = function ($bytes) {
            if (!$bytes) {
                return '0 MiB';
            }
            return number_format($bytes / 1024 / 1024, 1) . ' MiB';
        };
        $memMax = max($totalMem, 1);
        $hddMax = max($totalHdd, 1);
        $cpuUsagePercent = min(100, max(0, round($cpuLoad, 1)));
        $memUsagePercent = min(100, max(0, round(($usedMem / $memMax) * 100, 1)));
        $diskUsed = max($totalHdd - $freeHdd, 0);
        $diskUsagePercent = min(100, max(0, round(($diskUsed / $hddMax) * 100, 1)));
    @endphp

    <div class="flex justify-end">
        <x-mary-button label="Back" class="btn-outline btn-xs" icon="o-arrow-left" href="{{ route('routers.index') }}"
            wire:navigate />
    </div>

    {{-- Header --}}
    {{-- Header (compact, single card) --}}
    <x-mary-card class="bg-base-100 border border-base-300 shadow-sm">
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3 text-xs sm:text-sm">

            {{-- Router info --}}
            <div class="flex flex-col gap-3">
                <div class="flex items-center gap-3">
                    <x-mary-icon name="o-server-stack" class="w-9 h-9 text-primary" />
                    <div>
                        <div class="text-[11px] uppercase tracking-wide opacity-60">Router</div>
                        <div class="text-lg font-semibold leading-tight">
                            {{ $router->name }}
                        </div>
                        <div class="text-xs opacity-70">
                            {{ $router->address }} &middot; Port {{ $router->port ?? '8728' }}
                        </div>
                    </div>
                </div>

                <dl class="space-y-1">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="flex items-center gap-2 text-[11px] uppercase opacity-60">
                            <x-mary-icon name="o-map-pin" class="w-4 h-4" />
                            Zone
                        </dt>
                        <dd class="font-semibold text-sm">
                            {{ $router->zone->name ?? 'N/A' }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="flex items-center gap-2 text-[11px] uppercase opacity-60">
                            <x-mary-icon name="o-arrow-top-right-on-square" class="w-4 h-4" />
                            Login
                        </dt>
                        <dd class="font-semibold text-sm">
                            {{ $router->login_address ?? 'N/A' }}
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- System health --}}
            <div class="space-y-3">
                <div class="space-y-2">
                    <div>
                        <div class="flex justify-between  uppercase tracking-wide">
                            <span>CPU Load</span>
                            <span>{{ number_format($cpuUsagePercent, 1) }}%</span>
                        </div>
                        <progress class="progress progress-primary h-1" value="{{ $cpuUsagePercent }}"
                            max="100"></progress>
                    </div>

                    <div>
                        <div class="flex justify-between  uppercase tracking-wide">
                            <span>Memory</span>
                            <span>
                                {{ number_format($memUsagePercent, 1) }}%
                                ({{ $formatSize($usedMem) }} / {{ $formatSize($totalMem) }})
                            </span>
                        </div>
                        <progress class="progress progress-info h-1" value="{{ $memUsagePercent }}"
                            max="100"></progress>
                    </div>

                    <div>
                        <div class="flex justify-between mb-1 uppercase tracking-wide">
                            <span>Disk</span>
                            <span>
                                {{ number_format($diskUsagePercent, 1) }}%
                                ({{ $formatSize($diskUsed) }} / {{ $formatSize($totalHdd) }})
                            </span>
                        </div>
                        <progress class="progress progress-warning h-1" value="{{ $diskUsagePercent }}"
                            max="100"></progress>
                    </div>
                </div>
            </div>

            {{-- Clock & Uptime --}}
            <div class="space-y-3">
                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2 opacity-60">
                            <x-mary-icon name="o-sparkles" class="w-4 h-4" />
                            Uptime
                        </div>
                        <div class="text-base font-semibold">
                            {{ $this->formattedUptime }}
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2 opacity-60">
                            <x-mary-icon name="o-calendar-days" class="w-4 h-4" />
                            Router Time
                        </div>
                        <div class="text-base font-semibold">
                            {{ $clock['date'] ?? 'N/A' }} {{ $clock['time'] ?? '' }}
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </x-mary-card>

    {{-- Hotspot user status --}}
    <div class="space-y-2">
        <div class="text-sm uppercase font-semibold opacity-70">Hotspot User Status</div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-mary-card class="bg-base-100 border border-base-300 shadow-sm">
                <div class="text-xs uppercase opacity-60">All Hotspot Users</div>
                <div class="text-3xl font-semibold">{{ $hotspotUserStats['all'] ?? 0 }}</div>
            </x-mary-card>

            <x-mary-card class="bg-base-100 border border-base-300 shadow-sm">
                <div class="text-xs uppercase opacity-60">Active Hotspot Users</div>
                <div class="text-3xl font-semibold text-success">{{ $hotspotUserStats['active'] ?? 0 }}</div>
            </x-mary-card>

            <x-mary-card class="bg-base-100 border border-base-300 shadow-sm">
                <div class="text-xs uppercase opacity-60">Expiring Today</div>
                <div class="text-3xl font-semibold text-warning">{{ $hotspotUserStats['expiring_today'] ?? 0 }}</div>
            </x-mary-card>

            <x-mary-card class="bg-base-100 border border-base-300 shadow-sm">
                <div class="text-xs uppercase opacity-60">Expiring This Week</div>
                <div class="text-3xl font-semibold text-error">{{ $hotspotUserStats['expiring_week'] ?? 0 }}</div>
            </x-mary-card>
        </div>
    </div>

    {{-- Activation/Sales --}}
    <div class="space-y-2">
        <div class="text-sm uppercase font-semibold opacity-70">Activation & Sales</div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-mary-card class="bg-base-100 border border-base-300 shadow-sm">
                <div class="text-xs uppercase opacity-60">Activated Today</div>
                <div class="text-3xl font-semibold text-primary">{{ $activityStats['activated_today'] ?? 0 }}</div>
            </x-mary-card>

            <x-mary-card class="bg-base-100 border border-base-300 shadow-sm">
                <div class="text-xs uppercase opacity-60">Activated This Week</div>
                <div class="text-3xl font-semibold text-primary">{{ $activityStats['activated_week'] ?? 0 }}</div>
            </x-mary-card>

            <x-mary-card class="bg-base-100 border border-base-300 shadow-sm">
                <div class="text-xs uppercase opacity-60">Sales Today</div>
                <div class="text-3xl font-semibold text-success">{{ $activityStats['sales_today'] ?? 0 }}</div>
            </x-mary-card>

            <x-mary-card class="bg-base-100 border border-base-300 shadow-sm">
                <div class="text-xs uppercase opacity-60">Sales This Week</div>
                <div class="text-3xl font-semibold text-success">{{ $activityStats['sales_week'] ?? 0 }}</div>
            </x-mary-card>
        </div>
    </div>

    {{-- Full-width traffic --}}
    <x-mary-card class="bg-base-100 border border-base-300 shadow-sm" wire:poll.10s="refreshTrafficData">
        <div class="flex flex-col gap-4">
            <div>
                <div class="text-sm uppercase opacity-60">Live Traffic</div>
            </div>

            <div class="grid gap-4 lg:grid-cols-12">
                <div class="space-y-4 lg:col-span-8">
                    <div class="chart-wrapper w-full h-[350px]">
                        <x-mary-chart wire:model="trafficChart" class="w-full"
                            style="height: 320px; max-height: 320px;" />

                    </div>
                </div>
            </div>
        </div>
    </x-mary-card>


    {{-- Compact dashboard cards --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">

        {{-- Scripts Health --}}
        <x-mary-card class="bg-base-100 border border-base-300 shadow-sm flex flex-col">
            <div class="flex items-center justify-between mb-2">
                <div class="text-sm uppercase opacity-60">Scripts Health</div>

                <x-mary-button size="xs" icon="o-arrow-path" label="Sync" wire:click="syncScripts"
                    spinner="syncScripts" />
            </div>

            @php
                $missingScripts = collect($scriptStatuses)->where('present', false)->count();
            @endphp

            <div class="text-xs opacity-70 mb-3">
                {{ count($scriptStatuses) }} tracked | {{ $missingScripts }} missing
            </div>

            <div class="flex-1 overflow-y-auto">
                <table class="table table-compact text-xs w-full">
                    <thead>
                        <tr>
                            <th class="bg-base-300">Script Name</th>
                            <th class="bg-base-300 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($scriptStatuses as $script)
                            <tr class="border-b border-base-300/70 last:border-0">
                                <td class="truncate">{{ $script['name'] }}</td>
                                <td class="text-center">
                                    @if ($script['present'])
                                        <span class="badge badge-success badge-sm">OK</span>
                                    @else
                                        <span class="badge badge-error badge-sm">Missing</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center opacity-60 py-4">
                                    No scripts detected.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-mary-card>


        {{-- Hotspot Profiles --}}
        <x-mary-card class="bg-base-100 border border-base-300 shadow-sm flex flex-col">
            <div class="flex items-center justify-between mb-2">
                <div class="text-sm uppercase opacity-60">Hotspot Profiles</div>

                <x-mary-button size="xs" icon="o-arrow-path" label="Sync" wire:click="syncProfiles"
                    spinner="syncProfiles" />
            </div>

            <div class="text-xs opacity-70 mb-3">
                {{ count($profiles) }} profiles on router
            </div>

            <div class="flex-1 overflow-y-auto">
                <table class="table table-compact text-xs w-full">
                    <thead>
                        <tr>
                            <th class="bg-base-300">Name</th>
                            <th class="bg-base-300 text-center">Shared</th>
                            <th class="bg-base-300 text-right">Rate Limit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($profiles as $profile)
                            <tr class="border-b border-base-300/70 last:border-0">
                                <td>{{ $profile['name'] ?? 'N/A' }}</td>
                                <td class="text-center">{{ $profile['shared-users'] ?? 'N/A' }}</td>
                                <td class="text-right">{{ $profile['rate-limit'] ?? 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center opacity-60 py-4">
                                    No profiles found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-mary-card>


        {{-- Router Schedulers --}}
        <x-mary-card class="bg-base-100 border border-base-300 shadow-sm flex flex-col">
            <div class="flex items-center justify-between mb-2">
                <div class="text-sm uppercase opacity-60">Router Schedulers</div>

                <x-mary-button size="xs" icon="o-arrow-path" label="Sync" wire:click="syncSchedulers"
                    spinner="syncSchedulers" />
            </div>

            <div class="text-xs opacity-70 mb-3">
                {{ count($schedulerStatuses) }} schedulers tracked
            </div>

            <div class="flex-1 overflow-y-auto">
                <div class="overflow-x-auto">
                    <table class="table table-compact text-xs w-full">
                        <thead>
                            <tr>
                                <th class="bg-base-300">Scheduler</th>
                                <th class="bg-base-300 text-center">Interval</th>
                                <th class="bg-base-300 text-center">Next Run</th>
                                <th class="bg-base-300 text-center">Last Run</th>
                                <th class="bg-base-300 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($schedulerStatuses as $scheduler)
                                <tr class="border-b border-base-300/70 last:border-0">
                                    <td>
                                        <div class="font-semibold">{{ $scheduler['label'] }}</div>
                                        <div class="text-[11px] opacity-60">{{ $scheduler['name'] }}</div>
                                    </td>
                                    <td class="text-center">{{ $scheduler['interval'] ?? 'N/A' }}</td>
                                    <td class="text-center whitespace-nowrap">{{ $scheduler['next_run'] ?? 'N/A' }}
                                    </td>
                                    <td class="text-center whitespace-nowrap">{{ $scheduler['last_run'] ?? 'N/A' }}
                                    </td>
                                    <td class="text-center">
                                        <div class="flex justify-center gap-2">
                                            @if ($scheduler['missing'])
                                                <span class="badge badge-error badge-sm">Missing</span>
                                            @elseif ($scheduler['disabled'])
                                                <span class="badge badge-warning badge-sm">Disabled</span>
                                            @else
                                                <span class="badge badge-success badge-sm">Active</span>
                                            @endif

                                            <x-mary-button icon="o-play" size="xs" label="Run"
                                                class="btn-outline btn-xs"
                                                wire:click="runScheduler('{{ $scheduler['name'] }}')"
                                                spinner="runScheduler" />
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center opacity-60 py-4">
                                        No schedulers tracked yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </x-mary-card>

    </div>



</section>
