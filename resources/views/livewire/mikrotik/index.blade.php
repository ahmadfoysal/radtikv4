<div>
    {{-- Router Index — 3-column cards without pagination (MaryUI prefix: mary-) --}}

    <x-mary-card>
        <x-slot name="title">
            <div class="flex items-center gap-2">
                <x-mary-icon name="o-server-stack" class="w-5 h-5" />
                Routers
            </div>
        </x-slot>

        {{-- Optional top actions --}}
        <x-slot name="actions">
            <x-mary-button icon="o-plus" class="btn-primary btn-sm" wire:click="create">Add Router</x-mary-button>
        </x-slot>

        {{-- Grid layout --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($routers as $r)
                <x-mary-card class="h-full">
                    <x-slot name="title">
                        <div class="flex items-center justify-between">
                            <div class="font-semibold truncate">
                                {{ $r['name'] ?? 'Router' }}
                                <span class="opacity-60 text-xs">({{ $r['ip'] ?? '0.0.0.0' }})</span>
                            </div>

                            @php
                                $status = $r['status'] ?? 'Online';
                                $badge =
                                    [
                                        'Online' => 'badge-success',
                                        'Offline' => 'badge-error',
                                        'Degraded' => 'badge-warning',
                                    ][$status] ?? 'badge-ghost';
                            @endphp

                            <x-mary-badge class="{{ $badge }}">{{ $status }}</x-mary-badge>
                        </div>
                    </x-slot>

                    {{-- Router Info --}}
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="opacity-70">Hostname</span>
                            <span class="font-medium truncate">{{ $r['host'] ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="opacity-70">Protocol</span>
                            <span class="font-medium">{{ strtoupper($r['protocol'] ?? 'API') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="opacity-70">Port</span>
                            <span class="font-medium">{{ $r['port'] ?? 8728 }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="opacity-70">Uptime</span>
                            <span class="font-medium">{{ $r['uptime'] ?? '—' }}</span>
                        </div>
                    </div>

                    {{-- Card actions --}}
                    <x-slot name="actions">
                        <x-mary-button icon="o-eye" class="btn-ghost btn-sm"
                            wire:click="show('{{ $r['id'] }}')">View</x-mary-button>
                        <x-mary-button icon="o-pencil-square" class="btn-ghost btn-sm"
                            wire:click="edit('{{ $r['id'] }}')">Edit</x-mary-button>
                        <x-mary-button icon="o-power" class="btn-ghost btn-sm"
                            wire:click="toggle('{{ $r['id'] }}')">
                            {{ ($r['status'] ?? '') === 'Online' ? 'Disable' : 'Enable' }}
                        </x-mary-button>
                    </x-slot>
                </x-mary-card>
            @empty
                {{-- Empty state --}}
                <div class="col-span-full">
                    <div class="p-8 rounded-box bg-base-200 text-center">
                        <div class="text-lg font-semibold mb-1">No routers found</div>
                        <div class="opacity-70 text-sm mb-3">
                            Add your first MikroTik router to get started.
                        </div>
                        <x-mary-button icon="o-plus" class="btn-primary btn-sm" wire:click="create">
                            Add Router
                        </x-mary-button>
                    </div>
                </div>
            @endforelse
        </div>
    </x-mary-card>

</div>
