<section class="w-full max-w-7xl mx-auto">
    {{-- Header Card --}}
    <x-mary-card class="mb-4 bg-base-100 border border-base-300 shadow-sm">
        <div class="px-4 py-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <x-mary-icon name="o-server" class="w-6 h-6 text-primary" />
                <div>
                    <h1 class="font-semibold text-lg">{{ $nasDevice->name }}</h1>
                    <p class="text-sm text-base-content/60">NAS Device Details</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <x-mary-button icon="o-pencil" label="Edit" class="btn-sm btn-ghost"
                    href="{{ route('nas-devices.edit', $nasDevice->id) }}" wire:navigate />
                <x-mary-button icon="o-arrow-left" label="Back" class="btn-sm btn-ghost"
                    href="{{ route('nas-devices.index') }}" wire:navigate />
            </div>
        </div>
    </x-mary-card>

    {{-- Main Content --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- Basic Information --}}
        <x-mary-card title="Basic Information" class="bg-base-100">
            <div class="space-y-4">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-base-content/50 mb-1">Name</p>
                    <p class="text-base font-medium">{{ $nasDevice->name }}</p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-base-content/50 mb-1">IP Address / Host</p>
                    <p class="text-base font-medium font-mono">{{ $nasDevice->address }}</p>
                </div>

                @if($nasDevice->login_address)
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-base-content/50 mb-1">Login Address</p>
                    <a href="{{ $nasDevice->login_address }}" target="_blank" rel="noopener noreferrer"
                        class="text-base font-medium text-primary hover:underline">
                        {{ $nasDevice->login_address }}
                    </a>
                </div>
                @endif

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-base-content/50 mb-1">API Port</p>
                        <p class="text-base font-medium">{{ $nasDevice->port }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-base-content/50 mb-1">Username</p>
                        <p class="text-base font-medium">{{ $nasDevice->username }}</p>
                    </div>
                </div>

                @if($nasDevice->zone)
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-base-content/50 mb-1">Zone</p>
                    <p class="text-base font-medium">{{ $nasDevice->zone->name }}</p>
                </div>
                @endif

                @if($nasDevice->note)
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-base-content/50 mb-1">Note</p>
                    <p class="text-base text-base-content/80">{{ $nasDevice->note }}</p>
                </div>
                @endif
            </div>
        </x-mary-card>

        {{-- NAS Configuration --}}
        <x-mary-card title="NAS Configuration" class="bg-base-100">
            <div class="space-y-4">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-base-content/50 mb-1">Device Type</p>
                    <div class="flex items-center gap-2">
                        <span class="badge badge-info badge-sm">NAS Device</span>
                    </div>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-base-content/50 mb-1">Parent Router</p>
                    @if($nasDevice->parentRouter)
                        <div class="flex items-center gap-2">
                            <p class="text-base font-medium">{{ $nasDevice->parentRouter->name }}</p>
                            <x-mary-button icon="o-arrow-top-right-on-square" class="btn-xs btn-ghost"
                                href="{{ route('routers.show', $nasDevice->parentRouter->id) }}" wire:navigate />
                        </div>
                        <p class="text-sm text-base-content/60 mt-1">{{ $nasDevice->parentRouter->address }}</p>
                    @else
                        <p class="text-base text-base-content/60">—</p>
                    @endif
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-base-content/50 mb-1">RADIUS Server</p>
                    @if($nasDevice->radiusServer)
                        <div class="flex items-center gap-2">
                            <p class="text-base font-medium">{{ $nasDevice->radiusServer->name }}</p>
                        </div>
                        <p class="text-sm text-base-content/60 mt-1">{{ $nasDevice->radiusServer->host }}:{{ $nasDevice->radiusServer->auth_port }}</p>
                    @else
                        <p class="text-base text-base-content/60">—</p>
                    @endif
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-base-content/50 mb-1">Effective NAS Identifier</p>
                    <p class="text-base font-medium font-mono">
                        {{ $nasDevice->getEffectiveNasIdentifier() ?? 'Inherited from parent' }}
                    </p>
                    <p class="text-xs text-base-content/60 mt-1">This device uses the parent router's NAS identifier for RADIUS authentication</p>
                </div>
            </div>
        </x-mary-card>

        {{-- Owner Information --}}
        <x-mary-card title="Owner Information" class="bg-base-100">
            <div class="space-y-4">
                @if($nasDevice->user)
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-base-content/50 mb-1">Owner</p>
                    <p class="text-base font-medium">{{ $nasDevice->user->name }}</p>
                    <p class="text-sm text-base-content/60">{{ $nasDevice->user->email }}</p>
                </div>
                @endif

                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-base-content/50 mb-1">Created At</p>
                    <p class="text-base font-medium">{{ $nasDevice->created_at->format('M d, Y h:i A') }}</p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-base-content/50 mb-1">Last Updated</p>
                    <p class="text-base font-medium">{{ $nasDevice->updated_at->format('M d, Y h:i A') }}</p>
                </div>
            </div>
        </x-mary-card>

        {{-- Important Notes --}}
        <x-mary-card title="Important Notes" class="bg-base-100">
            <div class="space-y-3">
                <div class="alert alert-info">
                    <x-mary-icon name="o-information-circle" class="w-5 h-5" />
                    <div>
                        <p class="font-medium text-sm">Authentication Only</p>
                        <p class="text-xs">This NAS device is configured for authentication only. No vouchers will be generated under this device.</p>
                    </div>
                </div>

                <div class="alert alert-warning">
                    <x-mary-icon name="o-exclamation-triangle" class="w-5 h-5" />
                    <div>
                        <p class="font-medium text-sm">Inherited NAS Identifier</p>
                        <p class="text-xs">This device inherits the NAS identifier from its parent router ({{ $nasDevice->parentRouter?->name ?? 'N/A' }}) for RADIUS authentication.</p>
                    </div>
                </div>
            </div>
        </x-mary-card>
    </div>
</section>
