<div>
    {{-- Dashboard — RadTik (MaryUI prefix: mary-) --}}

    {{-- Top Stats --}}
    <div class="grid md:grid-cols-3 gap-4 mb-6">
        <x-mary-card>
            <x-slot name="title">Active Routers</x-slot>
            <div class="flex items-end justify-between">
                <div>
                    <div class="text-3xl font-bold text-primary">
                        {{ $activeRouters ?? 12 }}
                    </div>
                    <div class="text-xs opacity-70 mt-1">Online now</div>
                </div>
                <x-mary-button icon="o-server-stack" class="btn-outline btn-sm" href="#">Manage</x-mary-button>
            </div>
        </x-mary-card>

        <x-mary-card>
            <x-slot name="title">Online Users</x-slot>
            <div class="flex items-end justify-between">
                <div>
                    <div class="text-3xl font-bold text-success">
                        {{ $onlineUsers ?? 342 }}
                    </div>
                    <div class="text-xs opacity-70 mt-1">Active sessions</div>
                </div>
                <x-mary-button icon="o-user-group" class="btn-outline btn-sm" href="#">View</x-mary-button>
            </div>
        </x-mary-card>

        <x-mary-card>
            <x-slot name="title">Total Bandwidth (24h)</x-slot>
            <div class="flex items-end justify-between">
                <div>
                    <div class="text-3xl font-bold text-info">
                        {{ $bandwidth24h ?? '1.2 TB' }}
                    </div>
                    <div class="text-xs opacity-70 mt-1">Up + Down</div>
                </div>
                <x-mary-button icon="o-chart-bar" class="btn-outline btn-sm" href="#">Analytics</x-mary-button>
            </div>
        </x-mary-card>
    </div>

    {{-- Middle: Sessions + Quick Actions + System Health --}}
    <div class="grid xl:grid-cols-4 gap-4">
        {{-- Recent Sessions (xl: span 2) --}}
        <x-mary-card class="xl:col-span-2">
            <x-slot name="title">Recent Sessions</x-slot>

            <div class="overflow-x-auto">
                <table class="table table-zebra text-sm">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Router</th>
                            <th>Start</th>
                            <th>Usage</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sessions ?? [['user' => 'rahim', 'router' => 'MKT-01', 'start' => '10:21 AM', 'usage' => '2.4 GB', 'status' => 'Active'], ['user' => 'karim', 'router' => 'MKT-02', 'start' => '09:58 AM', 'usage' => '980 MB', 'status' => 'Idle'], ['user' => 'sumaiya', 'router' => 'MKT-03', 'start' => '09:30 AM', 'usage' => '3.1 GB', 'status' => 'Disconnected']] as $s)
                            <tr>
                                <td>{{ $s['user'] }}</td>
                                <td>{{ $s['router'] }}</td>
                                <td>{{ $s['start'] }}</td>
                                <td>{{ $s['usage'] }}</td>
                                <td>
                                    @php
                                        $badge =
                                            [
                                                'Active' => 'badge-success',
                                                'Idle' => 'badge-warning',
                                                'Disconnected' => 'badge-error',
                                            ][$s['status']] ?? 'badge-ghost';
                                    @endphp
                                    <x-mary-badge class="{{ $badge }}">{{ $s['status'] }}</x-mary-badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <x-slot name="actions">
                <x-mary-button icon="o-arrow-path" class="btn-ghost btn-sm"
                    wire:click="$refresh">Refresh</x-mary-button>
                <x-mary-button icon="o-arrow-down-tray" class="btn-ghost btn-sm" href="#">Export</x-mary-button>
            </x-slot>
        </x-mary-card>

        {{-- Quick Actions --}}
        <x-mary-card>
            <x-slot name="title">Quick Actions</x-slot>
            <div class="flex flex-col gap-2">
                <x-mary-button icon="o-plus" class="btn-primary" href="#" wire:click="createVoucher">Create
                    Voucher</x-mary-button>
                <x-mary-button icon="o-user-plus" class="btn-outline" href="#" wire:click="addHotspotUser">Add
                    Hotspot User</x-mary-button>
                <x-mary-button icon="o-server-stack" class="btn-outline" href="#" wire:click="addRouter">Add
                    Router</x-mary-button>
                <x-mary-button icon="o-arrow-path" class="btn-outline" href="#" wire:click="syncRadius">Sync
                    Radius</x-mary-button>
            </div>
        </x-mary-card>

        {{-- System Health --}}
        <x-mary-card>
            <x-slot name="title">System Health</x-slot>

            <div class="space-y-3">
                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span>CPU Load</span>
                        <span>{{ $cpu ?? 32 }}%</span>
                    </div>
                    <progress class="progress progress-primary" value="{{ $cpu ?? 32 }}" max="100"></progress>
                </div>

                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span>Memory</span>
                        <span>{{ $mem ?? 58 }}%</span>
                    </div>
                    <progress class="progress progress-info" value="{{ $mem ?? 58 }}" max="100"></progress>
                </div>

                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span>Disk</span>
                        <span>{{ $disk ?? 71 }}%</span>
                    </div>
                    <progress class="progress progress-warning" value="{{ $disk ?? 71 }}" max="100"></progress>
                </div>
            </div>

            <x-slot name="actions">
                <x-mary-button icon="o-bolt" class="btn-ghost btn-sm" wire:click="optimize">Optimize</x-mary-button>
            </x-slot>
        </x-mary-card>
    </div>

    {{-- Bottom: Routers & Revenue --}}
    <div class="grid lg:grid-cols-3 gap-4 mt-6">
        {{-- Router Status --}}
        <x-mary-card class="lg:col-span-2">
            <x-slot name="title">Routers</x-slot>

            <div class="grid md:grid-cols-2 gap-3">
                @foreach ($routers ?? [['name' => 'MKT-01', 'ip' => '10.0.0.1', 'status' => 'Online', 'uptime' => '6d 12h'], ['name' => 'MKT-02', 'ip' => '10.0.0.2', 'status' => 'Online', 'uptime' => '12h 03m'], ['name' => 'MKT-03', 'ip' => '10.0.0.3', 'status' => 'Offline', 'uptime' => '—'], ['name' => 'MKT-04', 'ip' => '10.0.0.4', 'status' => 'Degraded', 'uptime' => '2d 01h']] as $r)
                    @php
                        $dot =
                            [
                                'Online' => 'badge-success',
                                'Offline' => 'badge-error',
                                'Degraded' => 'badge-warning',
                            ][$r['status']] ?? 'badge-ghost';
                    @endphp

                    <div class="flex items-center justify-between p-3 rounded-box bg-base-200">
                        <div>
                            <div class="font-semibold">{{ $r['name'] }} <span
                                    class="opacity-60 text-xs">({{ $r['ip'] }})</span></div>
                            <div class="text-xs opacity-70 mt-0.5">Uptime: {{ $r['uptime'] }}</div>
                        </div>
                        <x-mary-badge class="{{ $dot }}">{{ $r['status'] }}</x-mary-badge>
                    </div>
                @endforeach
            </div>

            <x-slot name="actions">
                <x-mary-button icon="o-arrow-path" class="btn-ghost btn-sm"
                    wire:click="refreshRouters">Refresh</x-mary-button>
                <x-mary-button icon="o-cog-6-tooth" class="btn-ghost btn-sm" href="#">Manage</x-mary-button>
            </x-slot>
        </x-mary-card>

        {{-- Revenue (This Month) --}}
        <x-mary-card>
            <x-slot name="title">Revenue (This Month)</x-slot>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div class="text-2xl font-bold">{{ $revenue ?? '৳ 74,500' }}</div>
                    <x-mary-badge class="badge-success">+12%</x-mary-badge>
                </div>

                <div class="text-xs opacity-70">Top-ups, Voucher sales, Subscriptions</div>

                {{-- simple bars (placeholder for chart) --}}
                <div class="space-y-2 pt-2">
                    <div class="flex items-center gap-2">
                        <span class="w-20 text-xs opacity-70">Top-ups</span>
                        <progress class="progress progress-primary flex-1" value="70" max="100"></progress>
                        <span class="text-xs opacity-70">৳ 35k</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-20 text-xs opacity-70">Vouchers</span>
                        <progress class="progress progress-info flex-1" value="45" max="100"></progress>
                        <span class="text-xs opacity-70">৳ 22k</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-20 text-xs opacity-70">Subs</span>
                        <progress class="progress progress-warning flex-1" value="55" max="100"></progress>
                        <span class="text-xs opacity-70">৳ 17.5k</span>
                    </div>
                </div>
            </div>

            <x-slot name="actions">
                <x-mary-button icon="o-document-chart-bar" class="btn-ghost btn-sm" href="#">Full
                    Report</x-mary-button>
            </x-slot>
        </x-mary-card>
    </div>

</div>
