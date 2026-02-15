<x-mary-card title="Edit NAS Device" separator class="max-w-4xl mx-auto bg-base-100">
    <x-mary-form wire:submit="update">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-mary-input label="Name" wire:model.live.debounce.500ms="name" placeholder="Branch Office NAS" />
            </div>

            <div>
                <x-mary-input label="IP / Host" wire:model.live.debounce.500ms="address"
                    placeholder="192.168.0.1 or nas.example.com" />
            </div>

            <div>
                <x-mary-input label="API Port" type="number" min="1" max="65535"
                    wire:model.live.debounce.500ms="port" />
            </div>

            <div>
                <x-mary-input label="Login Address" wire:model.live.debounce.500ms="login_address"
                    placeholder="http://nas.local or 10.0.0.1" />
            </div>

            <div>
                <x-mary-input label="Username" wire:model.live.debounce.500ms="username" />
            </div>

            <div>
                <x-mary-password label="Password" type="password" wire:model.live.debounce.500ms="password" right />
            </div>

            <div class="sm:col-span-2">
                <x-mary-select label="Parent Router" wire:model.live="parent_router_id" :options="$parentRouters"
                    option-label="name" option-value="id" placeholder="Select parent router" />
                <p class="text-xs text-base-content/60 mt-1">This NAS device will inherit the NAS identifier and RADIUS server from the parent router</p>
            </div>

            <div class="sm:col-span-2">
                <x-mary-textarea label="Note" wire:model.live.debounce.500ms="note" 
                    placeholder="Optional notes about this NAS device" rows="3" />
            </div>
        </div>

        <x-slot:actions>
            <x-mary-button label="Cancel" class="btn-ghost" type="button" wire:click="cancel" />
            <x-mary-button label="Update NAS Device" class="btn-primary" type="submit" spinner="update" />
        </x-slot:actions>
    </x-mary-form>
</x-mary-card>
