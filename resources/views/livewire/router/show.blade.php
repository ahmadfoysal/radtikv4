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
    <x-mary-card class="bg-base-100 border border-base-300 shadow-sm rounded-none">
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
                    <div class="flex items-center justify-between gap-3">
                        <dt class="flex items-center gap-2 text-[11px] uppercase opacity-60">
                            <x-mary-icon name="o-identification" class="w-4 h-4" />
                            NAS ID
                        </dt>
                        <dd class="font-mono text-xs font-semibold">
                            {{ $router->nas_identifier ?? 'N/A' }}
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
            <x-mary-card class="bg-base-100 border border-base-300 shadow-sm rounded-none">
                <div class="text-xs uppercase opacity-60">All Hotspot Users</div>
                <div class="text-3xl font-semibold">{{ $hotspotUserStats['all'] ?? 0 }}</div>
            </x-mary-card>

            <x-mary-card class="bg-base-100 border border-base-300 shadow-sm rounded-none">
                <div class="text-xs uppercase opacity-60">Active Hotspot Users</div>
                <div class="text-3xl font-semibold text-success">{{ $hotspotUserStats['active'] ?? 0 }}</div>
            </x-mary-card>

            <x-mary-card class="bg-base-100 border border-base-300 shadow-sm rounded-none">
                <div class="text-xs uppercase opacity-60">Expiring Today</div>
                <div class="text-3xl font-semibold text-warning">{{ $hotspotUserStats['expiring_today'] ?? 0 }}</div>
            </x-mary-card>

            <x-mary-card class="bg-base-100 border border-base-300 shadow-sm rounded-none">
                <div class="text-xs uppercase opacity-60">Expiring This Week</div>
                <div class="text-3xl font-semibold text-error">{{ $hotspotUserStats['expiring_week'] ?? 0 }}</div>
            </x-mary-card>
        </div>
    </div>

    {{-- Activation/Sales --}}
    <div class="space-y-2">
        <div class="text-sm uppercase font-semibold opacity-70">Activation & Sales</div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-mary-card class="bg-base-100 border border-base-300 shadow-sm rounded-none">
                <div class="text-xs uppercase opacity-60">Activated Today</div>
                <div class="text-3xl font-semibold text-primary">{{ $activityStats['activated_today'] ?? 0 }}</div>
            </x-mary-card>

            <x-mary-card class="bg-base-100 border border-base-300 shadow-sm rounded-none">
                <div class="text-xs uppercase opacity-60">Activated This Week</div>
                <div class="text-3xl font-semibold text-primary">{{ $activityStats['activated_week'] ?? 0 }}</div>
            </x-mary-card>

            <x-mary-card class="bg-base-100 border border-base-300 shadow-sm rounded-none">
                <div class="text-xs uppercase opacity-60">Sales Today</div>
                <div class="text-3xl font-semibold text-success">{{ $activityStats['sales_today'] ?? 0 }}</div>
            </x-mary-card>

            <x-mary-card class="bg-base-100 border border-base-300 shadow-sm rounded-none">
                <div class="text-xs uppercase opacity-60">Sales This Week</div>
                <div class="text-3xl font-semibold text-success">{{ $activityStats['sales_week'] ?? 0 }}</div>
            </x-mary-card>
        </div>
    </div>

    {{-- Full-width traffic --}}
    <x-mary-card class="bg-base-100 border border-base-300 shadow-sm rounded-none" wire:poll.10s="refreshTrafficData">
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

    {{-- RADIUS Configuration Status --}}
    @if($router->radiusServer)
        <x-mary-card class="bg-base-100 border border-base-300 shadow-sm rounded-none">
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-mary-icon name="o-shield-check" class="w-6 h-6 text-primary" />
                        <h3 class="text-lg font-semibold">RADIUS Configuration Status</h3>
                    </div>

                    <div class="flex gap-2">
                        <x-mary-button 
                            icon="o-arrow-path" 
                            label="Check Status" 
                            class="btn-ghost btn-sm" 
                            wire:click="checkRadiusConfiguration"
                            spinner="checkRadiusConfiguration" />
                        
                        <x-mary-button 
                            icon="o-wrench-screwdriver" 
                            label="Apply RADIUS Config" 
                            class="btn-primary btn-sm" 
                            wire:click="applyRadiusConfiguration"
                            spinner="applyRadiusConfiguration" />
                    </div>
                </div>

                {{-- Overall Status --}}
                <div class="flex items-center gap-3 p-4 rounded-lg {{ $radiusConfig['configured'] ? 'bg-success/10' : 'bg-error/10' }}">
                    @if($radiusConfig['configured'])
                        <x-mary-icon name="o-check-circle" class="w-8 h-8 text-success" />
                        <div>
                            <div class="font-semibold text-success">All RADIUS settings are properly configured</div>
                            <div class="text-sm opacity-70">Your MikroTik router is ready for RADIUS authentication</div>
                        </div>
                    @else
                        <x-mary-icon name="o-x-circle" class="w-8 h-8 text-error" />
                        <div>
                            <div class="font-semibold text-error">RADIUS configuration issues detected</div>
                            <div class="text-sm opacity-70">Please review and fix the issues below</div>
                        </div>
                    @endif
                </div>

                {{-- Issues List --}}
                @if(!empty($radiusConfig['issues']))
                    <div class="space-y-2">
                        <div class="text-sm font-semibold uppercase opacity-70">Configuration Issues:</div>
                        <ul class="space-y-1">
                            @foreach($radiusConfig['issues'] as $issue)
                                <li class="flex items-start gap-2 text-sm">
                                    <x-mary-icon name="o-exclamation-circle" class="w-4 h-4 text-error mt-0.5 flex-shrink-0" />
                                    <span>{{ $issue }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Configuration Details --}}
                @if(!empty($radiusConfig['details']))
                    <div class="space-y-4">
                        <div class="text-sm font-semibold uppercase opacity-70">Configuration Details:</div>

                        {{-- Identity Status --}}
                        @if(isset($radiusConfig['details']['identity']))
                            @php $identity = $radiusConfig['details']['identity']; @endphp
                            <div class="p-3 bg-base-200 rounded-lg">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="font-semibold text-sm">System Identity (NAS Identifier)</div>
                                    @if($identity['match'])
                                        <span class="badge badge-success badge-sm">✓ Correct</span>
                                    @else
                                        <span class="badge badge-error badge-sm">✗ Mismatch</span>
                                    @endif
                                </div>
                                <div class="text-xs space-y-1">
                                    <div class="flex justify-between">
                                        <span class="opacity-60">Expected:</span>
                                        <span class="font-mono">{{ $identity['expected'] }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="opacity-60">Current:</span>
                                        <span class="font-mono">{{ $identity['current'] }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- RADIUS Server Status --}}
                        @if(isset($radiusConfig['details']['radius_server']))
                            @php $radServer = $radiusConfig['details']['radius_server']; @endphp
                            <div class="p-3 bg-base-200 rounded-lg">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="font-semibold text-sm">RADIUS Server Configuration</div>
                                    @if($radServer)
                                        <span class="badge badge-success badge-sm">✓ Configured</span>
                                    @else
                                        <span class="badge badge-error badge-sm">✗ Not Configured</span>
                                    @endif
                                </div>
                                @if($radServer)
                                    <div class="text-xs space-y-1">
                                        <div class="flex justify-between">
                                            <span class="opacity-60">Address:</span>
                                            <span class="font-mono">{{ $radServer['address'] }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="opacity-60">Timeout:</span>
                                            <span class="font-mono">{{ $radServer['timeout'] }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="opacity-60">Status:</span>
                                            @if($radServer['disabled'])
                                                <span class="badge badge-error badge-xs">Disabled</span>
                                            @else
                                                <span class="badge badge-success badge-xs">Enabled</span>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <div class="text-xs opacity-60">No RADIUS server configured for hotspot service</div>
                                @endif
                            </div>
                        @endif

                        {{-- Hotspot Profiles Status --}}
                        @if(isset($radiusConfig['details']['hotspot_profiles']))
                            @php $profiles = $radiusConfig['details']['hotspot_profiles']; @endphp
                            <div class="p-3 bg-base-200 rounded-lg">
                                <div class="font-semibold text-sm mb-2">Hotspot Profiles</div>
                                <div class="space-y-1">
                                    @forelse($profiles as $profile)
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="font-mono">{{ $profile['name'] }}</span>
                                            @if($profile['use_radius'])
                                                <span class="badge badge-success badge-xs">✓ RADIUS Enabled</span>
                                            @else
                                                <span class="badge badge-error badge-xs">✗ RADIUS Disabled</span>
                                            @endif
                                        </div>
                                    @empty
                                        <div class="text-xs opacity-60">No hotspot profiles found</div>
                                    @endforelse
                                </div>
                            </div>
                        @endif

                        {{-- RADIUS Server Information --}}
                        <div class="p-3 bg-primary/10 rounded-lg">
                            <div class="font-semibold text-sm mb-2 flex items-center gap-2">
                                <x-mary-icon name="o-information-circle" class="w-4 h-4" />
                                Connected RADIUS Server
                            </div>
                            <div class="text-xs space-y-1">
                                <div class="flex justify-between">
                                    <span class="opacity-60">Server:</span>
                                    <span class="font-mono">{{ $router->radiusServer->name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="opacity-60">Host:</span>
                                    <span class="font-mono">{{ $router->radiusServer->host }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="opacity-60">Auth Port:</span>
                                    <span class="font-mono">{{ $router->radiusServer->auth_port }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="opacity-60">Status:</span>
                                    @if($router->radiusServer->is_active)
                                        <span class="badge badge-success badge-xs">Active</span>
                                    @else
                                        <span class="badge badge-error badge-xs">Inactive</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </x-mary-card>
    @else
        <x-mary-alert icon="o-information-circle" class="alert-warning">
            <span>No RADIUS server is assigned to this router. Please assign a RADIUS server from the router edit page to enable RADIUS authentication.</span>
        </x-mary-alert>
    @endif

    {{-- Delete Router Section --}}
    <x-mary-card class="bg-base-100 border border-error/30 shadow-sm rounded-none">
        <div class="space-y-4">
            <div class="flex items-center gap-2">
                <x-mary-icon name="o-exclamation-triangle" class="w-5 h-5 text-error" />
                <h3 class="text-lg font-semibold text-error">Danger Zone</h3>
            </div>

            <div class="space-y-2">
                <p class="text-sm text-base-content/70">
                    Deleting this router will permanently remove it and all associated data. This action cannot be
                    undone.
                </p>

                <x-mary-button icon="o-trash" label="Delete Router" class="btn-error"
                    wire:click="openDeleteModal" />
            </div>
        </div>
    </x-mary-card>

    {{-- Delete Confirmation Modal --}}
    <x-mary-modal wire:model="showDeleteModal" title="Delete Router" class="backdrop-blur">
        <div class="space-y-4">
            <x-mary-alert icon="o-exclamation-triangle" class="alert-error">
                <div class="space-y-1">
                    <div class="font-semibold">Warning: This action cannot be undone!</div>
                    <div class="text-sm">
                        Deleting <strong>{{ $router->name }}</strong> will permanently remove:
                    </div>
                    <ul class="text-sm list-disc list-inside ml-2 space-y-1">
                        <li>The router configuration</li>
                        <li>All associated vouchers</li>
                        <li>All hotspot users</li>
                        <li>All related data</li>
                    </ul>
                </div>
            </x-mary-alert>

            <div class="space-y-2">
                <label class="text-sm font-medium">
                    Type <strong class="text-error">"delete"</strong> to confirm:
                </label>
                <x-mary-input wire:model="deleteConfirmation" placeholder="Type 'delete' to confirm" class="w-full"
                    autofocus />
            </div>
        </div>

        <x-slot:actions>
            <x-mary-button label="Cancel" class="btn-ghost" wire:click="closeDeleteModal" />

            <x-mary-button icon="o-trash" label="Delete Router" class="btn-error" wire:click="deleteRouter"
                spinner="deleteRouter" :disabled="strtolower(trim($deleteConfirmation)) !== 'delete'" />
        </x-slot:actions>
    </x-mary-modal>

</section>
