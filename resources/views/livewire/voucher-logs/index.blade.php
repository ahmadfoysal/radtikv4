<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold">Voucher Logs</h1>
            <p class="mt-1 text-sm text-base-content/70">Track voucher activations and deletions</p>
        </div>
    </div>

    {{-- Filters --}}
    <x-mary-card class="border border-base-300">
        <div class="grid gap-4 grid-cols-1 md:grid-cols-2 lg:grid-cols-5">
            {{-- Router Filter --}}
            <div>
                <x-mary-select label="Router" wire:model.live="router_id" :options="$routers" option-label="name"
                    option-value="id" placeholder="Select Router" />
            </div>

            {{-- Event Type Filter --}}
            <div>
                <x-mary-select label="Event Type" wire:model.live="event_type" :options="$eventTypes" option-label="name"
                    option-value="id" />
            </div>

            {{-- From Date --}}
            <div>
                <x-mary-input label="From Date" type="date" wire:model.live="from_date" />
            </div>

            {{-- To Date --}}
            <div>
                <x-mary-input label="To Date" type="date" wire:model.live="to_date" />
            </div>

            {{-- Search --}}
            <div>
                <x-mary-input label="Search" wire:model.live.debounce.500ms="search" placeholder="Username, profile..."
                    icon="o-magnifying-glass" clearable />
            </div>
        </div>
    </x-mary-card>

    {{-- Logs Table --}}
    <x-mary-card class="border border-base-300">
        <div class="overflow-x-auto">
            <table class="table table-sm table-zebra">
                <thead>
                    <tr class="border-b border-base-300">
                        <th class="text-xs text-base-content/70">Date/Time</th>
                        <th class="text-xs text-base-content/70">Event</th>
                        <th class="text-xs text-base-content/70">Username</th>
                        <th class="text-xs text-base-content/70">Profile</th>
                        <th class="text-xs text-base-content/70">Router</th>
                        <th class="text-xs text-base-content/70">Price</th>
                        <th class="text-xs text-base-content/70">Validity</th>
                        <th class="text-xs text-base-content/70">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr class="border-b border-base-300 hover:bg-base-200">
                            <td class="text-xs">
                                {{ $log->created_at->format('M d, Y') }}<br>
                                <span class="text-base-content/50">{{ $log->created_at->format('H:i:s') }}</span>
                            </td>
                            <td>
                                @if ($log->event_type === 'activated')
                                    <span class="badge badge-success badge-sm">
                                        <x-mary-icon name="o-check-circle" class="w-3 h-3 mr-1" />
                                        Activated
                                    </span>
                                @elseif($log->event_type === 'deleted')
                                    <span class="badge badge-error badge-sm">
                                        <x-mary-icon name="o-trash" class="w-3 h-3 mr-1" />
                                        Deleted
                                    </span>
                                @else
                                    <span class="badge badge-ghost badge-sm">
                                        {{ ucfirst($log->event_type) }}
                                    </span>
                                @endif
                            </td>
                            <td class="font-medium">{{ $log->username ?? 'N/A' }}</td>
                            <td class="text-sm">{{ $log->profile ?? 'N/A' }}</td>
                            <td class="text-sm">
                                @if ($log->router)
                                    <a href="{{ route('routers.show', $log->router) }}" wire:navigate
                                        class="link link-primary">
                                        {{ $log->router->name }}
                                    </a>
                                @else
                                    <span class="text-base-content/50">{{ $log->router_name ?? 'N/A' }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($log->price)
                                    ৳{{ number_format($log->price, 2) }}
                                @else
                                    <span class="text-base-content/50">-</span>
                                @endif
                            </td>
                            <td>
                                @if ($log->validity_days)
                                    {{ $log->validity_days }} days
                                @else
                                    <span class="text-base-content/50">-</span>
                                @endif
                            </td>
                            <td>
                                @if ($log->meta && count($log->meta) > 0)
                                    <x-mary-button icon="o-information-circle" class="btn-xs btn-ghost"
                                        wire:click="showDetails({{ json_encode($log->meta) }})" />
                                @else
                                    <span class="text-base-content/50">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-base-content/50 py-8">
                                <div class="flex flex-col items-center gap-2">
                                    <x-mary-icon name="o-document-text" class="w-12 h-12 text-base-content/30" />
                                    <p>No voucher logs found</p>
                                    <p class="text-xs">Try adjusting your filters</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($logs->hasPages())
            <div class="mt-4">
                {{ $logs->links() }}
            </div>
        @endif
    </x-mary-card>

    {{-- Log Details Modal --}}
    <x-mary-modal wire:model="showDetailsModal" title="Log Details" class="backdrop-blur">
        <div class="space-y-2">
            @if (!empty($selectedLogMeta))
                @foreach ($selectedLogMeta as $key => $value)
                    <div class="flex justify-between border-b border-base-300 pb-2">
                        <span class="font-medium text-base-content/70">
                            {{ ucwords(str_replace('_', ' ', $key)) }}:
                        </span>
                        <span class="text-base-content">
                            @if (str_contains($key, '_at') && $value)
                                {{ \Carbon\Carbon::parse($value)->format('M d, Y H:i:s') }}
                            @else
                                {{ is_bool($value) ? ($value ? 'Yes' : 'No') : $value }}
                            @endif
                        </span>
                    </div>
                @endforeach
            @else
                <p class="text-base-content/50">No additional details available</p>
            @endif
        </div>
    </x-mary-modal>
</div>
