<section class="w-full">

    {{-- Compact Filter Bar --}}
    <div class="flex flex-wrap items-center justify-between gap-2 px-1 sm:px-0 mb-3">
        <div class="flex items-center gap-2">
            <x-mary-icon name="o-ticket" class="w-5 h-5 text-primary" />
            <span class="font-semibold">Vouchers</span>
        </div>

        <div class="flex flex-wrap items-center gap-2 justify-end">
            {{-- Search --}}
            <x-mary-input placeholder="Search..." icon="o-magnifying-glass" class="w-40" input-class="input-xs"
                wire:model.live="q" />

            {{-- Router Select --}}
            <x-mary-select class="w-32 sm:w-40" placeholder="Router" :options="$routers->map(fn($r) => ['id' => $r['id'], 'name' => $r['name']])->toArray()" option-label="name"
                option-value="id" wire:model.live="routerFilter" />

            {{-- Status group: All | Active | Inactive | Disabled --}}
            <div class="join">
                <button class="btn btn-sm join-item {{ $status === 'all' ? 'btn-primary' : 'btn-ghost' }}"
                    wire:click="$set('status','all')">
                    All
                </button>
                <button class="btn btn-sm join-item {{ $status === 'active' ? 'btn-primary' : 'btn-ghost' }}"
                    wire:click="$set('status','active')">
                    Active
                </button>
                <button class="btn btn-sm join-item {{ $status === 'inactive' ? 'btn-primary' : 'btn-ghost' }}"
                    wire:click="$set('status','inactive')">
                    Inactive
                </button>
                <button class="btn btn-sm join-item {{ $status === 'disabled' ? 'btn-primary' : 'btn-ghost' }}"
                    wire:click="$set('status','disabled')">
                    Disabled
                </button>
            </div>


            {{-- Generate --}}
            <x-mary-button icon="o-plus" label="Generate" class="btn-sm btn-primary"
                href="{{ route('vouchers.generate') }}" wire:navigate />
        </div>
    </div>

    {{-- Voucher Grid --}}
    <div class="px-2 sm:px-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">

            @forelse($vouchers as $v)
                @php
                    $badge = $statusColor($v->status);
                @endphp

                <div class="bg-base-100 p-4 shadow-sm hover:shadow-md border border-base-300">

                    <div class="flex items-center gap-3">
                        <div class="p-3 bg-base-100">
                            <x-mary-icon name="s-ticket" class="w-6 h-6 text-primary" />
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="font-semibold truncate text-base">
                                {{ $v->username }}
                            </div>

                            <div class="mt-1 flex items-center gap-1 text-xs">
                                <span class="badge badge-xs {{ $badge }} capitalize">
                                    {{ $v->status }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 text-xs flex flex-wrap gap-x-4 gap-y-1">

                        {{-- Profile --}}
                        <div class="flex items-center gap-1">
                            <span class="opacity-60">Profile:</span>
                            <span class="font-medium">{{ $v->profile->name }}</span>
                        </div>

                        {{-- Expiry --}}
                        <div class="flex items-center gap-1">
                            <span class="opacity-60">Expiry:</span>
                            @if ($v->expires_at)
                                <span class="font-medium">
                                    @userDate($v->expires_at)
                                </span>
                            @else
                                <span class="opacity-50">Not activated</span>
                            @endif
                        </div>

                        {{-- MAC --}}
                        <div class="flex items-center gap-1">
                            <span class="opacity-60">MAC:</span>
                            @if ($v->mac_address)
                                <span class="font-medium">{{ $v->mac_address }}</span>
                            @else
                                <span class="opacity-50">00:00:00:00:00:00</span>
                            @endif
                        </div>

                        {{-- Data Usage (Download / Upload) --}}
                        @if ($v->bytes_out > 0 || $v->bytes_in > 0)
                            <div class="flex items-center gap-1">
                                <span class="opacity-60">Data:</span>
                                <div class="flex items-center gap-0.5" title="Download / Upload">
                                    <x-mary-icon name="o-arrow-down" class="w-3 h-3 text-success" />
                                    <span
                                        class="font-medium">{{ \Illuminate\Support\Number::fileSize($v->bytes_out ?? 0) }}</span>
                                    <span class="opacity-40 px-1">/</span>
                                    <x-mary-icon name="o-arrow-up" class="w-3 h-3 text-warning" />
                                    <span
                                        class="font-medium">{{ \Illuminate\Support\Number::fileSize($v->bytes_in ?? 0) }}</span>
                                </div>
                            </div>
                        @endif

                    </div>

                    <div class="mt-3 flex items-center justify-end gap-1.5">
                        {{-- Copy --}}
                        <div class="tooltip" data-tip="Copy username">
                            <button class="btn btn-ghost btn-xs !px-2"
                                x-on:click="navigator.clipboard.writeText('{{ $v->username }}')">
                                <x-mary-icon name="o-clipboard" class="w-4 h-4" />
                            </button>
                        </div>

                        {{-- Edit --}}
                        <x-mary-button icon="o-pencil" class="btn-ghost btn-xs !px-2"
                            href="{{ route('vouchers.edit', $v) }}" wire:navigate />

                        {{-- Disable / Enable --}}
                        <x-mary-button icon="o-no-symbol"
                            class="btn-ghost btn-xs !px-2 {{ $v->status === 'disabled' ? 'text-success' : 'text-warning' }}"
                            wire:click="toggleDisable({{ $v->id }})"
                            spinner="toggleDisable({{ $v->id }})" />

                        {{-- Delete --}}
                        <x-mary-button icon="o-trash" class="btn-ghost btn-xs !px-2 text-error"
                            wire:click="delete({{ $v->id }})" spinner="delete({{ $v->id }})"
                            onclick="return confirm('Delete voucher {{ $v->username }}?')" />
                    </div>
                </div>

            @empty
                <x-mary-card class="col-span-full bg-base-100 p-8 text-center opacity-70">
                    No vouchers found.
                </x-mary-card>
            @endforelse

        </div>

        {{-- <div class="mt-6">
            {{ $vouchers->links() }}
        </div> --}}

        {{-- Lazy Load: Load more button --}}
        @if ($vouchers->hasMorePages())
            <div class="mt-6 flex justify-center">
                <x-mary-button class="btn-outline btn-sm" wire:click="loadMore" spinner="loadMore">
                    Load more
                </x-mary-button>
            </div>
        @endif
    </div>
</section>
