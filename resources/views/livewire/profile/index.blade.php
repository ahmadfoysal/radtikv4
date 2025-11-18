<section class="w-full">

    {{-- Header --}}
    <x-mary-card class="mb-4 bg-base-200 border-0 shadow-sm">
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
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">

            @forelse ($profiles as $profile)
                @php
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
                            <x-mary-icon name="o-rectangle-group" class="w-6 h-6 {{ $iconColor }}" />
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="font-semibold truncate text-base">
                                {{ $profile->name }}
                            </div>

                            {{-- Rate limit --}}
                            <div class="text-sm opacity-70 truncate">
                                {{ $profile->rate_limit ? 'Rate: ' . $profile->rate_limit : 'No rate limit' }}
                            </div>

                            {{-- Validity + Price same line --}}
                            <div class="mt-1 text-xs flex flex-wrap gap-2 items-center">
                                <span class="opacity-70">
                                    {{ $profile->validity ? 'Validity: ' . $profile->validity : 'No validity' }}
                                </span>

                                <span class="badge badge-ghost badge-xs">
                                    ৳{{ number_format($profile->price, 2) }}
                                </span>
                            </div>
                        </div>

                    </div>

                    <div class="mt-3 text-xs flex items-center justify-between">

                        {{-- Left: MAC Bind tag + short description --}}
                        <div class="flex flex-col gap-1">
                            @if ($profile->mac_binding)
                                <span class="badge badge-success badge-xs w-fit">MAC Bind</span>
                            @endif

                            @if ($profile->description)
                                <span class="opacity-60 line-clamp-2">
                                    {{ $profile->description }}
                                </span>
                            @endif
                        </div>

                        {{-- Right: actions --}}
                        <div class="flex items-center gap-1.5">

                            {{-- Edit --}}
                            <div class="tooltip" data-tip="Edit Profile">
                                <x-mary-button icon="o-pencil" class="btn-ghost btn-xs hover:bg-base-100"
                                    href="{{ route('profiles.edit', $profile) }}" wire:navigate />
                            </div>

                            {{-- Delete --}}
                            <div class="tooltip" data-tip="Delete Profile">
                                <x-mary-button icon="o-trash"
                                    class="btn-ghost btn-xs text-error hover:text-error/80 !px-2"
                                    wire:click="delete({{ $profile->id }})" spinner="delete({{ $profile->id }})"
                                    wire:loading.attr="disabled" wire:target="delete({{ $profile->id }})"
                                    onclick="return confirm('Delete profile {{ $profile->name }}?')" />
                            </div>

                        </div>
                    </div>
                </div>

            @empty
                <x-mary-card class="col-span-full bg-base-200">
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
