<?php

namespace App\Services;

use App\Models\RadiusServer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class LinodeService
{
    protected string $apiToken;
    protected string $apiUrl = 'https://api.linode.com/v4';

    public function __construct()
    {
        $this->apiToken = config('services.linode.api_token');
        
        if (!$this->apiToken) {
            throw new Exception('Linode API token not configured. Please add LINODE_API_TOKEN to your .env file.');
        }
    }

    /**
     * Provision a new RADIUS server on Linode
     */
    public function provisionServer(RadiusServer $server): void
    {
        try {
            // Update status to creating
            $server->update([
                'installation_status' => 'creating',
                'installation_log' => 'Starting Linode instance creation...',
            ]);

            // Generate random root password for Linode instance
            $rootPassword = $this->generateSecurePassword();

            // Create Linode instance
            $linodeData = $this->createLinodeInstance([
                'label' => $server->linode_label,
                'region' => $server->linode_region,
                'type' => $server->linode_plan,
                'image' => $server->linode_image,
                'root_pass' => $rootPassword,
                'authorized_keys' => [], // Can add SSH keys here
                'backups_enabled' => false,
                'private_ip' => false,
            ]);

            // Update server with Linode details
            $server->update([
                'linode_node_id' => $linodeData['id'],
                'linode_ipv4' => $linodeData['ipv4'][0] ?? null,
                'linode_ipv6' => $linodeData['ipv6'] ?? null,
                'host' => $linodeData['ipv4'][0] ?? null,
                'ssh_password' => $rootPassword,
                'installation_status' => 'installing',
                'installation_log' => "Linode instance created. ID: {$linodeData['id']}, IP: {$linodeData['ipv4'][0]}\nWaiting for instance to boot...",
            ]);

            // Wait for instance to be running (poll status)
            $this->waitForInstanceRunning($server, $linodeData['id']);

            // Install FreeRADIUS
            $this->installFreeRadius($server);

        } catch (Exception $e) {
            Log::error('Linode provisioning failed', [
                'server_id' => $server->id,
                'error' => $e->getMessage(),
            ]);

            $server->update([
                'installation_status' => 'failed',
                'installation_log' => $server->installation_log . "\n\nError: " . $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Create a new Linode instance
     */
    protected function createLinodeInstance(array $data): array
    {
        $response = Http::withToken($this->apiToken)
            ->post("{$this->apiUrl}/linode/instances", $data);

        if (!$response->successful()) {
            throw new Exception("Failed to create Linode instance: " . $response->body());
        }

        return $response->json();
    }

    /**
     * Wait for Linode instance to be running
     */
    protected function waitForInstanceRunning(RadiusServer $server, int $linodeId, int $maxAttempts = 30): void
    {
        $attempts = 0;
        
        while ($attempts < $maxAttempts) {
            $status = $this->getLinodeStatus($linodeId);
            
            if ($status === 'running') {
                $server->update([
                    'installation_log' => $server->installation_log . "\nInstance is running. Waiting 30 seconds for SSH to be ready...",
                ]);
                
                // Wait additional time for SSH to be ready
                sleep(30);
                return;
            }

            $attempts++;
            sleep(10); // Wait 10 seconds between checks
        }

        throw new Exception('Instance did not start within expected time');
    }

    /**
     * Get Linode instance status
     */
    protected function getLinodeStatus(int $linodeId): string
    {
        $response = Http::withToken($this->apiToken)
            ->get("{$this->apiUrl}/linode/instances/{$linodeId}");

        if (!$response->successful()) {
            throw new Exception("Failed to get Linode status: " . $response->body());
        }

        return $response->json()['status'] ?? 'unknown';
    }

    /**
     * Install FreeRADIUS on the server via SSH
     */
    protected function installFreeRadius(RadiusServer $server): void
    {
        $server->update([
            'installation_log' => $server->installation_log . "\n\nStarting FreeRADIUS installation...",
        ]);

        try {
            // Installation script
            $installScript = $this->getFreeRadiusInstallScript($server);

            // Execute via SSH
            $output = $this->executeSSHCommand($server, $installScript);

            $server->update([
                'installation_status' => 'completed',
                'installation_log' => $server->installation_log . "\n\nFreeRADIUS installed successfully!\n\nInstallation Output:\n" . $output,
                'installed_at' => now(),
            ]);

        } catch (Exception $e) {
            throw new Exception('FreeRADIUS installation failed: ' . $e->getMessage());
        }
    }

    /**
     * Execute SSH command on remote server
     */
    protected function executeSSHCommand(RadiusServer $server, string $command): string
    {
        $host = $server->host;
        $port = $server->ssh_port;
        $username = $server->ssh_username;
        $password = $server->ssh_password;

        if (!$host) {
            throw new Exception('Server host not set');
        }

        // Use phpseclib for SSH connection
        if (!class_exists('\phpseclib3\Net\SSH2')) {
            throw new Exception('phpseclib3 not installed. Run: composer require phpseclib/phpseclib');
        }

        $ssh = new \phpseclib3\Net\SSH2($host, $port);

        if ($server->ssh_private_key) {
            // Key-based authentication
            $key = \phpseclib3\Crypt\PublicKeyLoader::load($server->ssh_private_key);
            if (!$ssh->login($username, $key)) {
                throw new Exception('SSH authentication failed with private key');
            }
        } elseif ($password) {
            // Password-based authentication
            if (!$ssh->login($username, $password)) {
                throw new Exception('SSH authentication failed with password');
            }
        } else {
            throw new Exception('No SSH authentication method provided');
        }

        $output = $ssh->exec($command);
        
        if ($ssh->getExitStatus() !== 0) {
            throw new Exception("Command execution failed: " . $output);
        }

        return $output;
    }

    /**
     * Get FreeRADIUS installation script
     */
    protected function getFreeRadiusInstallScript(RadiusServer $server): string
    {
        $secret = $server->secret;
        $authPort = $server->auth_port;
        $acctPort = $server->acct_port;

        return <<<BASH
#!/bin/bash
set -e

# Update system
apt-get update
apt-get upgrade -y

# Install FreeRADIUS
DEBIAN_FRONTEND=noninteractive apt-get install -y freeradius freeradius-utils

# Stop FreeRADIUS service
systemctl stop freeradius

# Configure client (MikroTik)
cat > /etc/freeradius/3.0/clients.conf << 'EOF'
client localhost {
    ipaddr = 127.0.0.1
    secret = {$secret}
}

client mikrotik {
    ipaddr = 0.0.0.0/0
    secret = {$secret}
    shortname = mikrotik
    nastype = other
}
EOF

# Configure ports if not default
if [ "{$authPort}" != "1812" ] || [ "{$acctPort}" != "1813" ]; then
    sed -i "s/port = 1812/port = {$authPort}/" /etc/freeradius/3.0/sites-enabled/default
    sed -i "s/port = 1813/port = {$acctPort}/" /etc/freeradius/3.0/sites-enabled/default
fi

# Enable FreeRADIUS service
systemctl enable freeradius
systemctl start freeradius

# Configure firewall
ufw allow {$authPort}/udp
ufw allow {$acctPort}/udp
ufw allow 22/tcp
ufw --force enable

# Test configuration
freeradius -XC

echo "FreeRADIUS installation completed successfully!"
BASH;
    }

    /**
     * Delete Linode instance
     */
    public function deleteLinodeInstance(RadiusServer $server): bool
    {
        if (!$server->linode_node_id) {
            return false;
        }

        try {
            $response = Http::withToken($this->apiToken)
                ->delete("{$this->apiUrl}/linode/instances/{$server->linode_node_id}");

            return $response->successful();
        } catch (Exception $e) {
            Log::error('Failed to delete Linode instance', [
                'server_id' => $server->id,
                'linode_id' => $server->linode_node_id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get Linode instance details
     */
    public function getLinodeDetails(int $linodeId): ?array
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->get("{$this->apiUrl}/linode/instances/{$linodeId}");

            if ($response->successful()) {
                return $response->json();
            }
        } catch (Exception $e) {
            Log::error('Failed to get Linode details', [
                'linode_id' => $linodeId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Restart Linode instance
     */
    public function restartLinodeInstance(RadiusServer $server): bool
    {
        if (!$server->linode_node_id) {
            return false;
        }

        try {
            $response = Http::withToken($this->apiToken)
                ->post("{$this->apiUrl}/linode/instances/{$server->linode_node_id}/reboot");

            return $response->successful();
        } catch (Exception $e) {
            Log::error('Failed to restart Linode instance', [
                'server_id' => $server->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Generate secure random password
     */
    protected function generateSecurePassword(int $length = 24): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
        $password = '';
        $max = strlen($chars) - 1;

        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $max)];
        }

        return $password;
    }

    /**
     * Test RADIUS server connection
     */
    public function testConnection(RadiusServer $server): bool
    {
        // This would require radclient or similar tool
        // For now, we'll just check if SSH is accessible
        try {
            $this->executeSSHCommand($server, 'echo "Connection test"');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
