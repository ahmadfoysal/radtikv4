<?php

namespace App\Jobs;

use App\Models\RadiusServer;
use App\Services\LinodeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProvisionRadiusServer implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 600; // 10 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        public RadiusServer $server
    ) {}

    /**
     * Execute the job.
     */
    public function handle(LinodeService $linodeService): void
    {
        try {
            Log::info('Starting RADIUS server provisioning', [
                'server_id' => $this->server->id,
                'name' => $this->server->name,
            ]);

            $linodeService->provisionServer($this->server);

            Log::info('RADIUS server provisioning completed', [
                'server_id' => $this->server->id,
            ]);

        } catch (\Exception $e) {
            Log::error('RADIUS server provisioning failed', [
                'server_id' => $this->server->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->server->update([
            'installation_status' => 'failed',
            'installation_log' => $this->server->installation_log . "\n\nJob failed: " . $exception->getMessage(),
        ]);

        Log::error('RADIUS server provisioning job failed', [
            'server_id' => $this->server->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
