<div class="max-w-7xl mx-auto">
    <x-mary-card title="My Invoices" class="rounded-xl shadow-sm border border-base-300">
        {{-- <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between mb-6">
            <x-mary-input placeholder="Search category, description or type" icon="o-magnifying-glass"
                wire:model.live.debounce.350ms="search" class="w-full md:w-1/2" />

            <div class="flex flex-col w-full gap-3 md:w-auto md:flex-row md:items-end">
                <x-mary-select label="Type" wire:model.live="type" class="w-full md:w-48" :options="[
                    ['id' => 'all', 'name' => 'All Types'],
                    ['id' => 'credit', 'name' => 'Credits'],
                    ['id' => 'debit', 'name' => 'Debits'],
                ]" option-label="name" option-value="id" />

                <x-mary-select label="Per Page" wire:model.live="perPage" class="w-full md:w-36" :options="[
                    ['id' => 10, 'name' => '10'],
                    ['id' => 15, 'name' => '15'],
                    ['id' => 25, 'name' => '25'],
                    ['id' => 50, 'name' => '50'],
                ]" option-label="name" option-value="id" />
            </div>
        </div> --}}


        <div class="flex flex-row items-end justify-between gap-4 mb-6">

            {{-- Search Input --}}
            <x-mary-input placeholder="Search category, description or type" icon="o-magnifying-glass"
                wire:model.live.debounce.350ms="search" class="w-full max-w-md" />

            {{-- Button Group for Type --}}
            <div class="flex flex-row items-end gap-3">

                <div class="btn-group">
                    <button wire:click="$set('type', 'all')"
                        class="btn btn-sm {{ $type === 'all' ? 'btn-primary' : 'btn-outline' }}">
                        All
                    </button>

                    <button wire:click="$set('type', 'credit')"
                        class="btn btn-sm {{ $type === 'credit' ? 'btn-success' : 'btn-outline' }}">
                        Credit
                    </button>

                    <button wire:click="$set('type', 'debit')"
                        class="btn btn-sm {{ $type === 'debit' ? 'btn-error' : 'btn-outline' }}">
                        Debit
                    </button>
                </div>

            </div>

        </div>


        <div class="overflow-x-auto">
            <table class="min-w-full text-sm border border-base-200 rounded-lg overflow-hidden">
                <thead>
                    <tr class="bg-base-200 text-base-content/80">
                        <th class="px-4 py-3 text-left cursor-pointer" wire:click="sortBy('created_at')">
                            <div class="flex items-center gap-2">
                                Date
                                <x-mary-icon :name="$this->getSortIcon('created_at')" class="w-4 h-4" />
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left cursor-pointer" wire:click="sortBy('category')">
                            <div class="flex items-center gap-2">
                                Category
                                <x-mary-icon :name="$this->getSortIcon('category')" class="w-4 h-4" />
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left">Type</th>
                        <th class="px-4 py-3 text-right cursor-pointer" wire:click="sortBy('amount')">
                            <div class="flex items-center gap-2 justify-end">
                                Amount (BDT)
                                <x-mary-icon :name="$this->getSortIcon('amount')" class="w-4 h-4" />
                            </div>
                        </th>
                        <th class="px-4 py-3 text-right cursor-pointer" wire:click="sortBy('balance_after')">
                            <div class="flex items-center gap-2 justify-end">
                                Balance After
                                <x-mary-icon :name="$this->getSortIcon('balance_after')" class="w-4 h-4" />
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left">Router</th>
                        <th class="px-4 py-3 text-left">Description</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($invoices as $invoice)
                        <tr class="border-t border-base-200 hover:bg-base-200/60">
                            <td class="px-4 py-3">
                                <div class="font-semibold">
                                    {{ $invoice->created_at?->format('d M, Y') }}
                                </div>
                                <div class="text-xs text-base-content/70">
                                    {{ $invoice->created_at?->format('h:i A') }}
                                </div>
                            </td>
                            <td class="px-4 py-3 font-medium">{{ ucfirst($invoice->category) }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $badgeClass = $invoice->type === 'credit' ? 'badge-success' : 'badge-error';
                                @endphp
                                <x-mary-badge class="{{ $badgeClass }}">{{ ucfirst($invoice->type) }}</x-mary-badge>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold">
                                ৳{{ number_format((float) $invoice->amount, 2) }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                ৳{{ number_format((float) $invoice->balance_after, 2) }}
                            </td>
                            <td class="px-4 py-3">
                                {{ $invoice->router?->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="line-clamp-2 text-sm text-base-content/80">
                                    {{ $invoice->description ?? '—' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-base-content/70">
                                No invoices found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $invoices->links() }}
        </div>
    </x-mary-card>
</div>
