<div class="space-y-6">
    {{-- Header Welcome --}}
    <div class="bg-gradient-to-r from-primary/10 to-secondary/10 rounded-lg p-6 border border-base-300">
        <h1 class="text-2xl font-bold mb-2">Welcome back, SuperAdmin 👋</h1>
        <p class="text-base-content/70">Here's what's happening with your platform today</p>
    </div>

    {{-- Key Metrics Overview --}}
    <div>
        <h2 class="text-lg font-semibold mb-4">Platform Overview</h2>
        <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Admins Card --}}
            <x-mary-card
                class="bg-gradient-to-br from-blue-500/10 to-blue-600/10 border border-blue-500/20 hover:shadow-lg transition-shadow">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="p-2 bg-blue-500/20 rounded-lg">
                            <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <span class="text-3xl font-bold text-blue-600">{{ number_format($adminStats['total']) }}</span>
                    </div>
                    <div>
                        <p class="font-semibold text-sm">Total Admins</p>
                        <div class="flex gap-3 text-xs mt-2">
                            <span class="text-success">✓ {{ number_format($adminStats['active']) }} Active</span>
                            <span class="text-base-content/60">{{ number_format($adminStats['registeredToday']) }}
                                Today</span>
                        </div>
                    </div>
                    @if ($adminStats['lowBalance'] > 0)
                        <div class="pt-2 border-t border-base-300">
                            <span class="text-xs text-warning">⚠️ {{ $adminStats['lowBalance'] }} with low
                                balance</span>
                        </div>
                    @endif
                </div>
            </x-mary-card>

            {{-- Resellers Card --}}
            <x-mary-card
                class="bg-gradient-to-br from-purple-500/10 to-purple-600/10 border border-purple-500/20 hover:shadow-lg transition-shadow">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="p-2 bg-purple-500/20 rounded-lg">
                            <svg class="w-6 h-6 text-purple-500" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <span
                            class="text-3xl font-bold text-purple-600">{{ number_format($resellerStats['total']) }}</span>
                    </div>
                    <div>
                        <p class="font-semibold text-sm">Resellers</p>
                        <p class="text-xs text-success mt-2">✓ {{ number_format($resellerStats['active']) }} Active
                            resellers</p>
                    </div>
                </div>
            </x-mary-card>

            {{-- Routers Card --}}
            <x-mary-card
                class="bg-gradient-to-br from-green-500/10 to-green-600/10 border border-green-500/20 hover:shadow-lg transition-shadow">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="p-2 bg-green-500/20 rounded-lg">
                            <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                            </svg>
                        </div>
                        <span
                            class="text-3xl font-bold text-green-600">{{ number_format($routerOverview['total']) }}</span>
                    </div>
                    <div>
                        <p class="font-semibold text-sm">Active Routers</p>
                        <div class="text-xs mt-2 space-y-1">
                            <p class="text-base-content/70">{{ number_format($routerOverview['withSubscription']) }}
                                with subscription</p>
                            <p class="text-base-content/60">ISP Cost:
                                ${{ number_format($routerOverview['totalIspCost'], 2) }}</p>
                        </div>
                    </div>
                </div>
            </x-mary-card>

            {{-- Revenue Card --}}
            <x-mary-card
                class="bg-gradient-to-br from-amber-500/10 to-amber-600/10 border border-amber-500/20 hover:shadow-lg transition-shadow">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="p-2 bg-amber-500/20 rounded-lg">
                            <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span
                            class="text-3xl font-bold text-amber-600">${{ number_format($salesSummary['month'], 0) }}</span>
                    </div>
                    <div>
                        <p class="font-semibold text-sm">Monthly Revenue</p>
                        <div class="text-xs mt-2 space-y-1">
                            <p class="text-base-content/70">Today: ${{ number_format($salesSummary['today'], 2) }}</p>
                            @if ($salesSummary['pending'] > 0)
                                <p class="text-warning">⏳ ${{ number_format($salesSummary['pending'], 2) }} pending</p>
                            @endif
                        </div>
                    </div>
                </div>
            </x-mary-card>
        </div>
    </div>

    {{-- Revenue & Analytics Section --}}
    <div class="grid gap-4 grid-cols-1 lg:grid-cols-3">
        {{-- Revenue Trend --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <x-slot name="title">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                    7-Day Revenue Trend
                </div>
            </x-slot>
            @php
                $maxTrend = max(collect($revenueTrend)->pluck('value')->max() ?? 0, 1);
            @endphp
            <div class="space-y-3">
                @foreach ($revenueTrend as $point)
                    @php
                        $percent = $maxTrend > 0 ? ($point['value'] / $maxTrend) * 100 : 0;
                    @endphp
                    <div class="text-sm">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-base-content/70 font-medium">{{ $point['label'] }}</span>
                            <span class="font-semibold text-primary">${{ number_format($point['value'], 2) }}</span>
                        </div>
                        <div class="h-2 bg-base-200 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-primary to-secondary transition-all duration-300"
                                style="width: {{ $percent }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-mary-card>

        {{-- Package Distribution --}}
        <x-mary-card class="lg:col-span-2 border border-base-300 bg-base-100">
            <x-slot name="title">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    Router Distribution by Package
                </div>
            </x-slot>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @forelse ($packageBreakdown as $package => $meta)
                    <div class="flex items-center justify-between p-4 bg-base-200/50 rounded-lg hover:bg-base-200 transition-colors"
                        wire:key="package-{{ \Illuminate\Support\Str::slug($package) }}">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-primary/20 flex items-center justify-center">
                                <span
                                    class="text-xl font-bold text-primary">{{ number_format($meta['count']) }}</span>
                            </div>
                            <div>
                                <p class="font-semibold">{{ $meta['package'] }}</p>
                                <p class="text-xs text-base-content/60">Active routers</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="badge badge-primary badge-outline">
                                {{ number_format(($meta['count'] / $routerOverview['total']) * 100, 1) }}%</div>
                        </div>
                    </div>
                @empty
                    <p class="col-span-2 py-8 text-center text-sm text-base-content/70">No package data available.</p>
                @endforelse
            </div>
        </x-mary-card>
    </div>

    {{-- Sales & Invoice Analytics --}}
    <div class="grid gap-4 grid-cols-1 lg:grid-cols-2">
        {{-- Sales by Category --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <x-slot name="title">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        Sales by Category
                    </div>
                    <a href="/billing/revenue-analytics" class="btn btn-xs btn-ghost">View All →</a>
                </div>
            </x-slot>
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr class="border-base-300">
                            <th class="bg-base-200">Category</th>
                            <th class="bg-base-200 text-right">Invoices</th>
                            <th class="bg-base-200 text-right">Revenue</th>
                            <th class="bg-base-200 text-right">Avg</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categoryBreakdown as $category)
                            <tr class="hover">
                                <td>
                                    <span class="badge badge-outline">{{ ucfirst($category->category) }}</span>
                                </td>
                                <td class="text-right font-medium">{{ number_format($category->invoices) }}</td>
                                <td class="text-right font-semibold text-success">
                                    ${{ number_format($category->total_amount, 2) }}</td>
                                <td class="text-right text-base-content/70">
                                    ${{ number_format($category->total_amount / $category->invoices, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-sm text-base-content/60 py-8">No sales data
                                    available</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-mary-card>

        {{-- Latest Invoices --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <x-slot name="title">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Recent Invoices
                    </div>
                    <a href="/admin/invoices" class="btn btn-xs btn-ghost">View All →</a>
                </div>
            </x-slot>
            <div class="space-y-3">
                @forelse ($recentInvoices as $invoice)
                    <div
                        class="flex items-center justify-between p-3 bg-base-200/50 rounded-lg hover:bg-base-200 transition-colors">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span class="font-mono font-semibold text-sm">#{{ $invoice->id }}</span>
                                <span
                                    class="badge badge-xs {{ $invoice->status === 'completed' ? 'badge-success' : ($invoice->status === 'pending' ? 'badge-warning' : 'badge-error') }}">
                                    {{ ucfirst($invoice->status) }}
                                </span>
                            </div>
                            <p class="text-xs text-base-content/70 mt-1">
                                {{ $invoice->user?->name ?? 'Unknown' }} ·
                                <span class="badge badge-xs badge-ghost">{{ ucfirst($invoice->category) }}</span>
                            </p>
                            <p class="text-xs text-base-content/50 mt-1">{{ $invoice->created_at->diffForHumans() }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-lg">${{ number_format($invoice->amount, 2) }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-sm text-base-content/60 py-8">No recent invoices</p>
                @endforelse
            </div>
        </x-mary-card>
    </div>

    {{-- Recent Admins Section --}}
    <x-mary-card class="border border-base-300 bg-base-100">
        <x-slot name="title">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    Recently Registered Admins
                </div>
                <a href="/admin/users" class="btn btn-xs btn-ghost">View All →</a>
            </div>
        </x-slot>
        <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
            @forelse ($recentAdmins as $admin)
                <div
                    class="p-4 border border-base-300 bg-base-100 rounded-lg hover:shadow-md hover:border-primary/30 transition-all">
                    <div class="flex items-start justify-between mb-3">
                        <div class="avatar placeholder">
                            <div class="bg-primary/20 text-primary rounded-full w-10 h-10">
                                <span class="text-lg font-bold">{{ substr($admin->name, 0, 1) }}</span>
                            </div>
                        </div>
                        <div class="badge badge-sm {{ $admin->is_active ? 'badge-success' : 'badge-error' }}">
                            {{ $admin->is_active ? 'Active' : 'Inactive' }}
                        </div>
                    </div>
                    <p class="font-semibold truncate" title="{{ $admin->name }}">{{ $admin->name }}</p>
                    <p class="text-xs text-base-content/60 truncate" title="{{ $admin->email }}">{{ $admin->email }}
                    </p>
                    <div class="mt-3 pt-3 border-t border-base-300 space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-base-content/70">Balance:</span>
                            <span class="font-semibold {{ $admin->balance < 50 ? 'text-warning' : 'text-success' }}">
                                ${{ number_format($admin->balance ?? 0, 2) }}
                            </span>
                        </div>
                        <div class="text-xs text-base-content/60">
                            <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ $admin->created_at->diffForHumans() }}
                        </div>
                    </div>
                </div>
            @empty
                <p class="col-span-full text-center text-sm text-base-content/70 py-8">No recent admin registrations
                </p>
            @endforelse
        </div>
    </x-mary-card>

    {{-- Quick Actions --}}
    <x-mary-card class="border border-primary/20 bg-primary/5">
        <x-slot name="title">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                Quick Actions
            </div>
        </x-slot>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <a href="/packages" class="btn btn-outline btn-sm gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                Packages
            </a>
            <a href="/admin/users" class="btn btn-outline btn-sm gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                Manage Users
            </a>
            <a href="/billing/revenue-analytics" class="btn btn-outline btn-sm gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Analytics
            </a>
            <a href="/superadmin/payment-gateways" class="btn btn-outline btn-sm gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
                Payments
            </a>
            <a href="/superadmin/email-settings" class="btn btn-outline btn-sm gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                Email Config
            </a>
            <a href="/superadmin/general-settings" class="btn btn-outline btn-sm gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Settings
            </a>
        </div>
    </x-mary-card>
</div>
