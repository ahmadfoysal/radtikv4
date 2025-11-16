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
            <x-mary-select class="w-32 sm:w-40" placeholder="Router" :options="$routers->map(fn($r) => ['id' => $r->id, 'name' => $r->name])->toArray()" option-label="name"
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

            {{-- Channel group: All | MT | Radius --}}
            <div class="join">
                <button class="btn btn-sm join-item {{ $channel === 'all' ? 'btn-primary' : 'btn-ghost' }}"
                    wire:click="$set('channel','all')">
                    All
                </button>
                <button class="btn btn-sm join-item {{ $channel === 'mikrotik' ? 'btn-primary' : 'btn-ghost' }}"
                    wire:click="$set('channel','mikrotik')">
                    MT
                </button>
                <button class="btn btn-sm join-item {{ $channel === 'radius' ? 'btn-primary' : 'btn-ghost' }}"
                    wire:click="$set('channel','radius')">
                    Radius
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
                    $iconC = $channelColor($v->is_radius);

                    $profileLabel = $v->is_radius
                        ? $v->radiusProfile->name ?? 'RADIUS Profile'
                        : $v->router_profile ?? 'MikroTik Profile';
                @endphp

                <div class="bg-base-200 rounded-2xl p-4 shadow-sm hover:shadow-md">

                    <div class="flex items-center gap-3">
                        <div class="p-3 rounded-xl bg-base-100">
                            <x-mary-icon name="s-ticket" class="w-6 h-6 {{ $iconC }}" />
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="font-semibold truncate text-base">
                                {{ $v->username }}
                            </div>

                            <div class="mt-1 flex items-center gap-1 text-xs">
                                <span class="badge badge-xs {{ $v->is_radius ? 'badge-accent' : 'badge-primary' }}">
                                    {{ $v->is_radius ? 'RADIUS' : 'MikroTik' }}
                                </span>

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
                            <span class="font-medium">{{ $profileLabel }}</span>
                        </div>

                        {{-- Expiry --}}
                        <div class="flex items-center gap-1">
                            <span class="opacity-60">Expiry:</span>
                            @if ($v->expires_at)
                                <span class="font-medium">
                                    {{ \Illuminate\Support\Carbon::parse($v->expires_at)->toDateString() }}
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
                <x-mary-card class="col-span-full bg-base-200 p-8 text-center opacity-70">
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
