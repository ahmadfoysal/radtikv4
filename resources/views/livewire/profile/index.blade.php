<section class="w-full">
    {{-- Header --}}
    <x-mary-card class="mb-4 bg-base-100 border border-base-300 shadow-sm">
        <div class="px-4 py-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2">
                <x-mary-icon name="o-rectangle-group" class="w-6 h-6 text-primary" />
                <span class="font-semibold text-lg">User Profiles</span>
            </div>

            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
                <x-mary-input placeholder="Search profiles..." icon="o-magnifying-glass" class="w-full sm:w-72"
                    input-class="input-sm" wire:model.live.debounce.400ms="q" />

                <div class="flex items-center gap-2 sm:justify-end">
                    <x-mary-button icon="o-plus" label="Add Profile" class="btn-sm btn-primary"
                        href="{{ route('profiles.create') }}" wire:navigate />
                </div>
            </div>
        </div>
    </x-mary-card>

    {{-- Profile Cards Grid --}}
    <div class="px-2 sm:px-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse ($profiles as $profile)
                @php
                    // Rotate through semantic colors for visual variety
                    $colors = ['primary', 'secondary', 'accent', 'info', 'success', 'warning'];
                    $colorClass = $colors[$loop->index % count($colors)];
                @endphp

                <div
                    class="group bg-base-100 rounded-xl border border-base-300 overflow-hidden shadow-sm hover:shadow-lg hover:border-{{ $colorClass }}/30 transition-all duration-300">
                    {{-- Card Header with Gradient --}}
                    <div
                        class="relative bg-gradient-to-br from-{{ $colorClass }}/10 via-{{ $colorClass }}/5 to-transparent p-5 pb-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3 flex-1 min-w-0">
                                <div class="relative">
                                    <div
                                        class="p-3 bg-{{ $colorClass }}/10 rounded-lg border border-{{ $colorClass }}/20 group-hover:scale-105 transition-transform duration-300">
                                        @if ($profile->mac_binding)
                                            <x-mary-icon name="o-lock-closed"
                                                class="w-6 h-6 text-{{ $colorClass }}" />
                                        @else
                                            <x-mary-icon name="o-user" class="w-6 h-6 text-{{ $colorClass }}" />
                                        @endif
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0 pt-0.5">
                                    <h3
                                        class="font-bold text-lg truncate text-base-content group-hover:text-{{ $colorClass }} transition-colors">
                                        {{ $profile->name }}
                                    </h3>
                                    <div class="flex items-center gap-1.5 mt-1.5">
                                        <x-mary-icon name="o-banknotes" class="w-3.5 h-3.5 text-base-content/50" />
                                        <span
                                            class="text-sm text-base-content/70">৳{{ number_format($profile->price, 2) }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- MAC Binding Badge --}}
                            @if ($profile->mac_binding)
                                <div class="flex-shrink-0">
                                    <div class="px-2.5 py-1 bg-success/10 rounded-full border border-success/20">
                                        <span class="text-xs font-semibold text-success flex items-center gap-1">
                                            <x-mary-icon name="o-lock-closed" class="w-3.5 h-3.5" />
                                            MAC
                                        </span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Card Body --}}
                    <div class="p-5 pt-4 space-y-4">
                        {{-- Profile Statistics --}}
                        <div class="flex items-center justify-between pb-3 border-b border-base-300">
                            <div class="flex items-center gap-2">
                                <div class="p-1.5 bg-{{ $colorClass }}/10 rounded">
                                    <x-mary-icon name="o-ticket" class="w-3.5 h-3.5 text-{{ $colorClass }}" />
                                </div>
                                <span class="text-xs text-base-content/60">Vouchers:</span>
                            </div>
                            <span class="text-lg font-bold text-{{ $colorClass }}">
                                {{ $profile->vouchers_count ?? 0 }}
                            </span>
                        </div>

                        {{-- Profile Details --}}
                        <div class="grid grid-cols-2 gap-4 pt-1">
                            <div class="flex flex-col gap-1">
                                <span class="text-xs text-base-content/50">Rate Limit</span>
                                <span class="text-sm font-semibold text-base-content">
                                    {{ $profile->rate_limit ?: 'Unlimited' }}
                                </span>
                            </div>
                            <div class="flex flex-col gap-1">
                                <span class="text-xs text-base-content/50">Validity</span>
                                <span class="text-sm font-semibold text-base-content">
                                    {{ $profile->validity ?: 'Unlimited' }}
                                </span>
                            </div>
                        </div>

                        {{-- Description --}}
                        @if ($profile->description)
                            <div class="pt-2">
                                <p class="text-xs text-base-content/60 line-clamp-2">
                                    {{ $profile->description }}
                                </p>
                            </div>
                        @endif
                    </div>

                    {{-- Card Footer --}}
                    <div class="px-5 pb-5">
                        <div class="flex items-center justify-between gap-2 pt-3 border-t border-base-300">
                            <div class="flex items-center gap-2">
                                {{-- Edit --}}
                                <div class="tooltip" data-tip="Edit Profile">
                                    <a href="{{ route('profiles.edit', $profile) }}" wire:navigate
                                        class="btn btn-circle btn-sm btn-ghost hover:bg-{{ $colorClass }}/10 hover:text-{{ $colorClass }} transition-colors">
                                        <x-mary-icon name="o-pencil" class="w-4 h-4" />
                                    </a>
                                </div>

                                {{-- Delete --}}
                                <div class="tooltip" data-tip="Delete Profile">
                                    <button wire:click="delete({{ $profile->id }})" wire:loading.attr="disabled"
                                        wire:target="delete({{ $profile->id }})"
                                        onclick="return confirm('Delete profile {{ $profile->name }}?')"
                                        class="btn btn-circle btn-sm btn-ghost hover:bg-error/10 hover:text-error transition-colors">
                                        <x-mary-icon name="o-trash" class="w-4 h-4" />
                                        <span wire:loading wire:target="delete({{ $profile->id }})"
                                            class="loading loading-spinner loading-xs"></span>
                                    </button>
                                </div>
                            </div>

                            <div class="flex-1"></div>

                            {{-- View/Details Button --}}
                            <a href="{{ route('profiles.edit', $profile) }}" wire:navigate
                                class="btn btn-sm btn-{{ $colorClass }}/90 hover:btn-{{ $colorClass }} gap-2">
                                <x-mary-icon name="o-eye" class="w-4 h-4" />
                                <span>View</span>
                            </a>
                        </div>
                    </div>
                </div>

            @empty
                <x-mary-card class="col-span-3 bg-base-100">
                    <div class="p-8 text-center opacity-70">No profiles found.</div>
                </x-mary-card>
            @endforelse
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $profiles->links() }}
        </div>
    </div>
</section>
