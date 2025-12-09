<div class="space-y-6">
    <div class="grid gap-4 md:grid-cols-2">
        <x-mary-card class="rounded-2xl border border-base-300 bg-base-200">
            <x-slot name="title">Assigned Routers</x-slot>
            <div class="grid grid-cols-3 gap-2 text-sm">
                <div>
                    <p class="text-xs text-base-content/60">Total</p>
                    <p class="text-2xl font-semibold">{{ number_format($routerStats['total']) }}</p>
                </div>
                <div>
                    <p class="text-xs text-base-content/60">Radius Enabled</p>
                    <p class="text-2xl font-semibold">{{ number_format($routerStats['radiusEnabled']) }}</p>
                </div>
                <div>
                    <p class="text-xs text-base-content/60">Login Access</p>
                    <p class="text-2xl font-semibold">{{ number_format($routerStats['withLogin']) }}</p>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="rounded-2xl border border-base-300 bg-base-200">
            <x-slot name="title">Voucher Activity</x-slot>
            <div class="grid grid-cols-3 gap-2 text-sm">
                <div>
                    <p class="text-xs text-base-content/60">Total</p>
                    <p class="text-2xl font-semibold">{{ number_format($voucherStats['total']) }}</p>
                </div>
                <div>
                    <p class="text-xs text-base-content/60">Active</p>
                    <p class="text-2xl font-semibold text-success">{{ number_format($voucherStats['active']) }}</p>
                </div>
                <div>
                    <p class="text-xs text-base-content/60">Expired</p>
                    <p class="text-2xl font-semibold text-warning">{{ number_format($voucherStats['expired']) }}</p>
                </div>
            </div>
        </x-mary-card>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <x-mary-card class="lg:col-span-2 rounded-2xl border border-base-300">
            <x-slot name="title">Routers You Can Access</x-slot>
            <div class="space-y-3">
                @forelse ($assignments as $assignment)
                    <div class="rounded-2xl border border-base-200 bg-base-100/80 p-4" wire:key="assignment-router-{{ $assignment->id }}">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-semibold">{{ $assignment->router?->name ?? 'Router unavailable' }}</p>
                                <p class="text-xs text-base-content/70">{{ $assignment->router?->address }}</p>
                            </div>
                            <span class="text-xs text-base-content/60">Owner: {{ $assignment->router?->user?->name ?? 'N/A' }}</span>
                        </div>
                        <div class="mt-2 grid gap-2 text-xs text-base-content/70 sm:grid-cols-3">
                            <span>Zone: {{ $assignment->router?->zone?->name ?? 'Unassigned' }}</span>
                            <span>Hotspot login: {{ $assignment->router?->login_address ?? 'Not shared' }}</span>
                            <span>Radius: {{ $assignment->router?->use_radius ? 'Enabled' : 'Disabled' }}</span>
                        </div>
                        <div class="mt-2 text-xs text-base-content/60">
                            Assigned {{ $assignment->created_at->diffForHumans() }} by {{ $assignment->assignedBy?->name ?? 'System' }}
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-base-content/70">No routers have been assigned to you yet.</p>
                @endforelse
            </div>
        </x-mary-card>

        <x-mary-card class="rounded-2xl border border-base-300">
            <x-slot name="title">Zones Covered</x-slot>
            <div class="space-y-3">
                @forelse ($zonesBreakdown as $zone => $count)
                    <div class="flex items-center justify-between text-sm" wire:key="zone-{{ \Illuminate\Support\Str::slug($zone) }}">
                        <span>{{ $zone }}</span>
                        <span class="font-semibold">{{ number_format($count) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-base-content/70">Your routers are not linked to zones yet.</p>
                @endforelse
            </div>
        </x-mary-card>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <x-mary-card class="rounded-2xl border border-base-300">
            <x-slot name="title">Recent Voucher Activity</x-slot>
            <div class="space-y-3">
                @forelse ($recentVouchers as $voucher)
                    <div class="flex items-center justify-between" wire:key="voucher-{{ $voucher->id }}">
                        <div>
                            <p class="font-semibold">{{ $voucher->username }}</p>
                            <p class="text-xs text-base-content/70">{{ $voucher->router?->name ?? 'Router removed' }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-base-content/60">{{ $voucher->created_at->diffForHumans() }}</p>
                            <p class="text-xs {{ $voucher->status === 'active' ? 'text-success' : ($voucher->status === 'expired' ? 'text-warning' : 'text-base-content/70') }}">
                                {{ ucfirst($voucher->status) }}
                            </p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-base-content/70">Voucher activity will appear here once you start generating access codes.</p>
                @endforelse
            </div>
        </x-mary-card>

        <x-mary-card class="rounded-2xl border border-base-300">
            <x-slot name="title">Assignment Timeline</x-slot>
            <div class="space-y-3">
                @forelse ($recentAssignments as $history)
                    <div class="text-sm" wire:key="history-{{ $history->id }}">
                        <p class="font-semibold">{{ $history->router?->name ?? 'Router removed' }}</p>
                        <p class="text-xs text-base-content/70">Assigned {{ $history->created_at->format('M d, Y') }} by {{ $history->assignedBy?->name ?? 'Admin' }}</p>
                    </div>
                @empty
                    <p class="text-sm text-base-content/70">Assignment history will show up once routers are linked to you.</p>
                @endforelse
            </div>
        </x-mary-card>
    </div>
</div>
