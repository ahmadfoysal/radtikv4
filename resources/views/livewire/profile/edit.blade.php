<x-mary-card title="Edit User Profile" progress-indicator separator
    class="max-w-2xl mx-auto bg-base-100 shadow-xl hover:shadow-2xl transition-shadow duration-300 rounded-2xl border border-base-300">

    <x-mary-form wire:submit="save">
        <div class="grid md:grid-cols-2 gap-4">

            <div>
                <x-mary-input label="Profile Name" wire:model.live.debounce.400ms="name" placeholder="e.g. 10M-1d" />
            </div>

            <div>
                <x-mary-input label="Rate Limit (optional)" wire:model.live.debounce.400ms="rate_limit"
                    placeholder="e.g. 5M/10M" />
            </div>

            <div>
                <x-mary-input label="Shared Users (optional)" type="number" min="1"
                    wire:model.live.debounce.500ms="shared_users" placeholder="e.g. 1" />
                <p class="text-xs opacity-70 mt-1">
                    Number of simultaneous logins allowed for this profile. Default is 1.
                </p>
            </div>

            <div>
                <x-mary-input label="Validity (optional)" wire:model.live.debounce.400ms="validity"
                    placeholder="e.g. 1d12h or 30d" />
            </div>

            <div>
                <x-mary-input label="Price" type="number" min="0" step="0.01"
                    wire:model.live.debounce.400ms="price" />
            </div>

            <div class="md:col-span-2 flex items-center gap-3">
                <x-mary-toggle label="Bind MAC on first login" wire:model.live="mac_binding" />
            </div>

            <div class="md:col-span-2">
                <x-mary-textarea label="Description (optional)" rows="2"
                    wire:model.live.debounce.400ms="description" placeholder="Short note about this profile..." />
            </div>
        </div>

        <x-slot:actions>
            <x-mary-button label="Cancel" class="btn-ghost" type="button" wire:click="cancel" />
            <x-mary-button label="Update Profile" class="btn-primary" type="submit" spinner="save" />
        </x-slot:actions>
    </x-mary-form>
</x-mary-card>
