<?php

namespace App\Services;

use App\Models\RadiusServer;
use Exception;
use Illuminate\Support\Facades\Log;
use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;

/**
 * Master SSH connection service for RADIUS servers
 * Handles all SSH operations including configuration, status checks, and management
 */
class RadiusServerSshService
{
    protected RadiusServer $server;
    protected ?SSH2 $connection = null;

    public function __construct(RadiusServer $server)
    {
        $this->server = $server;
    }

    /**
     * Establish SSH connection to RADIUS server
     */
    public function connect(): SSH2
    {
        if ($this->connection && $this->connection->isConnected()) {
            return $this->connection;
        }

        $host = $this->server->host ?? $this->server->linode_ipv4;
        
        if (!$host) {
            throw new Exception('RADIUS server host is not configured');
        }

        try {
            $this->connection = new SSH2($host, $this->server->ssh_port ?? 22);

            // Authenticate
            if ($this->server->ssh_private_key) {
                $key = PublicKeyLoader::load($this->server->ssh_private_key);
                if (!$this->connection->login($this->server->ssh_username, $key)) {
                    throw new Exception('SSH authentication failed (private key)');
                }
            } elseif ($this->server->ssh_password) {
                if (!$this->connection->login($this->server->ssh_username, $this->server->ssh_password)) {
                    throw new Exception('SSH authentication failed (password)');
                }
            } else {
                throw new Exception('No SSH authentication credentials configured');
            }

            return $this->connection;
        } catch (Exception $e) {
            Log::error('SSH connection to RADIUS server failed', [
                'server_id' => $this->server->id,
                'host' => $host,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Execute a command on the RADIUS server
     */
    public function execute(string $command): string
    {
        $ssh = $this->connect();
        $output = $ssh->exec($command);
        
        Log::debug('SSH command executed', [
            'server_id' => $this->server->id,
            'command' => $command,
            'output' => $output,
        ]);

        return $output;
    }

    /**
     * Test SSH connectivity
     */
    public function testConnection(): array
    {
        try {
            $ssh = $this->connect();
            $output = $ssh->exec('echo "connection_test_successful"');
            
            return [
                'success' => str_contains($output, 'connection_test_successful'),
                'message' => 'SSH connection successful',
                'latency' => $ssh->getLastError() ? null : 'Connected',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'SSH connection failed: ' . $e->getMessage(),
                'latency' => null,
            ];
        }
    }

    /**
     * Configure shared secret and auth token on RADIUS server
     */
    public function configureSecrets(string $sharedSecret, string $authToken): array
    {
        try {
            $ssh = $this->connect();

            // Path to config file
            $configPath = '/opt/radtik-radius/scripts/config.ini';

            // Update auth_token in config.ini
            $command = sprintf(
                "sudo sed -i 's/auth_token = .*/auth_token = %s/' %s",
                escapeshellarg($authToken),
                $configPath
            );
            $this->execute($command);

            // Update MikroTik clients.conf with shared secret
            $clientsPath = '/opt/radtik-radius/clients.conf';
            
            // Backup existing clients.conf
            $this->execute("sudo cp $clientsPath $clientsPath.bak");

            // Update secret in clients.conf
            $updateClientSecret = sprintf(
                "sudo sed -i 's/secret = .*/secret = %s/' %s",
                escapeshellarg($sharedSecret),
                $clientsPath
            );
            $this->execute($updateClientSecret);

            // Copy to FreeRADIUS directory
            $this->execute("sudo cp $clientsPath /etc/freeradius/3.0/clients.conf");

            // Restart services
            $this->execute('sudo systemctl restart radtik-radius-api');
            $this->execute('sudo systemctl restart freeradius');

            // Wait for services to start
            sleep(2);

            // Verify services are running
            $apiStatus = $this->execute('sudo systemctl is-active radtik-radius-api');
            $radiusStatus = $this->execute('sudo systemctl is-active freeradius');

            $allActive = (trim($apiStatus) === 'active' && trim($radiusStatus) === 'active');

            return [
                'success' => $allActive,
                'message' => $allActive ? 'Configuration applied successfully' : 'Configuration applied but services may need attention',
                'api_status' => trim($apiStatus),
                'radius_status' => trim($radiusStatus),
            ];

        } catch (Exception $e) {
            Log::error('Failed to configure RADIUS server secrets', [
                'server_id' => $this->server->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Configuration failed: ' . $e->getMessage(),
                'api_status' => 'unknown',
                'radius_status' => 'unknown',
            ];
        }
    }

    /**
     * Get RADIUS service status
     */
    public function getServiceStatus(): array
    {
        try {
            $ssh = $this->connect();

            // Check FreeRADIUS with detailed status
            $radiusStatus = trim($this->execute('sudo systemctl is-active freeradius 2>&1'));
            $radiusEnabled = trim($this->execute('sudo systemctl is-enabled freeradius 2>&1'));
            
            // Get detailed status output for debugging
            $radiusDetail = trim($this->execute('sudo systemctl status freeradius --no-pager | head -3'));

            // Check API Server with detailed status
            $apiStatus = trim($this->execute('sudo systemctl is-active radtik-radius-api 2>&1'));
            $apiEnabled = trim($this->execute('sudo systemctl is-enabled radtik-radius-api 2>&1'));
            
            // Get detailed status output for debugging
            $apiDetail = trim($this->execute('sudo systemctl status radtik-radius-api --no-pager | head -3'));

            // Check if port 5000 is listening
            $portCheck = $this->execute('sudo ss -tuln | grep ":5000 " 2>&1');
            $apiListening = !empty(trim($portCheck));

            Log::debug('RADIUS Service Status Check', [
                'radius_status' => $radiusStatus,
                'radius_enabled' => $radiusEnabled,
                'radius_detail' => $radiusDetail,
                'api_status' => $apiStatus,
                'api_enabled' => $apiEnabled,
                'api_detail' => $apiDetail,
                'port_5000_listening' => $apiListening,
            ]);

            return [
                'success' => true,
                'freeradius' => [
                    'status' => $radiusStatus,
                    'enabled' => $radiusEnabled === 'enabled',
                    'active' => $radiusStatus === 'active',
                    'running' => $radiusStatus === 'active',
                    'detail' => $radiusDetail,
                ],
                'api' => [
                    'status' => $apiStatus,
                    'enabled' => $apiEnabled === 'enabled',
                    'active' => $apiStatus === 'active',
                    'running' => $apiStatus === 'active',
                    'listening' => $apiListening,
                    'detail' => $apiDetail,
                ],
            ];
        } catch (Exception $e) {
            Log::error('Failed to get service status', [
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get system health information
     */
    public function getSystemHealth(): array
    {
        try {
            $ssh = $this->connect();

            // CPU usage
            $cpuUsage = trim($this->execute("top -bn1 | grep 'Cpu(s)' | sed 's/.*, *\\([0-9.]*\\)%* id.*/\\1/' | awk '{print 100 - $1}'"));

            // Memory usage
            $memInfo = $this->execute("free -m | grep Mem");
            preg_match('/\s+(\d+)\s+(\d+)/', $memInfo, $memMatches);
            $memTotal = $memMatches[1] ?? 0;
            $memUsed = $memMatches[2] ?? 0;
            $memPercent = $memTotal > 0 ? round(($memUsed / $memTotal) * 100, 2) : 0;

            // Disk usage
            $diskUsage = trim($this->execute("df -h / | tail -1 | awk '{print $5}' | sed 's/%//'"));

            // Uptime
            $uptime = trim($this->execute("uptime -p"));

            // Load average
            $loadAvg = trim($this->execute("cat /proc/loadavg | awk '{print $1, $2, $3}'"));

            // Database size
            $dbSize = trim($this->execute("du -sh /etc/freeradius/3.0/sqlite/radius.db 2>/dev/null | awk '{print $1}'"));

            // Count RADIUS users
            $userCount = trim($this->execute("sudo sqlite3 /etc/freeradius/3.0/sqlite/radius.db 'SELECT COUNT(*) FROM radcheck;' 2>/dev/null"));

            return [
                'success' => true,
                'cpu_usage' => (float) $cpuUsage,
                'memory_total' => (int) $memTotal,
                'memory_used' => (int) $memUsed,
                'memory_percent' => (float) $memPercent,
                'disk_usage_percent' => (int) $diskUsage,
                'uptime' => $uptime,
                'load_average' => $loadAvg,
                'database_size' => $dbSize ?: 'N/A',
                'radius_users' => (int) $userCount,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Test RADIUS authentication
     */
    public function testRadiusAuth(string $username = 'test', string $password = 'test'): array
    {
        try {
            $ssh = $this->connect();
            
            $command = sprintf(
                'radtest %s %s localhost 0 testing123 2>&1',
                escapeshellarg($username),
                escapeshellarg($password)
            );

            $output = $this->execute($command);

            $success = str_contains($output, 'Access-Accept') || str_contains($output, 'Access-Reject');

            return [
                'success' => $success,
                'output' => $output,
                'can_communicate' => $success,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'output' => $e->getMessage(),
                'can_communicate' => false,
            ];
        }
    }

    /**
     * Get recent RADIUS logs
     */
    public function getRecentLogs(int $lines = 50): array
    {
        try {
            $ssh = $this->connect();

            $radiusLog = $this->execute("sudo journalctl -u freeradius -n $lines --no-pager");
            $apiLog = $this->execute("sudo journalctl -u radtik-radius-api -n $lines --no-pager");

            return [
                'success' => true,
                'freeradius_log' => $radiusLog,
                'api_server_log' => $apiLog,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Restart RADIUS services
     */
    public function restartServices(): array
    {
        try {
            $ssh = $this->connect();

            $this->execute('sudo systemctl restart freeradius');
            $this->execute('sudo systemctl restart radtik-radius-api');

            sleep(3); // Wait for services to start

            $status = $this->getServiceStatus();

            return [
                'success' => true,
                'message' => 'Services restarted',
                'status' => $status,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to restart services: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Install or reinstall FreeRADIUS and API server
     */
    public function installRadiusServer(string $repoUrl = 'https://github.com/ahmadfoysal/radtik-radius.git', string $branch = 'main'): array
    {
        try {
            $ssh = $this->connect();

            Log::info('Starting RADIUS server installation', [
                'server_id' => $this->server->id,
                'repo_url' => $repoUrl,
                'branch' => $branch,
            ]);

            // Download and execute bootstrap installer
            $installCommand = sprintf(
                "curl -fsSL '%s/raw/%s/radtik-radius/bootstrap-install.sh' | sudo RADTIK_REPO_URL='%s' RADTIK_BRANCH='%s' bash",
                $repoUrl,
                $branch,
                $repoUrl,
                $branch
            );

            // Execute installation in background and return immediately
            $output = $this->execute($installCommand . ' 2>&1 &');

            Log::info('Installation command executed', [
                'server_id' => $this->server->id,
                'output' => $output,
            ]);

            return [
                'success' => true,
                'message' => 'Installation started. This may take 5-10 minutes.',
                'output' => $output,
            ];
        } catch (Exception $e) {
            Log::error('Failed to install RADIUS server', [
                'server_id' => $this->server->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Installation failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if RADIUS server installation is complete
     */
    public function checkInstallationStatus(): array
    {
        try {
            $ssh = $this->connect();

            // Check if FreeRADIUS is installed
            $radiusInstalled = trim($this->execute('which freeradius')) !== '';
            
            // Check if API directory exists
            $apiDirExists = trim($this->execute('test -d /opt/radtik-radius && echo "exists" || echo "missing"')) === 'exists';
            
            // Check service status
            $serviceStatus = $this->getServiceStatus();
            
            $allInstalled = $radiusInstalled && $apiDirExists && 
                           ($serviceStatus['freeradius']['active'] ?? false) && 
                           ($serviceStatus['api']['active'] ?? false);

            return [
                'success' => true,
                'installed' => $allInstalled,
                'radius_installed' => $radiusInstalled,
                'api_dir_exists' => $apiDirExists,
                'services' => $serviceStatus,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'installed' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Close SSH connection
     */
    public function disconnect(): void
    {
        if ($this->connection && $this->connection->isConnected()) {
            $this->connection->disconnect();
        }
        $this->connection = null;
    }

    /**
     * Destructor - ensure connection is closed
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
