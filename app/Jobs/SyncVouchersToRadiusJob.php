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

            // Check if there are vouchers to sync
            $totalVouchers = Voucher::where('batch', $this->batchId)
                ->where('radius_sync_status', 'pending')
                ->count();

            if ($totalVouchers === 0) {
                Log::info('No pending vouchers to sync', [
                    'batch_id' => $this->batchId,
                ]);
                return;
            }

            Log::info('Found vouchers to sync', [
                'batch_id' => $this->batchId,
                'count' => $totalVouchers,
            ]);

            // Initialize RADIUS API service
            $radiusApi = new RadiusApiService($router->radiusServer);

            // Process vouchers in chunks of 250 directly from database
            $totalChunks = 0;
            $successChunks = 0;
            $failedChunks = 0;
            $errors = [];

            // Get all pending vouchers for this batch ONCE to avoid chunking issues
            $pendingVouchers = Voucher::with('profile')
                ->where('batch', $this->batchId)
                ->where('radius_sync_status', 'pending')
                ->get();

            // Process in chunks using Collection chunk method (safer than chunkById with updates)
            foreach ($pendingVouchers->chunk(250) as $chunk) {
                $totalChunks++;
                
                try {
                    // Add delay between chunks to prevent rate limiting (skip for first chunk)
                    if ($totalChunks > 1) {
                        sleep(2); // 2 second delay between chunks
                    }

                    // Sync batch to RADIUS
                    $response = $radiusApi->syncBatch($chunk, $router);

                    // Collect IDs for batch update (more efficient than updating each model)
                    $voucherIds = $chunk->pluck('id')->toArray();
                    
                    // Batch update to 'synced' status
                    Voucher::whereIn('id', $voucherIds)->update([
                        'radius_sync_status' => 'synced',
                        'radius_synced_at' => now(),
                    ]);

                    $successChunks++;

                    Log::info('Voucher batch synced successfully', [
                        'batch_id' => $this->batchId,
                        'chunk_number' => $totalChunks,
                        'chunk_size' => $chunk->count(),
                        'response' => $response,
                    ]);

                } catch (Exception $e) {
                    $failedChunks++;
                    $errorMessage = $e->getMessage();
                    $errors[] = "Chunk {$totalChunks}: {$errorMessage}";
                    
                    // Collect IDs for batch update
                    $voucherIds = $chunk->pluck('id')->toArray();
                    
                    // Batch update to 'failed' status
                    Voucher::whereIn('id', $voucherIds)->update([
                        'radius_sync_status' => 'failed',
                        'radius_sync_error' => $errorMessage,
                    ]);

                    Log::error('Voucher batch sync failed', [
                        'batch_id' => $this->batchId,
                        'chunk_number' => $totalChunks,
                        'chunk_size' => $chunk->count(),
                        'error' => $errorMessage,
                    ]);

                    // Don't throw exception - continue processing remaining chunks
                }
            }

            // Log summary
            Log::info('RADIUS voucher sync completed', [
                'batch_id' => $this->batchId,
                'router_id' => $this->routerId,
                'total_vouchers' => $totalVouchers,
                'total_chunks' => $totalChunks,
                'successful_chunks' => $successChunks,
                'failed_chunks' => $failedChunks,
            ]);

            // If ALL chunks failed, throw exception to trigger retry
            if ($failedChunks > 0 && $successChunks === 0) {
                throw new Exception('All chunks failed to sync: ' . implode('; ', $errors));
            }
            
            // If SOME chunks failed, log warning but don't retry (partial success)
            if ($failedChunks > 0) {
                Log::warning('Partial sync failure', [
                    'batch_id' => $this->batchId,
                    'successful_chunks' => $successChunks,
                    'failed_chunks' => $failedChunks,
                    'errors' => $errors,
                ]);
            }

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
