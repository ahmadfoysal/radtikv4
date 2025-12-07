<?php

namespace App\MikroTik\Actions;

use App\MikroTik\Client\RouterClient;
use App\Models\Router;
use RouterOS\Query;

class SchedulerManager
{
    public function __construct(
        private RouterClient $client,
    ) {}

    /**
     * List schedulers from a router. Optionally filter by name.
     *
     * @param  array<int,string>  $names
     * @return array<int,array<string,mixed>>
     */
    public function list(Router $router, array $names = []): array
    {
        $ros = $this->client->make($router);

        $resp = $this->client->safeRead(
            $ros,
            (new Query('/system/scheduler/print'))
                ->equal('.proplist', '.id,name,interval,next-run,last-run,last-started,last-finished,on-event,disabled')
        );

        $schedulers = [];

        foreach ($resp as $row) {
            $name = $row['name'] ?? null;

            if (!$name || (!empty($names) && !in_array($name, $names, true))) {
                continue;
            }

            $schedulers[] = [
                'id'            => $row['.id'] ?? null,
                'name'          => $name,
                'interval'      => $row['interval'] ?? null,
                'next_run'      => $row['next-run'] ?? null,
                'last_run'      => $row['last-run'] ?? ($row['last-started'] ?? null),
                'last_started'  => $row['last-started'] ?? null,
                'last_finished' => $row['last-finished'] ?? null,
                'on_event'      => $row['on-event'] ?? null,
                'disabled'      => ($row['disabled'] ?? 'no') === 'yes',
            ];
        }

        return $schedulers;
    }

    /**
     * Trigger a scheduler entry to run immediately.
     */
    public function run(Router $router, string $name): void
    {
        $ros = $this->client->make($router);

        $idQuery = (new Query('/system/scheduler/print'))
            ->where('name', $name)
            ->equal('.proplist', '.id');

        $match = $this->client->safeRead($ros, $idQuery);
        $id    = $match[0]['.id'] ?? null;

        if (!$id) {
            throw new \RuntimeException("Scheduler {$name} not found.");
        }

        $runQuery = (new Query('/system/scheduler/run'))
            ->equal('numbers', $id);

        $this->client->safeRead($ros, $runQuery);
    }
}
