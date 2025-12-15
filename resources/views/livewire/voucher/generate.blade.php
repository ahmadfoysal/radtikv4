<x-mary-card title="Generate Vouchers" separator class="max-w-4xl mx-auto bg-base-100">

    {{-- === Content Area with Loading State === --}}
    <div class="relative">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

            {{-- Router Selection --}}
            <div class="col-span-1 sm:col-span-2">
                <x-mary-select label="Select Router" icon="o-server" wire:model.live="router_id" :options="$routers->map(fn($r) => ['id' => $r['id'], 'name' => $r['name']])->toArray()"
                    option-label="name" option-value="id" placeholder="Choose a router to assign vouchers" />
            </div>

            {{-- Profile Selection (Dynamic based on Type) --}}
            <div class="col-span-1 sm:col-span-2">
                <x-mary-select label="Profile"
                    icon="o-wifi" wire:model="profile_id" :options="$available_profiles" option-label="name" option-value="id"
                    placeholder="Select a profile" />
            </div>

            {{-- Quantity --}}
            <x-mary-input type="number" label="Quantity" wire:model="quantity" min="1" icon="o-hashtag" />

            {{-- Length --}}
            <x-mary-select label="Length" wire:model="length" icon="o-arrows-right-left" :options="[
                ['id' => 6, 'name' => '6 Characters'],
                ['id' => 8, 'name' => '8 Characters'],
                ['id' => 9, 'name' => '9 Characters'],
                ['id' => 10, 'name' => '10 Characters'],
                ['id' => 11, 'name' => '11 Characters'],
                ['id' => 12, 'name' => '12 Characters'],
            ]"
                option-label="name" option-value="id" />

            {{-- Prefix --}}
            <x-mary-input label="Prefix (Optional)" wire:model="prefix" placeholder="e.g. WF-" icon="o-tag" />

            {{-- Serial Start --}}
            <x-mary-input type="number" label="Serial Start (Optional)" wire:model="serial_start"
                placeholder="e.g. 1001" icon="o-list-bullet" />

            {{-- Character Type --}}
            <div class="col-span-1 sm:col-span-2">
                <x-mary-select label="Character Type" wire:model="char_type" icon="o-language" :options="[
                    ['id' => 'numeric', 'name' => 'Numeric (0-9)'],
                    ['id' => 'letters_upper', 'name' => 'Uppercase (A-Z)'],
                    ['id' => 'letters_lower', 'name' => 'Lowercase (a-z)'],
                    ['id' => 'letters_mixed', 'name' => 'Mixed Letters (a-Z)'],
                    ['id' => 'alnum_lower', 'name' => 'Alphanumeric Lower (a-z, 0-9)'],
                    ['id' => 'alnum_upper', 'name' => 'Alphanumeric Upper (A-Z, 0-9)'],
                    ['id' => 'alnum_mixed', 'name' => 'Alphanumeric Mixed (a-Z, 0-9)'],
                ]"
                    option-label="name" option-value="id" />
            </div>

        </div>
    </div>

    <x-slot:actions>
        <x-mary-button label="Cancel" class="btn-ghost" type="button" wire:click="cancel" />
        <x-mary-button class="btn-primary" label="Generate Vouchers" icon="o-bolt" wire:click="save" spinner="save" />
    </x-slot:actions>
</x-mary-card>
