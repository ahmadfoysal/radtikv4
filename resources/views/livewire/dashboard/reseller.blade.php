<div class="space-y-6">
    {{-- Top Stats Cards --}}
    <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Assigned Routers --}}
        <x-mary-card class="border border-base-300 bg-gradient-to-br from-primary/10 to-base-100">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <x-mary-icon name="o-server-stack" class="w-5 h-5 text-primary" />
                        <span class="text-sm font-medium text-base-content/70">Assigned Routers</span>
                    </div>
                    <p class="text-3xl font-bold text-primary">{{ number_format($routerStats['total']) }}</p>
                    <p class="text-xs text-base-content/60 mt-1">{{ number_format($routerStats['withLogin']) }} with
                        login access</p>
                </div>
            </div>
        </x-mary-card>

        {{-- Total Vouchers --}}
        <x-mary-card class="border border-base-300 bg-gradient-to-br from-info/10 to-base-100">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <x-mary-icon name="o-ticket" class="w-5 h-5 text-info" />
                        <span class="text-sm font-medium text-base-content/70">Total Vouchers</span>
                    </div>
                    <p class="text-3xl font-bold text-info">{{ number_format($voucherStats['total']) }}</p>
                    <p class="text-xs text-base-content/60 mt-1">Across all routers</p>
                </div>
            </div>
        </x-mary-card>

        {{-- Active Vouchers --}}
        <x-mary-card class="border border-base-300 bg-gradient-to-br from-success/10 to-base-100">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <x-mary-icon name="o-check-circle" class="w-5 h-5 text-success" />
                        <span class="text-sm font-medium text-base-content/70">Active Vouchers</span>
                    </div>
                    <p class="text-3xl font-bold text-success">{{ number_format($voucherStats['active']) }}</p>
                    <p class="text-xs text-base-content/60 mt-1">Currently in use</p>
                </div>
            </div>
        </x-mary-card>

        {{-- Expired Vouchers --}}
        <x-mary-card class="border border-base-300 bg-gradient-to-br from-warning/10 to-base-100">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <x-mary-icon name="o-clock" class="w-5 h-5 text-warning" />
                        <span class="text-sm font-medium text-base-content/70">Expired Vouchers</span>
                    </div>
                    <p class="text-3xl font-bold text-warning">{{ number_format($voucherStats['expired']) }}</p>
                    <p class="text-xs text-base-content/60 mt-1">Need attention</p>
                </div>
            </div>
        </x-mary-card>
    </div>

    {{-- Quick Actions Bar --}}
    <x-mary-card class="border border-base-300 bg-base-100">
        <div class="flex flex-wrap items-center gap-3">
            <span class="text-sm font-semibold text-base-content/70">Quick Actions:</span>
            @can('generate_vouchers')
                <x-mary-button icon="o-plus-circle" label="Generate Vouchers" class="btn-sm btn-primary"
                    href="{{ route('vouchers.generate') }}" wire:navigate />
            @endcan
            @can('view_vouchers')
                <x-mary-button icon="o-ticket" label="View Vouchers" class="btn-sm btn-info"
                    href="{{ route('vouchers.index') }}" wire:navigate />
            @endcan
            @can('view_router')
                <x-mary-button icon="o-server" label="My Routers" class="btn-sm btn-ghost"
                    href="{{ route('routers.index') }}" wire:navigate />
            @endcan
            @can('view_voucher_logs')
                <x-mary-button icon="o-chart-bar" label="Sales Summary" class="btn-sm btn-success"
                    href="{{ route('billing.sales-summary') }}" wire:navigate />
            @endcan
        </div>
    </x-mary-card>

    {{-- Main Content Grid --}}
    <div class="grid gap-4 lg:grid-cols-3">
        {{-- Assigned Routers List --}}
        <x-mary-card class="lg:col-span-2 border border-base-300 bg-base-100">
            <x-slot name="title">
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-server-stack" class="w-5 h-5 text-primary" />
                    <span>Routers You Can Access</span>
                </div>
            </x-slot>
            <div class="space-y-3 max-h-96 overflow-y-auto">
                @forelse ($assignments as $assignment)
                    <div class="border border-base-200 bg-base-100 rounded-lg p-4 hover:border-primary/50 transition-colors"
                        wire:key="assignment-router-{{ $assignment->id }}">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex-1">
                                <p class="font-semibold text-base">
                                    {{ $assignment->router?->name ?? 'Router unavailable' }}</p>
                                <p class="text-xs text-base-content/70">{{ $assignment->router?->address }}</p>
                            </div>
                            @if ($assignment->router?->login_address)
                                <span class="badge badge-sm badge-success">Login Shared</span>
                            @endif
                        </div>
                        <div class="grid gap-2 text-xs sm:grid-cols-2">
                            <div class="flex items-center gap-1">
                                <x-mary-icon name="o-globe-alt" class="w-4 h-4 text-info" />
                                <span class="text-base-content/70">Zone:
                                    {{ $assignment->router?->zone?->name ?? 'Unassigned' }}</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <x-mary-icon name="o-user" class="w-4 h-4 text-accent" />
                                <span class="text-base-content/70">Owner:
                                    {{ $assignment->router?->user?->name ?? 'N/A' }}</span>
                            </div>
                        </div>
                        @if ($assignment->router?->login_address)
                            <div class="mt-2 p-2 bg-success/10 rounded text-xs">
                                <span class="font-medium">Hotspot Login:</span>
                                <span class="text-success font-mono">{{ $assignment->router?->login_address }}</span>
                            </div>
                        @endif
                        <div class="mt-2 pt-2 border-t border-base-200 text-xs text-base-content/60">
                            <span>Assigned {{ $assignment->created_at->diffForHumans() }} by
                                {{ $assignment->assignedBy?->name ?? 'System' }}</span>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <x-mary-icon name="o-server-stack" class="w-12 h-12 text-base-content/30 mx-auto mb-2" />
                        <p class="text-sm text-base-content/70">No routers have been assigned to you yet.</p>
                        <p class="text-xs text-base-content/50 mt-1">Contact your admin to get router access.</p>
                    </div>
                @endforelse
            </div>
            @if ($assignments->isNotEmpty())
                <div class="mt-4 pt-4 border-t border-base-200">
                    <x-mary-button label="View All Routers" icon="o-arrow-right" class="btn-sm btn-ghost w-full"
                        href="{{ route('routers.index') }}" wire:navigate />
                </div>
            @endif
        </x-mary-card>

        {{-- Sidebar --}}
        <div class="space-y-4">
            {{-- Zones Covered --}}
            <x-mary-card class="border border-base-300 bg-base-100">
                <x-slot name="title">
                    <div class="flex items-center gap-2">
                        <x-mary-icon name="o-map-pin" class="w-5 h-5 text-info" />
                        <span>Zones Covered</span>
                    </div>
                </x-slot>
                <div class="space-y-3">
                    @forelse ($zonesBreakdown as $zone => $count)
                        <div class="flex items-center justify-between p-2 rounded hover:bg-base-200/50 transition-colors"
                            wire:key="zone-{{ \Illuminate\Support\Str::slug($zone) }}">
                            <span class="text-sm">{{ $zone }}</span>
                            <span class="badge badge-sm badge-primary">{{ number_format($count) }}</span>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <p class="text-sm text-base-content/70">Your routers are not linked to zones yet.</p>
                        </div>
                    @endforelse
                </div>
            </x-mary-card>

            {{-- Assignment Timeline --}}
            <x-mary-card class="border border-base-300 bg-base-100">
                <x-slot name="title">
                    <div class="flex items-center gap-2">
                        <x-mary-icon name="o-clock" class="w-5 h-5 text-accent" />
                        <span>Assignment Timeline</span>
                    </div>
                </x-slot>
                <div class="space-y-3">
                    @forelse ($recentAssignments as $history)
                        <div class="p-2 border-l-2 border-primary pl-3" wire:key="history-{{ $history->id }}">
                            <p class="font-semibold text-sm">{{ $history->router?->name ?? 'Router removed' }}</p>
                            <p class="text-xs text-base-content/70">{{ $history->created_at->format('M d, Y') }}</p>
                            <p class="text-xs text-base-content/60">by {{ $history->assignedBy?->name ?? 'Admin' }}
                            </p>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <p class="text-sm text-base-content/70">Assignment history will show up once routers are
                                linked to you.</p>
                        </div>
                    @endforelse
                </div>
            </x-mary-card>
        </div>
    </div>

    {{-- Recent Voucher Activity --}}
    <x-mary-card class="border border-base-300 bg-base-100">
        <x-slot name="title">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-ticket" class="w-5 h-5 text-success" />
                    <span>Recent Voucher Activity</span>
                </div>
                @can('view_vouchers')
                    <x-mary-button label="View All" icon="o-arrow-right" class="btn-xs btn-ghost"
                        href="{{ route('vouchers.index') }}" wire:navigate />
                @endcan
            </div>
        </x-slot>
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Router</th>
                        <th>Status</th>
                        <th class="text-right">Created</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentVouchers as $voucher)
                        <tr wire:key="voucher-{{ $voucher->id }}">
                            <td class="font-medium">{{ $voucher->username }}</td>
                            <td class="text-sm">{{ $voucher->router?->name ?? 'Router removed' }}</td>
                            <td>
                                <span
                                    class="badge badge-sm {{ $voucher->status === 'active' ? 'badge-success' : ($voucher->status === 'expired' ? 'badge-warning' : 'badge-ghost') }}">
                                    {{ ucfirst($voucher->status) }}
                                </span>
                            </td>
                            <td class="text-right text-xs text-base-content/60">
                                {{ $voucher->created_at->diffForHumans() }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-8">
                                <x-mary-icon name="o-ticket" class="w-12 h-12 text-base-content/30 mx-auto mb-2" />
                                <p class="text-sm text-base-content/70">Voucher activity will appear here once you
                                    start generating access codes.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-mary-card>
</div>
