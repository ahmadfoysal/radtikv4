<div class="max-w-7xl mx-auto">
    <x-mary-card title="Package List" class="shadow-sm border border-base-300">

        {{-- Toolbar --}}
        <div class="flex flex-col sm:flex-row sm:flex-wrap sm:items-end sm:justify-end gap-4 mb-6">
            {{-- Search --}}
            <x-mary-input placeholder="Search by name, description, billing cycle…" icon="o-magnifying-glass"
                wire:model.live.debounce.300ms="search" class="w-full sm:w-72" />

            {{-- Per page --}}
            <x-mary-select :options="[
                ['id' => 10, 'name' => '10'],
                ['id' => 25, 'name' => '25'],
                ['id' => 50, 'name' => '50'],
                ['id' => 100, 'name' => '100'],
            ]" option-value="id" option-label="name" wire:model.live="perPage"
                class="w-32 sm:text-right" />

            {{-- Buttons --}}
            <div class="flex justify-end gap-2 w-full sm:w-auto">
                <x-mary-button label="Clear" icon="o-x-mark" class="btn-ghost" wire:click="$set('search','')" />
                <x-mary-button label="New Package" icon="o-plus" class="btn-primary" wire:navigate
                    href="{{ route('packages.create') }}" />
            </div>
        </div>


        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm border border-base-200 overflow-hidden">
                <thead>
                    <tr class="bg-base-200 text-base-content/80 text-center">
                        <th class="px-4 py-3 cursor-pointer text-left" wire:click="sortBy('name')">
                            <div class="flex items-center gap-2">
                                Name
                                <x-mary-icon :name="$this->getSortIcon('name')" class="w-4 h-4" />
                            </div>
                        </th>
                        <th class="px-4 py-3 cursor-pointer text-left" wire:click="sortBy('price_monthly')">
                            <div class="flex items-center gap-2">
                                Price Monthly
                                <x-mary-icon :name="$this->getSortIcon('price_monthly')" class="w-4 h-4" />
                            </div>
                        </th>
                        <th class="px-4 py-3 cursor-pointer text-left" wire:click="sortBy('price_yearly')">
                            <div class="flex items-center gap-2">
                                Price Yearly
                                <x-mary-icon :name="$this->getSortIcon('price_yearly')" class="w-4 h-4" />
                            </div>
                        </th>
                        <th class="px-4 py-3 cursor-pointer text-left" wire:click="sortBy('max_routers')">
                            <div class="flex items-center gap-2">
                                Max Routers
                                <x-mary-icon :name="$this->getSortIcon('max_routers')" class="w-4 h-4" />
                            </div>
                        </th>
                        <th class="px-4 py-3 cursor-pointer text-left" wire:click="sortBy('max_users')">
                            <div class="flex items-center gap-2">
                                Max Users
                                <x-mary-icon :name="$this->getSortIcon('max_users')" class="w-4 h-4" />
                            </div>
                        </th>
                        <th class="px-4 py-3 cursor-pointer text-left" wire:click="sortBy('grace_period_days')">
                            <div class="flex items-center gap-2">
                                Grace Period
                                <x-mary-icon :name="$this->getSortIcon('grace_period_days')" class="w-4 h-4" />
                            </div>
                        </th>
                        <th class="px-4 py-3 cursor-pointer text-left" wire:click="sortBy('is_featured')">
                            <div class="flex items-center gap-2">
                                Featured
                                <x-mary-icon :name="$this->getSortIcon('is_featured')" class="w-4 h-4" />
                            </div>
                        </th>
                        <th class="px-4 py-3 cursor-pointer text-left" wire:click="sortBy('is_active')">
                            <div class="flex items-center gap-2">
                                Status
                                <x-mary-icon :name="$this->getSortIcon('is_active')" class="w-4 h-4" />
                            </div>
                        </th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($packages as $package)
                        <tr class="hover:bg-base-200/40 border-t border-base-200 text-center">
                            <td class="px-4 py-3 text-left font-medium">{{ $package->name }}</td>
                            <td class="px-4 py-3 text-left">
                                <div>
                                    @userCurrency($package->price_monthly)
                                    @if ($package->price_monthly > 0 && auth()->user()->hasRole('admin') && auth()->user()->commission > 0)
                                        <div class="text-xs text-success">
                                            Your price: @userCurrency($package->price_monthly * (1 - auth()->user()->commission / 100))
                                            <span
                                                class="badge badge-success badge-xs ml-1">-{{ auth()->user()->commission }}%</span>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-left">
                                @if ($package->price_yearly)
                                    <div>
                                        @userCurrency($package->price_yearly)
                                        @if ($package->price_yearly > 0 && auth()->user()->hasRole('admin') && auth()->user()->commission > 0)
                                            <div class="text-xs text-success">
                                                Your price: @userCurrency($package->price_yearly * (1 - auth()->user()->commission / 100))
                                                <span
                                                    class="badge badge-success badge-xs ml-1">-{{ auth()->user()->commission }}%</span>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $package->max_routers }}</td>
                            <td class="px-4 py-3">{{ $package->max_users }}</td>
                            <td class="px-4 py-3">{{ $package->grace_period_days }} days</td>
                            <td class="px-4 py-3">
                                @if ($package->is_featured)
                                    <x-mary-icon name="o-star" class="w-5 h-5 text-warning" />
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($package->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-error">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-3">
                                    {{-- Edit Icon --}}
                                    <a href="{{ route('packages.edit', $package) }}" wire:navigate
                                        class="text-primary hover:text-primary/80 transition-colors" title="Edit">
                                        <x-mary-icon name="o-pencil-square" class="w-5 h-5" />
                                    </a>

                                    {{-- Delete Icon --}}
                                    <button wire:click="delete({{ $package->id }})" wire:loading.attr="disabled"
                                        class="relative text-error hover:text-error/80 transition-colors" title="Delete"
                                        onclick="return confirm('Are you sure you want to delete {{ $package->name }}?')">
                                        {{-- Trash icon (visible when not deleting) --}}
                                        <x-mary-icon name="o-trash" class="w-5 h-5" wire:loading.remove
                                            wire:target="delete({{ $package->id }})" />

                                        {{-- MaryUI loader (visible while deleting this package) --}}
                                        <x-mary-loading wire:loading wire:target="delete({{ $package->id }})"
                                            class="w-5 h-5 text-error" />
                                    </button>


                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-base-content/70">
                                No packages found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Footer --}}
        <div class="mt-6">
            {{ $packages->links() }}
        </div>
    </x-mary-card>
</div>
