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
                <x-mary-input label="Max Routers" type="number" min="1"
                    wire:model.live.debounce.500ms="max_routers" placeholder="10"
                    hint="Maximum routers admin can manage" />
            </div>

            <div>
                <x-mary-input label="Max Users Per Router" type="number" min="1"
                    wire:model.live.debounce.500ms="max_users" placeholder="100" hint="Maximum users per router" />
            </div>

            <div>
                <x-mary-input label="Max Zones" type="number" min="0" wire:model.live.debounce.500ms="max_zones"
                    placeholder="0 (optional)" />
            </div>

            <div>
                <x-mary-input label="Max Vouchers Per Router" type="number" min="0"
                    wire:model.live.debounce.500ms="max_vouchers_per_router" placeholder="0 (optional)" />
            </div>

            <div>
                <x-mary-input label="Grace Period (Days)" type="number" min="1" max="30"
                    wire:model.live.debounce.500ms="grace_period_days" placeholder="3"
                    hint="Days after expiry before suspension" />
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
                <x-mary-toggle label="Featured Package" wire:model.live="is_featured"
                    hint="Highlight this package in pricing page" />
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
