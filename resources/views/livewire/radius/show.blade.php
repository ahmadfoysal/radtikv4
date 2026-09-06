<div class="space-y-5">
    {{-- Top actions --}}
    <div class="flex justify-end gap-2">
        <x-mary-button label="Back" icon="o-arrow-left" href="{{ route('radius.index') }}"
            wire:navigate class="btn-outline btn-xs" />
        <x-mary-button label="Refresh" icon="o-arrow-path" wire:click="refreshStatus"
            class="btn-primary btn-xs" spinner="refreshStatus" />
    </div>

    {{-- Server identity & connection --}}
    <x-mary-card class="bg-base-100 border border-base-300 shadow-sm rounded-none">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-3">
                <x-mary-icon name="o-shield-check" class="w-9 h-9 text-primary" />
                <div>
                    <div class="text-[11px] uppercase tracking-wide opacity-60">RADIUS Server</div>
                    <div class="text-lg font-semibold leading-tight">{{ $server->name ?? $server->host }}</div>
                    <div class="text-xs opacity-70 font-mono">{{ $server->host }}</div>
                </div>
            </div>

            <div class="flex items-center gap-6">
                <div class="text-center">
                    <div class="text-[11px] uppercase tracking-wide opacity-60 mb-1">Connection</div>
                    <x-mary-badge :value="$sshConnected ? 'Online' : 'Offline'"
                        :class="($sshConnected ? 'badge-success' : 'badge-error') . ' badge-md'" />
                </div>
                @if($lastChecked)
                    <div class="text-center">
                        <div class="text-[11px] uppercase tracking-wide opacity-60 mb-1">Last Checked</div>
                        <div class="text-xs font-medium">{{ $lastChecked }}</div>
                    </div>
                @endif
            </div>
        </div>
    </x-mary-card>

    {{-- Loading State --}}
    @if($loading)
        <div class="flex items-center justify-center py-12">
            <x-mary-loading class="loading-lg" />
        </div>
    @else
        {{-- Status Overview --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            <x-mary-card class="bg-base-100 border border-base-300 shadow-sm rounded-none">
                <div class="text-xs uppercase opacity-60">FreeRADIUS</div>
                <div class="mt-2">
                    <x-mary-badge :value="$this->getStatusText($radiusServiceActive)"
                        :class="$this->getStatusBadgeClass($radiusServiceActive)" />
                </div>
            </x-mary-card>
            <x-mary-card class="bg-base-100 border border-base-300 shadow-sm rounded-none">
                <div class="text-xs uppercase opacity-60">API Service</div>
                <div class="mt-2">
                    <x-mary-badge :value="$this->getStatusText($apiServiceActive)"
                        :class="$this->getStatusBadgeClass($apiServiceActive)" />
                </div>
            </x-mary-card>
            <x-mary-card class="bg-base-100 border border-base-300 shadow-sm rounded-none">
                <div class="text-xs uppercase opacity-60">Total Users</div>
                <div class="text-3xl font-semibold text-primary">{{ number_format($totalUsers) }}</div>
            </x-mary-card>
            <x-mary-card class="bg-base-100 border border-base-300 shadow-sm rounded-none">
                <div class="text-xs uppercase opacity-60">Version</div>
                @if($updateAvailable)
                    <div class="mt-2"><x-mary-badge value="Update Available" class="badge-warning" /></div>
                @else
                    <div class="text-3xl font-semibold">v{{ $installedVersion }}</div>
                @endif
            </x-mary-card>
            <x-mary-card class="bg-base-100 border border-base-300 shadow-sm rounded-none">
                <div class="text-xs uppercase opacity-60">Auth / Acct Port</div>
                <div class="text-xl font-semibold">{{ $server->auth_port }} / {{ $server->acct_port }}</div>
            </x-mary-card>
        </div>

        {{-- Connection Error --}}
        @if(!$sshConnected)
            <x-mary-alert icon="o-exclamation-triangle" class="alert-error">
                <div>
                    <div class="font-bold">SSH Connection Failed</div>
                    <div class="text-sm">Unable to connect to the RADIUS server. Verify the server is running and SSH credentials are correct, then use "Test SSH Connection" below.</div>
                </div>
            </x-mary-alert>
        @endif

        {{-- System Health --}}
        @if($sshConnected && !empty($systemHealth))
            <div class="space-y-2">
                <div class="text-sm uppercase font-semibold opacity-70">System Health</div>
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <x-mary-card class="bg-base-100 border border-base-300 shadow-sm rounded-none">
                        <div class="space-y-4">
                            @if(isset($systemHealth['cpu_usage']))
                                <div>
                                    <div class="flex justify-between mb-1 uppercase tracking-wide text-xs opacity-70">
                                        <span>CPU Usage</span>
                                        <span>{{ $systemHealth['cpu_usage'] }}%</span>
                                    </div>
                                    <progress class="progress progress-primary w-full"
                                        value="{{ $systemHealth['cpu_usage'] }}" max="100"></progress>
                                </div>
                            @endif

                            @if(isset($systemHealth['memory_usage']))
                                <div>
                                    <div class="flex justify-between mb-1 uppercase tracking-wide text-xs opacity-70">
                                        <span>Memory Usage</span>
                                        <span>{{ $systemHealth['memory_usage'] }}%</span>
                                    </div>
                                    <progress class="progress progress-info w-full"
                                        value="{{ $systemHealth['memory_usage'] }}" max="100"></progress>
                                </div>
                            @endif

                            @if(isset($systemHealth['disk_usage']))
                                <div>
                                    <div class="flex justify-between mb-1 uppercase tracking-wide text-xs opacity-70">
                                        <span>Disk Usage</span>
                                        <span>{{ $systemHealth['disk_usage'] }}%</span>
                                    </div>
                                    <progress class="progress progress-warning w-full"
                                        value="{{ $systemHealth['disk_usage'] }}" max="100"></progress>
                                </div>
                            @endif

                            @if(isset($systemHealth['load_average']))
                                <div class="pt-2">
                                    <div class="text-[11px] uppercase opacity-60 mb-1">Load Average</div>
                                    <div class="text-sm font-mono">{{ $systemHealth['load_average'] }}</div>
                                </div>
                            @endif
                        </div>
                    </x-mary-card>

                    <x-mary-card class="bg-base-100 border border-base-300 shadow-sm rounded-none">
                        <dl class="space-y-1">
                            @if(isset($systemHealth['hostname']))
                                <div class="flex items-center justify-between gap-3 py-1.5 border-b border-base-200">
                                    <dt class="text-[11px] uppercase opacity-60">Hostname</dt>
                                    <dd class="text-sm font-semibold">{{ $systemHealth['hostname'] }}</dd>
                                </div>
                            @endif
                            @if(isset($systemHealth['uptime']))
                                <div class="flex items-center justify-between gap-3 py-1.5 border-b border-base-200">
                                    <dt class="text-[11px] uppercase opacity-60">Uptime</dt>
                                    <dd class="text-sm font-semibold">{{ $systemHealth['uptime'] }}</dd>
                                </div>
                            @endif
                            <div class="flex items-center justify-between gap-3 py-1.5 border-b border-base-200">
                                <dt class="text-[11px] uppercase opacity-60">Auth Port</dt>
                                <dd class="text-sm font-semibold">{{ $server->auth_port }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3 py-1.5 border-b border-base-200">
                                <dt class="text-[11px] uppercase opacity-60">Accounting Port</dt>
                                <dd class="text-sm font-semibold">{{ $server->acct_port }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3 py-1.5">
                                <dt class="text-[11px] uppercase opacity-60">Timeout</dt>
                                <dd class="text-sm font-semibold">{{ $server->timeout }}s</dd>
                            </div>
                        </dl>
                    </x-mary-card>
                </div>
            </div>
        @endif

        {{-- Diagnostics & Service Control --}}
        <div class="space-y-2">
            <div class="text-sm uppercase font-semibold opacity-70">Diagnostics &amp; Service Control</div>
            <x-mary-card class="bg-base-100 border border-base-300 shadow-sm rounded-none">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <h4 class="font-semibold mb-3 flex items-center gap-2 text-sm">
                            <x-mary-icon name="o-signal" class="w-4 h-4 text-primary" />
                            Diagnostics
                        </h4>
                        <div class="flex flex-wrap gap-2">
                            <x-mary-button label="Test SSH Connection" icon="o-wifi"
                                wire:click="testConnection" class="btn-outline btn-sm" spinner="testConnection" />
                            <x-mary-button label="Test RADIUS Auth" icon="o-shield-check"
                                wire:click="testRadiusAuth" :disabled="!$sshConnected"
                                class="btn-outline btn-sm" spinner="testRadiusAuth" />
                        </div>
                    </div>

                    <div>
                        <h4 class="font-semibold mb-3 flex items-center gap-2 text-sm">
                            <x-mary-icon name="o-arrow-path" class="w-4 h-4 text-info" />
                            Restart Services
                        </h4>
                        <div class="flex flex-wrap gap-2">
                            <x-mary-button label="FreeRADIUS" icon="o-arrow-path"
                                wire:click="restartRadiusService" :disabled="!$sshConnected"
                                class="btn-ghost btn-sm" spinner="restartRadiusService"
                                wire:confirm="Restart FreeRADIUS service?" />
                            <x-mary-button label="API Service" icon="o-arrow-path"
                                wire:click="restartApiService" :disabled="!$sshConnected"
                                class="btn-ghost btn-sm" spinner="restartApiService"
                                wire:confirm="Restart API service?" />
                            <x-mary-button label="All Services" icon="o-arrow-path"
                                wire:click="restartServices" :disabled="!$sshConnected"
                                class="btn-warning btn-sm" spinner="restartServices"
                                wire:confirm="Restart all services? This will briefly interrupt connections." />
                        </div>
                    </div>
                </div>
                @if(!$sshConnected)
                    <p class="text-xs text-base-content/60 mt-4">Restart actions require an active SSH connection.</p>
                @endif
            </x-mary-card>
        </div>

        {{-- Software Version & Updates --}}
        <div class="space-y-2">
            <div class="text-sm uppercase font-semibold opacity-70">Software Version &amp; Updates</div>
            <x-mary-card class="bg-base-100 border border-base-300 shadow-sm rounded-none">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <dl class="space-y-1">
                        <div class="flex items-center justify-between gap-3 py-1.5 border-b border-base-200">
                            <dt class="text-[11px] uppercase opacity-60">Installed Version</dt>
                            <dd><x-mary-badge :value="'v' . $installedVersion" class="badge-primary badge-sm" /></dd>
                        </div>
                        @if($latestVersion)
                            <div class="flex items-center justify-between gap-3 py-1.5 border-b border-base-200">
                                <dt class="text-[11px] uppercase opacity-60">Latest Version</dt>
                                <dd>
                                    <x-mary-badge :value="'v' . $latestVersion"
                                        :class="($updateAvailable ? 'badge-success' : 'badge-ghost') . ' badge-sm'" />
                                </dd>
                            </div>
                        @endif
                        <div class="flex items-center justify-between gap-3 py-1.5">
                            <dt class="text-[11px] uppercase opacity-60">Update Status</dt>
                            <dd>
                                @if($updateAvailable)
                                    <x-mary-badge value="Update Available" class="badge-warning badge-sm" icon="o-arrow-down-tray" />
                                @elseif($latestVersion)
                                    <x-mary-badge value="Up to Date" class="badge-success badge-sm" icon="o-check-circle" />
                                @else
                                    <x-mary-badge value="Not Checked" class="badge-ghost badge-sm" />
                                @endif
                            </dd>
                        </div>
                        @if($updateMessage)
                            <div class="mt-2 p-3 bg-warning/10 border border-warning/30 rounded text-sm">
                                {{ $updateMessage }}
                            </div>
                        @endif
                    </dl>

                    <div class="flex flex-col justify-center gap-2">
                        <x-mary-button label="Check for Updates" icon="o-magnifying-glass"
                            wire:click="checkForUpdates" :disabled="$checkingUpdates || $applyingUpdate || !$sshConnected"
                            spinner="checkForUpdates" class="btn-primary btn-sm w-full" />

                        @if($updateAvailable)
                            <x-mary-button label="Apply Update" icon="o-arrow-down-tray"
                                wire:click="applyUpdate"
                                wire:confirm="Are you sure you want to update? The services will be restarted. A backup will be created automatically."
                                :disabled="$checkingUpdates || $applyingUpdate" spinner="applyUpdate"
                                class="btn-success btn-sm w-full" />
                        @endif

                        <p class="text-xs text-base-content/60 mt-1">Automatic backup is created before every update, and services are restarted with configuration preserved.</p>
                    </div>
                </div>
            </x-mary-card>
        </div>

        {{-- API Credentials & Sync --}}
        <div class="space-y-2">
            <div class="text-sm uppercase font-semibold opacity-70">API Credentials &amp; Sync</div>
            <x-mary-card class="bg-base-100 border border-base-300 shadow-sm rounded-none">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="space-y-3">
                        <div>
                            <label class="text-[11px] uppercase opacity-60">RADIUS Secret (clients.conf)</label>
                            <div class="flex items-center gap-2 mt-1">
                                <code class="flex-1 p-2 bg-base-200 rounded text-xs font-mono break-all">{{ $server->secret }}</code>
                                <x-mary-button icon="o-clipboard" tooltip="Copy" class="btn-ghost btn-xs"
                                    onclick="navigator.clipboard.writeText('{{ $server->secret }}')" />
                            </div>
                        </div>
                        <div>
                            <label class="text-[11px] uppercase opacity-60">API Auth Token (config.ini)</label>
                            <div class="flex items-center gap-2 mt-1">
                                <code class="flex-1 p-2 bg-base-200 rounded text-xs font-mono break-all">{{ $server->auth_token }}</code>
                                <x-mary-button icon="o-clipboard" tooltip="Copy" class="btn-ghost btn-xs"
                                    onclick="navigator.clipboard.writeText('{{ $server->auth_token }}')" />
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <p class="text-sm text-base-content/70">Push these credentials to the RADIUS server via SSH to update <span class="font-mono">config.ini</span> and <span class="font-mono">clients.conf</span> automatically.</p>
                        @if($sshConnected)
                            <div class="flex flex-wrap gap-2">
                                <x-mary-button label="Verify Token Sync" icon="o-shield-check"
                                    wire:click="verifyTokenSync" class="btn-info btn-sm" spinner="verifyTokenSync" />
                                <x-mary-button label="Push Credentials" icon="o-arrow-path"
                                    wire:click="configureCredentials" class="btn-success btn-sm" spinner="configureCredentials"
                                    wire:confirm="This will update config.ini and clients.conf, then restart services. Continue?" />
                            </div>
                            <p class="text-xs text-base-content/60">First verify token sync. If mismatched, push credentials to fix 401 errors.</p>
                        @else
                            <x-mary-button label="Push Credentials" icon="o-arrow-path" class="btn-success btn-sm" disabled />
                            <p class="text-xs text-error">SSH connection required</p>
                        @endif
                    </div>
                </div>
            </x-mary-card>
        </div>

        {{-- Service Logs --}}
        <div class="space-y-2">
            <div class="text-sm uppercase font-semibold opacity-70">Service Logs</div>
            @if($sshConnected && !empty($recentLogs))
                <x-mary-card class="bg-base-100 border border-base-300 shadow-sm rounded-none">
                    <x-mary-tabs wire:model="logTab">
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
                <x-mary-card class="bg-base-100 border border-base-300 shadow-sm rounded-none">
                    <div class="flex flex-col items-center justify-center py-8">
                        <x-mary-icon name="o-document" class="w-12 h-12 text-base-content/20 mb-3" />
                        <p class="text-sm text-base-content/60">Connect via SSH to view logs</p>
                    </div>
                </x-mary-card>
            @endif
        </div>

        {{-- Danger Zone --}}
        <x-mary-card class="bg-base-100 border border-error/30 shadow-sm rounded-none">
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-exclamation-triangle" class="w-5 h-5 text-error" />
                    <h3 class="text-lg font-semibold text-error">Danger Zone</h3>
                </div>

                <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                    <p class="text-sm text-base-content/70 max-w-xl">
                        Rebooting restarts the entire Ubuntu server. FreeRADIUS and the API service will be offline
                        for 2-3 minutes and all connected clients will be disconnected.
                    </p>
                    <x-mary-button label="Reboot Server" icon="o-power" wire:click="rebootServer"
                        :disabled="!$sshConnected" spinner="rebootServer" class="btn-error shrink-0"
                        wire:confirm="This will reboot the entire Ubuntu server. All services will be offline for 2-3 minutes. Continue?" />
                </div>
            </div>
        </x-mary-card>
    @endif
</div>
