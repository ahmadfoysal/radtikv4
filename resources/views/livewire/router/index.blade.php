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
                        input-class="input-sm" wire:model.live.debounce.400ms="q" />

                    <div class="flex items-center gap-2 sm:justify-end">
                        <x-mary-button icon="o-arrow-path" label="Refresh" class="btn-sm btn-ghost"
                            wire:click="refresh" />
                        <x-mary-button icon="o-plus" label="Add Router" class="btn-sm btn-primary"
                            href="{{ route('routers.create') }}" wire:navigate />
                        <x-mary-button icon="o-document-arrow-down" label="Import Routers" class="btn-sm btn-success"
                            href="{{ route('routers.import') }}" wire:navigate />
                    </div>
                </div>
            </div>
        </div>
    </x-mary-card>

    {{-- GRID (no wrapper card) --}}
    <div class="px-4 py-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 auto-rows-fr">

            @forelse ($routers as $router)
                @php
                    // Resource row for this router from controller-supplied cache
                    $res = $resources[$router->id] ?? [];

                    // Map RouterOS fields (present when API succeeded)
                    $model = $res['board-name'] ?? '—';
                    $version = $res['version'] ?? '—';
                    $uptime = $res['uptime'] ?? '—';
                    $cpu = isset($res['cpu-load']) ? (int) $res['cpu-load'] : null;

                    $totalMem = (int) ($res['total-memory'] ?? 0);
                    $freeMem = (int) ($res['free-memory'] ?? 0);
                    $ramPct = $totalMem > 0 ? round((($totalMem - $freeMem) / $totalMem) * 100) : null;

                    $totalHdd = (int) ($res['total-hdd-space'] ?? 0);
                    $freeHdd = (int) ($res['free-hdd-space'] ?? 0);
                    $diskPct = $totalHdd > 0 ? round((($totalHdd - $freeHdd) / $totalHdd) * 100) : null;

                    $status = isset($res['error']) ? 'Error' : 'Online'; // naive; refine later
                    $statusColor = match ($status) {
                        'Online' => 'text-success',
                        'Offline' => 'text-error',
                        'Degraded' => 'text-warning',
                        default => 'text-gray-400',
                    };
                @endphp

                {{-- Router card --}}
                <x-mary-card
                    class="bg-base-100 border border-base-300 rounded-xl p-3 sm:p-4 shadow hover:shadow-md transition-shadow duration-200">

                    {{-- Header --}}
                    <x-slot name="title">
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <div class="font-semibold truncate text-sm sm:text-base">
                                    {{ $router->name }}
                                </div>
                                <div class="text-xs opacity-70 flex items-center justify-between">
                                    <span class="truncate">Addr: {{ $router->address }}:{{ $router->port }}</span>
                                    <span class="font-medium text-success ml-8">
                                        Up: {{ $uptime }}
                                    </span>
                                </div>
                            </div>
                            <x-mary-icon name="s-server-stack" class="w-4 h-4 {{ $statusColor }}" />
                        </div>
                    </x-slot>

                    {{-- Quick facts --}}
                    <div class="mt-2 flex flex-wrap items-center justify-between text-[11px] sm:text-xs gap-x-3">
                        <div><span class="opacity-60">Model:</span> {{ $model }}</div>
                        <div><span class="opacity-60">Ver:</span> {{ $version }}</div>
                        <div><span class="opacity-60">Note:</span> {{ Str::limit($router->note, 24) }}</div>
                    </div>

                    {{-- Tiny meters --}}
                    <div class="mt-2 space-y-1">
                        @if (!is_null($cpu))
                            <div class="flex items-center justify-between text-[11px] sm:text-xs">
                                <div class="flex items-center gap-1">
                                    <x-mary-icon name="o-cpu-chip" class="w-3.5 h-3.5" /> CPU
                                </div>
                                <span>{{ $cpu }}%</span>
                            </div>
                            <progress class="progress progress-primary h-0.5" value="{{ $cpu }}"
                                max="100"></progress>
                        @endif

                        @if (!is_null($ramPct))
                            <div class="flex items-center justify-between text-[11px] sm:text-xs">
                                <div class="flex items-center gap-1">
                                    <x-mary-icon name="o-bolt" class="w-3.5 h-3.5" /> RAM
                                </div>
                                <span>{{ $ramPct }}%</span>
                            </div>
                            <progress class="progress progress-secondary h-0.5" value="{{ $ramPct }}"
                                max="100"></progress>
                        @endif

                        @if (!is_null($diskPct))
                            <div class="flex items-center justify-between text-[11px] sm:text-xs">
                                <div class="flex items-center gap-1">
                                    <x-mary-icon name="o-server" class="w-3.5 h-3.5" /> Disk
                                </div>
                                <span>{{ $diskPct }}%</span>
                            </div>
                            <progress class="progress progress-accent h-0.5" value="{{ $diskPct }}"
                                max="100"></progress>
                        @endif

                        @isset($res['error'])
                            <div class="text-error text-xs mt-1">
                                {{ $res['error'] }}
                            </div>
                        @endisset
                    </div>

                    {{-- Actions --}}
                    <x-slot name="actions">
                        <div class="flex flex-wrap items-center justify-end gap-1.5">
                            <x-mary-button icon="o-wifi" label="Ping" class="btn-ghost btn-xs !px-2"
                                wire:click="ping({{ $router->id }})">
                                <span class="hidden sm:inline">Ping</span>
                            </x-mary-button>
                            <x-mary-button icon="o-pencil-square" label="Edit" class="btn-ghost btn-xs !px-2"
                                wire:click="edit({{ $router->id }})">
                                <span class="hidden sm:inline">Edit</span>
                            </x-mary-button>
                            <x-mary-button icon="o-trash" label="Delete" class="btn-ghost btn-xs !px-2 text-error"
                                wire:click="delete({{ $router->id }})">
                                <span class="hidden sm:inline">Delete</span>
                            </x-mary-button>
                        </div>
                    </x-slot>
                </x-mary-card>
            @empty
                <x-mary-card class="col-span-full">
                    <div class="p-6 text-center opacity-70">
                        No routers found.
                    </div>
                </x-mary-card>
            @endforelse
        </div>

        {{-- Pagination (8 per page) --}}
        <div class="mt-4">
            {{ $routers->links() }}
        </div>
    </div>
</section>
