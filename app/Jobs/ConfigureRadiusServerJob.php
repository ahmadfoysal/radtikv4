<?php

namespace App\Jobs;

use App\Models\RadiusServer;
use App\Services\RadiusServerSshService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Job to configure RADIUS server secrets via SSH
 * Sets the shared secret and API auth token on the RADIUS server
 */
class ConfigureRadiusServerJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 300; // 5 minutes
    public int $backoff = 60; // Wait 60 seconds between retries

    /**
     * Create a new job instance.
     */
    public function __construct(
        public RadiusServer $server,
        public string $sharedSecret,
        public string $authToken
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting RADIUS server configuration', [
                'server_id' => $this->server->id,
                'name' => $this->server->name,
            ]);

            // Wait a bit if server was just created
            if ($this->server->wasRecentlyCreated) {
                Log::info('Server recently created, waiting for services to stabilize');
                sleep(10);
            }

            // Create SSH service instance
            $sshService = new RadiusServerSshService($this->server);

            // Test connection first
            $connectionTest = $sshService->testConnection();
            if (!$connectionTest['success']) {
                throw new \Exception('SSH connection test failed: ' . $connectionTest['message']);
            }

            Log::info('SSH connection test passed');

            // Configure secrets
            $result = $sshService->configureSecrets($this->sharedSecret, $this->authToken);

            if (!$result['success']) {
                throw new \Exception('Configuration failed: ' . $result['message']);
            }

            // Update server status
            $this->server->update([
                'installation_status' => 'completed',
                'installed_at' => now(),
            ]);

            Log::info('RADIUS server configuration completed successfully', [
                'server_id' => $this->server->id,
                'api_status' => $result['api_status'],
                'radius_status' => $result['radius_status'],
            ]);

        } catch (\Exception $e) {
            Log::error('RADIUS server configuration failed', [
                'server_id' => $this->server->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Update server status on final failure
            if ($this->attempts() >= $this->tries) {
                $this->server->update([
                    'installation_status' => 'failed',
                    'installation_log' => 'Configuration failed: ' . $e->getMessage(),
                ]);
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('RADIUS server configuration job failed permanently', [
            'server_id' => $this->server->id,
            'error' => $exception->getMessage(),
        ]);

        $this->server->update([
            'installation_status' => 'failed',
            'installation_log' => 'Configuration failed after ' . $this->tries . ' attempts: ' . $exception->getMessage(),
        ]);
    }
}
