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
                    <x-mary-button icon="o-document-arrow-down" label="Import" class="btn-sm btn-success"
                        href="{{ route('routers.import') }}" wire:navigate />
                </div>
            </div>
        </div>
    </x-mary-card>

    {{-- Router stats grid --}}
    <div class="px-2 sm:px-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @forelse ($routers as $router)
            @php
            // Get zone name from relation, fallback to note or dash
            $zone = $router->zone?->name ?? $router->note ?? '—';
            // Rotate through semantic colors for visual variety
            $colors = [
            'text-primary',
            'text-secondary',
            'text-accent',
            'text-info',
            'text-success',
            'text-warning',
            ];
            $iconColor = $colors[$loop->index % count($colors)];
            // Ensure package is always an array, handle JSON string case
            $packageRaw = $router->package;
            if (is_string($packageRaw)) {
            $package = json_decode($packageRaw, true) ?? [];
            } else {
            $package = is_array($packageRaw) ? $packageRaw : [];
            }
            $packageName = $package['name'] ?? 'No package assigned';
            $expiryDate = isset($package['end_date'])
            ? \Illuminate\Support\Carbon::parse($package['end_date'])->format('M d, Y')
            : '—';
            $userLimit = $package['user_limit'] ?? null;
            $totalUsers = $router->total_vouchers_count ?? 0;
            $activeUsers = $router->active_vouchers_count ?? 0;
            $expiredUsers = $router->expired_vouchers_count ?? 0;
            $inactiveUsers = max($totalUsers - $activeUsers, 0);
            $usagePercent = $userLimit ? min(100, (int) (($totalUsers / $userLimit) * 100)) : null;
            @endphp

            <div class="bg-base-100 p-4 space-y-4 shadow-sm hover:shadow-md transition-all duration-300 border border-base-300">
                {{-- Header Section --}}
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3 flex-1 min-w-0">
                        <div class="p-2.5 bg-base-200 flex-shrink-0">
                            <x-mary-icon name="s-server" class="w-6 h-6 {{ $iconColor }}" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-base truncate mb-1">
                                {{ $router->name }}
                            </h3>
                            <div class="text-xs text-base-content/60 flex items-center gap-1.5">
                                <x-mary-icon name="o-map-pin" class="w-3.5 h-3.5 flex-shrink-0" />
                                <span class="truncate">{{ $zone }}</span>
                            </div>
                        </div>
                    </div>
                    {{-- Status Indicator --}}
                    <div class="flex-shrink-0">
                        @if (isset($pingStatuses[$router->id]))
                        @if ($pingStatuses[$router->id] === 'ok')
                        <div class="tooltip tooltip-left" data-tip="Router Online">
                            <x-mary-icon name="o-check-circle" class="w-5 h-5 text-success" />
                        </div>
                        @elseif ($pingStatuses[$router->id] === 'fail')
                        <div class="tooltip tooltip-left" data-tip="Router Offline">
                            <x-mary-icon name="o-x-circle" class="w-5 h-5 text-error" />
                        </div>
                        @endif
                        @endif
                    </div>
                </div>

                {{-- Package Information --}}
                @if (!empty($package) && is_array($package) && isset($package['name']))
                <div class="bg-base-200/50 p-3 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-medium text-base-content/70">Package</span>
                        <span class="font-semibold text-primary">{{ $packageName }}</span>
                    </div>
                    <div class="flex flex-wrap gap-2 px-3">
                        <x-mary-badge value="{{ ucfirst($package['billing_cycle'] ?? 'N/A') }}" class="badge-primary">
                            <x-slot:icon>
                                <x-mary-icon name="o-arrow-path" class="w-3.5 h-3.5" />
                            </x-slot:icon>
                        </x-mary-badge>
                        <x-mary-badge value="{{ $expiryDate }}" class="badge-warning">
                            <x-slot:icon>
                                <x-mary-icon name="o-calendar-days" class="w-3.5 h-3.5" />
                            </x-slot:icon>
                        </x-mary-badge>
                        @if (isset($package['auto_renew_allowed']) && $package['auto_renew_allowed'])
                        <x-mary-badge value="Auto Renew" class="badge-success">
                            <x-slot:icon>
                                <x-mary-icon name="o-bolt" class="w-3.5 h-3.5" />
                            </x-slot:icon>
                        </x-mary-badge>
                        @endif
                    </div>
                </div>
                @endif

                {{-- User Statistics Section --}}
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-semibold text-base-content/70">User Usage</span>
                        <span class="font-bold text-primary">
                            {{ $totalUsers }}{{ $userLimit ? ' / ' . $userLimit : '' }}
                        </span>
                    </div>
                    @if ($usagePercent !== null)
                    <div class="space-y-1.5">
                        <progress class="progress progress-primary h-2 w-full" value="{{ $usagePercent }}" max="100"></progress>
                        <div class="text-[10px] text-base-content/50 text-right">{{ $usagePercent }}% used</div>
                    </div>
                    @else
                    <div class="h-2 bg-base-300"></div>
                    @endif
                    <div class="flex flex-wrap gap-1.5 pt-1">
                        <x-mary-badge value="{{ $activeUsers }}" class="badge-success badge-sm">
                            <x-slot:icon>
                                <x-mary-icon name="o-check-circle" class="w-3 h-3" />
                            </x-slot:icon>
                        </x-mary-badge>
                        <x-mary-badge value="{{ $inactiveUsers }}" class="badge-ghost badge-sm">
                            <x-slot:icon>
                                <x-mary-icon name="o-pause-circle" class="w-3 h-3" />
                            </x-slot:icon>
                        </x-mary-badge>
                        <x-mary-badge value="{{ $expiredUsers }}" class="badge-warning badge-sm">
                            <x-slot:icon>
                                <x-mary-icon name="o-clock" class="w-3 h-3" />
                            </x-slot:icon>
                        </x-mary-badge>
                    </div>
                </div>

                {{-- Technical Details --}}
                <div class="flex flex-wrap gap-3 pt-2 border-t border-base-300 text-[11px] text-base-content/60">
                    <div class="flex items-center gap-1.5">
                        <x-mary-icon name="o-user-group" class="w-3.5 h-3.5" />
                        <span>Limit: <strong class="text-base-content">{{ $userLimit ?? '∞' }}</strong></span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <x-mary-icon name="o-cpu-chip" class="w-3.5 h-3.5" />
                        <span>API: <strong class="text-base-content">{{ $router->port }}</strong></span>
                    </div>
                    @if ($router->ssh_port)
                    <div class="flex items-center gap-1.5">
                        <x-mary-icon name="o-key" class="w-3.5 h-3.5" />
                        <span>SSH: <strong class="text-base-content">{{ $router->ssh_port }}</strong></span>
                    </div>
                    @endif
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center justify-between gap-2 pt-2 border-t border-base-300">
                    {{-- Ping Status Message --}}
                    <div class="flex-1">
                        @if ($pingedId === $router->id)
                        @if ($pingSuccess)
                        <span class="text-xs font-medium text-success flex items-center gap-1">
                            <x-mary-icon name="o-check-circle" class="w-3.5 h-3.5" />
                            Ping OK
                        </span>
                        @else
                        <span class="text-xs font-medium text-error flex items-center gap-1">
                            <x-mary-icon name="o-x-circle" class="w-3.5 h-3.5" />
                            Ping Failed
                        </span>
                        @endif
                        @endif
                    </div>

                    {{-- Action Buttons Group --}}
                    <div class="flex items-center gap-1">
                        <div class="tooltip" data-tip="Install Scripts">
                            <x-mary-button icon="o-cog-6-tooth"
                                class="btn-ghost btn-xs !px-2 text-primary hover:bg-primary/10"
                                wire:click="installScripts({{ $router->id }})"
                                spinner="installScripts({{ $router->id }})"
                                wire:loading.attr="disabled"
                                wire:target="installScripts({{ $router->id }})" />
                        </div>
                        <div class="tooltip" data-tip="Ping Router">
                            <x-mary-button icon="o-wifi"
                                class="btn-ghost btn-xs !px-2 hover:bg-base-200"
                                wire:click="ping({{ $router->id }})"
                                spinner="ping({{ $router->id }})"
                                wire:loading.attr="disabled"
                                wire:target="ping({{ $router->id }})" />
                        </div>
                        <div class="tooltip" data-tip="View Details">
                            <x-mary-button icon="o-eye"
                                class="btn-ghost btn-xs !px-2 hover:bg-base-200"
                                href="{{ route('routers.show', $router) }}"
                                wire:navigate />
                        </div>
                        <div class="tooltip" data-tip="Edit Router">
                            <x-mary-button icon="o-pencil"
                                class="btn-ghost btn-xs !px-2 hover:bg-base-200"
                                href="{{ route('routers.edit', $router) }}"
                                wire:navigate />
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