<x-mary-card title="Add Router" progress-indicator separator
    class="max-w-2xl mx-auto bg-base-100 shadow-xl hover:shadow-2xl transition-shadow duration-300 rounded-2xl border border-base-300">

    <x-mary-form wire:submit="save">
        <div class="grid md:grid-cols-2 gap-4">
            <x-mary-input label="Name" wire:model="name" />
            <x-mary-input label="IP Address" wire:model="ip" />
            <x-mary-input label="Port" wire:model="port" type="number" />
            <x-mary-input label="Username" wire:model="username" />
            <x-mary-input label="Password" wire:model="password" type="password" />
            <x-mary-input label="Location" wire:model="location" class="md:col-span-2" />
        </div>

        <x-mary-textarea label="Notes" wire:model="notes" rows="1"
            placeholder="Short note about this router..." />

        <x-slot:actions>
            <x-mary-button label="Cancel" class="btn-ghost" type="button" wire:click="cancel" />
            <x-mary-button label="Save Router" class="btn-primary" type="submit" spinner="save" />
        </x-slot:actions>
    </x-mary-form>
</x-mary-card>
