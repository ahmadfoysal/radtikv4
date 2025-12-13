<x-mary-card title="Add Router" separator class="max-w-4xl mx-auto bg-base-100">
    <x-mary-form wire:submit="save">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-mary-input label="Name" wire:model.live.debounce.500ms="name" placeholder="Office Core Router" />
            </div>

            <div>
                <x-mary-input label="IP / Host" wire:model.live.debounce.500ms="address"
                    placeholder="192.168.0.1 or router.example.com" />
            </div>

            <div>
                <x-mary-input label="API Port" type="number" min="1" max="65535"
                    wire:model.live.debounce.500ms="port" />
            </div>

            <div>
                <x-mary-input label="Login Address" wire:model.live.debounce.500ms="login_address"
                    placeholder="http://router.local or 10.0.0.1" />
            </div>

            <div>
                <x-mary-input label="Username" wire:model.live.debounce.500ms="username" />
            </div>

            <div>
                <x-mary-password label="Password" type="password" wire:model.live.debounce.500ms="password" right />
            </div>

            <div class="sm:col-span-2">
                <x-mary-select label="Voucher Template" wire:model.live="voucher_template_id" :options="$voucherTemplates->map(fn($t) => ['id' => $t->id, 'name' => $t->name])->toArray()"
                    option-label="name" option-value="id" placeholder="Select a voucher template" />
            </div>

            <div class="sm:col-span-2">
                <x-mary-select label="Subscription Package" wire:model.live="package_id"
                    :options="$packages->map(fn($p) => ['id' => $p->id, 'name' => $p->name . ' (' . ucfirst($p->billing_cycle) . ')'])->toArray()"
                    option-label="name" option-value="id" placeholder="Select a package (optional)" />
            </div>

            <div class="sm:col-span-2">
                <x-mary-input label="Monthly Expense" type="number" min="0" step="0.01"
                    wire:model.live.debounce.500ms="monthly_expense" placeholder="0.00" />
            </div>
        </div>

        <x-slot:actions>
            <x-mary-button label="Cancel" class="btn-ghost" type="button" wire:click="cancel" />
            <x-mary-button label="Save Router" class="btn-primary" type="submit" spinner="save" />
        </x-slot:actions>
    </x-mary-form>
</x-mary-card>
