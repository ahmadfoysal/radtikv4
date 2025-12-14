<div class="space-y-6">
    {{-- Welcome Section --}}
    <x-mary-card class="border border-base-300 bg-base-100">
        <div class="text-center py-8">
            <x-mary-icon name="o-user-circle" class="w-20 h-20 mx-auto text-primary mb-4" />
            <h2 class="text-2xl font-bold mb-2">Welcome to RADTik</h2>
            <p class="text-base-content/70 mb-6">Your account is being set up. Please contact your administrator to assign a role and permissions.</p>
            <div class="flex gap-4 justify-center">
                <x-mary-button icon="o-user" label="View Profile" class="btn-primary" href="{{ route('settings.profile') }}" wire:navigate />
                <x-mary-button icon="o-cog-6-tooth" label="Settings" class="btn-outline" href="{{ route('settings.profile') }}" wire:navigate />
            </div>
        </div>
    </x-mary-card>

    {{-- Information Cards --}}
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        {{-- Account Status --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <x-mary-icon name="o-shield-check" class="w-5 h-5 text-info" />
                        <span class="text-sm font-medium text-base-content/70">Account Status</span>
                    </div>
                    <p class="text-xl font-bold">Pending Setup</p>
                    <p class="text-xs text-base-content/60 mt-1">Waiting for role assignment</p>
                </div>
            </div>
        </x-mary-card>

        {{-- Quick Actions --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <x-mary-icon name="o-bolt" class="w-5 h-5 text-warning" />
                        <span class="text-sm font-medium text-base-content/70">Quick Actions</span>
                    </div>
                    <div class="space-y-2 mt-3">
                        <x-mary-button icon="o-user" label="Update Profile" class="btn-sm btn-outline btn-block" href="{{ route('settings.profile') }}" wire:navigate />
                        <x-mary-button icon="o-key" label="Change Password" class="btn-sm btn-outline btn-block" href="{{ route('settings.security') }}" wire:navigate />
                    </div>
                </div>
            </div>
        </x-mary-card>

        {{-- Support --}}
        <x-mary-card class="border border-base-300 bg-base-100">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <x-mary-icon name="o-lifebuoy" class="w-5 h-5 text-success" />
                        <span class="text-sm font-medium text-base-content/70">Need Help?</span>
                    </div>
                    <p class="text-sm text-base-content/70 mb-3">Contact your administrator for access and support.</p>
                    <x-mary-button icon="o-envelope" label="Contact Support" class="btn-sm btn-primary btn-block" href="{{ route('tickets.index') }}" wire:navigate />
                </div>
            </div>
        </x-mary-card>
    </div>

    {{-- Getting Started Section --}}
    <x-mary-card class="border border-base-300 bg-base-100">
        <x-slot name="title">Getting Started</x-slot>
        <div class="space-y-4">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center">
                    <span class="text-primary font-semibold">1</span>
                </div>
                <div>
                    <h4 class="font-semibold mb-1">Complete Your Profile</h4>
                    <p class="text-sm text-base-content/70">Make sure your profile information is up to date.</p>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center">
                    <span class="text-primary font-semibold">2</span>
                </div>
                <div>
                    <h4 class="font-semibold mb-1">Wait for Role Assignment</h4>
                    <p class="text-sm text-base-content/70">Your administrator will assign you a role (Admin or Reseller) with appropriate permissions.</p>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center">
                    <span class="text-primary font-semibold">3</span>
                </div>
                <div>
                    <h4 class="font-semibold mb-1">Start Using the System</h4>
                    <p class="text-sm text-base-content/70">Once assigned, you'll have access to routers, vouchers, and other features based on your role.</p>
                </div>
            </div>
        </div>
    </x-mary-card>

    {{-- System Information --}}
    <div class="grid gap-4 md:grid-cols-2">
        <x-mary-card class="border border-base-300 bg-base-100">
            <x-slot name="title">About RADTik</x-slot>
            <p class="text-sm text-base-content/70 mb-4">
                RADTik is a comprehensive hotspot management system for MikroTik routers. 
                Manage vouchers, routers, users, and billing all in one place.
            </p>
            <div class="space-y-2 text-xs text-base-content/60">
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-check-circle" class="w-4 h-4 text-success" />
                    <span>Voucher Management</span>
                </div>
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-check-circle" class="w-4 h-4 text-success" />
                    <span>Router Integration</span>
                </div>
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-check-circle" class="w-4 h-4 text-success" />
                    <span>User Management</span>
                </div>
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-check-circle" class="w-4 h-4 text-success" />
                    <span>Billing & Invoicing</span>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="border border-base-300 bg-base-100">
            <x-slot name="title">Your Account</x-slot>
            <div class="space-y-3">
                <div>
                    <p class="text-xs text-base-content/60 mb-1">Email</p>
                    <p class="text-sm font-medium">{{ auth()->user()->email ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-base-content/60 mb-1">Name</p>
                    <p class="text-sm font-medium">{{ auth()->user()->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-base-content/60 mb-1">Account Created</p>
                    <p class="text-sm font-medium">{{ auth()->user()->created_at?->format('M d, Y') ?? 'N/A' }}</p>
                </div>
                <div class="pt-2">
                    <x-mary-button icon="o-pencil" label="Edit Profile" class="btn-sm btn-outline btn-block" href="{{ route('settings.profile') }}" wire:navigate />
                </div>
            </div>
        </x-mary-card>
    </div>
</div>

