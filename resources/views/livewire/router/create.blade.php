<x-mary-card title="Add Router" progress-indicator separator
    class="max-w-2xl mx-auto bg-base-100 shadow-xl hover:shadow-2xl transition-shadow duration-300 rounded-2xl border border-base-300">

    <x-mary-form wire:submit="save">
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <x-mary-input label="Name" wire:model.live.debounce.500ms="name" placeholder="Office Core Router" />

            </div>

            <div>
                <x-mary-input label="IP / Host" wire:model.live.debounce.500ms="address"
                    placeholder="192.168.0.1 or router.example.com" />

            </div>

            <div>
                <x-mary-input label="API Port" type="number" min="1" max="65535"
                    wire:model.live.debounce.500ms="port" />

            </div>

            <div>
                <x-mary-input label="Username" wire:model.live.debounce.500ms="username" />

            </div>

            <div class="md:col-span-2">
                <x-mary-password label="Password" type="password" wire:model.live.debounce.500ms="password" right />

            </div>

            <div class="md:col-span-2">
                <x-mary-textarea label="Note" rows="2" wire:model.live.debounce.500ms="note"
                    placeholder="Short note about this router..." />
            </div>
        </div>
        <x-slot:actions>
            <x-mary-button label="Cancel" class="btn-ghost" type="button" wire:click="cancel" />
            <x-mary-button label="Save Router" class="btn-primary" type="submit" spinner="save" />
        </x-slot:actions>
    </x-mary-form>
</x-mary-card>
