<x-mary-card title="Edit Voucher" separator class="max-w-4xl mx-auto bg-base-100">
    <x-mary-form wire:submit="update">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            {{-- Username --}}
            <div class="sm:col-span-2">
                <x-mary-input label="Username" wire:model="username" placeholder="Enter username" icon="o-user" />
            </div>

            {{-- Password --}}
            <div class="sm:col-span-2">
                <x-mary-input label="Password" type="password" wire:model="password" placeholder="Enter password" icon="o-key" />
            </div>

            {{-- Profile Selection --}}
            <div class="sm:col-span-2">
                <x-mary-select label="Hotspot Profile (Optional)" icon="o-wifi" 
                    wire:model="user_profile_id" 
                    :options="$available_profiles" 
                    option-label="name" 
                    option-value="id"
                    placeholder="Select a profile (optional)" />
                @if(empty($available_profiles))
                    <p class="text-xs text-warning mt-1">No profiles found. Please create a profile in the Profile Management section first.</p>
                @endif
            </div>
        </div>

        <x-slot:actions>
            <x-mary-button label="Cancel" class="btn-ghost" type="button" wire:click="cancel" />
            <x-mary-button class="btn-primary" label="Update Voucher" icon="o-check" wire:click="update" spinner="update" />
        </x-slot:actions>
    </x-mary-form>
</x-mary-card>
