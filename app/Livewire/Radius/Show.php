<?php

namespace App\Livewire\Radius;

use App\Models\RadiusServer;
use App\Services\RadiusServerSshService;
use App\Jobs\ConfigureRadiusServerJob;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
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
    
    public function mount(RadiusServer $server): void
    {
        $this->authorize('view_radius');
        $this->server = $server;
        $this->refreshStatus();
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
            
            if ($this->sshConnected) {
                // Get Service Status
                $serviceStatus = $sshService->getServiceStatus();
                $this->radiusServiceActive = $serviceStatus['freeradius']['active'] ?? false;
                $this->apiServiceActive = $serviceStatus['api']['active'] ?? false;
                
                // Get System Health
                $this->systemHealth = $sshService->getSystemHealth();
                
                // Get Recent Logs
                $this->recentLogs = $sshService->getRecentLogs(50);
            }
            
            // Get User Count from Database
            $this->totalUsers = $this->getTotalUsers();
            
            $this->lastChecked = now()->format('Y-m-d H:i:s');
            
        } catch (\Exception $e) {
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

    public function reconfigureServer(): void
    {
        try {
            // Generate new secrets
            $sharedSecret = Str::random(32);
            $authToken = Str::random(64);
            
            // Update server
            $this->server->update([
                'secret' => $sharedSecret,
                'auth_token' => $authToken,
                'installation_status' => 'configuring',
            ]);
            
            // Dispatch configuration job
            ConfigureRadiusServerJob::dispatch($this->server, $sharedSecret, $authToken);
            
            $this->success('Reconfiguration job dispatched! Secrets will be configured via SSH.');
            $this->refreshStatus();
            
        } catch (\Exception $e) {
            $this->error('Failed to dispatch reconfiguration job: ' . $e->getMessage());
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
