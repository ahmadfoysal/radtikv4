{{-- ===== Routers page (no nested mary-cards) ===== --}}
<section class="w-full">

    {{-- HEADER (single mary-card, not wrapping anything) --}}
    <x-mary-card>
        <div class="px-4 py-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <!-- Title -->
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-server-stack" class="w-5 h-5" />
                    <span class="font-semibold">Routers</span>
                </div>

                <!-- Tools -->
                <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
                    <x-mary-input placeholder="Search router..." icon="o-magnifying-glass" class="w-full sm:w-64"
                        input-class="input-sm" wire:model.debounce.400ms="q" />

                    <div class="flex items-center gap-2 sm:justify-end">
                        <x-mary-button icon="o-arrow-path" label="Refresh" class="btn-sm btn-ghost"
                            wire:click="refresh" />
                        <x-mary-button icon="o-plus" label="Add Router" class="btn-sm btn-primary"
                            wire:click="openCreate" />
                    </div>
                </div>
            </div>
        </div>
    </x-mary-card>


    {{-- GRID (no wrapper card) --}}
    <div class="px-4 py-4"> {{-- use the SAME px as header above to keep exact width --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 auto-rows-fr">

            @foreach ([
        ['id' => 1, 'identity' => 'Remote-GW-01', 'zone' => 'Dhaka', 'status' => 'Online', 'uptime' => '6d 12h', 'model' => 'CCR1009', 'version' => '7.16.1', 'cpu_load' => 32, 'ram_used_mb' => 768, 'ram_total_mb' => 2048, 'disk_used_gb' => 4.8, 'disk_total_gb' => 16, 'all_users' => 420, 'active_users' => 57],
        ['id' => 2, 'identity' => 'Edge-AP-02', 'zone' => 'Sylhet', 'status' => 'Offline', 'uptime' => '—', 'model' => 'RB4011', 'version' => '7.15', 'cpu_load' => 0, 'ram_used_mb' => 0, 'ram_total_mb' => 1024, 'disk_used_gb' => 0, 'disk_total_gb' => 8, 'all_users' => 310, 'active_users' => 0],
        ['id' => 3, 'identity' => 'City-Core-03', 'zone' => 'Chittagong', 'status' => 'Degraded', 'uptime' => '2d 01h', 'model' => 'hAP ac²', 'version' => '7.13', 'cpu_load' => 71, 'ram_used_mb' => 190, 'ram_total_mb' => 256, 'disk_used_gb' => 11.2, 'disk_total_gb' => 16, 'all_users' => 150, 'active_users' => 23],
        ['id' => 4, 'identity' => 'Branch-04', 'zone' => 'Rajshahi', 'status' => 'Online', 'uptime' => '1d 8h', 'model' => 'CCR2004', 'version' => '7.12', 'cpu_load' => 18, 'ram_used_mb' => 1024, 'ram_total_mb' => 4096, 'disk_used_gb' => 9.4, 'disk_total_gb' => 64, 'all_users' => 520, 'active_users' => 64],
        ['id' => 1, 'identity' => 'Remote-GW-01', 'zone' => 'Dhaka', 'status' => 'Online', 'uptime' => '6d 12h', 'model' => 'CCR1009', 'version' => '7.16.1', 'cpu_load' => 32, 'ram_used_mb' => 768, 'ram_total_mb' => 2048, 'disk_used_gb' => 4.8, 'disk_total_gb' => 16, 'all_users' => 420, 'active_users' => 57],
        ['id' => 2, 'identity' => 'Edge-AP-02', 'zone' => 'Sylhet', 'status' => 'Offline', 'uptime' => '—', 'model' => 'RB4011', 'version' => '7.15', 'cpu_load' => 0, 'ram_used_mb' => 0, 'ram_total_mb' => 1024, 'disk_used_gb' => 0, 'disk_total_gb' => 8, 'all_users' => 310, 'active_users' => 0],
        ['id' => 3, 'identity' => 'City-Core-03', 'zone' => 'Chittagong', 'status' => 'Degraded', 'uptime' => '2d 01h', 'model' => 'hAP ac²', 'version' => '7.13', 'cpu_load' => 71, 'ram_used_mb' => 190, 'ram_total_mb' => 256, 'disk_used_gb' => 11.2, 'disk_total_gb' => 16, 'all_users' => 150, 'active_users' => 23],
        ['id' => 4, 'identity' => 'Branch-04', 'zone' => 'Rajshahi', 'status' => 'Online', 'uptime' => '1d 8h', 'model' => 'CCR2004', 'version' => '7.12', 'cpu_load' => 18, 'ram_used_mb' => 1024, 'ram_total_mb' => 4096, 'disk_used_gb' => 9.4, 'disk_total_gb' => 64, 'all_users' => 520, 'active_users' => 64],
    ] as $r)
                @php
                    $statusColor = match ($r['status']) {
                        'Online' => 'text-success',
                        'Offline' => 'text-error',
                        'Degraded' => 'text-warning',
                        default => 'text-gray-400',
                    };
                    $pct = fn($u, $t) => $t ? round(($u / $t) * 100) : 0;
                    $cpu = (int) $r['cpu_load'];
                    $ram = $pct($r['ram_used_mb'], $r['ram_total_mb']);
                    $disk = $pct($r['disk_used_gb'], $r['disk_total_gb']);
                @endphp

                {{-- Router card (standalone mary-card) --}}
                <x-mary-card
                    class="bg-base-100 border border-base-300 rounded-xl p-3 sm:p-4 shadow hover:shadow-md transition-shadow duration-200">

                    {{-- Header --}}
                    <x-slot name="title">
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <div class="font-semibold truncate text-sm sm:text-base">{{ $r['identity'] }}</div>
                                {{-- Subheader: zone + uptime in one line --}}
                                <div class="text-xs opacity-70 flex items-center justify-between">
                                    <span class="truncate">Zone: {{ $r['zone'] }}</span>
                                    <span class="font-medium text-success ml-8">
                                        Up: {{ $r['uptime'] }}
                                    </span>
                                </div>

                            </div>
                            <x-mary-icon name="s-server-stack" class="w-4 h-4 {{ $statusColor }}" />
                        </div>
                    </x-slot>

                    {{-- Quick facts (two compact rows) --}}
                    <div class="mt-2 flex flex-wrap items-center justify-between text-[11px] sm:text-xs gap-x-3">
                        <div>
                            <span class="opacity-60">Model:</span> {{ $r['model'] }}
                        </div>
                        <div>
                            <span class="opacity-60">Ver:</span> {{ $r['version'] }}
                        </div>
                        <div>
                            <span class="opacity-60">Users:</span> {{ $r['all_users'] }}
                            <span class="opacity-60">(Active:</span> {{ $r['active_users'] }}<span
                                class="opacity-60">)</span>
                        </div>
                    </div>


                    {{-- Tiny meters (thinner bars + tighter spacing) --}}
                    <div class="mt-2 space-y-1">
                        <div class="flex items-center justify-between text-[11px] sm:text-xs">
                            <div class="flex items-center gap-1">
                                <x-mary-icon name="o-cpu-chip" class="w-3.5 h-3.5" /> CPU
                            </div>
                            <span>{{ $cpu }}%</span>
                        </div>
                        <progress class="progress progress-primary h-0.5" value="{{ $cpu }}"
                            max="100"></progress>

                        <div class="flex items-center justify-between text-[11px] sm:text-xs">
                            <div class="flex items-center gap-1">
                                <x-mary-icon name="o-bolt" class="w-3.5 h-3.5" /> RAM
                            </div>
                            <span>{{ $ram }}%</span>
                        </div>
                        <progress class="progress progress-secondary h-0.5" value="{{ $ram }}"
                            max="100"></progress>

                        <div class="flex items-center justify-between text-[11px] sm:text-xs">
                            <div class="flex items-center gap-1">
                                <x-mary-icon name="o-server" class="w-3.5 h-3.5" /> Disk
                            </div>
                            <span>{{ $disk }}%</span>
                        </div>
                        <progress class="progress progress-accent h-0.5" value="{{ $disk }}"
                            max="100"></progress>
                    </div>

                    {{-- Actions: icon-only on mobile; labels show from ≥sm --}}
                    <x-slot name="actions">
                        <div class="flex flex-wrap items-center justify-end gap-1.5">
                            <x-mary-button icon="o-wifi" label="Ping" class="btn-ghost btn-xs !px-2"
                                wire:click="ping({{ $r['id'] }})">
                                <span class="hidden sm:inline">Ping</span>
                            </x-mary-button>

                            <x-mary-button icon="o-pencil-square" label="Edit" class="btn-ghost btn-xs !px-2"
                                wire:click="edit({{ $r['id'] }})">
                                <span class="hidden sm:inline">Edit</span>
                            </x-mary-button>

                            <x-mary-button icon="o-trash" label="Delete" class="btn-ghost btn-xs !px-2 text-error"
                                wire:click="delete({{ $r['id'] }})">
                                <span class="hidden sm:inline">Delete</span>
                            </x-mary-button>
                        </div>
                    </x-slot>
                </x-mary-card>
            @endforeach

        </div>
    </div>
</section>
