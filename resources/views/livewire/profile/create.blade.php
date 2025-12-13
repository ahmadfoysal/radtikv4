<x-mary-card title="Add User Profile" separator class="max-w-4xl mx-auto  bg-base-100">
    <x-mary-form wire:submit="save">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-mary-input label="Profile Name" wire:model.live.debounce.500ms="name" placeholder="e.g. 10M-1d" />
            </div>

            <div>
                <x-mary-input label="Price (optional)" type="number" step="0.01" wire:model.live.debounce.500ms="price"
                    placeholder="e.g. 9.99" />
                <p class="mt-1 text-xs opacity-70">
                    Set a price for this profile (e.g. for billing purposes)
                </p>
            </div>

            <div>
                <x-mary-input label="Rate Limit (rx[/tx])" wire:model.live.debounce.500ms="rate_limit"
                    placeholder="e.g. 5M or 5M/10M" />
                <p class="mt-1 text-xs opacity-70">
                    Example: <code>128k</code>, <code>5M</code>, <code>5M/10M</code>
                </p>
            </div>

            <div>
                <x-mary-input label="Shared Users (optional)" type="number" min="1"
                    wire:model.live.debounce.500ms="shared_users" placeholder="e.g. 1" />
                <p class="mt-1 text-xs opacity-70">
                    Number of simultaneous logins allowed for this profile. Default is 1.
                </p>
            </div>

            <div>
                <x-mary-input label="Validity (optional)" wire:model.live.debounce.500ms="validity"
                    placeholder="e.g. 1d, 12h, 30m or 1d12h" />
                <p class="mt-1 ml-1 text-xs opacity-70">
                    Format: <code>1d</code>, <code>12h</code>, <code>30m</code>, or combine like <code>1d12h</code>
                </p>
            </div>

            <div class="flex items-center sm:mt-6">
                <x-mary-toggle label="Enable MAC Binding" description="Bind user to first MAC address"
                    wire:model.live.debounce.500ms="mac_binding" />
            </div>

            <div class="sm:col-span-2">
                <x-mary-textarea label="Description (optional)" rows="3"
                    wire:model.live.debounce.500ms="description" placeholder="Notes about this profile..." />
            </div>
        </div>

        <x-slot:actions>
            <x-mary-button label="Cancel" class="btn-ghost" type="button" wire:click="cancel" />
            <x-mary-button label="Save Profile" class="btn-primary" type="submit" spinner="save" />
        </x-slot:actions>
    </x-mary-form>
</x-mary-card>
