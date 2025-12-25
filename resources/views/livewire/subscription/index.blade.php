<div class="space-y-6">
    {{-- Current Subscription Status --}}
    @if ($currentSubscription)
        <x-mary-card class="border border-base-300 bg-gradient-to-br from-primary/10 to-base-100">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-check-badge" class="w-5 h-5 text-primary" />
                    <h3 class="text-lg font-bold text-primary">{{ $currentSubscription->package->name }}</h3>
                    <x-mary-badge value="{{ ucfirst($currentSubscription->status) }}"
                        class="badge-sm {{ $currentSubscription->status === 'active' ? 'badge-success' : 'badge-warning' }}" />
                </div>
                <x-mary-button label="Change" icon="o-arrow-path" class="btn-primary btn-sm"
                    onclick="document.getElementById('packages-section').scrollIntoView({behavior: 'smooth'})" />
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3 text-sm">
                <div class="p-2 bg-base-200 rounded">
                    <p class="text-xs text-base-content/60">Cycle</p>
                    <p class="font-semibold">{{ ucfirst($currentSubscription->billing_cycle) }}</p>
                </div>
                <div class="p-2 bg-base-200 rounded">
                    <p class="text-xs text-base-content/60">Amount</p>
                    <p class="font-semibold">@userCurrency($currentSubscription->amount)</p>
                </div>
                <div class="p-2 bg-success/10 rounded border border-success/20">
                    <p class="text-xs text-base-content/60">Started</p>
                    <p class="font-semibold text-success text-xs">
                        {{ $currentSubscription->start_date->format('M d, Y') }}</p>
                </div>
                <div class="p-2 bg-warning/10 rounded border border-warning/20">
                    <p class="text-xs text-base-content/60">Expires</p>
                    <p class="font-semibold text-warning text-xs">{{ $currentSubscription->end_date->format('M d, Y') }}
                    </p>
                </div>
                <div class="p-2 bg-info/10 rounded border border-info/20">
                    <p class="text-xs text-base-content/60">Days Left</p>
                    <p class="font-semibold text-info">{{ max(0, $currentSubscription->end_date->diffInDays(now())) }}
                    </p>
                </div>
                <div class="p-2 bg-base-200 rounded">
                    <p class="text-xs text-base-content/60">Routers</p>
                    <p class="font-semibold">{{ $currentSubscription->package->max_routers }}</p>
                </div>
                <div class="p-2 bg-base-200 rounded">
                    <p class="text-xs text-base-content/60">Vouchers</p>
                    <p class="font-semibold">{{ $currentSubscription->package->max_vouchers_per_router ?? '∞' }}</p>
                </div>
            </div>
        </x-mary-card>
    @else
        <x-mary-card class="border border-warning bg-warning/5">
            <div class="text-center py-8">
                <x-mary-icon name="o-exclamation-triangle" class="w-12 h-12 text-warning mx-auto mb-4" />
                <h3 class="text-xl font-semibold mb-2">No Active Subscription</h3>
                <p class="text-base-content/70 mb-4">You don't have an active subscription. Choose a package below to
                    get started.</p>
                <x-mary-button label="Browse Packages" icon="o-arrow-down" class="btn-primary"
                    onclick="document.getElementById('packages-section').scrollIntoView({behavior: 'smooth'})" />
            </div>
        </x-mary-card>
    @endif

    {{-- Wallet Balance --}}
    <x-mary-card class="border border-base-300 bg-base-100">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="p-3 bg-success/10 rounded-lg">
                    <x-mary-icon name="o-wallet" class="w-6 h-6 text-success" />
                </div>
                <div>
                    <p class="text-sm text-base-content/60">Wallet Balance</p>
                    <p class="text-2xl font-bold">@userCurrency($balance)</p>
                </div>
            </div>
            <x-mary-button label="Add Balance" icon="o-plus" class="btn-success"
                href="{{ route('billing.add-balance') }}" wire:navigate />
        </div>
    </x-mary-card>

    {{-- Available Packages --}}
    <div id="packages-section">
        <x-mary-card class="border border-base-300 bg-base-100">
            <x-slot name="title">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-mary-icon name="o-cube" class="w-6 h-6 text-primary" />
                        <span>Available Packages</span>
                    </div>

                    {{-- Billing Cycle Toggle --}}
                    <div class="flex items-center gap-3 bg-base-200 rounded-lg p-1">
                        <button wire:click="$set('viewCycle', 'monthly')"
                            class="px-4 py-2 rounded-md text-sm font-medium transition-all
                            {{ $viewCycle === 'monthly' ? 'bg-primary text-primary-content shadow-sm' : 'text-base-content/70 hover:text-base-content' }}">
                            Monthly
                        </button>
                        <button wire:click="$set('viewCycle', 'yearly')"
                            class="px-4 py-2 rounded-md text-sm font-medium transition-all
                            {{ $viewCycle === 'yearly' ? 'bg-success text-success-content shadow-sm' : 'text-base-content/70 hover:text-base-content' }}">
                            Yearly
                        </button>
                    </div>
                </div>
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse ($packages as $package)
                    @php
                        $price =
                            $viewCycle === 'yearly'
                                ? $package->price_yearly ?? $package->price_monthly * 12
                                : $package->price_monthly;
                        $discountedPrice =
                            $price > 0 && auth()->user()->commission > 0
                                ? $price * (1 - auth()->user()->commission / 100)
                                : $price;
                        $hasDiscount = $price > 0 && auth()->user()->commission > 0;
                    @endphp

                    <div
                        class="border border-base-300 rounded-lg p-4 hover:shadow-md transition-all 
                        {{ $currentSubscription && $currentSubscription->package_id === $package->id ? 'border-primary bg-primary/5' : 'bg-base-100' }}">

                        {{-- Package Header --}}
                        <div class="text-center mb-3">
                            <h3 class="text-xl font-bold mb-1">{{ $package->name }}</h3>
                            <p class="text-sm text-base-content/60 line-clamp-2">{{ $package->description }}</p>

                            @if ($currentSubscription && $currentSubscription->package_id === $package->id)
                                <x-mary-badge value="Current" class="badge-primary badge-sm mt-1" />
                            @endif
                        </div>

                        {{-- Pricing --}}
                        <div class="text-center py-3 border-y border-base-300">
                            @if ($hasDiscount)
                                <p class="text-base line-through text-base-content/40">@userCurrency($price)</p>
                                <p class="text-3xl font-bold text-success">@userCurrency($discountedPrice)</p>
                                <span
                                    class="badge badge-success badge-sm mt-1">-{{ auth()->user()->commission }}%</span>
                            @else
                                <p class="text-3xl font-bold {{ $price > 0 ? 'text-primary' : 'text-base-content' }}">
                                    @userCurrency($price)
                                </p>
                            @endif
                            <p class="text-xs text-base-content/50 mt-1">
                                /{{ $viewCycle === 'yearly' ? 'year' : 'month' }}</p>
                        </div>

                        {{-- Features --}}
                        <div class="space-y-2 my-3">
                            <div class="flex items-center gap-2 text-sm">
                                <x-mary-icon name="o-server" class="w-4 h-4 text-primary" />
                                <span>{{ $package->max_routers }} Routers</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <x-mary-icon name="o-users" class="w-4 h-4 text-primary" />
                                <span>{{ $package->max_users ?? '∞' }} Users</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <x-mary-icon name="o-map" class="w-4 h-4 text-primary" />
                                <span>{{ $package->max_zones ?? '∞' }} Zones</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <x-mary-icon name="o-ticket" class="w-4 h-4 text-primary" />
                                <span>{{ $package->max_vouchers_per_router ?? '∞' }} Vouchers</span>
                            </div>
                            @if ($package->grace_period_days > 0)
                                <div class="flex items-center gap-2 text-sm">
                                    <x-mary-icon name="o-clock" class="w-4 h-4 text-primary" />
                                    <span>{{ $package->grace_period_days }}d grace</span>
                                </div>
                            @endif
                        </div>

                        {{-- Action Button --}}
                        @if ($price > 0)
                            <x-mary-button :label="'Subscribe ' . ucfirst($viewCycle)" icon="o-arrow-right"
                                class="{{ $viewCycle === 'yearly' ? 'btn-success' : 'btn-primary' }} btn-block btn-sm"
                                wire:click="openSubscribeModal({{ $package->id }}, '{{ $viewCycle }}')" />
                        @else
                            <x-mary-button label="Get Free" icon="o-gift" class="btn-ghost btn-block btn-sm"
                                wire:click="openSubscribeModal({{ $package->id }}, 'monthly')" />
                        @endif
                    </div>
                @empty
                    <div class="col-span-3 text-center py-8">
                        <p class="text-base-content/60">No packages available at the moment.</p>
                    </div>
                @endforelse
            </div>
        </x-mary-card>
    </div>

    {{-- Subscribe Confirmation Modal --}}
    <x-mary-modal wire:model="showSubscribeModal" title="Confirm Subscription" class="backdrop-blur">
        @if ($selectedPackageId)
            @php
                $selectedPackage = $packages->firstWhere('id', $selectedPackageId);
                $originalAmount =
                    $selectedCycle === 'yearly'
                        ? $selectedPackage->price_yearly ?? $selectedPackage->price_monthly * 12
                        : $selectedPackage->price_monthly;
                $discount = 0;
                $finalAmount = $originalAmount;

                if ($originalAmount > 0 && auth()->user()->hasRole('admin') && auth()->user()->commission > 0) {
                    $discount = round(($originalAmount * auth()->user()->commission) / 100, 2);
                    $finalAmount = $originalAmount - $discount;
                }
            @endphp

            <div class="space-y-4">
                <div class="p-4 bg-base-200 rounded">
                    <p class="text-sm text-base-content/60 mb-2">You are subscribing to:</p>
                    <p class="text-lg font-bold">{{ $selectedPackage->name }}</p>
                    <p class="text-sm text-base-content/70">{{ ucfirst($selectedCycle) }} billing</p>
                </div>

                @if ($discount > 0)
                    <div class="space-y-2 p-4 bg-success/10 border border-success/20 rounded">
                        <div class="flex items-center justify-between text-sm">
                            <span>Original Price:</span>
                            <span class="line-through text-base-content/60">@userCurrency($originalAmount)</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="flex items-center gap-1">
                                <x-mary-icon name="o-tag" class="w-4 h-4" />
                                Commission Discount ({{ auth()->user()->commission }}%):
                            </span>
                            <span class="text-success font-semibold">-@userCurrency($discount)</span>
                        </div>
                        <div class="border-t border-success/30 pt-2 mt-2">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold">Final Amount:</span>
                                <span class="text-xl font-bold text-success">@userCurrency($finalAmount)</span>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="flex items-center justify-between p-4 bg-warning/10 border border-warning/20 rounded">
                        <span class="font-semibold">Amount to be charged:</span>
                        <span class="text-xl font-bold text-warning">@userCurrency($finalAmount)</span>
                    </div>
                @endif

                <div class="flex items-center justify-between p-4 bg-info/10 border border-info/20 rounded">
                    <span class="font-semibold">Your Balance:</span>
                    <span class="text-xl font-bold text-info">@userCurrency($balance)</span>
                </div>

                @if ($balance < $finalAmount)
                    <x-mary-alert icon="o-exclamation-triangle" class="alert-error">
                        Insufficient balance! Please add funds to your wallet first.
                    </x-mary-alert>
                @endif

                @if ($currentSubscription)
                    <x-mary-alert icon="o-information-circle" class="alert-warning">
                        Your current subscription will be cancelled and replaced with this new one.
                    </x-mary-alert>
                @endif
            </div>

            <x-slot:actions>
                <x-mary-button label="Cancel" wire:click="$set('showSubscribeModal', false)" />
                <x-mary-button label="Confirm & Subscribe" class="btn-primary" wire:click="subscribe"
                    spinner="subscribe" :disabled="$balance < $finalAmount" />
            </x-slot:actions>
        @endif
    </x-mary-modal>
</div>
