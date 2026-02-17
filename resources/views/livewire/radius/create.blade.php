<x-mary-card title="Add RADIUS Server" separator class="max-w-4xl mx-auto bg-base-100">
    <x-slot:menu>
        <x-mary-button label="Setup Guide" icon="o-book-open" link="/radius/setup-guide" wire:navigate class="btn-ghost btn-sm" />
    </x-slot:menu>

    <x-mary-form wire:submit="save">
        {{-- Server Configuration --}}
        <div class="mb-6">
            <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                <x-mary-icon name="o-server" class="w-5 h-5 text-primary" />
                Server Configuration
            </h3>
            <div class="alert alert-info mb-4">
                <x-mary-icon name="o-information-circle" class="w-5 h-5" />
                <span class="text-sm">Shared secret and API token will be auto-generated and configured automatically.</span>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-mary-input label="Host / IP Address" wire:model.live.debounce.500ms="host"
                        placeholder="192.168.1.10 or radius.example.com" 
                        hint="Hostname or IP address of your RADIUS server" />
                </div>
            </div>
        </div>

        {{-- SSH Configuration --}}
        <div class="mb-6">
            <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                <x-mary-icon name="o-command-line" class="w-5 h-5 text-primary" />
                SSH Configuration
            </h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-mary-input label="SSH Username" wire:model.live.debounce.500ms="ssh_username"
                        placeholder="root" />
                </div>

                <div>
                    <x-mary-input label="SSH Port" type="number" min="1" max="65535"
                        wire:model.live.debounce.500ms="ssh_port" />
                </div>

                <div class="sm:col-span-2">
                    <x-mary-password label="SSH Password" wire:model.live.debounce.500ms="ssh_password"
                        placeholder="Enter SSH password" right />
                </div>
            </div>
        </div>

        {{-- RADIUS Configuration --}}
        <div class="mb-6">
            <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                <x-mary-icon name="o-adjustments-horizontal" class="w-5 h-5 text-primary" />
                RADIUS Configuration
            </h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-mary-input label="Authentication Port" type="number" min="1" max="65535"
                        wire:model.live.debounce.500ms="auth_port" 
                        hint="Default: 1812" />
                </div>

                <div>
                    <x-mary-input label="Accounting Port" type="number" min="1" max="65535"
                        wire:model.live.debounce.500ms="acct_port"
                        hint="Default: 1813" />
                </div>

                <div>
                    <x-mary-input label="Timeout (seconds)" type="number" min="1" max="60"
                        wire:model.live.debounce.500ms="timeout" 
                        hint="Connection timeout" />
                </div>

                <div>
                    <x-mary-input label="Retries" type="number" min="1" max="10"
                        wire:model.live.debounce.500ms="retries"
                        hint="Number of connection attempts" />
                </div>
            </div>
        </div>

        <x-slot:actions>
            <x-mary-button label="Cancel" class="btn-ghost" type="button" 
                href="{{ route('radius.index') }}" wire:navigate />
            <x-mary-button label="Create Server" class="btn-primary" type="submit" spinner="save" 
                icon="o-plus" />
        </x-slot:actions>
    </x-mary-form>
</x-mary-card>
