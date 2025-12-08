<section class="w-full">
    {{-- Header --}}
    <x-mary-card class="mb-4 bg-base-200 border-0 shadow-sm">
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
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
            @forelse ($routers as $router)
                @php
                    $zone = $router->zone ?? ($router->location ?? ($router->note ?: '—'));
                    // এলোমেলো আইকন কালার (তুমি চাইলে ফিক্সডও করতে পারো)
                    $colors = [
                        'text-primary',
                        'text-success',
                        'text-warning',
                        'text-error',
                        'text-info',
                        'text-pink-500',
                    ];
                    $iconColor = $colors[$loop->index % count($colors)];
                    $package = $router->package ?? [];
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

                <div class="bg-base-200 rounded-2xl p-4 space-y-3 shadow-sm hover:shadow-md transition duration-300">
                    <div class="flex items-center gap-2">
                        <div class="p-2 rounded-xl bg-base-100">
                            <x-mary-icon name="s-server" class="w-6 h-6 {{ $iconColor }}" />
                        </div>

                        <div class="flex-1 min-w-0 leading-tight">
                            <div class="font-semibold truncate text-sm sm:text-base">
                                {{ $router->name }}
                            </div>
                            <div class="text-xs sm:text-sm opacity-70 truncate flex items-center gap-1">
                                <x-mary-icon name="o-map-pin" class="w-3.5 h-3.5" />
                                {{ $zone }}
                            </div>
                        </div>

                        <div class="flex items-center gap-1">
                            {{-- ✅ পিং ফলাফল UI --}}
                            @if (isset($pingStatuses[$router->id]))
                                @if ($pingStatuses[$router->id] === 'ok')
                                    <div class="tooltip tooltip-left" data-tip="Ping OK">
                                        <x-mary-icon name="o-check-circle" class="w-5 h-5 text-success animate-pulse" />
                                    </div>
                                @elseif ($pingStatuses[$router->id] === 'fail')
                                    <div class="tooltip tooltip-left" data-tip="Ping Failed">
                                        <x-mary-icon name="o-x-circle" class="w-5 h-5 text-error animate-pulse" />
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>

                    <div class="text-[11px] space-y-1.5">
                        <div class="flex flex-wrap gap-2 text-[10px] sm:text-xs opacity-70">
                            <span
                                class="inline-flex items-center gap-1 bg-blue-500/10 text-blue-600 px-2 py-1 rounded-full">
                                <x-mary-icon name="o-arrow-path" class="w-3.5 h-3.5" />
                                Cycle: <strong>{{ ucfirst($package['billing_cycle'] ?? 'N/A') }}</strong>
                            </span>
                            <span
                                class="inline-flex items-center gap-1 bg-orange-500/10 text-orange-600 px-2 py-1 rounded-full">
                                <x-mary-icon name="o-calendar-days" class="w-3.5 h-3.5" />
                                Exp: <strong>{{ $expiryDate }}</strong>
                            </span>
                            @if (isset($package['auto_renew_allowed']))
                                <span class="inline-flex items-center gap-1">
                                    <x-mary-icon name="o-bolt" class="w-3.5 h-3.5" />
                                    Auto renew: {{ $package['auto_renew_allowed'] ? 'Yes' : 'No' }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="space-y-2 text-[11px]">
                        <div class="flex justify-between uppercase font-semibold opacity-70">
                            <span>Users {{ $totalUsers }}{{ $userLimit ? ' / ' . $userLimit : '' }}</span>
                            <span>{{ $usagePercent !== null ? $usagePercent . '%' : 'Unlimited' }}</span>
                        </div>
                        @if ($usagePercent !== null)
                            <progress class="progress progress-primary h-1" value="{{ $usagePercent }}"
                                max="100"></progress>
                        @else
                            <div class="h-1.5 rounded-full bg-base-300"></div>
                        @endif
                        <div class="flex flex-wrap gap-2">
                            <span class="badge badge-sm badge-success gap-1 text-xs">
                                <x-mary-icon name="o-check" class="w-3 h-3" /> {{ $activeUsers }} Active
                            </span>
                            <span class="badge badge-sm gap-1 text-xs">
                                <x-mary-icon name="o-pause" class="w-3 h-3" /> {{ $inactiveUsers }} Inactive
                            </span>
                            <span class="badge badge-sm badge-warning gap-1 text-xs">
                                <x-mary-icon name="o-clock" class="w-3 h-3" /> {{ $expiredUsers }} Expired
                            </span>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3 text-[11px] uppercase tracking-wide opacity-70">
                        <span class="flex items-center gap-1">
                            <x-mary-icon name="o-user-group" class="w-3.5 h-3.5" />
                            Limit {{ $userLimit ?? '∞' }}
                        </span>
                        <span class="flex items-center gap-1">
                            <x-mary-icon name="o-cpu-chip" class="w-3.5 h-3.5" />
                            Port {{ $router->port }} / SSH {{ $router->ssh_port ?? '—' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-end gap-1.5">

                        {{-- Ping result text --}}
                        @if ($pingedId === $router->id)
                            @if ($pingSuccess)
                                <span class="text-xs font-semibold text-success">Ping OK</span>
                            @else
                                <span class="text-xs font-semibold text-error">Ping Error</span>
                            @endif
                        @endif

                        {{-- 🔧 Install Scripts button --}}
                        <div class="tooltip" data-tip="Install Scripts">
                            <x-mary-button icon="o-cog-6-tooth"
                                class="btn-ghost btn-xs !px-2 text-primary hover:text-primary/80 transition-colors"
                                wire:click="installScripts({{ $router->id }})"
                                spinner="installScripts({{ $router->id }})" wire:loading.attr="disabled"
                                wire:target="installScripts({{ $router->id }})" />
                        </div>

                        {{-- Ping Button --}}
                        <div class="tooltip" data-tip="Ping Router">
                            <x-mary-button icon="o-wifi" class="btn-ghost btn-xs !px-2"
                                wire:click="ping({{ $router->id }})" spinner="ping({{ $router->id }})"
                                wire:loading.attr="disabled" wire:target="ping({{ $router->id }})" />
                        </div>

                        {{-- View Details --}}
                        <div class="tooltip" data-tip="View Router">
                            <x-mary-button icon="o-eye" class="btn-ghost btn-xs hover:bg-base-100"
                                href="{{ route('routers.show', $router) }}" wire:navigate />
                        </div>

                        {{-- Edit --}}
                        <div class="tooltip" data-tip="Edit Router">
                            <x-mary-button icon="o-pencil" class="btn-ghost btn-xs hover:bg-base-100"
                                href="{{ route('routers.edit', $router) }}" wire:navigate />
                        </div>

                        {{-- Delete --}}
                        <div class="tooltip" data-tip="Delete Router">
                            <x-mary-button icon="o-trash"
                                class="btn-ghost btn-xs !px-2 text-error hover:text-error/80 transition-colors"
                                wire:click="delete({{ $router->id }})" spinner="delete({{ $router->id }})"
                                wire:loading.attr="disabled" wire:target="delete({{ $router->id }})"
                                onclick="return confirm('Are you sure you want to delete {{ $router->name }}?')" />
                        </div>

                    </div>


                </div>

            @empty
                <x-mary-card class="col-span-full bg-base-200">
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
