<div>
    <x-mary-header title="Log Management" subtitle="Laravel application logs (laravel.log)" separator>
        <x-slot:actions>
            <x-mary-button icon="o-arrow-path" label="Refresh" class="btn-sm btn-ghost" wire:click="refreshLogs" />
            <x-mary-button icon="o-arrow-down-tray" label="Download" class="btn-sm btn-primary"
                wire:click="downloadLog" />
            @if ($logInfo['exists'] && $logInfo['total_entries'] > 0)
                <x-mary-button icon="o-trash" label="Clear All" class="btn-error btn-sm" wire:click="clearAllLogs"
                    wire:confirm="Are you sure you want to delete ALL log entries? This action cannot be undone!" />
            @endif
        </x-slot:actions>
    </x-mary-header>

    {{-- Log File Info --}}
    <x-mary-card class="mb-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <div class="text-xs text-gray-500 uppercase">Status</div>
                <div class="text-lg font-semibold">
                    @if ($logInfo['exists'])
                        <span class="text-success">Active</span>
                    @else
                        <span class="text-error">Not Found</span>
                    @endif
                </div>
            </div>
            <div>
                <div class="text-xs text-gray-500 uppercase">File Size</div>
                <div class="text-lg font-semibold">{{ $logInfo['size_formatted'] }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500 uppercase">Total Entries</div>
                <div class="text-lg font-semibold">{{ number_format($logInfo['total_entries']) }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500 uppercase">Last Modified</div>
                <div class="text-lg font-semibold">{{ $logInfo['modified'] ?? 'N/A' }}</div>
            </div>
        </div>
    </x-mary-card>

    {{-- Filters --}}
    <x-mary-card class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-mary-select label="Filter by Level" icon="o-funnel" :options="$levelOptions" wire:model.live="searchLevel"
                placeholder="All Levels" />
            <x-mary-input label="Search" icon="o-magnifying-glass" wire:model.live.debounce.500ms="searchText"
                placeholder="Search in messages..." />
        </div>
    </x-mary-card>

    {{-- Logs Table --}}
    <x-mary-card>
        @if (count($logs) === 0)
            <div class="text-center py-16">
                <x-mary-icon name="o-document-text" class="w-20 h-20 mx-auto text-gray-400 mb-3" />
                <p class="text-gray-500 text-lg">
                    @if ($logInfo['exists'])
                        No log entries found
                    @else
                        Log file does not exist yet
                    @endif
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th class="w-16">#</th>
                            <th class="w-44">Date & Time</th>
                            <th class="w-24">Environment</th>
                            <th class="w-28">Level</th>
                            <th>Message</th>
                            <th class="w-24 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $index => $log)
                            <tr class="hover">
                                <td class="font-mono text-xs text-gray-500">{{ $index + 1 }}</td>
                                <td class="font-mono text-xs">{{ $log['datetime'] }}</td>
                                <td>
                                    <span class="badge badge-sm badge-outline">{{ $log['environment'] }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-sm {{ $this->getLevelBadgeClass($log['level']) }}">
                                        {{ strtoupper($log['level']) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="max-w-2xl">
                                        <p class="text-sm line-clamp-2 whitespace-pre-wrap break-words">
                                            {{ $log['message'] }}
                                        </p>
                                        @if (strlen($log['message']) > 150)
                                            <button type="button" class="text-xs text-primary hover:underline mt-1"
                                                onclick="document.getElementById('log-modal-{{ $log['index'] }}').showModal()">
                                                View full message
                                            </button>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="flex gap-1 justify-center">
                                        @if (strlen($log['message']) > 150)
                                            <x-mary-button icon="o-eye" class="btn-xs btn-ghost"
                                                tooltip="View Full Message"
                                                onclick="document.getElementById('log-modal-{{ $log['index'] }}').showModal()" />
                                        @endif
                                        <x-mary-button icon="o-trash" class="btn-xs btn-ghost text-error"
                                            wire:click="confirmDelete({{ $log['index'] }})" tooltip="Delete" />
                                    </div>
                                </td>
                            </tr>

                            {{-- Full Message Modal --}}
                            @if (strlen($log['message']) > 150)
                                <dialog id="log-modal-{{ $log['index'] }}" class="modal">
                                    <div class="modal-box max-w-4xl">
                                        <h3 class="font-bold text-lg mb-2">Log Entry Details</h3>
                                        <div class="space-y-3">
                                            <div>
                                                <span class="text-sm font-semibold text-gray-500">Date & Time:</span>
                                                <span class="font-mono text-sm">{{ $log['datetime'] }}</span>
                                            </div>
                                            <div>
                                                <span class="text-sm font-semibold text-gray-500">Level:</span>
                                                <span
                                                    class="badge {{ $this->getLevelBadgeClass($log['level']) }}">{{ strtoupper($log['level']) }}</span>
                                            </div>
                                            <div>
                                                <span class="text-sm font-semibold text-gray-500">Environment:</span>
                                                <span
                                                    class="badge badge-outline">{{ $log['environment'] }}</span>
                                            </div>
                                            <div>
                                                <div class="text-sm font-semibold text-gray-500 mb-2">Message:</div>
                                                <div
                                                    class="bg-base-200 p-4 rounded-lg font-mono text-xs max-h-96 overflow-y-auto whitespace-pre-wrap break-words">
                                                    {{ $log['message'] }}</div>
                                            </div>
                                        </div>
                                        <div class="modal-action">
                                            <form method="dialog">
                                                <button class="btn btn-sm">Close</button>
                                            </form>
                                        </div>
                                    </div>
                                    <form method="dialog" class="modal-backdrop">
                                        <button>close</button>
                                    </form>
                                </dialog>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination Info --}}
            <div class="mt-4 text-sm text-gray-500 text-center">
                Showing {{ count($logs) }} of {{ $logInfo['total_entries'] }} entries
            </div>
        @endif
    </x-mary-card>

    {{-- Delete Confirmation Modal --}}
    <x-mary-modal wire:model="showModal" title="Confirm Deletion" box-class="max-w-md">
        <div class="space-y-4">
            <p class="text-gray-600">Are you sure you want to delete this log entry?</p>
            <div class="p-4 bg-error/10 border border-error/30 rounded-lg">
                <p class="text-sm text-error font-medium">This action cannot be undone.</p>
                <p class="text-xs text-gray-500 mt-2">The log entry will be permanently removed from the file.</p>
            </div>
        </div>

        <x-slot:actions>
            <x-mary-button label="Cancel" wire:click="closeModal" />
            <x-mary-button label="Delete" class="btn-error" wire:click="deleteLogEntry" />
        </x-slot:actions>
    </x-mary-modal>
</div>
