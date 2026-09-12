<?php

namespace App\Services;

use App\Models\RadiusServer;
use Exception;
use Illuminate\Support\Facades\Http;
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

            // Update auth_token in config.ini (without quotes - Python configparser doesn't need them)
            // Use double quotes for sed script to avoid single quote conflicts
            $escapedToken = addslashes($authToken);
            $command = "sudo sed -i \"s/^auth_token = .*/auth_token = $escapedToken/\" $configPath";
            $this->execute($command);

            // Update MikroTik clients.conf with shared secret
            $clientsPath = '/opt/radtik-radius/clients.conf';
            
            // Backup existing clients.conf
            $this->execute("sudo cp $clientsPath $clientsPath.bak");

            // Update secret ONLY in the "client radtik" block, not localhost
            // This uses sed with a range: /client radtik/,/}/ means "from 'client radtik' to the closing brace"
            // Then we replace the first occurrence of "secret = " within that range
            $escapedSecret = addslashes($sharedSecret);
            $updateClientSecret = "sudo sed -i '/^client radtik/,/^}/ s/secret = .*/secret = $escapedSecret/' $clientsPath";
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
     * Get "owner/repo" slug from the configured repository URL
     */
    protected function getRepoSlug(): string
    {
        $url = rtrim(config('app.radtik_repo_url', 'https://github.com/ahmadfoysal/radtik-radius.git'), '/');
        $url = preg_replace('/\.git$/', '', $url);

        return trim((string) parse_url($url, PHP_URL_PATH), '/');
    }

    /**
     * Get the commit currently installed on the RADIUS server.
     * Uses a marker file written by applyUpdate(); falls back to the local
     * git HEAD for installs that predate the marker file.
     */
    public function getInstalledVersion(): array
    {
        try {
            $marker = trim($this->execute('cat /opt/radtik-radius/.installed_commit 2>/dev/null'));

            if ($marker === '') {
                $marker = trim($this->execute('cd /opt/radtik-radius 2>/dev/null && git rev-parse HEAD 2>/dev/null'));
            }

            if ($marker === '') {
                return [
                    'success' => false,
                    'version' => null,
                    'message' => 'Installed version could not be determined',
                ];
            }

            return [
                'success' => true,
                'version' => $marker,
                'short_version' => substr($marker, 0, 7),
                'message' => 'Version retrieved successfully',
            ];
        } catch (Exception $e) {
            Log::error('Failed to get installed version', [
                'server_id' => $this->server->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'version' => null,
                'message' => 'Failed to retrieve version: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check for available updates by comparing the installed commit against
     * the latest commit pushed to the configured branch on GitHub.
     * This repository is not tagged with releases, so we track branch commits directly.
     */
    public function checkForUpdates(): array
    {
        try {
            $installedResult = $this->getInstalledVersion();
            if (!$installedResult['success']) {
                return $installedResult;
            }

            $installedCommit = $installedResult['version'];
            $branch = config('app.radtik_branch', 'main');
            $repoSlug = $this->getRepoSlug();

            $response = Http::withHeaders(['Accept' => 'application/vnd.github+json'])
                ->timeout(15)
                ->get("https://api.github.com/repos/{$repoSlug}/commits/{$branch}");

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Failed to fetch latest commit from GitHub: HTTP ' . $response->status(),
                    'installed_version' => $installedCommit,
                    'latest_version' => null,
                    'update_available' => false,
                ];
            }

            $latest = $response->json();
            $latestCommit = $latest['sha'] ?? null;

            if (!$latestCommit) {
                return [
                    'success' => false,
                    'message' => 'Unexpected response from GitHub API',
                    'installed_version' => $installedCommit,
                    'latest_version' => null,
                    'update_available' => false,
                ];
            }

            $updateAvailable = $installedCommit !== $latestCommit;

            return [
                'success' => true,
                'installed_version' => $installedCommit,
                'installed_short' => substr($installedCommit, 0, 7),
                'latest_version' => $latestCommit,
                'latest_short' => substr($latestCommit, 0, 7),
                'update_available' => $updateAvailable,
                'release_url' => "https://github.com/{$repoSlug}/commit/{$latestCommit}",
                'release_notes' => $latest['commit']['message'] ?? null,
                'published_at' => $latest['commit']['author']['date'] ?? null,
                'message' => $updateAvailable
                    ? 'Update available: ' . substr($installedCommit, 0, 7) . ' → ' . substr($latestCommit, 0, 7)
                    : 'You are running the latest version',
            ];
        } catch (Exception $e) {
            Log::error('Failed to check for updates', [
                'server_id' => $this->server->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to check for updates: ' . $e->getMessage(),
                'update_available' => false,
            ];
        }
    }

    /**
     * Pull the latest commit for the configured branch and deploy it, preserving
     * server-specific files (config.ini, clients.conf, live SQLite database).
     */
    public function applyUpdate(string $targetVersion = 'latest'): array
    {
        try {
            // First check if update is available
            $updateCheck = $this->checkForUpdates();
            if (!$updateCheck['success']) {
                return $updateCheck;
            }

            if (!$updateCheck['update_available'] && $targetVersion === 'latest') {
                return [
                    'success' => false,
                    'message' => 'No updates available. Already running ' . ($updateCheck['installed_short'] ?? $updateCheck['installed_version']),
                ];
            }

            $commit = $targetVersion === 'latest' ? $updateCheck['latest_version'] : $targetVersion;
            $repoSlug = $this->getRepoSlug();
            $repoName = substr($repoSlug, strpos($repoSlug, '/') + 1);

            Log::info('Starting radtik-radius update', [
                'server_id' => $this->server->id,
                'from_commit' => $updateCheck['installed_version'],
                'to_commit' => $commit,
            ]);

            // Create backup directory with timestamp
            $timestamp = date('Y-m-d_H-i-s');
            $backupDir = "/opt/radtik-radius-backup-{$timestamp}";
            $this->execute("sudo cp -r /opt/radtik-radius $backupDir");

            Log::info('Created backup', [
                'server_id' => $this->server->id,
                'backup_dir' => $backupDir,
            ]);

            // Preserve server-specific files that must never be overwritten by an update
            $protectedDir = "/tmp/radtik-radius-protected-{$timestamp}";
            $this->execute("mkdir -p $protectedDir/scripts $protectedDir/sqlite");
            $this->execute("sudo cp /opt/radtik-radius/scripts/config.ini $protectedDir/scripts/config.ini 2>/dev/null || true");
            $this->execute("sudo cp /opt/radtik-radius/clients.conf $protectedDir/clients.conf 2>/dev/null || true");
            $this->execute("sudo cp /opt/radtik-radius/sqlite/radius.db $protectedDir/sqlite/radius.db 2>/dev/null || true");

            // Download the exact commit that was checked, avoiding a race with new pushes
            $downloadUrl = "https://github.com/{$repoSlug}/archive/{$commit}.tar.gz";
            $tmpDir = "/tmp/radtik-radius-update-{$timestamp}";
            $this->execute("mkdir -p $tmpDir && cd $tmpDir && curl -fsSL -o update.tar.gz '$downloadUrl'");
            $this->execute("cd $tmpDir && tar -xzf update.tar.gz");

            // Copy files to installation directory
            $extractedDir = "$tmpDir/{$repoName}-{$commit}";
            $this->execute("sudo cp -r $extractedDir/* /opt/radtik-radius/");

            // Restore protected files
            $this->execute("sudo cp $protectedDir/scripts/config.ini /opt/radtik-radius/scripts/config.ini 2>/dev/null || true");
            $this->execute("sudo cp $protectedDir/clients.conf /opt/radtik-radius/clients.conf 2>/dev/null || true");
            $this->execute("sudo cp $protectedDir/sqlite/radius.db /opt/radtik-radius/sqlite/radius.db 2>/dev/null || true");

            // Record the installed commit so future checks compare against it
            $this->execute("echo '{$commit}' | sudo tee /opt/radtik-radius/.installed_commit > /dev/null");

            // Set proper permissions
            $this->execute('sudo chown -R root:root /opt/radtik-radius');
            $this->execute('sudo chown -R freerad:freerad /opt/radtik-radius/sqlite 2>/dev/null || true');
            $this->execute('sudo chmod +x /opt/radtik-radius/install.sh 2>/dev/null || true');
            $this->execute('sudo chmod +x /opt/radtik-radius/scripts/*.py 2>/dev/null || true');

            // Restart services
            $this->execute('sudo systemctl restart radtik-radius-api');
            $this->execute('sudo systemctl restart freeradius');

            // Wait for services to start
            sleep(3);

            // Verify services are running
            $apiStatus = trim($this->execute('sudo systemctl is-active radtik-radius-api'));
            $radiusStatus = trim($this->execute('sudo systemctl is-active freeradius'));

            // Cleanup temp directories
            $this->execute("rm -rf $tmpDir $protectedDir");

            $allActive = ($apiStatus === 'active' && $radiusStatus === 'active');

            Log::info('Update completed', [
                'server_id' => $this->server->id,
                'new_commit' => $commit,
                'api_status' => $apiStatus,
                'radius_status' => $radiusStatus,
                'backup_dir' => $backupDir,
            ]);

            return [
                'success' => $allActive,
                'message' => $allActive ?
                    'Successfully updated to ' . substr($commit, 0, 7) :
                    'Update completed but services need attention',
                'old_version' => $updateCheck['installed_version'],
                'new_version' => $commit,
                'backup_location' => $backupDir,
                'api_status' => $apiStatus,
                'radius_status' => $radiusStatus,
            ];

        } catch (Exception $e) {
            Log::error('Failed to apply update', [
                'server_id' => $this->server->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Update failed: ' . $e->getMessage(),
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
