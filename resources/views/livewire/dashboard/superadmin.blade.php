<div class="space-y-6">
    <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        <x-mary-card class="bg-base-100 border border-base-300">
            <x-slot name="title">Admin Overview</x-slot>
            <div class="space-y-2">
                <p class="text-3xl font-semibold">{{ number_format($adminStats['total']) }}</p>
                <div class="text-sm text-base-content/70">Total admins in the system.</div>
                <div class="flex flex-wrap gap-4 text-xs mt-3">
                    <span class="font-semibold text-success">Active: {{ number_format($adminStats['active']) }}</span>
                    <span>Joined today: {{ number_format($adminStats['registeredToday']) }}</span>
                    <span class="text-warning">Low balance: {{ number_format($adminStats['lowBalance']) }}</span>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-base-100 border border-base-300">
            <x-slot name="title">Resellers</x-slot>
            <div class="space-y-2">
                <p class="text-3xl font-semibold">{{ number_format($resellerStats['total']) }}</p>
                <div class="text-sm text-base-content/70">Registered resellers</div>
                <div class="text-xs text-success mt-3">Active: {{ number_format($resellerStats['active']) }}</div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-base-100 border border-base-300">
            <x-slot name="title">Routers</x-slot>
            <div class="space-y-2">
                <p class="text-3xl font-semibold">{{ number_format($routerOverview['total']) }}</p>
                <div class="grid grid-cols-2 gap-2 text-xs text-base-content/70">
                    <span>With package: {{ number_format($routerOverview['withPackage']) }}</span>
                    <span>Expiring today: {{ number_format($routerOverview['expiringToday']) }}</span>
                    <span>Expiring 7 days: {{ number_format($routerOverview['expiringWeek']) }}</span>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-base-100 border border-base-300">
            <x-slot name="title">Sales Snapshot</x-slot>
            <div class="space-y-2 text-sm">
                <div>
                    <div class="text-xs text-base-content/70">Today</div>
                    <p class="text-xl font-semibold">{{ number_format($salesSummary['today'], 2) }}</p>
                </div>
                <div>
                    <div class="text-xs text-base-content/70">Month to date</div>
                    <p class="text-xl font-semibold text-primary">{{ number_format($salesSummary['month'], 2) }}</p>
                </div>
                <div class="text-xs text-warning">Pending invoices: {{ number_format($salesSummary['pending'], 2) }}
                </div>
            </div>
        </x-mary-card>
    </div>

    <div class="grid gap-4 grid-cols-1 lg:grid-cols-2 xl:grid-cols-3">
        <x-mary-card class="lg:col-span-2 border border-base-300">
            <x-slot name="title">Routers by Package</x-slot>
            <div class="divide-y divide-base-300">
                @forelse ($packageBreakdown as $package => $meta)
                    <div class="flex items-center justify-between py-3"
                        wire:key="package-{{ \Illuminate\Support\Str::slug($package) }}">
                        <div>
                            <p class="font-semibold">{{ $package }}</p>
                            <p class="text-xs text-base-content/60">
                                {{ $meta['billing'] ? ucfirst($meta['billing']) . ' cycle' : 'No subscription' }}</p>
                        </div>
                        <div class="text-2xl font-semibold">{{ number_format($meta['count']) }}</div>
                    </div>
                @empty
                    <p class="py-6 text-sm text-base-content/70">No routers available.</p>
                @endforelse
            </div>
        </x-mary-card>

        <x-mary-card class=" border border-base-300">
            <x-slot name="title">Revenue Trend (7 days)</x-slot>
            @php
                $maxTrend = max(collect($revenueTrend)->pluck('value')->max() ?? 0, 1);
            @endphp
            <div class="space-y-3">
                @foreach ($revenueTrend as $point)
                    @php
                        $percent = $maxTrend > 0 ? ($point['value'] / $maxTrend) * 100 : 0;
                    @endphp
                    <div class="text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-base-content/80">{{ $point['label'] }}</span>
                            <span class="font-semibold">{{ number_format($point['value'], 2) }}</span>
                        </div>
                        <div class="mt-1 h-2 bg-base-300">
                            <div class="h-2 bg-primary" style="width: {{ $percent }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-mary-card>
    </div>

    <div class="grid gap-4 grid-cols-1 xl:grid-cols-2">
        <x-mary-card class=" border border-base-300">
            <x-slot name="title">Sales by Category</x-slot>
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Invoices</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categoryBreakdown as $category)
                            <tr>
                                <td>{{ ucfirst($category->category) }}</td>
                                <td>{{ number_format($category->invoices) }}</td>
                                <td>{{ number_format($category->total_amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-sm text-base-content/60">No invoices yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-mary-card>

        <x-mary-card class=" border border-base-300">
            <x-slot name="title">Latest Invoices</x-slot>
            <div class="space-y-4">
                @forelse ($recentInvoices as $invoice)
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-semibold">#{{ $invoice->id }}</p>
                            <p class="text-xs text-base-content/70">{{ $invoice->user?->name ?? 'Unknown user' }} ·
                                {{ ucfirst($invoice->category) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold">{{ number_format($invoice->amount, 2) }}</p>
                            <p
                                class="text-xs {{ $invoice->status === 'completed' ? 'text-success' : ($invoice->status === 'pending' ? 'text-warning' : 'text-error') }}">
                                {{ ucfirst($invoice->status) }}
                            </p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-base-content/60">No recent invoices.</p>
                @endforelse
            </div>
        </x-mary-card>
    </div>

    <x-mary-card class=" border border-base-300">
        <x-slot name="title">Recently Added Admins</x-slot>
        <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($recentAdmins as $admin)
                <div class=" border border-base-300 bg-base-100/80 p-4">
                    <p class="font-semibold">{{ $admin->name }}</p>
                    <p class="text-xs text-base-content/60">{{ $admin->email }}</p>
                    <div class="mt-3 flex items-center justify-between text-xs">
                        <span class="{{ $admin->is_active ? 'text-success' : 'text-error' }}">
                            {{ $admin->is_active ? 'Active' : 'Inactive' }}
                        </span>
                        <span>Joined {{ $admin->created_at->diffForHumans() }}</span>
                    </div>
                    <div class="text-xs text-base-content/70">Balance: {{ number_format($admin->balance ?? 0, 2) }}
                    </div>
                </div>
            @empty
                <p class="text-sm text-base-content/70">No admin records found.</p>
            @endforelse
        </div>
    </x-mary-card>
</div>
