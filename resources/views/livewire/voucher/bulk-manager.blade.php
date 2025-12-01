<x-mary-card title="Bulk Operations" separator class="max-w-6xl mx-auto rounded-2xl bg-base-100 shadow-lg">

    {{-- === FILTERS SECTION === --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

        <x-mary-select label="Select Router" icon="o-server" wire:model.live="router_id" :options="$routers"
            option-label="name" option-value="id" placeholder="Filter by Router" />

        <x-mary-select label="Select Batch" icon="o-archive-box" wire:model.live="batch" :options="$batches"
            option-label="name" option-value="id" placeholder="All Batches" :disabled="empty($batches)" />

        <x-mary-select label="Status" icon="o-funnel" wire:model.live="status" :options="[
            ['id' => 'inactive', 'name' => 'Inactive (Unused)'],
            ['id' => 'active', 'name' => 'Active (Used)'],
            ['id' => 'expired', 'name' => 'Expired'],
            ['id' => 'all', 'name' => 'All Status'],
        ]" />
    </div>

    {{-- === ACTIONS HEADER === --}}
    <div class="flex flex-col sm:flex-row justify-between items-center bg-base-200 p-4 rounded-xl mb-4 gap-4">

        {{-- Stats --}}
        <div class="text-center sm:text-left flex items-center gap-3">
            <div class="p-3 bg-base-100 rounded-lg">
                <x-mary-icon name="o-ticket" class="w-6 h-6 text-primary" />
            </div>
            <div>
                <div class="text-2xl font-bold text-primary">{{ $total_count }}</div>
                <div class="text-xs opacity-60 uppercase font-semibold">Vouchers Found</div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex gap-3">
            <x-mary-button label="Delete All Found" icon="o-trash" class="btn-error text-white btn-sm"
                wire:click="delete"
                wire:confirm="WARNING: This will delete {{ $total_count }} vouchers permanently. Are you sure?"
                :disabled="$total_count === 0" spinner="delete" />

            <x-mary-button label="Print List (PDF)" icon="o-printer" class="btn-success text-white btn-sm"
                wire:click="print" :disabled="$total_count === 0" />
        </div>
    </div>

    {{-- === LIVE TABLE === --}}
    <x-mary-table :headers="$headers" :rows="$vouchers" striped>

        {{-- Custom Status Badge --}}
        @scope('cell_status', $voucher)
            <span
                class="badge badge-sm font-semibold {{ $voucher->status == 'active' ? 'badge-success' : ($voucher->status == 'inactive' ? 'badge-neutral' : 'badge-error') }}">
                {{ ucfirst($voucher->status) }}
            </span>
        @endscope

        {{-- Custom Profile Name (Safe Check) --}}
        @scope('cell_profile.name', $voucher)
            <span class="font-medium text-xs opacity-80">
                {{ $voucher->profile->name ?? '-' }}
            </span>
        @endscope

        {{-- Batch Badge --}}
        @scope('cell_batch', $voucher)
            <span class="badge badge-ghost badge-sm text-xs">
                {{ $voucher->batch }}
            </span>
        @endscope

        {{-- Actions per row (Optional single delete) --}}
        @scope('actions', $voucher)
            <x-mary-button icon="o-trash" wire:click="delete({{ $voucher->id }})" spinner
                class="btn-xs btn-ghost text-error" />
        @endscope

    </x-mary-table>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $vouchers->links() }}
    </div>

</x-mary-card>
