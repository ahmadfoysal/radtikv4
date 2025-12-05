<?php

namespace App\RadiusServer;

use App\Models\RadiusServer;
use Illuminate\Support\Facades\Log;
use phpseclib3\Net\SSH2;
use RuntimeException;
use Throwable;

class RadiusServerClient
{
    protected SSH2 $ssh;

    protected bool $connected = false;

    public function __construct(
        protected RadiusServer $server,
    ) {}

    /**
     * Test SSH connection to the RADIUS server.
     */
    public function testConnection(): bool
    {
        try {
            $this->connect();

            return true;
        } catch (Throwable $e) {
            Log::error('RadiusServerClient: Connection test failed', [
                'server_id' => $this->server->id,
                'host' => $this->server->host,
                'error' => $e->getMessage(),
            ]);

            return false;
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Run a command on the RADIUS server via SSH.
     */
    public function runCommand(string $command): string
    {
        try {
            $this->connect();

            $output = $this->ssh->exec($command);

            if ($output === false) {
                throw new RuntimeException('Command execution failed');
            }

            Log::debug('RadiusServerClient: Command executed', [
                'server_id' => $this->server->id,
                'command' => $command,
            ]);

            return $output;
        } catch (Throwable $e) {
            Log::error('RadiusServerClient: Command execution failed', [
                'server_id' => $this->server->id,
                'host' => $this->server->host,
                'command' => $command,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Failed to execute command: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Reload FreeRADIUS service to apply configuration changes.
     */
    public function reloadRadius(): void
    {
        try {
            $this->connect();

            // Try systemctl first, fallback to service command
            $output = $this->ssh->exec('sudo systemctl reload freeradius 2>/dev/null || sudo service freeradius reload');

            Log::info('RadiusServerClient: FreeRADIUS reloaded', [
                'server_id' => $this->server->id,
                'host' => $this->server->host,
                'output' => trim($output),
            ]);
        } catch (Throwable $e) {
            Log::error('RadiusServerClient: Failed to reload FreeRADIUS', [
                'server_id' => $this->server->id,
                'host' => $this->server->host,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Failed to reload FreeRADIUS: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Add a user to FreeRADIUS users file.
     *
     * @param  array  $attributes  Array containing user data (username, password, profile, etc.)
     */
    public function addUser(array $attributes): void
    {
        try {
            $this->connect();

            $username = $attributes['username'] ?? null;
            $password = $attributes['password'] ?? null;

            if (! $username || ! $password) {
                throw new RuntimeException('Username and password are required');
            }

            // Build the user entry for FreeRADIUS users file
            $userEntry = $this->buildUserEntry($attributes);

            // Escape single quotes for shell
            $escapedEntry = str_replace("'", "'\\''", $userEntry);

            // Append to users file (default FreeRADIUS location)
            $command = "echo '{$escapedEntry}' | sudo tee -a /etc/freeradius/3.0/users > /dev/null";
            $this->ssh->exec($command);

            Log::info('RadiusServerClient: User added to FreeRADIUS', [
                'server_id' => $this->server->id,
                'username' => $username,
            ]);
        } catch (Throwable $e) {
            Log::error('RadiusServerClient: Failed to add user', [
                'server_id' => $this->server->id,
                'host' => $this->server->host,
                'username' => $attributes['username'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Failed to add user: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Remove a user from FreeRADIUS users file.
     */
    public function removeUser(string $username): void
    {
        try {
            $this->connect();

            // Escape username for sed pattern
            $escapedUsername = preg_quote($username, '/');

            // Remove user entry from users file using sed
            // This removes the user line and any following reply attributes until blank line
            $command = "sudo sed -i '/^{$escapedUsername}[[:space:]]/,/^$/d' /etc/freeradius/3.0/users";
            $this->ssh->exec($command);

            Log::info('RadiusServerClient: User removed from FreeRADIUS', [
                'server_id' => $this->server->id,
                'username' => $username,
            ]);
        } catch (Throwable $e) {
            Log::error('RadiusServerClient: Failed to remove user', [
                'server_id' => $this->server->id,
                'host' => $this->server->host,
                'username' => $username,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Failed to remove user: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Connect to the RADIUS server via SSH.
     */
    protected function connect(): void
    {
        if ($this->connected) {
            return;
        }

        $host = $this->server->host;
        $port = $this->server->ssh_port ?: 22;
        $username = $this->server->ssh_username ?: $this->server->username;
        $password = $this->server->ssh_password ?: $this->server->password;
        $authType = $this->server->ssh_auth_type ?: 'password';

        $this->ssh = new SSH2($host, $port);

        if ($authType === 'key') {
            // Key-based authentication - not implemented in this version
            throw new RuntimeException('Key-based authentication is not yet implemented');
        }

        if (! $this->ssh->login($username, $password)) {
            throw new RuntimeException("SSH authentication failed for {$username}@{$host}:{$port}");
        }

        $this->connected = true;

        Log::debug('RadiusServerClient: SSH connection established', [
            'server_id' => $this->server->id,
            'host' => $host,
            'port' => $port,
        ]);
    }

    /**
     * Disconnect from the RADIUS server.
     */
    protected function disconnect(): void
    {
        if ($this->connected && isset($this->ssh)) {
            $this->ssh->disconnect();
            $this->connected = false;
        }
    }

    /**
     * Build a FreeRADIUS user entry from attributes.
     */
    protected function buildUserEntry(array $attributes): string
    {
        $username = $attributes['username'];
        $password = $attributes['password'];
        $profile = $attributes['profile'] ?? null;

        // Build the check items line
        $entry = "{$username} Cleartext-Password := \"{$password}\"";

        // Add profile/group if specified
        if ($profile) {
            $entry .= "\n\tUser-Profile := \"{$profile}\"";
        }

        // Add any additional reply attributes
        if (isset($attributes['reply_items']) && is_array($attributes['reply_items'])) {
            foreach ($attributes['reply_items'] as $attr => $value) {
                $entry .= "\n\t{$attr} := \"{$value}\"";
            }
        }

        // Add blank line to separate entries
        $entry .= "\n";

        return $entry;
    }

    /**
     * Destructor to ensure disconnection.
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
