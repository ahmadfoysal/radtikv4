<?php

namespace App\MikroTik\Actions;

use App\MikroTik\Client\RouterClient;
use App\Models\Router;
use RouterOS\Query;

class HotspotProfileManager
{
    public function __construct(
        private RouterClient $client,
    ) {}

    /**
     * List hotspot user profiles with a slim proplist.
     */
    public function listProfiles(Router $router): array
    {
        $ros = $this->client->make($router);

        $q = (new Query('/ip/hotspot/user/profile/print'))
            ->equal('.proplist', 'name,.id,shared-users,rate-limit,session-timeout');

        return $this->client->safeRead($ros, $q);
    }

    /**
     * Get a single hotspot profile by name or .id.
     */
    public function getProfile(Router $router, string $nameOrId): ?array
    {
        $ros = $this->client->make($router);

        // If looks like an internal ID, query by .id
        if (str_starts_with($nameOrId, '*')) {
            $q = (new Query('/ip/hotspot/user/profile/print'))
                ->where('.id', $nameOrId);
        } else {
            $q = (new Query('/ip/hotspot/user/profile/print'))
                ->where('name', $nameOrId);
        }

        $q->equal('.proplist', 'name,.id,shared-users,rate-limit,session-timeout');

        $resp = $this->client->safeRead($ros, $q);

        return $this->client->firstRow($resp);
    }

    /**
     * Create a new hotspot user profile.
     *
     * $attributes can include keys like:
     *  - shared-users
     *  - rate-limit
     *  - session-timeout
     *  - idle-timeout
     *  - keepalive-timeout
     *  - mac-cookie-timeout
     *  - address-pool
     */
    public function createProfile(
        Router $router,
        string $name,
        array $attributes = []
    ): array {
        $ros = $this->client->make($router);

        $q = (new Query('/ip/hotspot/user/profile/add'))
            ->equal('name', $name);

        foreach ($attributes as $key => $value) {
            if ($value === null) {
                continue;
            }
            $q->equal((string) $key, (string) $value);
        }

        return $this->client->safeRead($ros, $q);
    }

    /**
     * Update an existing hotspot profile by name or .id.
     *
     * Only attributes passed in $attributes will be updated.
     */
    public function updateProfile(
        Router $router,
        string $nameOrId,
        array $attributes
    ): array {
        $ros = $this->client->make($router);

        $id = $this->resolveProfileId($ros, $nameOrId);

        if (!$id) {
            return ['ok' => false, 'message' => 'Profile not found'];
        }

        $q = (new Query('/ip/hotspot/user/profile/set'))
            ->equal('.id', $id);

        foreach ($attributes as $key => $value) {
            if ($value === null) {
                continue;
            }
            $q->equal((string) $key, (string) $value);
        }

        return $this->client->safeRead($ros, $q);
    }

    /**
     * Delete a hotspot profile by name or .id.
     */
    public function deleteProfile(Router $router, string $nameOrId): array
    {
        $ros = $this->client->make($router);

        $id = $this->resolveProfileId($ros, $nameOrId);

        if (!$id) {
            return ['ok' => false, 'message' => 'Profile not found'];
        }

        $q = (new Query('/ip/hotspot/user/profile/remove'))
            ->equal('.id', $id);

        return $this->client->safeRead($ros, $q);
    }

    /**
     * Resolve profile .id by name or return given .id.
     */
    protected function resolveProfileId(\RouterOS\Client $ros, string $nameOrId): ?string
    {
        if (str_starts_with($nameOrId, '*')) {
            return $nameOrId;
        }

        $q = (new Query('/ip/hotspot/user/profile/print'))
            ->where('name', $nameOrId)
            ->equal('.proplist', '.id');

        $row = $this->client->firstRow(
            $this->client->safeRead($ros, $q)
        );

        return $row['.id'] ?? null;
    }
}
