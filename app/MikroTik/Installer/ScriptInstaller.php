<?php

namespace App\MikroTik\Installer;

use App\MikroTik\Client\RouterClient;
use App\MikroTik\Scripts\CleanOrphanUsersScript;
use App\MikroTik\Scripts\PullInactiveUsersScript;
use App\MikroTik\Scripts\PushActiveUsersScript;
use App\MikroTik\Scripts\PullProfilesScript;
use App\MikroTik\Scripts\ProfileOnLoginScript;
use App\MikroTik\Scripts\PullActiveUsersScript;
use App\Models\Router;
use RouterOS\Query;

class ScriptInstaller
{
    public function __construct(
        private RouterClient $client,
    ) {}

    public function upsertScript(
        Router $router,
        string $name,
        string $source,
        string $policy = 'read,write,policy,test'
    ): array {
        $ros = $this->client->make($router);

        $checkQuery = (new Query('/system/script/print'))
            ->where('name', $name)
            ->equal('.proplist', '.id');

        $existing = $this->client->safeRead($ros, $checkQuery);
        $id       = $existing[0]['.id'] ?? null;

        if ($id) {
            $q = (new Query('/system/script/set'))
                ->equal('.id', $id)
                ->equal('source', $source)
                ->equal('policy', $policy);
        } else {
            $q = (new Query('/system/script/add'))
                ->equal('name', $name)
                ->equal('source', $source)
                ->equal('policy', $policy);
        }

        return $this->client->safeRead($ros, $q);
    }

    public function installPullInactiveUsersScript(Router $router, string $baseUrl): array
    {
        $name   = PullInactiveUsersScript::name();
        $source = PullInactiveUsersScript::build($router, $baseUrl);

        return $this->upsertScript($router, $name, $source);
    }

    public function installPullActiveUsersScript(Router $router, string $baseUrl): array
    {
        $name   = PullActiveUsersScript::name();
        $source = PullActiveUsersScript::build($router, $baseUrl);

        return $this->upsertScript($router, $name, $source);
    }

    public function installPushActiveUsersScript(Router $router, string $baseUrl): array
    {
        $name   = PushActiveUsersScript::name();
        $source = PushActiveUsersScript::build($router, $baseUrl);

        return $this->upsertScript($router, $name, $source);
    }

    public function installRemoveOrphanUsersScript(Router $router, string $baseUrl): array
    {
        $name   = CleanOrphanUsersScript::name();
        $source = CleanOrphanUsersScript::build($router, $baseUrl);

        return $this->upsertScript($router, $name, $source);
    }

    public function installPullProfilesScript(Router $router, string $baseUrl): array
    {
        $name   = PullProfilesScript::name();
        $source = PullProfilesScript::build($router, $baseUrl);

        return $this->upsertScript($router, $name, $source);
    }


    public function installProfileOnLoginScript(Router $router): array
    {
        $name   = ProfileOnLoginScript::name();
        $source = ProfileOnLoginScript::build($router);

        return $this->upsertScript($router, $name, $source);
    }

    /**
     * Upsert a scheduler entry on MikroTik.
     */
    public function upsertScheduler(
        Router $router,
        string $name,
        string $interval,
        string $onEvent
    ): array {
        $ros = $this->client->make($router);

        // Check if scheduler with this name already exists
        $checkQuery = (new Query('/system/scheduler/print'))
            ->where('name', $name)
            ->equal('.proplist', '.id');

        $existing = $this->client->safeRead($ros, $checkQuery);
        $id       = $existing[0]['.id'] ?? null;

        if ($id) {
            // Update existing scheduler
            $q = (new Query('/system/scheduler/set'))
                ->equal('.id', $id)
                ->equal('interval', $interval)
                ->equal('on-event', $onEvent)
                ->equal('disabled', 'no');
        } else {
            // Create new scheduler
            $q = (new Query('/system/scheduler/add'))
                ->equal('name', $name)
                ->equal('interval', $interval)
                ->equal('on-event', $onEvent)
                ->equal('disabled', 'no');
        }

        return $this->client->safeRead($ros, $q);
    }
}
