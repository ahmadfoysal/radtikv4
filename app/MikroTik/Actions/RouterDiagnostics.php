<?php

namespace App\MikroTik\Actions;

use App\MikroTik\Client\RouterClient;
use App\Models\Router;
use RouterOS\Query;

class RouterDiagnostics
{
    protected array $scriptNameCache = [];

    public function __construct(
        private RouterClient $client,
    ) {}

    /**
     * Basic system resource payload.
     */
    public function systemResource(Router $router): array
    {
        $ros = $this->client->make($router);
        $resp = $this->client->safeRead($ros, new Query('/system/resource/print'));

        return $this->client->firstRow($resp);
    }

    /**
     * Router clock for time/uptime display.
     */
    public function systemClock(Router $router): array
    {
        $ros = $this->client->make($router);
        $resp = $this->client->safeRead($ros, new Query('/system/clock/print'));

        return $this->client->firstRow($resp);
    }

    /**
     * Verify MikroTik scripts against local definitions.
     */
    public function scriptStatuses(Router $router): array
    {
        $ros = $this->client->make($router);

        $resp = $this->client->safeRead(
            $ros,
            (new Query('/system/script/print'))
                ->equal('.proplist', 'name,policy,owner,last-updated')
        );

        $remoteScripts = [];
        foreach ($resp as $row) {
            if (! isset($row['name'])) {
                continue;
            }
            $remoteScripts[$row['name']] = $row;
        }

        $statuses = [];
        foreach ($this->discoverScriptNames() as $scriptName) {
            $match = $remoteScripts[$scriptName] ?? null;

            $statuses[] = [
                'name' => $scriptName,
                'present' => $match !== null,
                'policy' => $match['policy'] ?? null,
                'owner' => $match['owner'] ?? null,
                'metadata' => $match,
            ];
        }

        return $statuses;
    }

    /**
     * Hotspot user statistics.
     */
    public function hotspotCounts(Router $router): array
    {
        $ros = $this->client->make($router);

        $users = $this->client->safeRead(
            $ros,
            (new Query('/ip/hotspot/user/print'))
                ->equal('.proplist', '.id,disabled')
        );

        $totalUsers = count($users);
        $disabled = 0;
        foreach ($users as $user) {
            if (($user['disabled'] ?? 'no') === 'yes') {
                $disabled++;
            }
        }

        $active = $this->client->safeRead(
            $ros,
            (new Query('/ip/hotspot/active/print'))
                ->equal('.proplist', '.id')
        );

        $activeCount = count($active);

        return [
            'total' => $totalUsers,
            'active' => $activeCount,
            'disabled' => $disabled,
            'inactive' => max($totalUsers - $activeCount, 0),
        ];
    }

    /**
     * Recent hotspot related log entries.
     */
    public function hotspotLogs(Router $router, int $limit = 10): array
    {
        $ros = $this->client->make($router);

        $resp = $this->client->safeRead(
            $ros,
            (new Query('/log/print'))
                ->equal('.proplist', 'time,date,topics,message')
        );

        $filtered = array_values(array_filter($resp, function ($row) {
            $topics = $row['topics'] ?? '';

            return str_contains(strtolower($topics), 'hotspot');
        }));

        $filtered = array_slice(array_reverse($filtered), 0, $limit);

        return array_reverse($filtered);
    }

    /**
     * List router interfaces for traffic monitoring.
     */
    public function interfaces(Router $router): array
    {
        $ros = $this->client->make($router);

        $resp = $this->client->safeRead(
            $ros,
            (new Query('/interface/print'))
                ->equal('.proplist', 'name,type,comment,disabled')
        );

        // Keep only physical ethernet interfaces (RouterOS reports them with type "ether*").
        $physical = array_values(array_filter($resp, function ($row) {
            $type = strtolower($row['type'] ?? '');

            return str_starts_with($type, 'ether');
        }));

        return $physical;
    }

    /**
     * Grab a single traffic sample (bits per second).
     */
    public function interfaceTraffic(Router $router, string $interface): ?array
    {
        if ($interface === '') {
            return null;
        }

        $ros = $this->client->make($router);

        $resp = $this->client->safeRead(
            $ros,
            (new Query('/interface/monitor-traffic'))
                ->equal('interface', $interface)
                ->equal('once', 'yes')
        );

        $row = $this->client->firstRow($resp);

        if (empty($row)) {
            return null;
        }

        return [
            'rx' => (float) ($row['rx-bits-per-second'] ?? 0),
            'tx' => (float) ($row['tx-bits-per-second'] ?? 0),
            'datetime' => now()->toIso8601String(),
        ];
    }

    /**
     * Discover script class names under app/MikroTik/Scripts dynamically.
     */
    protected function discoverScriptNames(): array
    {
        if (! empty($this->scriptNameCache)) {
            return $this->scriptNameCache;
        }

        $basePath = app_path('MikroTik/Scripts');
        $names = [];

        if (is_dir($basePath)) {
            foreach (glob($basePath.'/*.php') as $file) {
                $class = 'App\\MikroTik\\Scripts\\'.basename($file, '.php');
                if (! class_exists($class) || ! method_exists($class, 'name')) {
                    continue;
                }

                $names[] = $class::name();
            }
        }

        sort($names);

        return $this->scriptNameCache = $names;
    }
}
