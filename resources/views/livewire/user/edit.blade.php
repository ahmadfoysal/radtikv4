<x-mary-card title="Edit User" separator class="max-w-4xl mx-auto bg-base-100">
    <x-mary-form wire:submit="update">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="col-span-1">
                <x-mary-input label="Name" wire:model.live.debounce.500ms="name" />
            </div>

            <div class="col-span-1">
                <x-mary-input label="Email" wire:model.live.debounce.500ms="email" />
            </div>

            <div class="col-span-1 sm:col-span-2">
                <x-mary-input label="Password" type="password" hint="Leave blank to keep current password"
                    wire:model.live.debounce.500ms="password" />
            </div>

            <div class="col-span-1">
                <x-mary-input label="Phone" wire:model.live.debounce.500ms="phone" />
            </div>

            <div class="col-span-1 sm:col-span-2">
                <x-mary-input label="Address" wire:model.live.debounce.500ms="address" />
            </div>

            <div class="col-span-1 sm:col-span-2">
                <x-mary-input label="Commission (%)" type="number" step="0.01"
                    wire:model.live.debounce.500ms="commission" />
            </div>
        </div>

        <x-slot:actions>
            <x-mary-button label="Cancel" class="btn-ghost" type="button" wire:click="cancel" />
            <x-mary-button label="Update User" class="btn-primary" type="submit" spinner="update" />
        </x-slot:actions>
    </x-mary-form>
</x-mary-card>
