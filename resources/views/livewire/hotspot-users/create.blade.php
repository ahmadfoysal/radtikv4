<x-mary-card title="Create Single Hotspot User" separator class="max-w-4xl mx-auto bg-base-100">

    {{-- === Content Area === --}}
    <div class="relative">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

            {{-- Router Selection --}}
            <div class="col-span-1 sm:col-span-2">
                <x-mary-select label="Select Router" icon="o-server" wire:model.live="router_id" 
                    :options="$routers->map(fn($r) => ['id' => $r->id, 'name' => $r->name])->toArray()"
                    option-label="name" option-value="id" placeholder="Choose a router" />
            </div>

            {{-- Profile Selection (loaded dynamically based on router) --}}
            <div class="col-span-1 sm:col-span-2">
                <x-mary-select label="Hotspot Profile (Optional)" icon="o-wifi" 
                    wire:model="profile" 
                    :options="$available_profiles" 
                    option-label="name" 
                    option-value="id"
                    placeholder="Select a profile (optional)" 
                    :disabled="!$router_id || empty($available_profiles)" />
                @if($router_id && empty($available_profiles))
                    <p class="text-xs text-warning mt-1">No profiles found. Please create a profile in the Profile Management section first.</p>
                @endif
            </div>

            {{-- Username --}}
            <x-mary-input label="Username" wire:model="username" placeholder="Enter username" icon="o-user" />

            {{-- Password --}}
            <x-mary-input label="Password" type="password" wire:model="password" placeholder="Enter password" icon="o-key" />

        </div>
    </div>

    <x-slot:actions>
        <x-mary-button label="Cancel" class="btn-ghost" type="button" wire:click="cancel" />
        <x-mary-button class="btn-primary" label="Create User" icon="o-user-plus" wire:click="save" spinner="save" />
    </x-slot:actions>
</x-mary-card>
