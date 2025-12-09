<section class="w-full">
    {{-- Header --}}
    <x-mary-card class="mb-4 bg-base-200 border-0 shadow-sm">
        <div class="px-4 py-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2">
                <x-mary-icon name="o-signal" class="w-6 h-6 text-primary" />
                <span class="font-semibold text-lg">Active Hotspot Sessions</span>
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

    {{-- Sessions Table --}}
    <div class="px-2 sm:px-4">
        @if($router_id && !empty($sessions))
            <x-mary-card class="bg-base-200 overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Address</th>
                            <th>MAC Address</th>
                            <th>Uptime</th>
                            <th>Bytes In</th>
                            <th>Bytes Out</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sessions as $session)
                            <tr>
                                <td class="font-medium">{{ $session['user'] ?? 'N/A' }}</td>
                                <td>{{ $session['address'] ?? 'N/A' }}</td>
                                <td>{{ $session['mac-address'] ?? 'N/A' }}</td>
                                <td>{{ $session['uptime'] ?? '0s' }}</td>
                                <td>{{ isset($session['bytes-in']) ? number_format($session['bytes-in']) : '0' }} B</td>
                                <td>{{ isset($session['bytes-out']) ? number_format($session['bytes-out']) : '0' }} B</td>
                                <td class="text-center">
                                    <x-mary-button 
                                        icon="o-trash" 
                                        class="btn-ghost btn-xs text-error"
                                        wire:click="deleteSession('{{ $session['.id'] ?? '' }}')"
                                        spinner="deleteSession"
                                        onclick="return confirm('Are you sure you want to remove this session?')"
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-mary-card>
        @elseif($router_id && empty($sessions) && !$loading)
            <x-mary-card class="bg-base-200">
                <div class="p-8 text-center opacity-70">
                    <x-mary-icon name="o-inbox" class="w-12 h-12 mx-auto mb-2" />
                    <p>No active sessions found for this router.</p>
                </div>
            </x-mary-card>
        @elseif(!$router_id)
            <x-mary-card class="bg-base-200">
                <div class="p-8 text-center opacity-70">
                    <x-mary-icon name="o-server" class="w-12 h-12 mx-auto mb-2" />
                    <p>Please select a router to view active sessions.</p>
                </div>
            </x-mary-card>
        @endif

        @if($loading)
            <x-mary-card class="bg-base-200">
                <div class="p-8 text-center">
                    <span class="loading loading-spinner loading-lg text-primary"></span>
                    <p class="mt-2">Loading active sessions...</p>
                </div>
            </x-mary-card>
        @endif
    </div>
</section>
