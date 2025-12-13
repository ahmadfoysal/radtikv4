<section class="w-full">
    {{-- Header --}}
    <x-mary-card class="mb-4 bg-base-100 border border-base-300 shadow-sm">
        <div class="px-4 py-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2">
                <x-mary-icon name="o-squares-plus" class="w-6 h-6 text-primary" />
                <span class="font-semibold text-lg">Hotspot Logs</span>
            </div>

            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
                <x-mary-select 
                    icon="o-server" 
                    wire:model.live="router_id" 
                    :options="$routers->map(fn($r) => ['id' => $r->id, 'name' => $r->name])->toArray()"
                    option-label="name" 
                    option-value="id" 
                    placeholder="Select a router" 
                    class="w-full sm:w-72"
                    select-class="select-sm"
                />
            </div>
        </div>
    </x-mary-card>

    {{-- Logs Table --}}
    <div class="px-2 sm:px-4">
        @if($router_id && !empty($logs))
            <x-mary-card class="bg-base-100 overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Topics</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                            <tr>
                                <td class="whitespace-nowrap">{{ $log['time'] ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge badge-sm badge-primary">{{ $log['topics'] ?? 'N/A' }}</span>
                                </td>
                                <td class="break-all">{{ $log['message'] ?? 'No message' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-mary-card>
        @elseif($router_id && empty($logs) && !$loading)
            <x-mary-card class="bg-base-100">
                <div class="p-8 text-center opacity-70">
                    <x-mary-icon name="o-inbox" class="w-12 h-12 mx-auto mb-2" />
                    <p>No hotspot logs found for this router.</p>
                </div>
            </x-mary-card>
        @elseif(!$router_id)
            <x-mary-card class="bg-base-100">
                <div class="p-8 text-center opacity-70">
                    <x-mary-icon name="o-server" class="w-12 h-12 mx-auto mb-2" />
                    <p>Please select a router to view hotspot logs.</p>
                </div>
            </x-mary-card>
        @endif

        @if($loading)
            <x-mary-card class="bg-base-100">
                <div class="p-8 text-center">
                    <span class="loading loading-spinner loading-lg text-primary"></span>
                    <p class="mt-2">Loading hotspot logs...</p>
                </div>
            </x-mary-card>
        @endif
    </div>
</section>
