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
        $ros = $this->client->make($router);
        $activeId = $this->client->resolveActiveUserId($ros, $usernameOrActiveId);

        if (! $activeId) {
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

        if (! $id) {
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
     * Get hotspot profiles (server profiles) from MikroTik.
     */
    public function getHotspotProfiles(Router $router): array
    {
        $ros = $this->client->make($router);

        $q = (new Query('/ip/hotspot/user/profile/print'))
            ->equal('.proplist', 'name,.id');

        return $this->client->safeRead($ros, $q);
    }

    /**
     * Get active hotspot sessions with detailed information.
     */
    public function getActiveSessions(Router $router): array
    {
        $ros = $this->client->make($router);

        $q = (new Query('/ip/hotspot/active/print'))
            ->equal('.proplist', 'user,address,mac-address,uptime,bytes-in,bytes-out,.id');

        return $this->client->safeRead($ros, $q);
    }

    /**
     * Get session cookies from MikroTik.
     */
    public function getSessionCookies(Router $router): array
    {
        $ros = $this->client->make($router);

        $q = (new Query('/ip/hotspot/cookie/print'))
            ->equal('.proplist', 'user,mac-address,domain,.id');

        return $this->client->safeRead($ros, $q);
    }

    /**
     * Delete a session cookie by .id.
     */
    public function deleteSessionCookie(Router $router, string $cookieId): array
    {
        $ros = $this->client->make($router);

        if (! str_starts_with($cookieId, '*')) {
            return ['ok' => false, 'message' => 'Invalid cookie ID format'];
        }

        $q = (new Query('/ip/hotspot/cookie/remove'))
            ->equal('.id', $cookieId);

        return $this->client->safeRead($ros, $q);
    }

    /**
     * Get hotspot logs from MikroTik.
     */
    public function getHotspotLogs(Router $router): array
    {
        $ros = $this->client->make($router);

        // Hotspot logs are typically found in the log with topics containing "hotspot"
        $q = (new Query('/log/print'))
            ->where('topics', 'hotspot')
            ->equal('.proplist', 'time,topics,message');

        return $this->client->safeRead($ros, $q);
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

        if (! $id) {
            return ['ok' => false, 'message' => 'User not found'];
        }

        $q = (new Query('/ip/hotspot/user/set'))
            ->equal('.id', $id)
            ->equal('disabled', $disabled ? 'yes' : 'no');

        return $this->client->safeRead($ros, $q);
    }
}
