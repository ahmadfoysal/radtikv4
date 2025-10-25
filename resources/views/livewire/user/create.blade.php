<x-mary-card title="Add Router" progress-indicator separator
    class="max-w-2xl mx-auto bg-base-100 shadow-xl hover:shadow-2xl transition-shadow duration-300 rounded-2xl border border-base-300">

    <x-mary-form wire:submit="save">

        <div class="w-full md:w-auto">
            <x-mary-input label="Name" wire:model.live.debounce.500ms="name" />
        </div>
        <div class="w-full md:w-auto">
            <x-mary-input label="Email" wire:model.live.debounce.500ms="email" />
        </div>
        <div class="w-full md:w-auto">
            <x-mary-input label="Password" type="password" wire:model.live.debounce.500ms="password" />
        </div>
        <div class="w-full md:w-auto">
            <x-mary-input label="Phone" wire:model.live.debounce.500ms="phone" />
        </div>
        <div class="w-full md:w-auto">
            <x-mary-input label="Address" wire:model.live.debounce.500ms="address" />
        </div>
        <x-slot:actions>
            <x-mary-button label="Cancel" class="btn-ghost" type="button" wire:click="cancel" />
            <x-mary-button label="Add User" class="btn-primary" type="submit" spinner="save" />
        </x-slot:actions>
    </x-mary-form>

</x-mary-card>
