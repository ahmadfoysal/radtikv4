<div class="space-y-6">
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <x-mary-card class="rounded-2xl border border-base-300 bg-base-200">
            <x-slot name="title">Wallet Balance</x-slot>
            <p class="text-4xl font-semibold text-primary">{{ number_format($balance, 2) }}</p>
            <p class="text-sm text-base-content/70">Available balance for subscriptions and renewals.</p>
        </x-mary-card>

        <x-mary-card class="rounded-2xl border border-base-300 bg-base-200">
            <x-slot name="title">Router Health</x-slot>
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div>
                    <p class="text-xs text-base-content/60">Total</p>
                    <p class="text-xl font-semibold">{{ number_format($routerStats['total']) }}</p>
                </div>
                <div>
                    <p class="text-xs text-base-content/60">Expiring 7d</p>
                    <p class="text-xl font-semibold text-warning">{{ number_format($routerStats['expiringWeek']) }}</p>
                </div>
                <div>
                    <p class="text-xs text-base-content/60">Expiring today</p>
                    <p class="text-xl font-semibold text-error">{{ number_format($routerStats['expiringToday']) }}</p>
                </div>
                <div>
                    <p class="text-xs text-base-content/60">No package</p>
                    <p class="text-xl font-semibold">{{ number_format($routerStats['withoutPackage']) }}</p>
                </div>
            </div>
            <div class="mt-4 text-xs text-base-content/70">Monthly expense tracked: {{ number_format($routerStats['monthlyExpense'], 2) }}</div>
        </x-mary-card>

        <x-mary-card class="rounded-2xl border border-base-300 bg-base-200">
            <x-slot name="title">Resellers</x-slot>
            <div class="space-y-2 text-sm">
                <div class="flex items-center justify-between">
                    <span>Total</span>
                    <span class="font-semibold">{{ number_format($resellerStats['total']) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span>Active</span>
                    <span class="font-semibold text-success">{{ number_format($resellerStats['active']) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span>With routers</span>
                    <span class="font-semibold">{{ number_format($resellerStats['withRouters']) }}</span>
                </div>
            </div>
        </x-mary-card>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <x-mary-card class="rounded-2xl border border-base-300">
            <x-slot name="title">Packages In Use</x-slot>
            <div class="space-y-3">
                @forelse ($routerUsage as $package => $count)
                    <div class="flex items-center justify-between text-sm" wire:key="admin-package-{{ \Illuminate\Support\Str::slug($package) }}">
                        <span>{{ $package }}</span>
                        <span class="font-semibold">{{ number_format($count) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-base-content/70">No package data yet.</p>
                @endforelse
            </div>
        </x-mary-card>

        <x-mary-card class="lg:col-span-2 rounded-2xl border border-base-300">
            <x-slot name="title">Upcoming Renewals</x-slot>
            <div class="space-y-3">
                @forelse ($routerAlerts as $router)
                    <div class="flex items-center justify-between" wire:key="alert-{{ $router->id }}">
                        <div>
                            <p class="font-semibold">{{ $router->name }}</p>
                            @php
                                $endDate = $router->package['end_date'] ?? null;
                            @endphp
                            <p class="text-xs text-base-content/70">
                                {{ $endDate ? 'Ending ' . \Carbon\Carbon::parse($endDate)->diffForHumans() : 'No package date' }}
                            </p>
                        </div>
                        <x-mary-badge class="badge-warning">Attention</x-mary-badge>
                    </div>
                @empty
                    <p class="text-sm text-base-content/70">No upcoming renewals in the next 10 days.</p>
                @endforelse
            </div>
        </x-mary-card>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <x-mary-card class="rounded-2xl border border-base-300">
            <x-slot name="title">Recent Routers</x-slot>
            <div class="space-y-3">
                @forelse ($recentRouters as $router)
                    <div class="rounded-2xl border border-base-200 bg-base-100/80 p-4" wire:key="recent-router-{{ $router->id }}">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-semibold">{{ $router->name }}</p>
                                <p class="text-xs text-base-content/70">{{ $router->address }}</p>
                            </div>
                            <span class="text-xs text-base-content/70">{{ $router->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="mt-2 text-xs text-base-content/60">Zone: {{ $router->zone->name ?? 'N/A' }}</div>
                        <div class="mt-1 text-xs">
                            Active vouchers: {{ $router->active_vouchers_count ?? 0 }} · Expired: {{ $router->expired_vouchers_count ?? 0 }}
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-base-content/70">No routers found.</p>
                @endforelse
            </div>
        </x-mary-card>

        <x-mary-card class="rounded-2xl border border-base-300">
            <x-slot name="title">Recent Invoices</x-slot>
            <div class="space-y-3">
                @forelse ($recentInvoices as $invoice)
                    <div class="flex items-center justify-between" wire:key="invoice-{{ $invoice->id }}">
                        <div>
                            <p class="font-semibold">#{{ $invoice->id }}</p>
                            <p class="text-xs text-base-content/70">{{ ucfirst($invoice->category) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold">{{ number_format($invoice->amount, 2) }}</p>
                            <p class="text-xs {{ $invoice->status === 'completed' ? 'text-success' : ($invoice->status === 'pending' ? 'text-warning' : 'text-error') }}">
                                {{ ucfirst($invoice->status) }}
                            </p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-base-content/70">No invoices yet.</p>
                @endforelse
            </div>
        </x-mary-card>
    </div>
</div>
