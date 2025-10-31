<?php

// app/Services/Mikrotik/MikrotikService.php
namespace App\Services;

use App\Models\Router;
use RouterOS\Client;
use RouterOS\Query;
use Throwable;

class MikrotikService
{
    public function __construct(
        private ?int $timeout = 10, // seconds
        private ?int $attempts = 1 // reconnect attempts
    ) {}

    /** Build a RouterOS client using credentials from the Router model */
    protected function client(Router $router): Client
    {
        // If you published config, you can merge defaults here as needed
        return new Client([
            'host' => $router->address,
            'user' => $router->username,
            'pass' => $router->decryptedPassword(),
            'port' => (int)($router->port ?: 8728),
            'timeout' => $this->timeout,
            'attempts' => $this->attempts,
        ]);
    }

    /** /system/resource/print — CPU, memory, version, uptime, etc. */
    public function getRouterResource(Router $router): array
    {
        $client = $this->client($router);
        $resp = $client->query(new Query('/system/resource/print'))->read();

        return $this->firstRow($resp);
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

        return $client->query($q)->read();
    }

    /** Enable hotspot user by name (or pass .id directly) */
    public function enableHotspotUser(Router $router, string $nameOrId): array
    {
        return $this->setHotspotUserDisabled($router, $nameOrId, false);
    }

    /** Disable hotspot user by name (or pass .id directly) */
    public function disableHotspotUser(Router $router, string $nameOrId): array
    {
        return $this->setHotspotUserDisabled($router, $nameOrId, true);
    }

    /** Remove an active hotspot session for given username or active .id */
    public function removeActiveUser(Router $router, string $usernameOrActiveId): array
    {
        $client = $this->client($router);
        $activeId = $this->resolveActiveUserId($client, $usernameOrActiveId);

        if (!$activeId) {
            return ['ok' => false, 'message' => 'Active user not found'];
        }

        return $client->query(
            (new Query('/ip/hotspot/active/remove'))->equal('.id', $activeId)
        )->read();
    }

    /* Get active user list */

    public function listActiveUsers(Router $router): array
    {
        $client = $this->client($router);
        return $client->query(new Query('/ip/hotspot/active/print'))->read();
    }


    /* Remove hotspot user */

    public function removeHotspotUser(Router $router, string $nameOrId): array
    {
        $client = $this->client($router);
        $id = str_starts_with($nameOrId, '*') ? $nameOrId : $this->resolveUserIdByName($client, $nameOrId);
        if (!$id) return ['ok' => false, 'message' => 'User not found'];

        return $client->query((new Query('/ip/hotspot/user/remove'))->equal('.id', $id))->read();
    }

    /** (Optional) Get hotspot user by name */
    public function getHotspotUser(Router $router, string $name): ?array
    {
        $client = $this->client($router);

        $resp = $client->query(
            (new Query('/ip/hotspot/user/print'))->where('name', $name)
        )->read();

        return $this->firstRow($resp);
    }

    /* Ping router */

    public function pingRouter(Router $router): bool
    {
        try {
            $client = $this->client($router);
            // A simple query to check connectivity
            $client->query(new Query('/system/resource/print'))->read();
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }



    // ----------------- helpers -----------------

    protected function setHotspotUserDisabled(Router $router, string $nameOrId, bool $disabled): array
    {
        $client = $this->client($router);

        $id = str_starts_with($nameOrId, '*') // RouterOS .id usually starts with *
            ? $nameOrId
            : $this->resolveUserIdByName($client, $nameOrId);

        if (!$id) {
            return ['ok' => false, 'message' => 'User not found'];
        }

        return $client->query(
            (new Query('/ip/hotspot/user/set'))
                ->equal('.id', $id)
                ->equal('disabled', $disabled ? 'yes' : 'no')
        )->read();
    }

    protected function resolveUserIdByName(Client $client, string $name): ?string
    {
        $resp = $client->query(
            (new Query('/ip/hotspot/user/print'))->where('name', $name)
        )->read();

        $row = $this->firstRow($resp);
        return $row['.id'] ?? null;
    }

    protected function resolveActiveUserId(Client $client, string $usernameOrActiveId): ?string
    {
        if (str_starts_with($usernameOrActiveId, '*')) {
            return $usernameOrActiveId; // already an .id
        }

        $resp = $client->query(
            (new Query('/ip/hotspot/active/print'))->where('user', $usernameOrActiveId)
        )->read();

        $row = $this->firstRow($resp);
        return $row['.id'] ?? null;
    }

    protected function firstRow(mixed $response): array
    {
        if (is_array($response) && isset($response[0]) && is_array($response[0])) {
            return $response[0];
        }
        return [];
    }
}
