<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Customer Management</h1>
            <p class="text-sm text-base-content/70 mt-1">Manage all admin customers and their subscriptions</p>
        </div>
    </div>

    {{-- Filters --}}
    <x-mary-card>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            {{-- Search --}}
            <div class="md:col-span-2">
                <x-mary-input wire:model.live.debounce.500ms="search"
                    placeholder="Search by name, email, phone, address..." icon="o-magnifying-glass" clearable />
            </div>

            {{-- Status Filter --}}
            <x-mary-select wire:model.live="status" icon="o-funnel" :options="[
                ['id' => 'all', 'name' => 'All Status'],
                ['id' => 'active', 'name' => 'Active'],
                ['id' => 'inactive', 'name' => 'Inactive'],
                ['id' => 'suspended', 'name' => 'Suspended'],
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

    {{-- Customer Table --}}
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
                        <th class="cursor-pointer" wire:click="sortBy('name')">
                            <div class="flex items-center gap-2">
                                Customer
                                <x-mary-icon :name="$this->getSortIcon('name')" class="w-4 h-4" />
                            </div>
                        </th>
                        <th>Contact</th>
                        <th>Package</th>
                        <th class="cursor-pointer" wire:click="sortBy('balance')">
                            <div class="flex items-center gap-2">
                                Balance
                                <x-mary-icon :name="$this->getSortIcon('balance')" class="w-4 h-4" />
                            </div>
                        </th>
                        <th>Commission</th>
                        <th>Routers</th>
                        <th class="cursor-pointer" wire:click="sortBy('created_at')">
                            <div class="flex items-center gap-2">
                                Joined
                                <x-mary-icon :name="$this->getSortIcon('created_at')" class="w-4 h-4" />
                            </div>
                        </th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr wire:key="customer-{{ $customer->id }}">
                            <td>
                                <span class="font-mono text-xs">#{{ $customer->id }}</span>
                            </td>
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="avatar placeholder">
                                        <div class="bg-primary text-primary-content rounded-full w-10">
                                            <span class="text-sm">{{ substr($customer->name, 0, 2) }}</span>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="font-semibold">{{ $customer->name }}</div>
                                        <div class="text-xs text-base-content/60">{{ $customer->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="text-sm">
                                    @if ($customer->phone)
                                        <div class="flex items-center gap-1">
                                            <x-mary-icon name="o-phone" class="w-3 h-3" />
                                            {{ $customer->phone }}
                                        </div>
                                    @endif
                                    @if ($customer->country)
                                        <div class="flex items-center gap-1 text-base-content/60">
                                            <x-mary-icon name="o-map-pin" class="w-3 h-3" />
                                            {{ $customer->country }}
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @php
                                    $subscription = $customer->subscriptions->first();
                                @endphp
                                @if ($subscription && $subscription->package)
                                    <div>
                                        <div class="font-medium">{{ $subscription->package->name }}</div>
                                        <div class="text-xs text-base-content/60">
                                            {{ ucfirst($subscription->billing_cycle) }}
                                        </div>
                                    </div>
                                @else
                                    <span class="text-xs text-base-content/50">No package</span>
                                @endif
                            </td>
                            <td>
                                <div class="font-semibold text-success">
                                    ${{ number_format($customer->balance, 2) }}
                                </div>
                            </td>
                            <td>
                                <x-mary-badge value="{{ $customer->commission }}%" class="badge-sm badge-info" />
                            </td>
                            <td>
                                <div class="text-center">
                                    <x-mary-badge value="{{ $customer->routers_count }}"
                                        class="badge-sm badge-primary" />
                                </div>
                            </td>
                            <td>
                                <div class="text-sm">
                                    {{ $customer->created_at->format('M d, Y') }}
                                </div>
                                <div class="text-xs text-base-content/60">
                                    {{ $customer->created_at->diffForHumans() }}
                                </div>
                            </td>
                            <td>
                                <x-mary-badge :value="$this->getStatusText($customer)" :class="'badge-sm ' . $this->getStatusBadgeClass($customer)" />
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('customers.show', $customer) }}" wire:navigate
                                        class="btn btn-ghost btn-xs" title="View Details">
                                        <x-mary-icon name="o-eye" class="w-4 h-4" />
                                    </a>
                                    <a href="{{ route('customers.edit', $customer) }}" wire:navigate
                                        class="btn btn-ghost btn-xs" title="Edit">
                                        <x-mary-icon name="o-pencil" class="w-4 h-4" />
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-8">
                                <div class="flex flex-col items-center gap-3">
                                    <x-mary-icon name="o-user-group" class="w-16 h-16 text-base-content/20" />
                                    <div class="text-base-content/60">
                                        @if ($search || $status !== 'all')
                                            No customers found matching your filters
                                        @else
                                            No customers available
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
        @if ($customers->hasPages())
            <div class="mt-4">
                {{ $customers->links() }}
            </div>
        @endif
    </x-mary-card>

    {{-- Summary Stats --}}
    @if ($customers->total() > 0)
        <x-mary-card title="Summary Statistics">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="text-center p-4 bg-base-200 rounded-lg">
                    <div class="text-2xl font-bold text-primary">{{ $customers->total() }}</div>
                    <div class="text-sm text-base-content/70">Total Customers</div>
                </div>
                <div class="text-center p-4 bg-base-200 rounded-lg">
                    <div class="text-2xl font-bold text-success">
                        ${{ number_format($customers->sum('balance'), 2) }}
                    </div>
                    <div class="text-sm text-base-content/70">Total Balance</div>
                </div>
                <div class="text-center p-4 bg-base-200 rounded-lg">
                    <div class="text-2xl font-bold text-info">
                        {{ $customers->sum('routers_count') }}
                    </div>
                    <div class="text-sm text-base-content/70">Total Routers</div>
                </div>
                <div class="text-center p-4 bg-base-200 rounded-lg">
                    <div class="text-2xl font-bold text-warning">
                        {{ number_format($customers->avg('commission'), 1) }}%
                    </div>
                    <div class="text-sm text-base-content/70">Avg Commission</div>
                </div>
            </div>
        </x-mary-card>
    @endif
</div>
