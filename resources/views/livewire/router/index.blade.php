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
                    </div>

                    <div class="mt-3 flex items-center justify-end gap-1.5">
                        <x-mary-button icon="o-wifi" class="btn-ghost btn-xs hover:bg-base-100"
                            wire:click="ping({{ $router->id }})" />
                        <x-mary-button icon="o-pencil" class="btn-ghost btn-xs hover:bg-base-100"
                            href="{{ route('routers.edit', $router) }}" wire:navigate />
                        <x-mary-button icon="o-trash" class="btn-ghost btn-xs text-error hover:bg-base-100"
                            wire:click="delete({{ $router->id }})" />
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
