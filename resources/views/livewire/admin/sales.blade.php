<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Sales History</h1>
            <p class="text-sm text-base-content/70 mt-1">Complete purchase history of all subscriptions</p>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <x-mary-card class="bg-gradient-to-br from-primary/10 to-base-100">
            <div class="flex items-center gap-3">
                <x-mary-icon name="o-shopping-cart" class="w-12 h-12 text-primary" />
                <div>
                    <div class="text-2xl font-bold">{{ number_format($stats['total_sales']) }}</div>
                    <div class="text-sm text-base-content/70">Total Sales</div>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-br from-success/10 to-base-100">
            <div class="flex items-center gap-3">
                <x-mary-icon name="o-banknotes" class="w-12 h-12 text-success" />
                <div>
                    <div class="text-2xl font-bold text-success">${{ number_format($stats['total_revenue'], 2) }}</div>
                    <div class="text-sm text-base-content/70">Total Revenue</div>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-br from-info/10 to-base-100">
            <div class="flex items-center gap-3">
                <x-mary-icon name="o-check-circle" class="w-12 h-12 text-info" />
                <div>
                    <div class="text-2xl font-bold text-info">{{ number_format($stats['active_subscriptions']) }}</div>
                    <div class="text-sm text-base-content/70">Active Subscriptions</div>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-br from-warning/10 to-base-100">
            <div class="flex items-center gap-3">
                <x-mary-icon name="o-receipt-percent" class="w-12 h-12 text-warning" />
                <div>
                    <div class="text-2xl font-bold text-warning">${{ number_format($stats['total_discount_given'], 2) }}</div>
                    <div class="text-sm text-base-content/70">Total Discounts</div>
                </div>
            </div>
        </x-mary-card>
    </div>

    {{-- Filters --}}
    <x-mary-card>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            {{-- Search --}}
            <div class="md:col-span-2">
                <x-mary-input wire:model.live.debounce.500ms="search"
                    placeholder="Search by customer, package, promo code..." icon="o-magnifying-glass" clearable />
            </div>

            {{-- Status Filter --}}
            <x-mary-select wire:model.live="status" icon="o-funnel" :options="[
                ['id' => 'all', 'name' => 'All Status'],
                ['id' => 'active', 'name' => 'Active'],
                ['id' => 'cancelled', 'name' => 'Cancelled'],
                ['id' => 'expired', 'name' => 'Expired'],
                ['id' => 'suspended', 'name' => 'Suspended'],
                ['id' => 'grace_period', 'name' => 'Grace Period'],
            ]" />

            {{-- Billing Cycle Filter --}}
            <x-mary-select wire:model.live="billing_cycle" icon="o-clock" :options="[
                ['id' => 'all', 'name' => 'All Cycles'],
                ['id' => 'monthly', 'name' => 'Monthly'],
                ['id' => 'yearly', 'name' => 'Yearly'],
            ]" />

            {{-- Per Page --}}
            <x-mary-select wire:model.live="perPage" icon="o-list-bullet" :options="[
                ['id' => 10, 'name' => '10 per page'],
                ['id' => 15, 'name' => '15 per page'],
                ['id' => 25, 'name' => '25 per page'],
                ['id' => 50, 'name' => '50 per page'],
                ['id' => 100, 'name' => '100 per page'],
            ]" />
        </div>
    </x-mary-card>

    {{-- Sales Table --}}
    <x-mary-card>
        <div class="overflow-x-auto">
            <table class="table table-zebra table-sm">
                <thead>
                    <tr>
                        <th class="cursor-pointer" wire:click="sortBy('id')">
                            <div class="flex items-center gap-2">
                                Sale #
                                <x-mary-icon :name="$this->getSortIcon('id')" class="w-4 h-4" />
                            </div>
                        </th>
                        <th>Customer</th>
                        <th>Package</th>
                        <th>Billing Cycle</th>
                        <th class="cursor-pointer" wire:click="sortBy('start_date')">
                            <div class="flex items-center gap-2">
                                Start Date
                                <x-mary-icon :name="$this->getSortIcon('start_date')" class="w-4 h-4" />
                            </div>
                        </th>
                        <th class="cursor-pointer" wire:click="sortBy('end_date')">
                            <div class="flex items-center gap-2">
                                End Date
                                <x-mary-icon :name="$this->getSortIcon('end_date')" class="w-4 h-4" />
                            </div>
                        </th>
                        <th>Original Price</th>
                        <th>Discount</th>
                        <th class="cursor-pointer" wire:click="sortBy('amount')">
                            <div class="flex items-center gap-2">
                                Paid Amount
                                <x-mary-icon :name="$this->getSortIcon('amount')" class="w-4 h-4" />
                            </div>
                        </th>
                        <th>Promo Code</th>
                        <th class="cursor-pointer" wire:click="sortBy('cycle_count')">
                            <div class="flex items-center gap-2">
                                Cycle
                                <x-mary-icon :name="$this->getSortIcon('cycle_count')" class="w-4 h-4" />
                            </div>
                        </th>
                        <th class="cursor-pointer" wire:click="sortBy('status')">
                            <div class="flex items-center gap-2">
                                Status
                                <x-mary-icon :name="$this->getSortIcon('status')" class="w-4 h-4" />
                            </div>
                        </th>
                        <th class="cursor-pointer" wire:click="sortBy('created_at')">
                            <div class="flex items-center gap-2">
                                Purchase Date
                                <x-mary-icon :name="$this->getSortIcon('created_at')" class="w-4 h-4" />
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sales as $sale)
                        <tr wire:key="sale-{{ $sale->id }}">
                            <td>
                                <span class="font-mono text-xs font-semibold">#{{ $sale->id }}</span>
                            </td>
                            <td>
                                <div>
                                    <div class="font-semibold">{{ $sale->user->name }}</div>
                                    <div class="text-xs text-base-content/60">{{ $sale->user->email }}</div>
                                </div>
                            </td>
                            <td>
                                <div class="font-medium">{{ $sale->package->name }}</div>
                            </td>
                            <td>
                                <x-mary-badge value="{{ ucfirst($sale->billing_cycle) }}"
                                    class="badge-xs {{ $sale->billing_cycle === 'yearly' ? 'badge-primary' : 'badge-secondary' }}" />
                            </td>
                            <td>
                                <div class="text-xs">{{ $sale->start_date->format('M d, Y') }}</div>
                            </td>
                            <td>
                                <div class="text-xs">{{ $sale->end_date->format('M d, Y') }}</div>
                            </td>
                            <td>
                                @if ($sale->original_price > $sale->amount)
                                    <div class="text-xs line-through text-base-content/50">
                                        ${{ number_format($sale->original_price, 2) }}
                                    </div>
                                @else
                                    <div class="text-xs">
                                        ${{ number_format($sale->original_price, 2) }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if ($sale->discount_percent > 0)
                                    <x-mary-badge value="{{ $sale->discount_percent }}%"
                                        class="badge-xs badge-success" />
                                    <div class="text-xs text-success mt-1">
                                        -${{ number_format($sale->original_price - $sale->amount, 2) }}
                                    </div>
                                @else
                                    <span class="text-xs text-base-content/50">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="font-semibold text-success">
                                    ${{ number_format($sale->amount, 2) }}
                                </div>
                            </td>
                            <td>
                                @if ($sale->promo_code)
                                    <x-mary-badge value="{{ $sale->promo_code }}"
                                        class="badge-xs badge-accent" />
                                @else
                                    <span class="text-xs text-base-content/50">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="text-center">
                                    <x-mary-badge value="{{ $sale->cycle_count }}"
                                        class="badge-xs badge-ghost" />
                                </div>
                            </td>
                            <td>
                                <x-mary-badge value="{{ ucfirst($sale->status) }}"
                                    class="badge-xs {{ $this->getStatusBadgeClass($sale->status) }}" />
                            </td>
                            <td>
                                <div class="text-xs">{{ $sale->created_at->format('M d, Y') }}</div>
                                <div class="text-xs text-base-content/60">{{ $sale->created_at->format('h:i A') }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="text-center py-8">
                                <div class="flex flex-col items-center gap-3">
                                    <x-mary-icon name="o-shopping-cart" class="w-16 h-16 text-base-content/20" />
                                    <div class="text-base-content/60">
                                        @if ($search || $status !== 'all' || $billing_cycle !== 'all')
                                            No sales found matching your filters
                                        @else
                                            No sales records available
                                        @endif
                                    </div>
                                    @if ($search || $status !== 'all' || $billing_cycle !== 'all')
                                        <button wire:click="$set('search', ''); $set('status', 'all'); $set('billing_cycle', 'all')"
                                            class="btn btn-sm btn-ghost">
                                            Clear Filters
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($sales->hasPages())
            <div class="mt-4">
                {{ $sales->links() }}
            </div>
        @endif
    </x-mary-card>
</div>
