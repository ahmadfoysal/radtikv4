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
    @endphp

    <div class="flex justify-end">
        <x-mary-button label="Back" class="btn-outline btn-xs" icon="o-arrow-left"
            href="{{ route('routers.index') }}" wire:navigate />
    </div>

    {{-- Header --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <x-mary-card class="bg-base-200 border-0 shadow-sm">
            <div class="flex flex-col gap-4 text-xs sm:text-sm">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <x-mary-icon name="o-server-stack" class="w-9 h-9 text-primary" />
                        <div>
                            <div class="text-[11px] uppercase tracking-wide opacity-60">Router</div>
                            <div class="text-lg font-semibold leading-tight">{{ $router->name }}</div>
                            <div class="text-xs opacity-70">
                                {{ $router->address }} &middot; Port {{ $router->port ?? '8728' }}
                            </div>
                        </div>
                    </div>
                </div>

                <dl class="space-y-2">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="flex items-center gap-2 text-[11px] uppercase opacity-60">
                            <x-mary-icon name="o-map-pin" class="w-4 h-4" />
                            Zone
                        </dt>
                        <dd class="font-semibold text-sm">{{ $router->zone->name ?? 'N/A' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="flex items-center gap-2 text-[11px] uppercase opacity-60">
                            <x-mary-icon name="o-user-circle" class="w-4 h-4" />
                            Owner
                        </dt>
                        <dd class="font-semibold text-sm">{{ $router->user->name ?? 'N/A' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="flex items-center gap-2 text-[11px] uppercase opacity-60">
                            <x-mary-icon name="o-arrow-top-right-on-square" class="w-4 h-4" />
                            Login
                        </dt>
                        <dd class="font-semibold text-sm">{{ $router->login_address ?? 'N/A' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="flex items-center gap-2 text-[11px] uppercase opacity-60">
                            <x-mary-icon name="o-key" class="w-4 h-4" />
                            SSH Port
                        </dt>
                        <dd class="font-semibold text-sm">{{ $router->ssh_port ?? '22' }}</dd>
                    </div>
                </dl>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-base-200 border-0 shadow-sm">
            <div class="flex items-center justify-between text-[11px] uppercase opacity-60">
                <span>Router Resource</span>
                <x-mary-icon name="o-cpu-chip" class="w-4 h-4" />
            </div>
            <div class="mt-3 space-y-2 text-xs sm:text-sm">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2 opacity-60">
                        <x-mary-icon name="o-cube" class="w-4 h-4" />
                        Board
                    </div>
                    <div class="font-semibold text-sm">{{ $resource['board-name'] ?? 'N/A' }}</div>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2 opacity-60">
                        <x-mary-icon name="o-command-line" class="w-4 h-4" />
                        RouterOS
                    </div>
                    <div class="font-semibold text-sm">{{ $resource['version'] ?? 'N/A' }}</div>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2 opacity-60">
                        <x-mary-icon name="o-chart-bar" class="w-4 h-4" />
                        CPU Load
                    </div>
                    <div class="font-semibold text-sm">{{ $resource['cpu-load'] ?? '0' }}%</div>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2 opacity-60">
                        <x-mary-icon name="o-rectangle-stack" class="w-4 h-4" />
                        Free HDD
                    </div>
                    <div class="font-semibold text-sm">{{ $formatSize($freeHdd) }}</div>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-base-200 border-0 shadow-sm">
            <div class="flex items-center justify-between text-[11px] uppercase opacity-60">
                <span>Clock &amp; Uptime</span>
                <x-mary-icon name="o-clock" class="w-4 h-4" />
            </div>
            <div class="mt-3 space-y-3 text-xs sm:text-sm">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2 opacity-60">
                        <x-mary-icon name="o-sparkles" class="w-4 h-4" />
                        Uptime
                    </div>
                    <div class="text-base font-semibold">{{ $this->formattedUptime }}</div>
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
        </x-mary-card>
    </div>
    {{-- Hotspot user status --}}
    <div class="space-y-2">
        <div class="text-sm uppercase font-semibold opacity-70">Hotspot User Status</div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-mary-card class="bg-base-200 border-0 shadow-sm">
                <div class="text-xs uppercase opacity-60">All Hotspot Users</div>
                <div class="text-3xl font-semibold">{{ $hotspotUserStats['all'] ?? 0 }}</div>
                <div class="text-xs opacity-70">Non-radius vouchers</div>
            </x-mary-card>

            <x-mary-card class="bg-base-200 border-0 shadow-sm">
                <div class="text-xs uppercase opacity-60">Active Hotspot Users</div>
                <div class="text-3xl font-semibold text-success">{{ $hotspotUserStats['active'] ?? 0 }}</div>
                <div class="text-xs opacity-70">Currently active</div>
            </x-mary-card>

            <x-mary-card class="bg-base-200 border-0 shadow-sm">
                <div class="text-xs uppercase opacity-60">Expiring Today</div>
                <div class="text-3xl font-semibold text-warning">{{ $hotspotUserStats['expiring_today'] ?? 0 }}</div>
                <div class="text-xs opacity-70">Expires {{ now()->format('M d') }}</div>
            </x-mary-card>

            <x-mary-card class="bg-base-200 border-0 shadow-sm">
                <div class="text-xs uppercase opacity-60">Expiring This Week</div>
                <div class="text-3xl font-semibold text-error">{{ $hotspotUserStats['expiring_week'] ?? 0 }}</div>
                <div class="text-xs opacity-70">Within current week</div>
            </x-mary-card>
        </div>
    </div>

    {{-- Activation/Sales --}}
    <div class="space-y-2">
        <div class="text-sm uppercase font-semibold opacity-70">Activation & Sales</div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-mary-card class="bg-base-200 border-0 shadow-sm">
                <div class="text-xs uppercase opacity-60">Activated Today</div>
                <div class="text-3xl font-semibold text-primary">{{ $activityStats['activated_today'] ?? 0 }}</div>
            </x-mary-card>

            <x-mary-card class="bg-base-200 border-0 shadow-sm">
                <div class="text-xs uppercase opacity-60">Activated This Week</div>
                <div class="text-3xl font-semibold text-primary">{{ $activityStats['activated_week'] ?? 0 }}</div>
            </x-mary-card>

            <x-mary-card class="bg-base-200 border-0 shadow-sm">
                <div class="text-xs uppercase opacity-60">Sales Today</div>
                <div class="text-3xl font-semibold text-success">{{ $activityStats['sales_today'] ?? 0 }}</div>
            </x-mary-card>

            <x-mary-card class="bg-base-200 border-0 shadow-sm">
                <div class="text-xs uppercase opacity-60">Sales This Week</div>
                <div class="text-3xl font-semibold text-success">{{ $activityStats['sales_week'] ?? 0 }}</div>
            </x-mary-card>
        </div>
    </div>

    {{-- Full-width traffic --}}
    <x-mary-card class="bg-base-200 border-0 shadow-sm">
        <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="text-sm uppercase opacity-60">Live Traffic</div>
                    <div class="text-xs opacity-70">Updated every few seconds</div>
                </div>
                <select class="select select-xs w-36" wire:model="interface">
                    @foreach ($interfaces as $iface)
                        <option value="{{ $iface['name'] }}">{{ $iface['name'] }}
                            {{ ($iface['disabled'] ?? 'no') === 'yes' ? '(disabled)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="w-full h-48">
                <x-mary-chart wire:model="trafficChart" />
            </div>

            @php
                $latest = collect($trafficSeries)->last();
                $peak = collect($trafficSeries)
                    ->map(fn($sample) => max($sample['rx'] ?? 0, $sample['tx'] ?? 0))
                    ->max() ?? 0;
            @endphp

            <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-3">
                <div>
                    <div class="text-xs uppercase opacity-60">Latest Rx</div>
                    <div class="text-lg font-semibold">
                        @if ($latest)
                            {{ number_format(($latest['rx'] ?? 0) / 1_000_000, 2) }} Mbps
                        @else
                            N/A
                        @endif
                    </div>
                </div>
                <div>
                    <div class="text-xs uppercase opacity-60">Latest Tx</div>
                    <div class="text-lg font-semibold">
                        @if ($latest)
                            {{ number_format(($latest['tx'] ?? 0) / 1_000_000, 2) }} Mbps
                        @else
                            N/A
                        @endif
                    </div>
                </div>
                <div>
                    <div class="text-xs uppercase opacity-60">Peak</div>
                    <div class="text-lg font-semibold">
                        {{ number_format($peak / 1_000_000, 2) }} Mbps
                    </div>
                </div>
            </div>
        </div>
    </x-mary-card>

    {{-- Compact dashboard cards --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-5">
        <x-mary-card class="bg-base-200 border-0 shadow-sm lg:col-span-2">
            <div class="text-sm uppercase opacity-60 mb-2">Scripts Health</div>
            @php
                $missingScripts = collect($scriptStatuses)->where('present', false)->count();
            @endphp
            <div class="text-xs opacity-70 mb-3">
                {{ count($scriptStatuses) }} tracked | {{ $missingScripts }} missing
            </div>
            <div class="mb-3">
                <x-mary-button size="xs" icon="o-arrow-path" label="Sync Scripts" wire:click="syncScripts"
                    spinner="syncScripts" />
            </div>
            <div class="max-h-36 overflow-y-auto pr-1">
                <ul class="space-y-2 text-sm">
                    @forelse ($scriptStatuses as $script)
                        <li class="flex items-center justify-between gap-3">
                            <div class="truncate">{{ $script['name'] }}</div>
                            @if ($script['present'])
                                <span class="badge badge-success badge-sm">OK</span>
                            @else
                                <span class="badge badge-error badge-sm">Missing</span>
                            @endif
                        </li>
                    @empty
                        <li class="text-xs opacity-60">No scripts detected.</li>
                    @endforelse
                </ul>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-base-200 border-0 shadow-sm lg:col-span-3">
            <div class="text-sm uppercase opacity-60 mb-2">Hotspot Profiles</div>
            <div class="text-xs opacity-70 mb-3">
                {{ count($profiles) }} profiles on router
            </div>
            <div class="max-h-60 overflow-y-auto">
                <table class="table table-compact text-xs">
                    <thead>
                        <tr>
                            <th class="bg-base-300">Name</th>
                            <th class="bg-base-300">Shared</th>
                            <th class="bg-base-300">Rate Limit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($profiles as $profile)
                            <tr>
                                <td>{{ $profile['name'] ?? 'N/A' }}</td>
                                <td>{{ $profile['shared-users'] ?? 'N/A' }}</td>
                                <td>{{ $profile['rate-limit'] ?? 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center opacity-60 py-4">No profiles found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-mary-card>
    </div>
</section>
