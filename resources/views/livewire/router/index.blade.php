<section class="w-full">
    {{-- Header --}}
    <x-mary-card class="mb-4 bg-base-100 border border-base-300 shadow-sm">
        <div class="px-4 py-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2">
                <x-mary-icon name="o-server-stack" class="w-6 h-6 text-primary" />
                <span class="font-semibold text-lg">Routers</span>
            </div>

            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
                <x-mary-input placeholder="Search router..." icon="o-magnifying-glass" class="w-full sm:w-72"
                    input-class="input-sm" wire:model.live.debounce.400ms="q" />

                <div class="flex items-center gap-2 sm:justify-end">
                    <x-mary-button icon="o-plus" label="Add Router" class="btn-sm btn-primary"
                        href="{{ route('routers.create') }}" wire:navigate />
                    {{-- <x-mary-button icon="o-document-arrow-down" label="Import" class="btn-sm btn-success"
                        href="{{ route('routers.import') }}" wire:navigate /> --}}
                </div>
            </div>
        </div>
    </x-mary-card>

    {{-- Router stats grid --}}
    <div class="px-2 sm:px-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse ($routers as $router)
                @php
                    // Get zone name from relation, fallback to note or dash
                    $zone = $router->zone?->name ?? ($router->note ?? '—');
                    // Rotate through semantic colors for visual variety
                    $colors = ['primary', 'secondary', 'accent', 'info', 'success', 'warning'];
                    $colorClass = $colors[$loop->index % count($colors)];

                    $totalUsers = $router->total_vouchers_count ?? 0;
                    $activeUsers = $router->active_vouchers_count ?? 0;
                    $expiredUsers = $router->expired_vouchers_count ?? 0;
                    $inactiveUsers = max($totalUsers - $activeUsers, 0);

                    $isOnline = isset($pingStatuses[$router->id]) && $pingStatuses[$router->id] === 'ok';
                    $isOffline = isset($pingStatuses[$router->id]) && $pingStatuses[$router->id] === 'fail';
                @endphp

                <div
                    class="group bg-base-100 rounded-xl border border-base-300 overflow-hidden shadow-sm hover:shadow-lg hover:border-{{ $colorClass }}/30 transition-all duration-300">
                    {{-- Card Header with Gradient --}}
                    <div
                        class="relative bg-gradient-to-br from-{{ $colorClass }}/10 via-{{ $colorClass }}/5 to-transparent p-5 pb-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3 flex-1 min-w-0">
                                <div class="relative">
                                    @if ($router->logo_url)
                                        <div
                                            class="w-12 h-12 bg-{{ $colorClass }}/10 rounded-lg group-hover:scale-105 transition-transform duration-300 overflow-hidden flex items-center justify-center">
                                            <img src="{{ $router->logo_url }}" alt="{{ $router->name }}"
                                                class="w-full h-full object-cover">
                                        </div>
                                    @else
                                        <div
                                            class="p-3 bg-{{ $colorClass }}/10 rounded-lg border border-{{ $colorClass }}/20 group-hover:scale-105 transition-transform duration-300">
                                            <x-mary-icon name="s-server" class="w-6 h-6 text-{{ $colorClass }}" />
                                        </div>
                                    @endif
                                    {{-- Online Pulse Indicator --}}
                                    @if ($isOnline)
                                        <span class="absolute -top-1 -right-1 flex h-3 w-3">
                                            <span
                                                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-success opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-3 w-3 bg-success"></span>
                                        </span>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0 pt-0.5">
                                    <h3
                                        class="font-bold text-lg truncate text-base-content group-hover:text-{{ $colorClass }} transition-colors">
                                        {{ $router->name }}
                                    </h3>
                                    <div class="flex items-center gap-1.5 mt-1.5">
                                        <x-mary-icon name="o-map-pin" class="w-3.5 h-3.5 text-base-content/50" />
                                        <span class="text-sm text-base-content/70 truncate">{{ $zone }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Status Badge --}}
                            {{-- <div class="flex-shrink-0">
                                @if ($isOnline)
                                    <div class="px-2.5 py-1 bg-success/10 rounded-full border border-success/20">
                                        <span class="text-xs font-semibold text-success flex items-center gap-1">
                                            <x-mary-icon name="o-check-circle" class="w-3.5 h-3.5" />
                                            Online
                                        </span>
                                    </div>
                                @elseif($isOffline)
                                    <div class="px-2.5 py-1 bg-error/10 rounded-full border border-error/20">
                                        <span class="text-xs font-semibold text-error flex items-center gap-1">
                                            <x-mary-icon name="o-x-circle" class="w-3.5 h-3.5" />
                                            Offline
                                        </span>
                                    </div>
                                @else
                                    <div class="px-2.5 py-1 bg-base-200 rounded-full border border-base-300">
                                        <span
                                            class="text-xs font-semibold text-base-content/50 flex items-center gap-1">
                                            <x-mary-icon name="o-question-mark-circle" class="w-3.5 h-3.5" />
                                            Unknown
                                        </span>
                                    </div>
                                @endif
                            </div> --}}
                        </div>
                    </div>

                    {{-- Card Body --}}
                    <div class="p-5 pt-4 space-y-4">
                        {{-- Voucher Statistics --}}
                        <div class="bg-base-200/50 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-semibold text-base-content/80">Voucher Overview</span>
                                <div class="px-2.5 py-0.5 bg-{{ $colorClass }}/10 rounded-full">
                                    <span
                                        class="text-sm font-bold text-{{ $colorClass }}">{{ $totalUsers }}</span>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 mt-3">
                                <div class="flex items-center gap-1.5">
                                    <div class="w-2 h-2 rounded-full bg-success"></div>
                                    <span class="text-xs text-base-content/60">Active:</span>
                                    <span class="text-sm font-bold text-success">{{ $activeUsers }}</span>
                                </div>
                                <div class="w-px h-4 bg-base-300"></div>
                                <div class="flex items-center gap-1.5">
                                    <div class="w-2 h-2 rounded-full bg-base-content/40"></div>
                                    <span class="text-xs text-base-content/60">Inactive:</span>
                                    <span class="text-sm font-bold text-base-content/70">{{ $inactiveUsers }}</span>
                                </div>
                                <div class="w-px h-4 bg-base-300"></div>
                                <div class="flex items-center gap-1.5">
                                    <div class="w-2 h-2 rounded-full bg-warning"></div>
                                    <span class="text-xs text-base-content/60">Expired:</span>
                                    <span class="text-sm font-bold text-warning">{{ $expiredUsers }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Technical Information --}}
                        <div class="flex items-center gap-4 pt-2 flex-wrap">
                            <div class="flex items-center gap-2 text-xs">
                                <div class="p-1.5 bg-{{ $colorClass }}/10 rounded">
                                    <x-mary-icon name="o-cpu-chip" class="w-3.5 h-3.5 text-{{ $colorClass }}" />
                                </div>
                                <span class="text-base-content/60">API:</span>
                                <span class="font-semibold text-base-content">{{ $router->port }}</span>
                            </div>
                            @if ($router->ssh_port)
                                <div class="flex items-center gap-2 text-xs">
                                    <div class="p-1.5 bg-{{ $colorClass }}/10 rounded">
                                        <x-mary-icon name="o-key" class="w-3.5 h-3.5 text-{{ $colorClass }}" />
                                    </div>
                                    <span class="text-base-content/60">SSH:</span>
                                    <span class="font-semibold text-base-content">{{ $router->ssh_port }}</span>
                                </div>
                            @endif
                            <div class="flex items-center gap-2 text-xs">
                                <div class="p-1.5 bg-{{ $colorClass }}/10 rounded">
                                    <x-mary-icon name="o-globe-alt" class="w-3.5 h-3.5 text-{{ $colorClass }}" />
                                </div>
                                <span class="text-base-content/60">Login:</span>
                                <span class="font-semibold text-base-content">{{ $router->address }}</span>
                            </div>
                        </div>

                        {{-- Ping Status Message --}}
                        @if ($pingedId === $router->id)
                            <div class="animate-fade-in">
                                @if ($pingSuccess)
                                    <div
                                        class="flex items-center gap-2 p-2.5 bg-success/10 rounded-lg border border-success/20">
                                        <x-mary-icon name="o-check-circle" class="w-4 h-4 text-success" />
                                        <span class="text-sm font-medium text-success">Connection successful</span>
                                    </div>
                                @else
                                    <div
                                        class="flex items-center gap-2 p-2.5 bg-error/10 rounded-lg border border-error/20">
                                        <x-mary-icon name="o-x-circle" class="w-4 h-4 text-error" />
                                        <span class="text-sm font-medium text-error">Connection failed</span>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Card Footer --}}
                    <div class="px-5 pb-5">
                        <div class="flex items-center justify-between gap-2 pt-3 border-t border-base-300">
                            <div class="tooltip" data-tip="Test Connection">
                                <button wire:click="ping({{ $router->id }})" wire:loading.attr="disabled"
                                    wire:target="ping({{ $router->id }})"
                                    class="btn btn-circle btn-sm btn-ghost hover:bg-{{ $colorClass }}/10 hover:text-{{ $colorClass }} transition-colors">
                                    <x-mary-icon name="o-wifi" class="w-4 h-4" />
                                    <span wire:loading wire:target="ping({{ $router->id }})"
                                        class="loading loading-spinner loading-xs"></span>
                                </button>
                            </div>

                            <div class="flex-1"></div>

                            <a href="{{ route('routers.show', $router) }}" wire:navigate
                                class="btn btn-sm btn-{{ $colorClass }}/90 hover:btn-{{ $colorClass }} gap-2 group/btn">
                                <x-mary-icon name="o-eye" class="w-4 h-4" />
                                <span>View</span>
                            </a>

                            <div class="tooltip" data-tip="Edit Router">
                                <a href="{{ route('routers.edit', $router) }}" wire:navigate
                                    class="btn btn-circle btn-sm btn-ghost hover:bg-base-200">
                                    <x-mary-icon name="o-pencil" class="w-4 h-4" />
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            @empty
                <x-mary-card class="col-span-3 bg-base-100">
                    <div class="p-8 text-center opacity-70">No routers found.</div>
                </x-mary-card>
            @endforelse
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $routers->links() }}
        </div>
    </div>
</section>
