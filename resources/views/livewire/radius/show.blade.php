<div>
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">RADIUS Server Management</h1>
                <p class="text-sm text-gray-600 mt-1">{{ $server->host }}</p>
                @if($lastChecked)
                    <p class="text-xs text-gray-500 mt-1">Last checked: {{ $lastChecked }}</p>
                @endif
            </div>
            <div class="flex gap-2">
                <x-mary-button label="Back" icon="o-arrow-left" href="{{ route('radius.index') }}" 
                    wire:navigate class="btn-ghost btn-sm" />
                <x-mary-button label="Refresh" icon="o-arrow-path" wire:click="refreshStatus" 
                    class="btn-sm btn-primary" spinner="refreshStatus" />
            </div>
        </div>
    </div>

    {{-- Loading State --}}
    @if($loading)
        <div class="flex items-center justify-center py-12">
            <x-mary-loading class="loading-lg" />
        </div>
    @else
        {{-- Quick Status Bar --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
            <div class="stat bg-base-200 rounded-lg p-4">
                <div class="stat-title text-xs">SSH</div>
                <div class="stat-value text-lg">
                    <x-mary-badge :value="$sshConnected ? 'Connected' : 'Offline'" 
                        :class="$sshConnected ? 'badge-success badge-sm' : 'badge-error badge-sm'" />
                </div>
            </div>
            <div class="stat bg-base-200 rounded-lg p-4">
                <div class="stat-title text-xs">FreeRADIUS</div>
                <div class="stat-value text-lg">
                    <x-mary-badge :value="$this->getStatusText($radiusServiceActive)" 
                        :class="$this->getStatusBadgeClass($radiusServiceActive) . ' badge-sm'" />
                </div>
            </div>
            <div class="stat bg-base-200 rounded-lg p-4">
                <div class="stat-title text-xs">API Service</div>
                <div class="stat-value text-lg">
                    <x-mary-badge :value="$this->getStatusText($apiServiceActive)" 
                        :class="$this->getStatusBadgeClass($apiServiceActive) . ' badge-sm'" />
                </div>
            </div>
            <div class="stat bg-base-200 rounded-lg p-4">
                <div class="stat-title text-xs">Total Users</div>
                <div class="stat-value text-lg text-primary">{{ number_format($totalUsers) }}</div>
            </div>
            <div class="stat bg-base-200 rounded-lg p-4">
                <div class="stat-title text-xs">Version</div>
                <div class="stat-value text-sm">
                    @if($updateAvailable)
                        <x-mary-badge value="Update Available" class="badge-warning badge-sm" />
                    @else
                        v{{ $installedVersion }}
                    @endif
                </div>
            </div>
        </div>

        {{-- Tabbed Interface --}}
        {{-- Tabbed Interface --}}
        <x-mary-tabs wire:model="selectedTab" class="mb-6">
            
            {{-- OVERVIEW TAB --}}
            <x-mary-tab name="overview" label="Overview" icon="o-chart-bar">
                @if($sshConnected && !empty($systemHealth))
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
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
                                    <div class="flex justify-between items-center py-2 border-b">
                                        <span class="text-sm text-gray-600">Uptime</span>
                                        <span class="text-sm font-medium">{{ $systemHealth['uptime'] }}</span>
                                    </div>
                                @endif
                                
                                @if(isset($systemHealth['hostname']))
                                    <div class="flex justify-between items-center py-2 border-b">
                                        <span class="text-sm text-gray-600">Hostname</span>
                                        <span class="text-sm font-medium">{{ $systemHealth['hostname'] }}</span>
                                    </div>
                                @endif

                                <div class="flex justify-between items-center py-2 border-b">
                                    <span class="text-sm text-gray-600">Total RADIUS Users</span>
                                    <span class="text-sm font-bold text-primary">{{ number_format($totalUsers) }}</span>
                                </div>

                                <div class="flex justify-between items-center py-2 border-b">
                                    <span class="text-sm text-gray-600">Auth Port</span>
                                    <span class="text-sm font-medium">{{ $server->auth_port }}</span>
                                </div>

                                <div class="flex justify-between items-center py-2 border-b">
                                    <span class="text-sm text-gray-600">Accounting Port</span>
                                    <span class="text-sm font-medium">{{ $server->acct_port }}</span>
                                </div>
                                
                                <div class="flex justify-between items-center py-2">
                                    <span class="text-sm text-gray-600">Timeout</span>
                                    <span class="text-sm font-medium">{{ $server->timeout }}s</span>
                                </div>
                            </div>
                        </x-mary-card>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-12">
                        <x-mary-icon name="o-signal-slash" class="w-16 h-16 text-base-content/20 mb-4" />
                        <p class="text-sm text-base-content/60">Connect via SSH to view system health</p>
                    </div>
                @endif
            </x-mary-tab>

            {{-- MANAGEMENT TAB --}}
            <x-mary-tab name="management" label="Management" icon="o-cog-6-tooth">
                <div class="space-y-6">
                    
                    {{-- Version & Updates --}}
                    @if($sshConnected)
                        <x-mary-card title="Software Version & Updates" separator>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {{-- Version Information --}}
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between py-2 border-b">
                                        <span class="text-sm font-medium text-gray-600">Installed Version</span>
                                        <x-mary-badge :value="'v' . $installedVersion" class="badge-primary badge-md" />
                                    </div>
                                    
                                    @if($latestVersion)
                                        <div class="flex items-center justify-between py-2 border-b">
                                            <span class="text-sm font-medium text-gray-600">Latest Version</span>
                                            <x-mary-badge :value="'v' . $latestVersion" 
                                                :class="$updateAvailable ? 'badge-success' : 'badge-ghost'" 
                                                class="badge-md" />
                                        </div>
                                    @endif
                                    
                                    <div class="flex items-center justify-between py-2">
                                        <span class="text-sm font-medium text-gray-600">Update Status</span>
                                        @if($updateAvailable)
                                            <x-mary-badge value="Update Available" class="badge-warning badge-md" 
                                                icon="o-arrow-down-tray" />
                                        @elseif($latestVersion)
                                            <x-mary-badge value="Up to Date" class="badge-success badge-md" 
                                                icon="o-check-circle" />
                                        @else
                                            <x-mary-badge value="Not Checked" class="badge-ghost badge-md" />
                                        @endif
                                    </div>

                                    @if($updateMessage)
                                        <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                            <p class="text-sm text-yellow-800">{{ $updateMessage }}</p>
                                        </div>
                                    @endif
                                </div>

                                {{-- Update Actions --}}
                                <div class="flex flex-col justify-center space-y-3">
                                    <x-mary-button label="Check for Updates" 
                                        icon="o-magnifying-glass" 
                                        wire:click="checkForUpdates"
                                        :disabled="$checkingUpdates || $applyingUpdate"
                                        spinner="checkForUpdates"
                                        class="btn-primary w-full" />

                                    @if($updateAvailable)
                                        <x-mary-button label="Apply Update" 
                                            icon="o-arrow-down-tray" 
                                            wire:click="applyUpdate"
                                            wire:confirm="Are you sure you want to update? The services will be restarted. A backup will be created automatically."
                                            :disabled="$checkingUpdates || $applyingUpdate"
                                            spinner="applyUpdate"
                                            class="btn-success w-full" />
                                    @endif

                                    <div class="text-xs text-gray-500 mt-2 space-y-1">
                                        <p>✓ Automatic backup before update</p>
                                        <p>✓ Services restarted after update</p>
                                        <p>✓ Configuration preserved</p>
                                    </div>
                                </div>
                            </div>
                        </x-mary-card>
                    @endif

                    {{-- Service Control --}}
                    <x-mary-card title="Service Control" separator>
                        @if($sshConnected)
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                {{-- Diagnostics --}}
                                <div>
                                    <h4 class="font-semibold mb-3 flex items-center gap-2">
                                        <x-mary-icon name="o-signal" class="w-5 h-5 text-primary" />
                                        Diagnostics
                                    </h4>
                                    <div class="space-y-2">
                                        <x-mary-button label="Test SSH Connection" icon="o-wifi" 
                                            wire:click="testConnection"
                                            class="btn-primary btn-sm w-full" 
                                            spinner="testConnection" />
                                        
                                        <x-mary-button label="Test RADIUS Auth" icon="o-shield-check" 
                                            wire:click="testRadiusAuth"
                                            class="btn-outline btn-sm w-full" 
                                            spinner="testRadiusAuth" />
                                    </div>
                                </div>

                                {{-- Service Operations --}}
                                <div>
                                    <h4 class="font-semibold mb-3 flex items-center gap-2">
                                        <x-mary-icon name="o-cog-6-tooth" class="w-5 h-5 text-info" />
                                        Restart Services
                                    </h4>
                                    <div class="space-y-2">
                                        <x-mary-button label="Restart FreeRADIUS" icon="o-arrow-path"
                                            wire:click="restartRadiusService"
                                            class="btn-ghost btn-sm w-full" 
                                            spinner="restartRadiusService"
                                            onclick="return confirm('Restart FreeRADIUS service?')" />

                                        <x-mary-button label="Restart API Service" icon="o-arrow-path"
                                            wire:click="restartApiService"
                                            class="btn-ghost btn-sm w-full" 
                                            spinner="restartApiService"
                                            onclick="return confirm('Restart API service?')" />

                                        <x-mary-button label="Restart All Services" icon="o-arrow-path"
                                            wire:click="restartServices"
                                            class="btn-warning btn-sm w-full" 
                                            spinner="restartServices"
                                            onclick="return confirm('⚠️ Restart all services? This will briefly interrupt connections.')" />
                                    </div>
                                </div>

                                {{-- Critical Actions --}}
                                <div>
                                    <h4 class="font-semibold mb-3 flex items-center gap-2 text-error">
                                        <x-mary-icon name="o-exclamation-triangle" class="w-5 h-5" />
                                        Critical
                                    </h4>
                                    <div class="space-y-2">
                                        <x-mary-button label="Reboot Server" icon="o-power" 
                                            wire:click="rebootServer"
                                            class="btn-error btn-sm w-full" 
                                            spinner="rebootServer"
                                            onclick="return confirm('⚠️ This will reboot the entire Ubuntu server. All services will be offline for 2-3 minutes. Continue?')" />
                                        
                                        <div class="alert alert-warning py-2 px-3">
                                            <x-mary-icon name="o-information-circle" class="w-4 h-4 shrink-0" />
                                            <span class="text-xs">Disconnects all clients temporarily</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="flex flex-col items-center justify-center py-12">
                                <x-mary-icon name="o-exclamation-circle" class="w-16 h-16 text-base-content/20 mb-4" />
                                <p class="text-sm text-base-content/60">SSH connection required to manage services</p>
                            </div>
                        @endif
                    </x-mary-card>

                    {{-- API Configuration --}}
                    <x-mary-card title="API Configuration" separator>
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
                </div>
            </x-mary-tab>

            {{-- LOGS TAB --}}
            <x-mary-tab name="logs" label="Logs" icon="o-document-text">
                @if($sshConnected && !empty($recentLogs))
                    <x-mary-card separator>
                        <x-mary-tabs wire:model="logTab">
                            {{-- FreeRADIUS Logs --}}
                            <x-mary-tab name="freeradius" label="FreeRADIUS" icon="o-shield-check">
                                @if(isset($recentLogs['freeradius']) && !empty($recentLogs['freeradius']))
                                    <div class="bg-base-200 p-4 rounded-lg overflow-x-auto max-h-96 overflow-y-auto">
                                        <pre class="text-xs font-mono whitespace-pre-wrap">{{ implode("\n", array_slice($recentLogs['freeradius'], -50)) }}</pre>
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
                                    <div class="bg-base-200 p-4 rounded-lg overflow-x-auto max-h-96 overflow-y-auto">
                                        <pre class="text-xs font-mono whitespace-pre-wrap">{{ implode("\n", array_slice($recentLogs['api'], -50)) }}</pre>
                                    </div>
                                @else
                                    <div class="alert alert-info">
                                        <span class="text-sm">No recent API server logs available</span>
                                    </div>
                                @endif
                            </x-mary-tab>
                        </x-mary-tabs>
                    </x-mary-card>
                @else
                    <div class="flex flex-col items-center justify-center py-12">
                        <x-mary-icon name="o-document" class="w-16 h-16 text-base-content/20 mb-4" />
                        <p class="text-sm text-base-content/60">Connect via SSH to view logs</p>
                    </div>
                @endif
            </x-mary-tab>

        </x-mary-tabs>

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
