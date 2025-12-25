<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <x-mary-button icon="o-arrow-left" class="btn-ghost" wire:click="$dispatch('back')" />
            <div>
                <h1 class="text-3xl font-bold text-base-content">User Details</h1>
                <p class="text-base-content/60">Manage user information and statistics</p>
            </div>
        </div>
        <div class="flex gap-2">
            <x-mary-button icon="o-pencil" class="btn-primary btn-sm" wire:click="edit">
                Edit User
            </x-mary-button>
            <x-mary-dropdown>
                <x-slot:trigger>
                    <x-mary-button icon="o-ellipsis-vertical" class="btn-ghost btn-sm" />
                </x-slot:trigger>
                <x-mary-menu-item title="View Activity" icon="o-clock" />
                <x-mary-menu-item title="Send Message" icon="o-envelope" />
                <x-mary-menu-separator />
                <x-mary-menu-item title="Suspend Account" icon="o-no-symbol" class="text-warning" />
            </x-mary-dropdown>
        </div>
    </div>

    <!-- User Profile Card -->
    <x-mary-card class="shadow-lg">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Avatar & Basic Info -->
            <div class="flex flex-col items-center lg:items-start lg:w-1/3">
                <x-mary-avatar :src="$user->avatar_url ?? null" size="2xl" class="mb-6 ring-4 ring-primary/20" />
                <div class="text-center lg:text-left w-full">
                    <h2 class="text-2xl font-bold text-base-content mb-2">{{ $user->name }}</h2>
                    <p class="text-base-content/60 mb-4 flex items-center justify-center lg:justify-start gap-2">
                        <x-mary-icon name="o-envelope" class="w-4 h-4" />
                        {{ $user->email }}
                    </p>

                    <!-- Role Badge -->
                    <div class="flex flex-wrap gap-2 justify-center lg:justify-start mb-4">
                        @foreach ($user->roles as $role)
                            <x-mary-badge :value="ucfirst($role->name)" :class="match ($role->name) {
                                'superadmin' => 'badge-error',
                                'admin' => 'badge-warning',
                                'reseller' => 'badge-primary',
                                default => 'badge-neutral',
                            }" />
                        @endforeach
                    </div>

                    <!-- Registration Date -->
                    <div class="flex items-center justify-center lg:justify-start gap-2 text-sm text-base-content/60">
                        <x-mary-icon name="o-calendar" class="w-4 h-4" />
                        <span>Member since {{ $user->created_at->format('M d, Y') }}</span>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="lg:w-2/3">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Current Subscription -->
                    <div class="stats stats-vertical lg:stats-horizontal shadow">
                        <div class="stat">
                            <div class="stat-figure text-primary">
                                <x-mary-icon name="o-star" class="w-8 h-8" />
                            </div>
                            <div class="stat-title">Active Subscription</div>
                            <div class="stat-value text-primary text-lg">
                                {{ $user->activeSubscription()?->name ?? 'None' }}
                            </div>
                        </div>
                    </div>

                    <!-- Balance -->
                    <div class="stats stats-vertical lg:stats-horizontal shadow">
                        <div class="stat">
                            <div class="stat-figure text-info">
                                <x-mary-icon name="o-banknotes" class="w-8 h-8" />
                            </div>
                            <div class="stat-title">Current Balance</div>
                            <div class="stat-value text-info text-lg">
                                ${{ number_format($user->balance, 2) }}
                            </div>
                        </div>
                    </div>

                    <!-- Total Topup -->
                    <div class="stats stats-vertical lg:stats-horizontal shadow">
                        <div class="stat">
                            <div class="stat-figure text-success">
                                <x-mary-icon name="o-arrow-trending-up" class="w-8 h-8" />
                            </div>
                            <div class="stat-title">Total Topup</div>
                            <div class="stat-value text-success text-lg">
                                ${{ number_format($user->totalTopup(), 2) }}
                            </div>
                        </div>
                    </div>

                    <!-- Total Spend -->
                    <div class="stats stats-vertical lg:stats-horizontal shadow">
                        <div class="stat">
                            <div class="stat-figure text-error">
                                <x-mary-icon name="o-arrow-trending-down" class="w-8 h-8" />
                            </div>
                            <div class="stat-title">Total Spend</div>
                            <div class="stat-value text-error text-lg">
                                ${{ number_format($user->totalSpend(), 2) }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Stats Row -->
                <div class="mt-6 grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-base-200 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-primary">{{ $user->routers()->count() }}</div>
                        <div class="text-sm text-base-content/60">Total Routers</div>
                    </div>
                    <div class="bg-base-200 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-info">{{ $user->vouchers()->count() ?? 0 }}</div>
                        <div class="text-sm text-base-content/60">Vouchers</div>
                    </div>
                    <div class="bg-base-200 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-warning">{{ $user->ticketsCreated()->count() ?? 0 }}</div>
                        <div class="text-sm text-base-content/60">Tickets</div>
                    </div>
                    <div class="bg-base-200 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-success">
                            {{ $user->updated_at->diffForHumans() }}
                        </div>
                        <div class="text-sm text-base-content/60">Last Updated</div>
                    </div>
                </div>
            </div>
        </div>
    </x-mary-card>

    <!-- Activity Timeline (Optional) -->
    <x-mary-card title="Recent Activity" subtitle="Latest user actions and events" class="shadow-lg">
        <div class="space-y-4">
            @forelse($user->activities ?? [] as $activity)
                <div class="flex items-start gap-4 p-4 bg-base-100 rounded-lg border">
                    <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center">
                        <x-mary-icon name="o-clock" class="w-5 h-5 text-primary" />
                    </div>
                    <div class="flex-1">
                        <p class="font-medium">{{ $activity->description }}</p>
                        <p class="text-sm text-base-content/60">{{ $activity->created_at->diffForHumans() }}</p>
                    </div>
                </div>
            @empty
                <div class="text-center py-8">
                    <p class="text-base-content/60">No recent activity</p>
                </div>
            @endforelse
        </div>
    </x-mary-card>
</div>
