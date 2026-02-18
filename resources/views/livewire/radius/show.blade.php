<div>
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">RADIUS Server Status</h1>
                <p class="text-sm text-gray-600 mt-1">{{ $server->host }}</p>
            </div>
            <div class="flex gap-2">
                <x-mary-button label="Back" icon="o-arrow-left" href="{{ route('radius.index') }}" 
                    wire:navigate class="btn-ghost btn-sm" />
                <x-mary-button label="Refresh" icon="o-arrow-path" wire:click="refreshStatus" 
                    class="btn-sm" spinner="refreshStatus" />
            </div>
        </div>
        @if($lastChecked)
            <p class="text-xs text-gray-500 mt-2">Last checked: {{ $lastChecked }}</p>
        @endif
    </div>

    {{-- Loading State --}}
    @if($loading)
        <div class="flex items-center justify-center py-12">
            <x-mary-loading class="loading-lg" />
        </div>
    @else
        {{-- Service Status Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            {{-- SSH Connection Status --}}
            <x-mary-card>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">SSH Connection</p>
                        <p class="text-2xl font-bold mt-1">
                            <x-mary-badge :value="$sshConnected ? 'Connected' : 'Disconnected'" 
                                :class="$sshConnected ? 'badge-success' : 'badge-error'" />
                        </p>
                    </div>
                    <x-mary-icon name="o-command-line" class="w-12 h-12 text-primary opacity-20" />
                </div>
            </x-mary-card>

            {{-- Installation Status --}}
            <x-mary-card>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Installation</p>
                        <p class="text-2xl font-bold mt-1">
                            <x-mary-badge :value="ucfirst($server->installation_status)" 
                                :class="$server->installation_status === 'completed' ? 'badge-success' : 
                                       ($server->installation_status === 'failed' ? 'badge-error' : 'badge-warning')" />
                        </p>
                    </div>
                    <x-mary-icon name="o-cog-6-tooth" class="w-12 h-12 text-primary opacity-20" />
                </div>
            </x-mary-card>

            {{-- FreeRADIUS Service Status --}}
            <x-mary-card>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">FreeRADIUS Service</p>
                        <p class="text-2xl font-bold mt-1">
                            <x-mary-badge :value="$this->getStatusText($radiusServiceActive)" 
                                :class="$this->getStatusBadgeClass($radiusServiceActive)" />
                        </p>
                        @if($sshConnected && config('app.debug'))
                            <p class="text-xs text-gray-500 mt-1">Debug: {{ $radiusServiceActive ? 'true' : 'false' }}</p>
                        @endif
                    </div>
                    <x-mary-icon name="o-shield-check" class="w-12 h-12 text-primary opacity-20" />
                </div>
            </x-mary-card>

            {{-- API Service Status --}}
            <x-mary-card>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">API Service</p>
                        <p class="text-2xl font-bold mt-1">
                            <x-mary-badge :value="$this->getStatusText($apiServiceActive)" 
                                :class="$this->getStatusBadgeClass($apiServiceActive)" />
                        </p>
                        @if($sshConnected && config('app.debug'))
                            <p class="text-xs text-gray-500 mt-1">Debug: {{ $apiServiceActive ? 'true' : 'false' }}</p>
                        @endif
                    </div>
                    <x-mary-icon name="o-cloud" class="w-12 h-12 text-primary opacity-20" />
                </div>
            </x-mary-card>
        </div>

        {{-- System Health & Stats --}}
        @if($sshConnected && !empty($systemHealth))
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                {{-- System Resources --}}
                <x-mary-card title="System Resources" separator>
                    <div class="space-y-4">
                        {{-- CPU Usage --}}
                        @if(isset($systemHealth['cpu_usage']))
                            <div>
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm font-medium">CPU Usage</span>
                                    <span class="text-sm font-bold">{{ $systemHealth['cpu_usage'] }}%</span>
                                </div>
                                <progress class="progress progress-primary w-full" 
                                    value="{{ $systemHealth['cpu_usage'] }}" max="100"></progress>
                            </div>
                        @endif

                        {{-- Memory Usage --}}
                        @if(isset($systemHealth['memory_usage']))
                            <div>
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm font-medium">Memory Usage</span>
                                    <span class="text-sm font-bold">{{ $systemHealth['memory_usage'] }}%</span>
                                </div>
                                <progress class="progress progress-info w-full" 
                                    value="{{ $systemHealth['memory_usage'] }}" max="100"></progress>
                            </div>
                        @endif

                        {{-- Disk Usage --}}
                        @if(isset($systemHealth['disk_usage']))
                            <div>
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm font-medium">Disk Usage</span>
                                    <span class="text-sm font-bold">{{ $systemHealth['disk_usage'] }}%</span>
                                </div>
                                <progress class="progress progress-warning w-full" 
                                    value="{{ $systemHealth['disk_usage'] }}" max="100"></progress>
                            </div>
                        @endif

                        {{-- Load Average --}}
                        @if(isset($systemHealth['load_average']))
                            <div class="mt-4 p-3 bg-base-200 rounded-lg">
                                <p class="text-xs text-gray-600 mb-1">Load Average</p>
                                <p class="text-sm font-mono">{{ $systemHealth['load_average'] }}</p>
                            </div>
                        @endif
                    </div>
                </x-mary-card>

                {{-- Server Information --}}
                <x-mary-card title="Server Information" separator>
                    <div class="space-y-3">
                        @if(isset($systemHealth['uptime']))
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Uptime</span>
                                <span class="text-sm font-medium">{{ $systemHealth['uptime'] }}</span>
                            </div>
                        @endif

                        <div class="divider my-2"></div>

                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Total Users</span>
                            <span class="text-sm font-bold">{{ number_format($totalUsers) }}</span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Auth Port</span>
                            <span class="text-sm font-medium">{{ $server->auth_port }}</span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Accounting Port</span>
                            <span class="text-sm font-medium">{{ $server->acct_port }}</span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Installation Status</span>
                            <x-mary-badge :value="ucfirst($server->installation_status)" 
                                :class="$server->installation_status === 'completed' ? 'badge-success' : 
                                       ($server->installation_status === 'failed' ? 'badge-error' : 'badge-warning')" />
                        </div>
                    </div>
                </x-mary-card>
            </div>
        @endif

        {{-- Recent Logs --}}
        @if($sshConnected && !empty($recentLogs))
            <x-mary-card title="Recent Logs" separator class="mb-6">
                <x-mary-tabs wire:model="activeTab">
                    {{-- FreeRADIUS Logs --}}
                    <x-mary-tab name="freeradius" label="FreeRADIUS" icon="o-shield-check">
                        @if(isset($recentLogs['freeradius']) && !empty($recentLogs['freeradius']))
                            <div class="bg-base-200 p-4 rounded-lg overflow-x-auto">
                                <pre class="text-xs font-mono whitespace-pre-wrap">{{ implode("\n", array_slice($recentLogs['freeradius'], -20)) }}</pre>
                            </div>
                        @else
                            <div class="alert alert-info">
                                <span class="text-sm">No recent FreeRADIUS logs available</span>
                            </div>
                        @endif
                    </x-mary-tab>

                    {{-- API Server Logs --}}
                    <x-mary-tab name="api" label="API Server" icon="o-cloud">
                        @if(isset($recentLogs['api']) && !empty($recentLogs['api']))
                            <div class="bg-base-200 p-4 rounded-lg overflow-x-auto">
                                <pre class="text-xs font-mono whitespace-pre-wrap">{{ implode("\n", array_slice($recentLogs['api'], -20)) }}</pre>
                            </div>
                        @else
                            <div class="alert alert-info">
                                <span class="text-sm">No recent API server logs available</span>
                            </div>
                        @endif
                    </x-mary-tab>
                </x-mary-tabs>
            </x-mary-card>
        @endif

        {{-- Action Buttons --}}
        <x-mary-card title="Actions" separator>
            <div class="flex flex-wrap gap-3">
                {{-- Connection Test --}}
                <x-mary-button label="Test Connection" icon="o-wifi" wire:click="testConnection" 
                    class="btn-primary" spinner="testConnection" />
                
                @if($sshConnected)
                    {{-- Installation Actions --}}
                    @if(in_array($server->installation_status, ['pending', 'failed']))
                        <x-mary-button label="Install RADIUS Server" icon="o-arrow-down-tray" 
                            wire:click="installRadiusServer" 
                            class="btn-success" spinner="installRadiusServer"
                            onclick="return confirm('This will install FreeRADIUS and API server. This takes 5-10 minutes. Continue?')" />
                    @elseif($server->installation_status === 'installing')
                        <x-mary-button label="Check Installation Progress" icon="o-magnifying-glass" 
                            wire:click="checkInstallation" 
                            class="btn-info" spinner="checkInstallation" />
                    @else
                        <x-mary-button label="Reinstall RADIUS Server" icon="o-arrow-path" 
                            wire:click="installRadiusServer" 
                            class="btn-warning" spinner="installRadiusServer"
                            onclick="return confirm('This will reinstall FreeRADIUS and API server. All data will be preserved. Continue?')" />
                    @endif

                    {{-- Service Management --}}
                    @if($server->installation_status === 'completed')
                        <x-mary-button label="Restart Services" icon="o-arrow-path" wire:click="restartServices" 
                            class="btn-warning" spinner="restartServices" 
                            onclick="return confirm('Are you sure you want to restart all services?')" />
                        
                        <x-mary-button label="Test RADIUS Auth" icon="o-shield-check" wire:click="testRadiusAuth" 
                            class="btn-info" spinner="testRadiusAuth" />
                    @endif
                @endif

                {{-- Configuration --}}
                <x-mary-button label="Reconfigure Secrets" icon="o-cog-6-tooth" wire:click="reconfigureServer" 
                    class="btn-error" spinner="reconfigureServer"
                    onclick="return confirm('This will generate new secrets and reconfigure the server. Continue?')" />
            </div>
        </x-mary-card>

        {{-- Connection Error --}}
        @if(!$sshConnected)
            <div class="alert alert-error mt-6">
                <x-mary-icon name="o-exclamation-triangle" class="w-6 h-6" />
                <div>
                    <h3 class="font-bold">SSH Connection Failed</h3>
                    <p class="text-sm">Unable to connect to the RADIUS server. Please verify the server is running and SSH credentials are correct.</p>
                </div>
            </div>
        @endif
    @endif
</div>
