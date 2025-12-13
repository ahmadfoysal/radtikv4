<x-mary-card title="Add Package" separator class="max-w-4xl mx-auto  bg-base-100">
    <x-mary-form wire:submit="save">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-mary-input label="Name" wire:model.live.debounce.500ms="name" placeholder="Package Name" />
            </div>

            <div>
                <x-mary-input label="Price Monthly" type="number" step="0.01" min="0"
                    wire:model.live.debounce.500ms="price_monthly" placeholder="0.00" />
            </div>

            <div>
                <x-mary-input label="Price Yearly" type="number" step="0.01" min="0"
                    wire:model.live.debounce.500ms="price_yearly" placeholder="0.00 (optional)" />
            </div>

            <div>
                <x-mary-input label="User Limit" type="number" min="1"
                    wire:model.live.debounce.500ms="user_limit" placeholder="10" />
            </div>

            <div>
                <x-mary-select label="Billing Cycle" wire:model.live="billing_cycle"
                    :options="[
                        ['id' => 'monthly', 'name' => 'Monthly'],
                        ['id' => 'yearly', 'name' => 'Yearly'],
                    ]"
                    option-label="name" option-value="id" />
            </div>

            <div>
                <x-mary-input label="Early Pay Days" type="number" min="0"
                    wire:model.live.debounce.500ms="early_pay_days" placeholder="0 (optional)" />
            </div>

            <div>
                <x-mary-input label="Early Pay Discount (%)" type="number" min="0" max="100"
                    wire:model.live.debounce.500ms="early_pay_discount_percent" placeholder="0 (optional)" />
            </div>

            <div class="sm:col-span-2">
                <x-mary-textarea label="Description" wire:model.live.debounce.500ms="description"
                    placeholder="Package description (optional)" rows="3" />
            </div>

            <div>
                <x-mary-toggle label="Auto Renew Allowed" wire:model.live="auto_renew_allowed" />
            </div>

            <div>
                <x-mary-toggle label="Active" wire:model.live="is_active" />
            </div>
        </div>

        <x-slot:actions>
            <x-mary-button label="Cancel" class="btn-ghost" type="button" wire:click="cancel" />
            <x-mary-button label="Add Package" class="btn-primary" type="submit" spinner="save" />
        </x-slot:actions>
    </x-mary-form>
</x-mary-card>
