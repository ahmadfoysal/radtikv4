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
                @endphp

                <div class="bg-base-200 rounded-2xl p-4 shadow-sm hover:shadow-md transition duration-300">
                    <div class="flex items-center gap-3">
                        <div class="p-3 rounded-xl bg-base-100">
                            <x-mary-icon name="s-server" class="w-6 h-6 {{ $iconColor }}" />
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="font-semibold truncate text-base">
                                {{ $router->name }}
                            </div>
                            <div class="text-sm opacity-70 truncate">
                                {{ $zone }}
                            </div>
                        </div>

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

                    <div class="mt-3 flex items-center justify-end gap-1.5">

                        {{-- 🟢 Ping result text --}}
                        @if ($pingedId === $router->id)
                            @if ($pingSuccess)
                                <span class="text-xs font-semibold text-success">Ping OK</span>
                            @else
                                <span class="text-xs font-semibold text-error">Ping Error</span>
                            @endif
                        @endif

                        {{-- ✅ Tooltip সহ Ping বাটন --}}
                        <div class="tooltip" data-tip="Ping Router">
                            <x-mary-button icon="o-wifi" class="btn-ghost btn-xs !px-2"
                                wire:click="ping({{ $router->id }})" spinner="ping({{ $router->id }})"
                                wire:loading.attr="disabled" wire:target="ping({{ $router->id }})" />
                        </div>

                        {{-- Tooltip সহ Edit --}}
                        <div class="tooltip" data-tip="Edit Router">
                            <x-mary-button icon="o-pencil" class="btn-ghost btn-xs hover:bg-base-100"
                                href="{{ route('routers.edit', $router) }}" wire:navigate />
                        </div>

                        {{-- Tooltip সহ Delete --}}
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
