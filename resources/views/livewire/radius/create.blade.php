<x-mary-card title="Add RADIUS Server" separator class="max-w-4xl mx-auto bg-base-100">
    <x-mary-form wire:submit="save">
        {{-- Basic Information --}}
        <div class="mb-6">
            <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                <x-mary-icon name="o-information-circle" class="w-5 h-5 text-primary" />
                Basic Information
            </h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-mary-input label="Server Name" wire:model.live.debounce.500ms="name" 
                        placeholder="Primary RADIUS Server" 
                        hint="A friendly name to identify this server" />
                </div>

                <div class="sm:col-span-2">
                    <x-mary-checkbox label="Auto-Provision on Linode" wire:model.live="auto_provision" 
                        hint="Automatically create a Linode instance and install FreeRADIUS" />
                </div>

                @if(!$auto_provision)
                    <div>
                        <x-mary-input label="Host / IP Address" wire:model.live.debounce.500ms="host"
                            placeholder="192.168.1.10 or radius.example.com" 
                            hint="Hostname or IP address of existing RADIUS server" />
                    </div>
                @endif

                <div>
                    <x-mary-password label="Shared Secret" wire:model.live.debounce.500ms="secret"
                        placeholder="Enter shared secret" 
                        hint="Min 8 characters" right />
                </div>

                <div>
                    <x-mary-textarea label="Description (Optional)" wire:model.live.debounce.500ms="description"
                        placeholder="Notes about this RADIUS server..."
                        rows="2" />
                </div>
            </div>
        </div>

        {{-- Linode Configuration (only if auto_provision is true) --}}
        @if($auto_provision)
            <div class="mb-6 p-4 bg-primary/5 rounded-lg border border-primary/20">
                <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                    <x-mary-icon name="o-cloud" class="w-5 h-5 text-primary" />
                    Linode Configuration
                </h3>
                <div class="alert alert-info">
                    <x-mary-icon name="o-information-circle" class="w-5 h-5" />
                    <span>Server will be provisioned in <strong>Asia Pacific (Singapore)</strong> with <strong>1GB Shared ($5/month)</strong> running <strong>Ubuntu 22.04 LTS</strong></span>
                </div>
            </div>
        @endif

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
                    <x-mary-password label="SSH Password (Optional)" wire:model.live.debounce.500ms="ssh_password"
                        placeholder="Leave empty if using SSH key" 
                        hint="Use password or SSH key authentication" right />
                </div>

                <div class="sm:col-span-2">
                    <x-mary-textarea label="SSH Private Key (Optional)" wire:model.live.debounce.500ms="ssh_private_key"
                        placeholder="-----BEGIN OPENSSH PRIVATE KEY-----"
                        hint="Paste your private key here for key-based authentication"
                        rows="4" />
                </div>
            </div>
        </div>

        {{-- RADIUS Configuration --}}
        <div class="mb-6">
            <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                <x-mary-icon name="o-server" class="w-5 h-5 text-primary" />
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

                <div class="sm:col-span-2">
                    <x-mary-checkbox label="Active" wire:model.live="is_active" 
                        hint="Enable or disable this RADIUS server" />
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
