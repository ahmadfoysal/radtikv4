<section class="w-full">
    {{-- Header + Filters --}}
    <x-mary-card class="mb-4 bg-base-200 border-0 shadow-sm">
        <div class="px-4 py-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2">
                <x-mary-icon name="o-ticket" class="w-6 h-6 text-primary" />
                <span class="font-semibold text-lg">Vouchers</span>
            </div>

            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
                {{-- search --}}
                <x-mary-input placeholder="Search username/batch..." icon="o-magnifying-glass" class="w-full sm:w-64"
                    input-class="input-sm" wire:model.live.debounce.400ms="q" />

                {{-- channel --}}
                <x-mary-select label="Channel" class="min-w-36" :options="[
                    ['id' => 'all', 'name' => 'All'],
                    ['id' => 'mikrotik', 'name' => 'MikroTik'],
                    ['id' => 'radius', 'name' => 'Radius'],
                ]" option-label="name" option-value="id"
                    wire:model="channel" />

                {{-- status --}}
                <x-mary-select label="Status" class="min-w-36" :options="[
                    ['id' => 'all', 'name' => 'All'],
                    ['id' => 'new', 'name' => 'New'],
                    ['id' => 'delivered', 'name' => 'Delivered'],
                    ['id' => 'active', 'name' => 'Active'],
                    ['id' => 'expired', 'name' => 'Expired'],
                    ['id' => 'used', 'name' => 'Used'],
                ]" option-label="name" option-value="id"
                    wire:model="status" />

                {{-- created by --}}
                <x-mary-select label="Created By" class="min-w-40" :options="[
                    ['id' => 'all', 'name' => 'All'],
                    ['id' => 'me', 'name' => 'Me'],
                    ...$creators->map(fn($u) => ['id' => (string) $u->id, 'name' => $u->name])->toArray(),
                ]" option-label="name"
                    option-value="id" wire:model="createdBy" />

                <div class="flex items-center gap-2 sm:justify-end">
                    <x-mary-button icon="o-plus" label="Generate" class="btn-sm btn-primary"
                        href="{{ route('vouchers.generate') }}" wire:navigate />
                </div>
            </div>
        </div>
    </x-mary-card>

    {{-- Voucher cards grid (same feel as Routers) --}}
    <div class="px-2 sm:px-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
            @forelse ($vouchers as $v)
                @php
                    $chan = $v->delivery_channel ?? '—';
                    $badge = $statusColor($v->status ?? '');
                    $iconC = $channelColor($v->delivery_channel ?? null);
                @endphp

                <div class="bg-base-200 rounded-2xl p-4 shadow-sm hover:shadow-md transition duration-300">
                    <div class="flex items-center gap-3">
                        <div class="p-3 rounded-xl bg-base-100">
                            <x-mary-icon name="s-ticket" class="w-6 h-6 {{ $iconC }}" />
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="font-semibold truncate text-base">
                                {{ $v->username }}
                            </div>
                            <div class="text-sm opacity-70 truncate">
                                Batch: {{ $v->batch ?? '—' }}
                            </div>
                        </div>

                        {{-- status badge --}}
                        <span class="badge badge-sm {{ $badge }} capitalize">
                            {{ $v->status ?? '—' }}
                        </span>
                    </div>

                    <div class="mt-3 text-xs flex flex-wrap items-center gap-2">
                        <span class="badge badge-ghost badge-sm">
                            {{ strtoupper($chan) }}
                        </span>

                        @if ($v->router_profile)
                            <span class="badge badge-primary badge-sm">MT: {{ $v->router_profile }}</span>
                        @endif
                        @if ($v->radius_profile)
                            <span class="badge badge-accent badge-sm">RADIUS: {{ $v->radius_profile }}</span>
                        @endif
                        @if ($v->router_id)
                            <span class="badge badge-neutral badge-sm">Router #{{ $v->router_id }}</span>
                        @endif
                        @if ($v->activated_at)
                            <span class="badge badge-success badge-outline badge-sm">
                                Active: {{ \Illuminate\Support\Carbon::parse($v->activated_at)->diffForHumans() }}
                            </span>
                        @endif
                        @if ($v->expires_at)
                            <span class="badge badge-warning badge-outline badge-sm">
                                Exp: {{ \Illuminate\Support\Carbon::parse($v->expires_at)->toDateString() }}
                            </span>
                        @endif
                    </div>

                    <div class="mt-3 flex items-center justify-end gap-1.5">
                        {{-- Optional actions (copy, delete etc.) --}}
                        <div class="tooltip" data-tip="Copy username">
                            <button class="btn btn-ghost btn-xs !px-2" x-data
                                x-on:click="navigator.clipboard.writeText('{{ $v->username }}')">
                                <x-mary-icon name="o-clipboard" class="w-4 h-4" />
                            </button>
                        </div>

                        {{-- (Optional) Delete --}}
                        <div class="tooltip" data-tip="Delete Voucher">
                            <x-mary-button icon="o-trash"
                                class="btn-ghost btn-xs !px-2 text-error hover:text-error/80 transition-colors"
                                wire:click="delete({{ $v->id }})" spinner="delete({{ $v->id }})"
                                wire:loading.attr="disabled" wire:target="delete({{ $v->id }})"
                                onclick="return confirm('Delete voucher {{ $v->username }}?')" />
                        </div>
                    </div>
                </div>
            @empty
                <x-mary-card class="col-span-full bg-base-200">
                    <div class="p-8 text-center opacity-70">No vouchers found.</div>
                </x-mary-card>
            @endforelse
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $vouchers->links() }}
        </div>
    </div>
</section>
