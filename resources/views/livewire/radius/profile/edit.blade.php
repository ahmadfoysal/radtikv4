<x-mary-card title="Edit RADIUS Profile" progress-indicator separator
    class="max-w-2xl mx-auto bg-base-100 shadow-xl hover:shadow-2xl transition-shadow duration-300 rounded-2xl border border-base-300">
    <x-mary-form wire:submit="save">
        <div class="grid md:grid-cols-2 gap-4">

            {{-- Profile Name --}}
            <div>
                <x-mary-input label="Profile Name" wire:model.live.debounce.500ms="name" placeholder="e.g. 10M-1d" />
            </div>

            {{-- Rate Limit --}}
            <div>
                <x-mary-input label="Rate Limit (rx[/tx])" wire:model.live.debounce.500ms="rate_limit"
                    placeholder="e.g. 5M or 5M/10M" />
                <p class="text-xs opacity-70 mt-1">
                    Example: <code>128k</code>, <code>5M</code>, <code>5M/10M</code>
                </p>
            </div>

            {{-- Validity --}}
            <div>
                <x-mary-input label="Validity (optional)" wire:model.live.debounce.500ms="validity"
                    placeholder="e.g. 1d, 12h, 30m, or 1d12h" />
                <p class="text-xs opacity-70 mt-1">
                    Example: <code>1d</code>, <code>12h</code>, <code>30m</code>, <code>1d12h</code>
                </p>
            </div>

            {{-- MAC Binding --}}
            <div class="flex items-center md:mt-6">
                <x-mary-toggle label="Enable MAC Binding" description="Bind user to first MAC address"
                    wire:model.live.debounce.500ms="mac_binding" />
            </div>

            {{-- Description --}}
            <div class="md:col-span-2">
                <x-mary-textarea label="Description (optional)" rows="3"
                    wire:model.live.debounce.500ms="description" placeholder="Notes about this profile..." />
            </div>

        </div>

        <x-slot:actions>
            <x-mary-button label="Cancel" class="btn-ghost" type="button" wire:click="cancel" />
            <x-mary-button label="Update Profile" class="btn-primary" type="submit" spinner="save" />
        </x-slot:actions>
    </x-mary-form>
</x-mary-card>
