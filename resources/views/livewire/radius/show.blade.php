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
                <x-mary-button label="Refresh Status" icon="o-arrow-path" wire:click="refreshStatus" 
                    class="btn-sm btn-primary" spinner="refreshStatus" />
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
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
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

            {{-- FreeRADIUS Service Status --}}
            <x-mary-card>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">FreeRADIUS Service</p>
                        <p class="text-2xl font-bold mt-1">
                            <x-mary-badge :value="$this->getStatusText($radiusServiceActive)" 
                                :class="$this->getStatusBadgeClass($radiusServiceActive)" />
                        </p>
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
                        
                        @if(isset($systemHealth['hostname']))
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Hostname</span>
                                <span class="text-sm font-medium">{{ $systemHealth['hostname'] }}</span>
                            </div>
                        @endif

                        <div class="divider my-2"></div>

                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Total RADIUS Users</span>
                            <span class="text-sm font-bold text-primary">{{ number_format($totalUsers) }}</span>
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
                            <span class="text-sm text-gray-600">Timeout</span>
                            <span class="text-sm font-medium">{{ $server->timeout }}s</span>
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

        {{-- API Configuration Card --}}
        <x-mary-card title="API Configuration" separator class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="font-semibold mb-3 flex items-center gap-2">
                        <x-mary-icon name="o-key" class="w-5 h-5 text-primary" />
                        Generated Credentials
                    </h4>
                    <div class="space-y-3">
                        <div>
                            <label class="text-xs text-base-content/60">RADIUS Secret (clients.conf)</label>
                            <div class="flex items-center gap-2 mt-1">
                                <code class="flex-1 p-2 bg-base-200 rounded text-xs font-mono break-all">{{ $server->secret }}</code>
                                <x-mary-button icon="o-clipboard" tooltip="Copy" class="btn-ghost btn-xs" 
                                    onclick="navigator.clipboard.writeText('{{ $server->secret }}')" />
                            </div>
                        </div>
                        <div>
                            <label class="text-xs text-base-content/60">API Auth Token (config.ini)</label>
                            <div class="flex items-center gap-2 mt-1">
                                <code class="flex-1 p-2 bg-base-200 rounded text-xs font-mono break-all">{{ $server->auth_token }}</code>
                                <x-mary-button icon="o-clipboard" tooltip="Copy" class="btn-ghost btn-xs"
                                    onclick="navigator.clipboard.writeText('{{ $server->auth_token }}')" />
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <h4 class="font-semibold mb-3 flex items-center gap-2">
                        <x-mary-icon name="o-cog" class="w-5 h-5 text-success" />
                        Auto-Configure Server
                    </h4>
                    <div class="space-y-3">
                        <p class="text-sm text-base-content/70">Push credentials to your RADIUS server via SSH. This will update config.ini and clients.conf automatically.</p>
                        @if($sshConnected)
                            <div class="flex gap-2">
                                <x-mary-button label="Verify Token Sync" icon="o-shield-check" 
                                    wire:click="verifyTokenSync"
                                    class="btn-info btn-sm" spinner="verifyTokenSync" />
                                <x-mary-button label="Push Credentials" icon="o-arrow-path" 
                                    wire:click="configureCredentials"
                                    class="btn-success btn-sm" spinner="configureCredentials"
                                    onclick="return confirm('This will update config.ini and clients.conf, then restart services. Continue?')" />
                            </div>
                            <div class="alert alert-info py-2 px-3">
                                <x-mary-icon name="o-information-circle" class="w-4 h-4 shrink-0" />
                                <span class="text-xs">First verify token sync. If mismatched, push credentials to fix 401 errors.</span>
                            </div>
                        @else
                            <x-mary-button label="Configure Credentials via SSH" icon="o-arrow-path" 
                                class="btn-success btn-sm" disabled />
                            <p class="text-xs text-error">SSH connection required</p>
                        @endif
                    </div>
                </div>
            </div>
        </x-mary-card>

        {{-- Server Management --}}
        <x-mary-card title="Server Management" separator>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Diagnostics Panel --}}
                <div class="rounded-xl border border-base-300 bg-gradient-to-br from-base-100 to-base-200/30 p-5 space-y-4 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-primary/10 rounded-lg">
                                <x-mary-icon name="o-signal" class="w-5 h-5 text-primary" />
                            </div>
                            <div>
                                <h4 class="font-semibold text-sm">Diagnostics</h4>
                                <p class="text-xs text-base-content/50">Connection tests</p>
                            </div>
                        </div>
                        <x-mary-badge :value="$sshConnected ? 'Online' : 'Offline'"
                            :class="$sshConnected ? 'badge-success badge-sm' : 'badge-error badge-sm'" />
                    </div>

                    <div class="space-y-2 pt-2">
                        <x-mary-button label="Test SSH Connection" icon="o-wifi" wire:click="testConnection"
                            class="btn-primary btn-sm justify-start w-full normal-case font-medium" spinner="testConnection" />

                        @if($sshConnected)
                            <x-mary-button label="Test RADIUS Auth" icon="o-shield-check" wire:click="testRadiusAuth"
                                class="btn-outline btn-sm justify-start w-full normal-case font-medium" spinner="testRadiusAuth" />
                        @else
                            <div class="text-xs text-base-content/50 px-3 py-2 bg-base-200/50 rounded-lg text-center">
                                Connect SSH to enable auth test
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Service Operations Panel --}}
                <div class="rounded-xl border border-base-300 bg-gradient-to-br from-base-100 to-base-200/30 p-5 space-y-4 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-info/10 rounded-lg">
                            <x-mary-icon name="o-cog-6-tooth" class="w-5 h-5 text-info" />
                        </div>
                        <div>
                            <h4 class="font-semibold text-sm">Service Control</h4>
                            <p class="text-xs text-base-content/50">Manage services</p>
                        </div>
                    </div>

                    @if($sshConnected)
                        {{-- Service Status Cards --}}
                        <div class="space-y-2">
                            <div class="flex items-center justify-between p-3 rounded-lg border border-base-300 bg-base-100">
                                <div class="flex items-center gap-2">
                                    <x-mary-icon name="o-shield-check" class="w-4 h-4 text-base-content/70" />
                                    <span class="text-xs font-medium">FreeRADIUS</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="{{ $radiusServiceActive ? 'bg-success' : 'bg-error' }} w-2 h-2 rounded-full animate-pulse"></div>
                                    <x-mary-badge :value="$this->getStatusText($radiusServiceActive)"
                                        :class="$this->getStatusBadgeClass($radiusServiceActive) . ' badge-xs'" />
                                </div>
                            </div>
                            <div class="flex items-center justify-between p-3 rounded-lg border border-base-300 bg-base-100">
                                <div class="flex items-center gap-2">
                                    <x-mary-icon name="o-cloud" class="w-4 h-4 text-base-content/70" />
                                    <span class="text-xs font-medium">API Service</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="{{ $apiServiceActive ? 'bg-success' : 'bg-error' }} w-2 h-2 rounded-full animate-pulse"></div>
                                    <x-mary-badge :value="$this->getStatusText($apiServiceActive)"
                                        :class="$this->getStatusBadgeClass($apiServiceActive) . ' badge-xs'" />
                                </div>
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="space-y-2 pt-1">
                            <x-mary-button label="Restart FreeRADIUS" icon="o-arrow-path"
                                wire:click="restartRadiusService"
                                class="btn-ghost btn-sm justify-start w-full normal-case font-medium hover:bg-base-200" 
                                spinner="restartRadiusService"
                                onclick="return confirm('Restart FreeRADIUS service?')" />

                            <x-mary-button label="Restart API Service" icon="o-arrow-path"
                                wire:click="restartApiService"
                                class="btn-ghost btn-sm justify-start w-full normal-case font-medium hover:bg-base-200" 
                                spinner="restartApiService"
                                onclick="return confirm('Restart API service?')" />

                            <div class="divider my-1 text-xs text-base-content/40">Quick Action</div>

                            <x-mary-button label="Restart All Services" icon="o-arrow-path"
                                wire:click="restartServices"
                                class="btn-warning btn-sm justify-start w-full normal-case font-medium" 
                                spinner="restartServices"
                                onclick="return confirm('⚠️ Restart all services? This will briefly interrupt connections.')" />
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-8 space-y-2">
                            <x-mary-icon name="o-exclamation-circle" class="w-10 h-10 text-base-content/20" />
                            <p class="text-xs text-base-content/50 text-center">SSH connection required<br/>to manage services</p>
                        </div>
                    @endif
                </div>

                {{-- Critical Actions Panel --}}
                <div class="rounded-xl border border-error/20 bg-gradient-to-br from-base-100 to-error/5 p-5 space-y-4 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-error/10 rounded-lg">
                            <x-mary-icon name="o-exclamation-triangle" class="w-5 h-5 text-error" />
                        </div>
                        <div>
                            <h4 class="font-semibold text-sm text-error">Critical Actions</h4>
                            <p class="text-xs text-base-content/50">Handle with care</p>
                        </div>
                    </div>

                    <div class="space-y-3 pt-2">
                        @if($sshConnected)
                            <x-mary-button label="Reboot Server" icon="o-power" wire:click="rebootServer"
                                class="btn-error btn-sm justify-start w-full normal-case font-medium" 
                                spinner="rebootServer"
                                onclick="return confirm('⚠️ This will reboot the entire Ubuntu server. All services will be offline for 2-3 minutes. Continue?')" />
                        @else
                            <x-mary-button label="Reboot Server" icon="o-power" 
                                class="btn-error btn-sm justify-start w-full normal-case font-medium" disabled />
                        @endif

                        <div class="alert alert-warning py-3 px-3">
                            <x-mary-icon name="o-information-circle" class="w-4 h-4 shrink-0" />
                            <span class="text-xs leading-tight">Server reboot will temporarily disconnect all active RADIUS clients.</span>
                        </div>
                    </div>
                </div>
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
