<section class="w-full">
    {{-- Header --}}
    <x-mary-card class="mb-4 bg-base-100 border border-base-300 shadow-sm">
        <div class="px-4 py-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2">
                <x-mary-icon name="o-server-stack" class="w-6 h-6 text-primary" />
                <span class="font-semibold text-lg">NAS Devices</span>
            </div>

            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
                <x-mary-input placeholder="Search NAS device..." icon="o-magnifying-glass" class="w-full sm:w-72"
                    input-class="input-sm" wire:model.live.debounce.400ms="q" />

                <div class="flex items-center gap-2 sm:justify-end">
                    <x-mary-button icon="o-plus" label="Add NAS Device" class="btn-sm btn-primary"
                        href="{{ route('nas-devices.create') }}" wire:navigate />
                </div>
            </div>
        </div>
    </x-mary-card>

    {{-- NAS Devices grid --}}
    <div class="px-2 sm:px-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse ($nasDevices as $nas)
                @php
                    $zone = $nas->zone?->name ?? '—';
                    $colors = ['primary', 'secondary', 'accent', 'info', 'success', 'warning'];
                    $colorClass = $colors[$loop->index % count($colors)];
                    $effectiveNasId = $nas->getEffectiveNasIdentifier() ?? 'Inherited';
                @endphp

                <div
                    class="group bg-base-100 rounded-xl border border-base-300 overflow-hidden shadow-sm hover:shadow-lg hover:border-{{ $colorClass }}/30 transition-all duration-300">
                    {{-- Card Header with Gradient --}}
                    <div
                        class="relative bg-gradient-to-br from-{{ $colorClass }}/10 via-{{ $colorClass }}/5 to-transparent p-5 pb-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3 flex-1 min-w-0">
                                <div
                                    class="p-2.5 rounded-lg bg-{{ $colorClass }}/10 ring-2 ring-{{ $colorClass }}/20 shrink-0">
                                    <x-mary-icon name="o-server" class="w-5 h-5 text-{{ $colorClass }}" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-base font-semibold truncate">
                                        {{ $nas->name }}
                                    </h3>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <x-mary-icon name="o-globe-alt" class="w-3.5 h-3.5 text-base-content/50" />
                                        <span class="text-xs text-base-content/70 truncate">{{ $nas->address }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Card Body --}}
                    <div class="px-5 py-4 space-y-3 bg-base-100/75">
                        {{-- Parent Router Info --}}
                        <div class="flex items-start gap-2">
                            <x-mary-icon name="o-arrow-up-circle" class="w-4 h-4 text-base-content/50 mt-0.5 shrink-0" />
                            <div class="flex-1 min-w-0">
                                <div class="text-[10px] font-medium uppercase tracking-wider text-base-content/50 mb-0.5">
                                    Parent Router
                                </div>
                                <div class="text-sm font-medium truncate">
                                    {{ $nas->parentRouter?->name ?? '—' }}
                                </div>
                            </div>
                        </div>

                        {{-- RADIUS Server Info --}}
                        <div class="flex items-start gap-2">
                            <x-mary-icon name="o-server-stack" class="w-4 h-4 text-base-content/50 mt-0.5 shrink-0" />
                            <div class="flex-1 min-w-0">
                                <div class="text-[10px] font-medium uppercase tracking-wider text-base-content/50 mb-0.5">
                                    RADIUS Server
                                </div>
                                <div class="text-sm font-medium truncate">
                                    {{ $nas->radiusServer?->name ?? '—' }}
                                </div>
                            </div>
                        </div>

                        {{-- NAS Identifier Info --}}
                        <div class="flex items-start gap-2">
                            <x-mary-icon name="o-identification" class="w-4 h-4 text-base-content/50 mt-0.5 shrink-0" />
                            <div class="flex-1 min-w-0">
                                <div class="text-[10px] font-medium uppercase tracking-wider text-base-content/50 mb-0.5">
                                    Effective NAS ID
                                </div>
                                <div class="text-sm font-medium truncate">
                                    {{ $effectiveNasId }}
                                </div>
                            </div>
                        </div>

                        {{-- Zone --}}
                        @if($nas->zone)
                        <div class="flex items-start gap-2">
                            <x-mary-icon name="o-map-pin" class="w-4 h-4 text-base-content/50 mt-0.5 shrink-0" />
                            <div class="flex-1 min-w-0">
                                <div class="text-[10px] font-medium uppercase tracking-wider text-base-content/50 mb-0.5">
                                    Zone
                                </div>
                                <div class="text-sm font-medium truncate">
                                    {{ $zone }}
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    {{-- Card Actions --}}
                    <div class="px-5 py-3 border-t border-base-300/60 bg-base-100/50">
                        <div class="flex items-center justify-end gap-2">
                            <x-mary-button icon="o-eye" class="btn-xs btn-ghost" 
                                href="{{ route('nas-devices.show', $nas->id) }}" wire:navigate />
                            <x-mary-button icon="o-pencil" class="btn-xs btn-ghost" 
                                href="{{ route('nas-devices.edit', $nas->id) }}" wire:navigate />
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full">
                    <x-mary-card class="text-center py-12">
                        <x-mary-icon name="o-server-stack" class="w-16 h-16 mx-auto text-base-content/30 mb-4" />
                        <p class="text-base-content/60 mb-4">No NAS devices found.</p>
                        <x-mary-button label="Add Your First NAS Device" class="btn-primary btn-sm"
                            href="{{ route('nas-devices.create') }}" wire:navigate />
                    </x-mary-card>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if ($nasDevices->hasPages())
            <div class="mt-6">
                {{ $nasDevices->links() }}
            </div>
        @endif
    </div>
</section>
