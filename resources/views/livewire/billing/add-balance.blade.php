<x-mary-card title="Add Balance" separator class="max-w-4xl mx-auto bg-base-100">
    <x-mary-form wire:submit="submit">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            
            <div class="sm:col-span-2">
                <x-mary-input 
                    label="Amount" 
                    type="number" 
                    min="1" 
                    step="0.01"
                    wire:model.live.debounce.500ms="amount" 
                    placeholder="Enter amount"
                    icon="o-currency-dollar"
                    hint="Minimum amount: 1.00"
                />
            </div>

            <div class="sm:col-span-2">
                <x-mary-select 
                    label="Payment Gateway" 
                    wire:model.live="payment_gateway_id" 
                    :options="$gateways"
                    option-label="name" 
                    option-value="id" 
                    placeholder="Select a payment gateway"
                    icon="o-credit-card"
                />
            </div>

        </div>

        <div class="mt-6 grid grid-cols-1 gap-4">
            <div class=" border border-base-300 bg-base-100/80 p-4">
                <p class="text-sm text-base-content/70">Instructions</p>
                <p class="mt-1 text-base-content">
                    Enter the amount you want to add to your balance and select a payment gateway. 
                    You will be redirected to the payment page to complete the transaction.
                </p>
                @if($amount)
                    <p class="mt-2 text-base-content font-semibold">
                        Amount to pay: {{ number_format($amount, 2) }}
                    </p>
                @endif
            </div>
        </div>

        <x-slot:actions>
            <x-mary-button label="Proceed to Payment" type="submit" icon="o-arrow-right" class="btn-primary" 
                spinner="submit" />
        </x-slot:actions>
    </x-mary-form>
</x-mary-card>
