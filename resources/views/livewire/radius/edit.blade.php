<x-mary-card title="Edit RADIUS Server" separator class="max-w-4xl mx-auto bg-base-100">
    <x-mary-form wire:submit="save">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-mary-input label="Server Name" wire:model.live.debounce.500ms="name" 
                    placeholder="Primary RADIUS Server" 
                    hint="A friendly name to identify this server" />
            </div>

            <div>
                <x-mary-input label="Host / IP Address" wire:model.live.debounce.500ms="host"
                    placeholder="192.168.1.10 or radius.example.com" 
                    hint="Hostname or IP address of the RADIUS server" />
            </div>

            <div>
                <x-mary-password label="Shared Secret" wire:model.live.debounce.500ms="secret"
                    placeholder="Enter shared secret" 
                    hint="RADIUS secret from clients.conf" right />
            </div>
            
            <div class="sm:col-span-2">
                <x-mary-password label="API Authentication Token" wire:model.live.debounce.500ms="auth_token"
                    placeholder="Enter API token" 
                    hint="API token from config.ini (min 32 characters)" right />
            </div>

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
                    hint="Connection timeout in seconds" />
            </div>

            <div>
                <x-mary-input label="Retries" type="number" min="1" max="10"
                    wire:model.live.debounce.500ms="retries"
                    hint="Number of connection attempts" />
            </div>

            <div class="sm:col-span-2">
                <x-mary-textarea label="Description (Optional)" wire:model.live.debounce.500ms="description"
                    placeholder="Notes about this RADIUS server..."
                    rows="3" />
            </div>

            <div class="sm:col-span-2">
                <x-mary-checkbox label="Active" wire:model.live="is_active" 
                    hint="Enable or disable this RADIUS server" />
            </div>
        </div>

        <x-slot:actions>
            <x-mary-button label="Cancel" class="btn-ghost" type="button" 
                href="{{ route('radius.index') }}" wire:navigate />
            <x-mary-button label="Update Server" class="btn-primary" type="submit" spinner="save" />
        </x-slot:actions>
    </x-mary-form>
</x-mary-card>
