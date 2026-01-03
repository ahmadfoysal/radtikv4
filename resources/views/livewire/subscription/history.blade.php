<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Subscription History</h1>
            <p class="text-sm text-base-content/70 mt-1">View all your subscription records and billing history</p>
        </div>
    </div>

    {{-- Filters --}}
    <x-mary-card>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Search --}}
            <x-mary-input wire:model.live.debounce.500ms="search" placeholder="Search by package, cycle, promo..."
                icon="o-magnifying-glass" clearable />

            {{-- Status Filter --}}
            <x-mary-select wire:model.live="status" icon="o-funnel" :options="[
                ['id' => 'all', 'name' => 'All Status'],
                ['id' => 'active', 'name' => 'Active'],
                ['id' => 'cancelled', 'name' => 'Cancelled'],
                ['id' => 'expired', 'name' => 'Expired'],
                ['id' => 'suspended', 'name' => 'Suspended'],
                ['id' => 'grace_period', 'name' => 'Grace Period'],
            ]" />

            {{-- Per Page --}}
            <x-mary-select wire:model.live="perPage" icon="o-list-bullet" :options="[
                ['id' => 10, 'name' => '10 per page'],
                ['id' => 15, 'name' => '15 per page'],
                ['id' => 25, 'name' => '25 per page'],
                ['id' => 50, 'name' => '50 per page'],
            ]" />
        </div>
    </x-mary-card>

    {{-- Subscription History Table --}}
    <x-mary-card>
        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th class="cursor-pointer" wire:click="sortBy('id')">
                            <div class="flex items-center gap-2">
                                ID
                                <x-mary-icon :name="$this->getSortIcon('id')" class="w-4 h-4" />
                            </div>
                        </th>
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
                        <th>Amount</th>
                        <th>Discount</th>
                        <th class="cursor-pointer" wire:click="sortBy('status')">
                            <div class="flex items-center gap-2">
                                Status
                                <x-mary-icon :name="$this->getSortIcon('status')" class="w-4 h-4" />
                            </div>
                        </th>
                        <th>Auto Renew</th>
                        <th>Promo Code</th>
                        <th>Cycles</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($subscriptions as $subscription)
                        <tr wire:key="subscription-{{ $subscription->id }}">
                            <td>
                                <span class="font-mono text-xs">#{{ $subscription->id }}</span>
                            </td>
                            <td>
                                <div class="font-semibold">{{ $subscription->package->name }}</div>
                            </td>
                            <td>
                                <x-mary-badge value="{{ ucfirst($subscription->billing_cycle) }}"
                                    class="badge-sm {{ $subscription->billing_cycle === 'yearly' ? 'badge-primary' : 'badge-secondary' }}" />
                            </td>
                            <td>
                                <div class="text-sm">
                                    {{ $subscription->start_date->format('M d, Y') }}
                                </div>
                            </td>
                            <td>
                                <div class="text-sm">
                                    {{ $subscription->end_date->format('M d, Y') }}
                                </div>
                                @if ($subscription->isActive() && $subscription->hasEnded())
                                    <div class="text-xs text-error">Expired</div>
                                @elseif ($subscription->isActive())
                                    <div class="text-xs text-info">
                                        {{ abs($subscription->daysUntilExpiry()) }} days left
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="font-semibold">
                                    ${{ number_format($subscription->amount, 2) }}
                                </div>
                                @if ($subscription->original_price > $subscription->amount)
                                    <div class="text-xs text-base-content/60 line-through">
                                        ${{ number_format($subscription->original_price, 2) }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if ($subscription->discount_percent > 0)
                                    <x-mary-badge value="{{ $subscription->discount_percent }}% OFF"
                                        class="badge-sm badge-success" />
                                @else
                                    <span class="text-xs text-base-content/50">-</span>
                                @endif
                            </td>
                            <td>
                                <x-mary-badge value="{{ ucfirst($subscription->status) }}"
                                    class="badge-sm {{ $this->getStatusBadgeClass($subscription->status) }}" />
                            </td>
                            <td>
                                @if ($subscription->auto_renew)
                                    <x-mary-icon name="o-check-circle" class="w-5 h-5 text-success" />
                                @else
                                    <x-mary-icon name="o-x-circle" class="w-5 h-5 text-base-content/30" />
                                @endif
                            </td>
                            <td>
                                @if ($subscription->promo_code)
                                    <x-mary-badge value="{{ $subscription->promo_code }}"
                                        class="badge-sm badge-accent" />
                                @else
                                    <span class="text-xs text-base-content/50">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="text-sm text-center">
                                    <span class="font-mono">{{ $subscription->cycle_count }}</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center py-8">
                                <div class="flex flex-col items-center gap-3">
                                    <x-mary-icon name="o-document-text" class="w-16 h-16 text-base-content/20" />
                                    <div class="text-base-content/60">
                                        @if ($search || $status !== 'all')
                                            No subscriptions found matching your filters
                                        @else
                                            No subscription history available
                                        @endif
                                    </div>
                                    @if ($search || $status !== 'all')
                                        <button wire:click="$set('search', ''); $set('status', 'all')"
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
        @if ($subscriptions->hasPages())
            <div class="mt-4">
                {{ $subscriptions->links() }}
            </div>
        @endif
    </x-mary-card>

    {{-- Summary Stats --}}
    @if ($subscriptions->total() > 0)
        <x-mary-card>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="text-center p-4 bg-base-200 rounded-lg">
                    <div class="text-2xl font-bold text-primary">{{ $subscriptions->total() }}</div>
                    <div class="text-sm text-base-content/70">Total Subscriptions</div>
                </div>
                <div class="text-center p-4 bg-base-200 rounded-lg">
                    <div class="text-2xl font-bold text-success">
                        {{ $subscriptions->where('status', 'active')->count() }}
                    </div>
                    <div class="text-sm text-base-content/70">Active</div>
                </div>
                <div class="text-center p-4 bg-base-200 rounded-lg">
                    <div class="text-2xl font-bold text-warning">
                        {{ $subscriptions->where('status', 'expired')->count() }}
                    </div>
                    <div class="text-sm text-base-content/70">Expired</div>
                </div>
                <div class="text-center p-4 bg-base-200 rounded-lg">
                    <div class="text-2xl font-bold text-error">
                        {{ $subscriptions->where('status', 'cancelled')->count() }}
                    </div>
                    <div class="text-sm text-base-content/70">Cancelled</div>
                </div>
            </div>
        </x-mary-card>
    @endif
</div>
