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

        $result = $this->client->safeRead($ros, $q);
        return ['ok' => true, 'data' => $result];
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
     * Reset voucher/hotspot user: remove cookie, clear MAC, remove from active, reset counters.
     * 
     * This method performs the following actions:
     * 1. Remove cookie for this hotspot user
     * 2. Set MAC address to 00:00:00:00:00:00
     * 3. Remove user from active list
     * 4. Reset counters (bytes-in, bytes-out) for this user
     *
     * @param Router $router The router instance
     * @param string $username The hotspot username to reset
     * @return array ['ok' => bool, 'message' => string, 'actions' => array]
     */
    public function resetVoucher(Router $router, string $username): array
    {
        $ros = $this->client->make($router);
        $actions = [];
        $errors = [];

        // 1. Get user ID first
        $userId = $this->client->resolveUserIdByName($ros, $username);
        if (!$userId) {
            return ['ok' => false, 'message' => 'User not found', 'actions' => []];
        }

        // 2. Remove cookie for this user
        try {
            $cookies = $this->getSessionCookies($router);
            foreach ($cookies as $cookie) {
                if (isset($cookie['user']) && $cookie['user'] === $username) {
                    if (isset($cookie['.id'])) {
                        $cookieResult = $this->deleteSessionCookie($router, $cookie['.id']);
                        if ($cookieResult['ok']) {
                            $actions[] = 'Cookie removed';
                        } else {
                            $errors[] = 'Failed to remove cookie: ' . ($cookieResult['message'] ?? 'Unknown error');
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            $errors[] = 'Error removing cookie: ' . $e->getMessage();
        }

        // 3. Remove user from active sessions
        try {
            $activeResult = $this->removeActiveUser($router, $username);
            if ($activeResult['ok'] ?? false) {
                $actions[] = 'Removed from active sessions';
            } else {
                // User might not be active, which is fine
                $actions[] = 'No active session found (user may not be logged in)';
            }
        } catch (\Throwable $e) {
            $errors[] = 'Error removing active session: ' . $e->getMessage();
        }

        // 4. Update user: Set MAC address to 00:00:00:00:00:00 and reset counters
        try {
            $q = (new Query('/ip/hotspot/user/set'))
                ->equal('.id', $userId)
                ->equal('mac-address', '00:00:00:00:00:00')
                ->equal('bytes-in', '0')
                ->equal('bytes-out', '0');

            $updateResult = $this->client->safeRead($ros, $q);

            if (!isset($updateResult['ok']) || $updateResult['ok'] !== false) {
                $actions[] = 'MAC address reset to 00:00:00:00:00:00';
                $actions[] = 'Counters reset (bytes-in: 0, bytes-out: 0)';
            } else {
                $errors[] = 'Failed to update user: ' . ($updateResult['message'] ?? 'Unknown error');
            }
        } catch (\Throwable $e) {
            $errors[] = 'Error updating user: ' . $e->getMessage();
        }

        // Return result
        $success = empty($errors);
        $message = $success
            ? 'Voucher reset successfully. ' . implode(', ', $actions)
            : 'Some actions failed: ' . implode('; ', $errors);

        return [
            'ok' => $success,
            'message' => $message,
            'actions' => $actions,
            'errors' => $errors,
        ];
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
