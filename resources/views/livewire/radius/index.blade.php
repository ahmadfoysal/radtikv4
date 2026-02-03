<section class="w-full">
    {{-- Header --}}
    <x-mary-card class="mb-4 bg-base-100 border border-base-300 shadow-sm">
        <div class="px-4 py-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2">
                <x-mary-icon name="o-server" class="w-6 h-6 text-primary" />
                <span class="font-semibold text-lg">RADIUS Servers</span>
            </div>

            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
                <x-mary-input placeholder="Search servers..." icon="o-magnifying-glass" class="w-full sm:w-72"
                    input-class="input-sm" wire:model.live.debounce.400ms="q" />

                <div class="flex items-center gap-2 sm:justify-end">
                    <x-mary-button icon="o-plus" label="Add Server" class="btn-sm btn-primary"
                        href="{{ route('radius.create') }}" wire:navigate />
                </div>
            </div>
        </div>
    </x-mary-card>

    {{-- Servers list --}}
    <div class="px-2 sm:px-4">
        <div class="grid grid-cols-1 gap-4">
            @forelse ($servers as $server)
                <div
                    class="group bg-base-100 rounded-xl border border-base-300 overflow-hidden shadow-sm hover:shadow-lg hover:border-primary/30 transition-all duration-300">
                    <div class="p-5">
                        <div class="flex items-start justify-between gap-4">
                            {{-- Server Info --}}
                            <div class="flex items-start gap-4 flex-1 min-w-0">
                                <div class="relative">
                                    <div
                                        class="p-3 bg-primary/10 rounded-lg border border-primary/20 group-hover:scale-105 transition-transform duration-300">
                                        <x-mary-icon name="s-server" class="w-6 h-6 text-primary" />
                                    </div>
                                    @if ($server->is_active)
                                        <span class="absolute -top-1 -right-1 flex h-3 w-3">
                                            <span
                                                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-success opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-3 w-3 bg-success"></span>
                                        </span>
                                    @endif
                                </div>

                                <div class="flex-1 min-w-0">
                                    <h3 class="font-bold text-lg truncate text-base-content group-hover:text-primary transition-colors">
                                        {{ $server->name }}
                                    </h3>
                                    
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-3">
                                        <div class="flex items-center gap-1.5">
                                            <x-mary-icon name="o-globe-alt" class="w-4 h-4 text-base-content/50" />
                                            <span class="text-sm text-base-content/70">{{ $server->host }}</span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <x-mary-icon name="o-signal" class="w-4 h-4 text-base-content/50" />
                                            <span class="text-sm text-base-content/70">Auth: {{ $server->auth_port }} / Acct: {{ $server->acct_port }}</span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <x-mary-icon name="o-clock" class="w-4 h-4 text-base-content/50" />
                                            <span class="text-sm text-base-content/70">Timeout: {{ $server->timeout }}s / Retries: {{ $server->retries }}</span>
                                        </div>
                                        @if ($server->description)
                                            <div class="flex items-center gap-1.5">
                                                <x-mary-icon name="o-information-circle" class="w-4 h-4 text-base-content/50" />
                                                <span class="text-sm text-base-content/70 truncate">{{ $server->description }}</span>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex items-center gap-2 mt-3">
                                        @if ($server->is_active)
                                            <x-mary-badge value="Active" class="badge-success badge-sm" />
                                        @else
                                            <x-mary-badge value="Inactive" class="badge-error badge-sm" />
                                        @endif
                                        
                                        @if ($server->installation_status === 'pending')
                                            <x-mary-badge value="Pending" class="badge-warning badge-sm" />
                                        @elseif ($server->installation_status === 'creating')
                                            <x-mary-badge value="Creating..." class="badge-info badge-sm" />
                                        @elseif ($server->installation_status === 'installing')
                                            <x-mary-badge value="Installing..." class="badge-info badge-sm" />
                                        @elseif ($server->installation_status === 'completed')
                                            <x-mary-badge value="Ready" class="badge-success badge-sm" />
                                        @elseif ($server->installation_status === 'failed')
                                            <x-mary-badge value="Failed" class="badge-error badge-sm" />
                                        @endif
                                        
                                        @if ($server->auto_provision)
                                            <x-mary-badge value="Auto-Provisioned" class="badge-primary badge-sm" />
                                        @endif
                                        
                                        <span class="text-xs text-base-content/50">
                                            Updated {{ $server->updated_at->diffForHumans() }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="flex flex-col gap-2">
                                <x-mary-button icon="o-pencil" tooltip="Edit" class="btn-sm btn-ghost"
                                    href="{{ route('radius.edit', $server->id) }}" wire:navigate />
                                
                                <x-mary-button icon="o-power" 
                                    tooltip="{{ $server->is_active ? 'Deactivate' : 'Activate' }}" 
                                    class="btn-sm btn-ghost {{ $server->is_active ? 'text-warning' : 'text-success' }}"
                                    wire:click="toggleActive({{ $server->id }})" />
                                
                                <x-mary-button icon="o-trash" tooltip="Delete" 
                                    class="btn-sm btn-ghost text-error"
                                    wire:click="confirmDelete({{ $server->id }})" />
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <x-mary-card class="text-center py-8">
                    <div class="flex flex-col items-center gap-3">
                        <x-mary-icon name="o-server" class="w-16 h-16 text-base-content/20" />
                        <div>
                            <p class="text-base-content/70 font-medium">No RADIUS servers found</p>
                            @if ($q)
                                <p class="text-sm text-base-content/50 mt-1">Try adjusting your search</p>
                            @else
                                <p class="text-sm text-base-content/50 mt-1">Add your first RADIUS server to get started</p>
                            @endif
                        </div>
                        @if (!$q)
                            <x-mary-button icon="o-plus" label="Add Server" class="btn-primary btn-sm mt-2"
                                href="{{ route('radius.create') }}" wire:navigate />
                        @endif
                    </div>
                </x-mary-card>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if ($servers->hasPages())
            <div class="mt-6">
                {{ $servers->links() }}
            </div>
        @endif
    </div>

    {{-- Delete Confirmation Modal --}}
    <x-mary-modal wire:model="deletingId" title="Delete RADIUS Server" subtitle="Are you sure you want to delete this server?"
        class="backdrop-blur">
        <div class="text-sm text-base-content/70 mb-4">
            This action cannot be undone. The server configuration will be permanently removed.
        </div>

        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.deletingId = null" />
            <x-mary-button label="Delete" class="btn-error" wire:click="delete" />
        </x-slot:actions>
    </x-mary-modal>
</section>
