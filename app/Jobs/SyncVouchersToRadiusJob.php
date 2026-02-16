<?php

namespace App\Jobs;

use App\Models\Voucher;
use App\Models\Router;
use App\Services\RadiusApiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Exception;

class SyncVouchersToRadiusJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 300; // 5 minutes
    public int $backoff = 60; // Wait 60 seconds between retries

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $batchId,
        public int $routerId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting RADIUS voucher sync', [
                'batch_id' => $this->batchId,
                'router_id' => $this->routerId,
            ]);

            // Get router with RADIUS server relationship
            $router = Router::with('radiusServer')->findOrFail($this->routerId);

            if (!$router->radiusServer) {
                Log::warning('Router has no RADIUS server configured', [
                    'router_id' => $this->routerId,
                    'batch_id' => $this->batchId,
                ]);
                
                // Mark all vouchers as failed
                Voucher::where('batch', $this->batchId)
                    ->where('radius_sync_status', 'pending')
                    ->update([
                        'radius_sync_status' => 'failed',
                        'radius_sync_error' => 'Router has no RADIUS server configured',
                    ]);
                
                return;
            }

            // Get pending vouchers with profile relationship
            $pendingVouchers = Voucher::with('profile')
                ->where('batch', $this->batchId)
                ->where('radius_sync_status', 'pending')
                ->get();

            if ($pendingVouchers->isEmpty()) {
                Log::info('No pending vouchers to sync', [
                    'batch_id' => $this->batchId,
                ]);
                return;
            }

            Log::info('Found vouchers to sync', [
                'batch_id' => $this->batchId,
                'count' => $pendingVouchers->count(),
            ]);

            // Initialize RADIUS API service
            $radiusApi = new RadiusApiService($router->radiusServer);

            // Process vouchers in chunks of 250
            $pendingVouchers->chunk(250)->each(function ($chunk) use ($radiusApi, $router) {
                try {
                    // Sync batch to RADIUS
                    $response = $radiusApi->syncBatch($chunk, $router);

                    // Mark vouchers as synced
                    $chunk->each(function ($voucher) {
                        $voucher->markAsSynced();
                    });

                    Log::info('Voucher batch synced successfully', [
                        'batch_id' => $this->batchId,
                        'chunk_size' => $chunk->count(),
                        'response' => $response,
                    ]);

                } catch (Exception $e) {
                    // Mark vouchers as failed
                    $errorMessage = $e->getMessage();
                    
                    $chunk->each(function ($voucher) use ($errorMessage) {
                        $voucher->markAsFailed($errorMessage);
                    });

                    Log::error('Voucher batch sync failed', [
                        'batch_id' => $this->batchId,
                        'chunk_size' => $chunk->count(),
                        'error' => $errorMessage,
                    ]);

                    throw $e; // Re-throw to trigger job retry
                }
            });

            Log::info('RADIUS voucher sync completed', [
                'batch_id' => $this->batchId,
                'router_id' => $this->routerId,
                'total_synced' => $pendingVouchers->count(),
            ]);

        } catch (Exception $e) {
            Log::error('RADIUS sync job failed', [
                'batch_id' => $this->batchId,
                'router_id' => $this->routerId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e; // Re-throw for retry logic
        }
    }

    /**
     * Handle a job failure (after all retries exhausted).
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('RADIUS sync job failed permanently', [
            'batch_id' => $this->batchId,
            'router_id' => $this->routerId,
            'error' => $exception->getMessage(),
        ]);

        // Mark remaining pending vouchers as failed
        Voucher::where('batch', $this->batchId)
            ->where('radius_sync_status', 'pending')
            ->update([
                'radius_sync_status' => 'failed',
                'radius_sync_error' => 'Sync failed after ' . $this->tries . ' attempts: ' . $exception->getMessage(),
            ]);
    }
}
