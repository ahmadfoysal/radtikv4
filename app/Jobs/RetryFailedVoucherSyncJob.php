<?php

namespace App\Jobs;

use App\Models\Voucher;
use App\Models\Router;
use App\Services\RadiusApiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Exception;

class RetryFailedVoucherSyncJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;
    public int $timeout = 300;
    public int $backoff = 120; // Wait 2 minutes between retries

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $routerId,
        public ?int $limit = 1000 // Limit retries per run to avoid overwhelming
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting failed voucher retry', [
                'router_id' => $this->routerId,
                'limit' => $this->limit,
            ]);

            // Get router with RADIUS server relationship
            $router = Router::with('radiusServer')->findOrFail($this->routerId);

            if (!$router->radiusServer) {
                Log::warning('Router has no RADIUS server configured - skipping retry', [
                    'router_id' => $this->routerId,
                ]);
                return;
            }

            // Get failed vouchers for this router
            // Only retry vouchers that failed within last 24 hours to avoid retrying old failures
            $failedVouchers = Voucher::with('profile')
                ->where('router_id', $this->routerId)
                ->where('radius_sync_status', 'failed')
                ->where('updated_at', '>=', now()->subHours(24))
                ->limit($this->limit)
                ->get();

            if ($failedVouchers->isEmpty()) {
                Log::info('No failed vouchers to retry', [
                    'router_id' => $this->routerId,
                ]);
                return;
            }

            Log::info('Found failed vouchers to retry', [
                'router_id' => $this->routerId,
                'count' => $failedVouchers->count(),
            ]);

            // Reset their status to pending before retrying
            $failedVouchers->each(function ($voucher) {
                $voucher->resetSyncStatus();
            });

            // Initialize RADIUS API service
            $radiusApi = new RadiusApiService($router->radiusServer);

            $successCount = 0;
            $failCount = 0;

            // Process vouchers in chunks of 250
            $failedVouchers->chunk(250)->each(function ($chunk) use ($radiusApi, $router, &$successCount, &$failCount) {
                try {
                    // Sync batch to RADIUS
                    $response = $radiusApi->syncBatch($chunk, $router);

                    // Mark vouchers as synced
                    $chunk->each(function ($voucher) {
                        $voucher->markAsSynced();
                    });

                    $successCount += $chunk->count();

                    Log::info('Failed voucher batch retry succeeded', [
                        'router_id' => $this->routerId,
                        'chunk_size' => $chunk->count(),
                        'response' => $response,
                    ]);

                } catch (Exception $e) {
                    // Mark vouchers as failed again
                    $errorMessage = $e->getMessage();
                    
                    $chunk->each(function ($voucher) use ($errorMessage) {
                        $voucher->markAsFailed($errorMessage);
                    });

                    $failCount += $chunk->count();

                    Log::error('Failed voucher batch retry failed again', [
                        'router_id' => $this->routerId,
                        'chunk_size' => $chunk->count(),
                        'error' => $errorMessage,
                    ]);

                    // Don't throw - continue with next chunk
                }
            });

            Log::info('Failed voucher retry completed', [
                'router_id' => $this->routerId,
                'success_count' => $successCount,
                'fail_count' => $failCount,
            ]);

        } catch (Exception $e) {
            Log::error('Failed voucher retry job error', [
                'router_id' => $this->routerId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
