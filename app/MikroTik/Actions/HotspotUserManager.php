<?php

namespace App\MikroTik\Actions;

use App\MikroTik\Client\RouterClient;
use App\Models\Router;
use RouterOS\Query;

class HotspotUserManager
{
    public function __construct(
        private RouterClient $client,
    ) {}

    /**
     * Add hotspot user with optional profile.
     */
    public function addUser(
        Router $router,
        string $name,
        string $password,
        ?string $profile = null
    ): array {
        $ros = $this->client->make($router);

        $q = (new Query('/ip/hotspot/user/add'))
            ->equal('name', $name)
            ->equal('password', $password);

        if ($profile) {
            $q->equal('profile', $profile);
        }

        return $this->client->safeRead($ros, $q);
    }

    /**
     * Enable hotspot user by name or .id.
     */
    public function enableUser(Router $router, string $nameOrId): array
    {
        return $this->setUserDisabled($router, $nameOrId, false);
    }

    /**
     * Disable hotspot user by name or .id.
     */
    public function disableUser(Router $router, string $nameOrId): array
    {
        return $this->setUserDisabled($router, $nameOrId, true);
    }

    /**
     * List active hotspot sessions with a slim proplist.
     */
    public function listActiveUsers(Router $router): array
    {
        $ros = $this->client->make($router);

        $q = (new Query('/ip/hotspot/active/print'))
            ->equal('.proplist', 'user,mac-address,address,.id');

        return $this->client->safeRead($ros, $q);
    }

    /**
     * Remove an active hotspot session by username or active .id.
     */
    public function removeActiveUser(Router $router, string $usernameOrActiveId): array
    {
        $ros       = $this->client->make($router);
        $activeId  = $this->client->resolveActiveUserId($ros, $usernameOrActiveId);

        if (!$activeId) {
            return ['ok' => false, 'message' => 'Active user not found'];
        }

        $q = (new Query('/ip/hotspot/active/remove'))
            ->equal('.id', $activeId);

        return $this->client->safeRead($ros, $q);
    }

    /**
     * Remove hotspot user by name or .id.
     */
    public function removeUser(Router $router, string $nameOrId): array
    {
        $ros = $this->client->make($router);

        $id = str_starts_with($nameOrId, '*')
            ? $nameOrId
            : $this->client->resolveUserIdByName($ros, $nameOrId);

        if (!$id) {
            return ['ok' => false, 'message' => 'User not found'];
        }

        $q = (new Query('/ip/hotspot/user/remove'))
            ->equal('.id', $id);

        return $this->client->safeRead($ros, $q);
    }

    /**
     * Get hotspot user by name with a slim proplist.
     */
    public function getUser(Router $router, string $name): ?array
    {
        $ros = $this->client->make($router);

        $q = (new Query('/ip/hotspot/user/print'))
            ->where('name', $name)
            ->equal('.proplist', 'name,.id,disabled,comment,profile');

        $resp = $this->client->safeRead($ros, $q);

        return $this->client->firstRow($resp);
    }

    /**
     * Internal helper to set disabled flag for a user.
     */
    protected function setUserDisabled(
        Router $router,
        string $nameOrId,
        bool $disabled
    ): array {
        $ros = $this->client->make($router);

        $id = str_starts_with($nameOrId, '*')
            ? $nameOrId
            : $this->client->resolveUserIdByName($ros, $nameOrId);

        if (!$id) {
            return ['ok' => false, 'message' => 'User not found'];
        }

        $q = (new Query('/ip/hotspot/user/set'))
            ->equal('.id', $id)
            ->equal('disabled', $disabled ? 'yes' : 'no');

        return $this->client->safeRead($ros, $q);
    }
}
