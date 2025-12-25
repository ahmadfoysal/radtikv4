<x-mary-card title="Manual Balance Adjustment" separator class="max-w-4xl mx-auto bg-base-100">
    <x-mary-form wire:submit="submit">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-mary-choices label="Admin User" placeholder="Search admin..." wire:model.live="adminId"
                    :options="$adminOptions" single searchable clearable option-label="name" option-sub-label="email"
                    option-value="id" />
            </div>

            <div>
                <x-mary-select label="Transaction Type" wire:model.live="action" :options="[
                    ['id' => 'credit', 'name' => 'Add Balance (Credit)'],
                    ['id' => 'debit', 'name' => 'Deduct Balance (Debit)'],
                    ['id' => 'adjust', 'name' => 'Adjust to Exact Amount'],
                ]" option-label="name"
                    option-value="id" />
            </div>

            <div>
                <x-mary-input label="Amount" type="number" min="0" step="0.01"
                    wire:model.live.debounce.500ms="amount" />
            </div>

            <div>
                <x-mary-input label="Category" wire:model.live.debounce.500ms="category"
                    placeholder="manual_adjustment" />
            </div>

            <div class="sm:col-span-2">
                <x-mary-textarea label="Description (optional)" rows="3"
                    wire:model.live.debounce.500ms="description" placeholder="Add any internal notes" />
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class=" border border-base-300 bg-base-100/80 p-4">
                <p class="text-sm text-base-content/70">Instructions</p>
                @switch($action)
                    @case('credit')
                        <p class="mt-1 text-base-content">Adds the entered amount to the selected admin’s balance and
                            logs an invoice entry.</p>
                    @break

                    @case('debit')
                        <p class="mt-1 text-base-content">Removes the entered amount from the admin’s balance if enough
                            funds are available.</p>
                    @break

                    @case('adjust')
                        <p class="mt-1 text-base-content">Sets the admin’s balance to the exact amount entered by
                            issuing the necessary credit or debit.</p>
                    @break
                @endswitch
            </div>

            @if (!is_null($currentBalance))
                <div class=" border border-base-300 bg-base-100/80 p-4">
                    <p class="text-sm text-base-content/70">Current Balance</p>
                    <p class="mt-1 text-3xl font-semibold">৳{{ number_format($currentBalance, 2) }}</p>
                </div>
            @endif
        </div>

        <x-slot:actions>
            <x-mary-button label="Reset" class="btn-ghost" type="button" wire:click="resetForm" />
            <x-mary-button label="Update Balance" class="btn-primary" type="submit" spinner="submit" />
        </x-slot:actions>
    </x-mary-form>
</x-mary-card>
