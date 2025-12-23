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
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-cube" class="w-6 h-6 text-primary" />
                    <span>Available Packages</span>
                </div>
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse ($packages as $package)
                    <div
                        class="border border-base-300 rounded-lg p-6 space-y-4 hover:shadow-lg transition-all 
                        {{ $currentSubscription && $currentSubscription->package_id === $package->id ? 'border-primary bg-primary/5' : 'bg-base-100' }}">

                        {{-- Package Header --}}
                        <div class="text-center">
                            <h3 class="text-xl font-bold mb-2">{{ $package->name }}</h3>
                            <p class="text-sm text-base-content/70">{{ $package->description }}</p>

                            @if ($currentSubscription && $currentSubscription->package_id === $package->id)
                                <x-mary-badge value="Current Plan" class="badge-primary mt-2" />
                            @endif
                        </div>

                        {{-- Pricing --}}
                        <div class="text-center py-4 border-y border-base-300">
                            <div class="mb-3">
                                <p class="text-sm text-base-content/60 mb-1">Monthly</p>
                                <p class="text-3xl font-bold text-primary">@userCurrency($package->price_monthly)</p>
                                <p class="text-xs text-base-content/50">/month</p>
                            </div>
                            @if ($package->price_yearly)
                                <div>
                                    <p class="text-sm text-base-content/60 mb-1">Yearly</p>
                                    <p class="text-2xl font-bold text-success">@userCurrency($package->price_yearly)</p>
                                    <p class="text-xs text-base-content/50">/year</p>
                                </div>
                            @endif
                        </div>

                        {{-- Features --}}
                        <div class="space-y-2">
                            <div class="flex items-center gap-2 text-sm">
                                <x-mary-icon name="o-check-circle" class="w-4 h-4 text-success" />
                                <span>{{ $package->max_routers }} Routers</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <x-mary-icon name="o-check-circle" class="w-4 h-4 text-success" />
                                <span>{{ $package->max_users ?? 'Unlimited' }} Users</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <x-mary-icon name="o-check-circle" class="w-4 h-4 text-success" />
                                <span>{{ $package->max_zones ?? 'Unlimited' }} Zones</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <x-mary-icon name="o-check-circle" class="w-4 h-4 text-success" />
                                <span>{{ $package->max_vouchers_per_router ?? 'Unlimited' }} Vouchers/Router</span>
                            </div>
                            @if ($package->grace_period_days > 0)
                                <div class="flex items-center gap-2 text-sm">
                                    <x-mary-icon name="o-check-circle" class="w-4 h-4 text-success" />
                                    <span>{{ $package->grace_period_days }} days grace period</span>
                                </div>
                            @endif
                        </div>

                        {{-- Action Buttons --}}
                        <div class="pt-4 space-y-2">
                            @if ($package->price_monthly > 0)
                                <x-mary-button label="Subscribe Monthly" icon="o-arrow-right"
                                    class="btn-primary btn-block btn-sm"
                                    wire:click="openSubscribeModal({{ $package->id }}, 'monthly')" />
                            @endif
                            @if ($package->price_yearly)
                                <x-mary-button label="Subscribe Yearly" icon="o-arrow-right"
                                    class="btn-success btn-block btn-sm"
                                    wire:click="openSubscribeModal({{ $package->id }}, 'yearly')" />
                            @endif
                            @if ($package->price_monthly === 0)
                                <x-mary-button label="Get Free Package" icon="o-gift"
                                    class="btn-ghost btn-block btn-sm"
                                    wire:click="openSubscribeModal({{ $package->id }}, 'monthly')" />
                            @endif
                        </div>
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
                $amount =
                    $selectedCycle === 'yearly'
                        ? $selectedPackage->price_yearly ?? $selectedPackage->price_monthly * 12
                        : $selectedPackage->price_monthly;
            @endphp

            <div class="space-y-4">
                <div class="p-4 bg-base-200 rounded">
                    <p class="text-sm text-base-content/60 mb-2">You are subscribing to:</p>
                    <p class="text-lg font-bold">{{ $selectedPackage->name }}</p>
                    <p class="text-sm text-base-content/70">{{ ucfirst($selectedCycle) }} billing</p>
                </div>

                <div class="flex items-center justify-between p-4 bg-warning/10 border border-warning/20 rounded">
                    <span class="font-semibold">Amount to be charged:</span>
                    <span class="text-xl font-bold text-warning">@userCurrency($amount)</span>
                </div>

                <div class="flex items-center justify-between p-4 bg-info/10 border border-info/20 rounded">
                    <span class="font-semibold">Your Balance:</span>
                    <span class="text-xl font-bold text-info">@userCurrency($balance)</span>
                </div>

                @if ($balance < $amount)
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
                    spinner="subscribe" :disabled="$balance < $amount" />
            </x-slot:actions>
        @endif
    </x-mary-modal>
</div>
