<?php

namespace App\MikroTik\Actions;

use App\MikroTik\Client\RouterClient;
use App\Models\Router;
use RouterOS\Query;

class RouterManager
{
    public function __construct(
        private RouterClient $client,
    ) {}

    /**
     * Get basic system resource information:
     * CPU load, memory, uptime, version, etc.
     */
    public function getRouterResource(Router $router): array
    {
        $ros = $this->client->make($router);

        $data = $this->client->safeRead(
            $ros,
            new Query('/system/resource/print')
        );

        return $this->client->firstRow($data);
    }

    /**
     * Check raw TCP reachability using fsockopen.
     * Fast check to confirm host:port is reachable.
     */
    public function isReachable(Router $router): bool
    {
        return $this->client->reachable($router);
    }

    /**
     * Soft API ping using RouterOS call.
     * Confirms that RouterOS API is responding.
     */
    public function pingRouter(Router $router): bool
    {
        try {
            $ros = $this->client->make($router);

            $this->client->safeRead(
                $ros,
                new Query('/system/identity/print')
            );

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
