<?php

namespace App\Livewire\Radius;

use App\Models\RadiusServer;
use App\Services\RadiusServerSshService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\Attributes\On;
use Mary\Traits\Toast;

class Show extends Component
{
    use AuthorizesRequests, Toast;

    public RadiusServer $server;
    
    // Status Properties
    public bool $sshConnected = false;
    public bool $radiusServiceActive = false;
    public bool $apiServiceActive = false;
    public array $systemHealth = [];
    public int $totalUsers = 0;
    public array $recentLogs = [];
    public bool $loading = true;
    public string $lastChecked = '';
    
    // Update Properties
    public string $installedVersion = '';
    public string $latestVersion = '';
    public bool $updateAvailable = false;
    public bool $checkingUpdates = false;
    public bool $applyingUpdate = false;
    public string $updateMessage = '';
    public array $releaseNotes = [];
    
    // Tab State
    public string $selectedTab = 'overview';
    public string $logTab = 'freeradius';
    
    public function mount(RadiusServer $server): void
    {
        $this->authorize('view_radius');
        $this->server = $server;
        $this->refreshStatus();
        $this->getInstalledVersion();
    }

    #[On('refresh-status')]
    public function refreshStatus(): void
    {
        $this->loading = true;
        
        try {
            $sshService = new RadiusServerSshService($this->server);
            
            // Test SSH Connection
            $connectionResult = $sshService->testConnection();
            $this->sshConnected = $connectionResult['success'] ?? false;
            
            Log::info('RADIUS Show: Connection test', [
                'server_id' => $this->server->id,
                'connected' => $this->sshConnected,
                'connection_result' => $connectionResult,
            ]);
            
            if ($this->sshConnected) {
                // Get Service Status
                $serviceStatus = $sshService->getServiceStatus();
                
                Log::info('RADIUS Show: Service status retrieved', [
                    'server_id' => $this->server->id,
                    'service_status' => $serviceStatus,
                ]);
                
                $this->radiusServiceActive = $serviceStatus['freeradius']['active'] ?? false;
                $this->apiServiceActive = $serviceStatus['api']['active'] ?? false;
                
                Log::info('RADIUS Show: Active status set', [
                    'server_id' => $this->server->id,
                    'radiusServiceActive' => $this->radiusServiceActive,
                    'apiServiceActive' => $this->apiServiceActive,
                ]);
                
                // Get System Health
                $this->systemHealth = $sshService->getSystemHealth();
                
                // Get Recent Logs
                $this->recentLogs = $sshService->getRecentLogs(50);
            } else {
                Log::warning('RADIUS Show: SSH connection failed', [
                    'server_id' => $this->server->id,
                    'host' => $this->server->host,
                ]);
            }
            
            // Get User Count from Database
            $this->totalUsers = $this->getTotalUsers();
            
            $this->lastChecked = now()->format('Y-m-d H:i:s');
            
        } catch (\Exception $e) {
            Log::error('RADIUS Show: Error during refresh', [
                'server_id' => $this->server->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error('Failed to fetch server status: ' . $e->getMessage());
        } finally {
            $this->loading = false;
        }
    }

    public function testConnection(): void
    {
        try {
            $sshService = new RadiusServerSshService($this->server);
            $result = $sshService->testConnection();
            
            if ($result['success']) {
                $this->success('SSH connection successful!');
                $this->refreshStatus();
            } else {
                $this->error('SSH connection failed. Please check your credentials.');
            }
        } catch (\Exception $e) {
            $this->error('Connection test failed: ' . $e->getMessage());
        }
    }

    public function restartServices(): void
    {
        try {
            $sshService = new RadiusServerSshService($this->server);
            $result = $sshService->restartServices();
            
            if ($result['success']) {
                $this->success('Services restarted successfully!');
                sleep(2); // Give services time to start
                $this->refreshStatus();
            } else {
                $this->error('Failed to restart services: ' . ($result['error'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            $this->error('Failed to restart services: ' . $e->getMessage());
        }
    }

    public function restartRadiusService(): void
    {
        try {
            $sshService = new RadiusServerSshService($this->server);
            $result = $sshService->execute('systemctl restart freeradius');
            
            if ($result !== false) {
                $this->success('FreeRADIUS service restarted successfully!');
                sleep(2);
                $this->refreshStatus();
            } else {
                $this->error('Failed to restart FreeRADIUS service');
            }
        } catch (\Exception $e) {
            $this->error('Failed to restart FreeRADIUS: ' . $e->getMessage());
        }
    }

    public function restartApiService(): void
    {
        try {
            $sshService = new RadiusServerSshService($this->server);
            $result = $sshService->execute('systemctl restart radtik-radius-api');
            
            if ($result !== false) {
                $this->success('API service restarted successfully!');
                sleep(2);
                $this->refreshStatus();
            } else {
                $this->error('Failed to restart API service');
            }
        } catch (\Exception $e) {
            $this->error('Failed to restart API service: ' . $e->getMessage());
        }
    }

    public function rebootServer(): void
    {
        try {
            $sshService = new RadiusServerSshService($this->server);
            $result = $sshService->execute('reboot');
            
            $this->warning('Server reboot command sent. Server will be offline for a few minutes.');
        } catch (\Exception $e) {
            $this->error('Failed to reboot server: ' . $e->getMessage());
        }
    }

    public function testRadiusAuth(): void
    {
        try {
            $sshService = new RadiusServerSshService($this->server);
            $result = $sshService->testRadiusAuth('testuser', 'testpass');
            
            if ($result['success']) {
                $this->success('RADIUS authentication test completed successfully!');
            } else {
                $this->warning('RADIUS auth test failed (expected for non-existent user): ' . ($result['error'] ?? ''));
            }
        } catch (\Exception $e) {
            $this->error('Auth test failed: ' . $e->getMessage());
        }
    }

    public function configureCredentials(): void
    {
        try {
            $sshService = new RadiusServerSshService($this->server);
            
            $this->info('Configuring RADIUS server credentials via SSH...');
            
            $result = $sshService->configureSecrets(
                $this->server->secret,
                $this->server->auth_token
            );
            
            if ($result['success']) {
                $this->success('Credentials configured successfully! Python API and FreeRADIUS restarted.');
                sleep(2);
                $this->refreshStatus();
            } else {
                $this->error('Configuration failed: ' . ($result['message'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            $this->error('Failed to configure credentials: ' . $e->getMessage());
        }
    }

    public function verifyTokenSync(): void
    {
        try {
            $sshService = new RadiusServerSshService($this->server);
            
            // Read the auth_token from the server's config.ini
            // Use awk to properly parse INI format and trim whitespace
            $command = "grep 'auth_token' /opt/radtik-radius/scripts/config.ini | awk -F' = ' '{print \$2}' | tr -d '\"'";
            $serverToken = trim($sshService->execute($command));
            
            $laravelToken = $this->server->auth_token;
            
            Log::info('Token verification', [
                'server_id' => $this->server->id,
                'laravel_token' => $laravelToken,
                'server_token' => $serverToken,
                'laravel_length' => strlen($laravelToken),
                'server_length' => strlen($serverToken),
                'match' => $serverToken === $laravelToken,
            ]);
            
            if ($serverToken === $laravelToken) {
                $this->success('✓ Tokens match! Server and Laravel are in sync.');
            } else {
                $this->warning(
                    "⚠️ Token mismatch detected!\n" .
                    "Laravel: " . substr($laravelToken, 0, 20) . "... (length: " . strlen($laravelToken) . ")\n" .
                    "Server: " . substr($serverToken, 0, 20) . "... (length: " . strlen($serverToken) . ")\n" .
                    "Click 'Push Credentials' to sync."
                );
            }
        } catch (\Exception $e) {
            $this->error('Failed to verify token: ' . $e->getMessage());
        }
    }

    private function getTotalUsers(): int
    {
        try {
            $sshService = new RadiusServerSshService($this->server);
            
            if (!$this->sshConnected) {
                return 0;
            }
            
            $command = "sqlite3 /etc/freeradius/3.0/sqlite/radius.db 'SELECT COUNT(*) FROM radcheck;'";
            $result = $sshService->execute($command);
            
            return (int) trim($result);
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function getInstalledVersion(): void
    {
        try {
            $sshService = new RadiusServerSshService($this->server);
            $result = $sshService->getInstalledVersion();
            
            if ($result['success']) {
                $this->installedVersion = $result['version'];
            } else {
                $this->installedVersion = 'Unknown';
            }
        } catch (\Exception $e) {
            $this->installedVersion = 'Unknown';
            Log::error('Failed to get installed version', [
                'server_id' => $this->server->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function checkForUpdates(): void
    {
        $this->checkingUpdates = true;
        $this->updateMessage = '';
        
        try {
            $sshService = new RadiusServerSshService($this->server);
            $result = $sshService->checkForUpdates();
            
            if ($result['success']) {
                $this->installedVersion = $result['installed_version'];
                $this->latestVersion = $result['latest_version'];
                $this->updateAvailable = $result['update_available'];
                
                if ($this->updateAvailable) {
                    $this->updateMessage = $result['message'];
                    $this->success("Update available: v{$this->installedVersion} → v{$this->latestVersion}");
                } else {
                    $this->info('You are running the latest version (' . $this->installedVersion . ')');
                }
            } else {
                $this->error('Failed to check for updates: ' . ($result['message'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            $this->error('Failed to check for updates: ' . $e->getMessage());
            Log::error('Check for updates failed', [
                'server_id' => $this->server->id,
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->checkingUpdates = false;
        }
    }

    public function applyUpdate(): void
    {
        $this->applyingUpdate = true;
        
        try {
            $sshService = new RadiusServerSshService($this->server);
            
            $this->info('Applying update... This may take a few minutes.');
            
            $result = $sshService->applyUpdate();
            
            if ($result['success']) {
                $this->success(
                    "Successfully updated from v{$result['old_version']} to v{$result['new_version']}! " .
                    "Backup saved at: {$result['backup_location']}"
                );
                
                // Refresh version info
                $this->getInstalledVersion();
                $this->updateAvailable = false;
                $this->latestVersion = $result['new_version'];
                
                // Refresh status to check services
                sleep(2);
                $this->refreshStatus();
            } else {
                $this->error('Update failed: ' . ($result['message'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            $this->error('Failed to apply update: ' . $e->getMessage());
            Log::error('Apply update failed', [
                'server_id' => $this->server->id,
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->applyingUpdate = false;
        }
    }

    public function getStatusBadgeClass(bool $active): string
    {
        return $active ? 'badge-success' : 'badge-error';
    }

    public function getStatusText(bool $active): string
    {
        return $active ? 'Active' : 'Inactive';
    }

    public function render()
    {
        return view('livewire.radius.show');
    }
}
