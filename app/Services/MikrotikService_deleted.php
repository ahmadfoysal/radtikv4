<?php

namespace App\Services;

use App\Models\Router;
use RouterOS\Client;
use RouterOS\Query;
use Throwable;

class MikrotikService
{
    public function __construct(
        private int $timeout = 4,    // connect timeout (s)
        private int $socketTimeout = 3,  // read timeout (s)
        private int $attempts = 1,    // reconnect attempts
        private int $retryDelay = 0,  // seconds
    ) {}

    /** Build a RouterOS client using credentials from the Router model */
    protected function client(Router $router): Client
    {
        $host = $router->address;                  // prefer IP
        $port = (int) ($router->port ?: 8728);      // ensure correct field
        $useSSL = ($port === 8729) || (bool) ($router->use_tls ?? false);

        $options = [
            'host' => $host,
            'user' => $router->username,
            'pass' => $router->decryptedPassword(),
            'port' => $port,
            'timeout' => $this->timeout,
            'socket_timeout' => $this->socketTimeout,
            'attempts' => $this->attempts,
            'delay' => $this->retryDelay,
        ];

        if ($useSSL) {
            $options['ssl'] = true;
            // self-signed dev/edge cases; in prod prefer proper cert
            $options['ssl_context'] = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ]);
        }

        return new Client($options);
    }

    /** Quick TCP reachability pre-check to avoid long hangs */
    protected function reachable(string $host, int $port): bool
    {
        $errno = 0;
        $errstr = '';
        $fp = @fsockopen($host, $port, $errno, $errstr, 1.2); // ~1s
        if ($fp) {
            fclose($fp);

            return true;
        }

        return false;
    }

    /** Safe query wrapper with clear exceptions */
    protected function safeRead(Client $client, Query $query): array
    {
        try {
            $resp = $client->query($query)->read();

            return is_array($resp) ? $resp : [];
        } catch (Throwable $e) {
            // Bubble up a clean message; caller shows toast/logs
            $msg = $e->getMessage() ?: 'RouterOS read failed';
            throw new \RuntimeException($msg, 0, $e);
        }
    }

    /** --- Public APIs --- */

    /** /system/resource/print — CPU, memory, version, uptime, etc. */
    public function getRouterResource(Router $router): array
    {
        $client = $this->client($router);
        $data = $this->safeRead($client, new Query('/system/resource/print'));

        return $this->firstRow($data);
    }

    /** Hotspot profiles (proplist to keep payload small) */
    public function getHotspotProfiles(Router $router): array
    {
        $client = $this->client($router);
        $q = (new Query('/ip/hotspot/user/profile/print'))
            ->equal('.proplist', 'name,.id'); // slim response

        return $this->safeRead($client, $q);
    }

    /** Add hotspot user: name, password, profile (optional) */
    public function addHotspotUser(Router $router, string $name, string $password, ?string $profile = null): array
    {
        $client = $this->client($router);
        $q = (new Query('/ip/hotspot/user/add'))
            ->equal('name', $name)
            ->equal('password', $password);

        if ($profile) {
            $q->equal('profile', $profile);
        }

        return $this->safeRead($client, $q);
    }

    /** Enable/Disable hotspot user by name or .id */
    public function enableHotspotUser(Router $router, string $nameOrId): array
    {
        return $this->setHotspotUserDisabled($router, $nameOrId, false);
    }

    public function disableHotspotUser(Router $router, string $nameOrId): array
    {
        return $this->setHotspotUserDisabled($router, $nameOrId, true);
    }

    /** Remove an active hotspot session for given username or active .id */
    public function removeActiveUser(Router $router, string $usernameOrActiveId): array
    {
        $client = $this->client($router);
        $activeId = $this->resolveActiveUserId($client, $usernameOrActiveId);

        if (! $activeId) {
            return ['ok' => false, 'message' => 'Active user not found'];
        }

        return $this->safeRead(
            $client,
            (new Query('/ip/hotspot/active/remove'))->equal('.id', $activeId)
        );
    }

    /** List active users (slim proplist) */
    public function listActiveUsers(Router $router): array
    {
        $client = $this->client($router);
        $q = (new Query('/ip/hotspot/active/print'))
            ->equal('.proplist', 'user,mac-address,address,.id');

        return $this->safeRead($client, $q);
    }

    /** Remove hotspot user */
    public function removeHotspotUser(Router $router, string $nameOrId): array
    {
        $client = $this->client($router);
        $id = str_starts_with($nameOrId, '*') ? $nameOrId : $this->resolveUserIdByName($client, $nameOrId);
        if (! $id) {
            return ['ok' => false, 'message' => 'User not found'];
        }

        return $this->safeRead(
            $client,
            (new Query('/ip/hotspot/user/remove'))->equal('.id', $id)
        );
    }

    /** Get hotspot user by name (slim) */
    public function getHotspotUser(Router $router, string $name): ?array
    {
        $client = $this->client($router);
        $q = (new Query('/ip/hotspot/user/print'))
            ->where('name', $name)
            ->equal('.proplist', 'name,.id,disabled,comment,profile');
        $resp = $this->safeRead($client, $q);

        return $this->firstRow($resp);
    }

    /** Optional: ping by quick read */
    public function pingRouter(Router $router): bool
    {
        try {
            $client = $this->client($router);
            $this->safeRead($client, new Query('/system/identity/print'));

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /** --- Helpers --- */
    protected function setHotspotUserDisabled(Router $router, string $nameOrId, bool $disabled): array
    {
        $client = $this->client($router);
        $id = str_starts_with($nameOrId, '*') ? $nameOrId : $this->resolveUserIdByName($client, $nameOrId);
        if (! $id) {
            return ['ok' => false, 'message' => 'User not found'];
        }

        return $this->safeRead(
            $client,
            (new Query('/ip/hotspot/user/set'))
                ->equal('.id', $id)
                ->equal('disabled', $disabled ? 'yes' : 'no')
        );
    }

    protected function resolveUserIdByName(Client $client, string $name): ?string
    {
        $q = (new Query('/ip/hotspot/user/print'))
            ->where('name', $name)
            ->equal('.proplist', '.id');
        $row = $this->firstRow($this->safeRead($client, $q));

        return $row['.id'] ?? null;
    }

    protected function resolveActiveUserId(Client $client, string $usernameOrActiveId): ?string
    {
        if (str_starts_with($usernameOrActiveId, '*')) {
            return $usernameOrActiveId;
        }
        $q = (new Query('/ip/hotspot/active/print'))
            ->where('user', $usernameOrActiveId)
            ->equal('.proplist', '.id');
        $row = $this->firstRow($this->safeRead($client, $q));

        return $row['.id'] ?? null;
    }

    protected function firstRow(mixed $response): array
    {
        return (is_array($response) && isset($response[0]) && is_array($response[0])) ? $response[0] : [];
    }
}
