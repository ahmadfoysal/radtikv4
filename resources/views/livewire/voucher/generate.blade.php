<x-mary-card title="Generate Vouchers" separator class="max-w-4xl mx-auto rounded-2xl bg-base-200">
    <x-mary-tabs wire:model="tab" class="mb-4">
        {{-- === MikroTik TAB === --}}
        <x-mary-tab name="mikrotik" label="MikroTik" icon="o-server">
            <div class="relative">
                {{-- === Overlay Loading Spinner === --}}
                <div wire:loading.flex wire:target="router_id"
                    class="absolute inset-0 bg-base-200/80 flex flex-col items-center justify-center z-20 rounded-xl">
                    <span class="loading loading-spinner loading-xl text-primary"></span>

                    <p class="mt-2 text-sm text-base-content/70">Loading profiles...</p>
                </div>

                {{-- === Actual Content === --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-3">
                    <x-mary-select label="Select Router" wire:model.live="router_id" :options="$routers->map(fn($r) => ['id' => $r->id, 'name' => $r->name])->toArray()"
                        option-label="name" option-value="id" />

                    <x-mary-select label="MikroTik Profile" wire:model="mikrotik_profile" :options="$mikrotik_profiles"
                        option-label="name" option-value="id" placeholder="Select MikroTik profile" :key="'mk-profile-' . $router_id . '-' . count($mikrotik_profiles)" />

                    <x-mary-input type="number" label="Quantity" wire:model="quantity" min="1" />

                    <x-mary-select label="Length" wire:model="length" :options="[
                        ['id' => 8, 'name' => '8 Characters'],
                        ['id' => 9, 'name' => '9 Characters'],
                        ['id' => 10, 'name' => '10 Characters'],
                        ['id' => 11, 'name' => '11 Characters'],
                        ['id' => 12, 'name' => '12 Characters'],
                    ]" option-label="name"
                        option-value="id" placeholder="Select length" searchable />

                    <x-mary-input label="Prefix (optional)" wire:model="prefix" placeholder="e.g. RM-" />

                    <x-mary-input type="number" label="Serial Start (optional)" wire:model="serial_start"
                        placeholder="e.g. 1001" />

                    <x-mary-select label="Character Type" wire:model="char_type" :options="[
                        ['id' => 'letters_upper', 'name' => 'Random ABCDEF'],
                        ['id' => 'letters_lower', 'name' => 'Random abcdef'],
                        ['id' => 'letters_mixed', 'name' => 'Random aBcDeF'],
                        ['id' => 'alnum_lower', 'name' => 'Random 5ab2c34d'],
                        ['id' => 'alnum_upper', 'name' => 'Random 5AB2C34D'],
                        ['id' => 'alnum_mixed', 'name' => 'Random 5aB2c34D'],
                        ['id' => 'numeric', 'name' => 'Random 0123456789'],
                    ]" option-label="name"
                        option-value="id" />
                </div>
            </div>
        </x-mary-tab>


        {{-- === RADIUS TAB === --}}
        <x-mary-tab name="radius" label="RADIUS" icon="o-bolt">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-3">
                <x-mary-select label="Select Router" placeholder="Choose router" :options="$routers
                    ->map(fn($r) => ['id' => $r->id, 'name' => $r->name . ' (' . $r->address . ')'])
                    ->toArray()" option-label="name"
                    option-value="id" wire:model="router_id" />
                <x-mary-select label="RADIUS Profile" wire:model="radius_profile_id" :options="$radiusProfiles"
                    option-label="name" option-value="id" placeholder="Select RADIUS Profile" />

                <x-mary-input type="number" label="Quantity" wire:model="quantity" min="1" />
                <x-mary-select label="Length" wire:model="length" :options="[
                    ['id' => 8, 'name' => '8 Characters'],
                    ['id' => 9, 'name' => '9 Characters'],
                    ['id' => 10, 'name' => '10 Characters'],
                    ['id' => 11, 'name' => '11 Characters'],
                    ['id' => 12, 'name' => '12 Characters'],
                ]" option-label="name"
                    option-value="id" placeholder="Select length" searchable />

                <x-mary-input label="Prefix (optional)" wire:model="prefix" placeholder="e.g. RD-" />
                <x-mary-input type="number" label="Serial Start (optional)" wire:model="serial_start"
                    placeholder="e.g. 1001" />

                <x-mary-select label="Character Type" wire:model="char_type" :options="[
                    ['id' => 'letters_upper', 'name' => 'Random ABCDEF'],
                    ['id' => 'letters_lower', 'name' => 'Random abcdef'],
                    ['id' => 'letters_mixed', 'name' => 'Random aBcDeF'],
                    ['id' => 'alnum_lower', 'name' => 'Random 5ab2c34d'],
                    ['id' => 'alnum_upper', 'name' => 'Random 5AB2C34D'],
                    ['id' => 'alnum_mixed', 'name' => 'Random 5aB2c34D'],
                    ['id' => 'numeric', 'name' => 'Random 0123456789'],
                ]" option-label="name"
                    option-value="id" />


            </div>
        </x-mary-tab>
    </x-mary-tabs>

    <x-slot:actions>
        <x-mary-button label="Cancel" class="btn-ghost" type="button" wire:click="cancel" />
        <x-mary-button class="btn-primary" label="Generate" wire:click="save" spinner="save" />
    </x-slot:actions>
</x-mary-card>
