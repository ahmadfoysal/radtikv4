<?php

namespace App\Console\Commands;

use App\Models\Voucher;
use App\Services\RadiusApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoDeleteExpiredVouchers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vouchers:delete-expired {--dry-run : Run without actually deleting vouchers}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically delete expired vouchers (runs every 5 minutes)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('🔍 Running in DRY RUN mode - no vouchers will be deleted');
        }

        $this->info('🧹 Starting expired voucher cleanup process...');
        $startTime = microtime(true);

        try {
            $now = now();

            // Get expired vouchers
            $expiredVouchersQuery = Voucher::where('expires_at', '<', $now)
                ->whereNotNull('expires_at');

            $count = $expiredVouchersQuery->count();

            if ($count === 0) {
                $this->info('✓ No expired vouchers to delete');
                return self::SUCCESS;
            }

            $this->info("📋 Found {$count} expired voucher(s) to delete");

            if (!$isDryRun) {
                // Get sample data for logging before deletion
                $sampleVouchers = $expiredVouchersQuery->limit(5)
                    ->with(['router:id,name', 'creator:id,name'])
                    ->get(['id', 'username', 'batch', 'status', 'expires_at', 'router_id', 'created_by']);

                // Delete expired vouchers (RADIUS first, then database)
                $deletedCount = 0;
                $failedCount = 0;
                $radiusDeletedCount = 0;

                // Process in chunks to avoid memory issues with large datasets
                Voucher::where('expires_at', '<', $now)
                    ->whereNotNull('expires_at')
                    ->with('router.radiusServer') // Eager load RADIUS server
                    ->chunkById(100, function ($vouchers) use (&$deletedCount, &$failedCount, &$radiusDeletedCount) {
                        foreach ($vouchers as $voucher) {
                            try {
                                $router = $voucher->router;
                                $radiusDeleted = false;

                                // If router has RADIUS server configured, delete from RADIUS first
                                if ($router && $router->radiusServer && $router->radiusServer->isReady()) {
                                    try {
                                        $radiusService = new RadiusApiService($router->radiusServer);
                                        $radiusResult = $radiusService->deleteVoucher($voucher->username);

                                        Log::info('Expired voucher deleted from RADIUS server', [
                                            'voucher_id' => $voucher->id,
                                            'username' => $voucher->username,
                                            'radius_server_id' => $router->radiusServer->id,
                                            'radius_response' => $radiusResult,
                                        ]);

                                        $radiusDeleted = true;
                                        $radiusDeletedCount++;
                                    } catch (\Exception $e) {
                                        // Log error and skip this voucher - don't delete from DB if RADIUS deletion fails
                                        Log::error('Failed to delete expired voucher from RADIUS server', [
                                            'voucher_id' => $voucher->id,
                                            'username' => $voucher->username,
                                            'radius_server_id' => $router->radiusServer->id,
                                            'error' => $e->getMessage(),
                                        ]);

                                        $failedCount++;
                                        return; // Skip to next voucher (continue in foreach)
                                    }
                                }

                                // Delete from RADTik database only after successful RADIUS deletion (or no RADIUS configured)
                                \App\Services\VoucherLogger::log(
                                    $voucher,
                                    $router,
                                    'deleted',
                                    [
                                        'deleted_by' => null, // Automated deletion
                                        'batch' => $voucher->batch,
                                        'status' => $voucher->status,
                                        'expired_at' => $voucher->expires_at?->toDateTimeString(),
                                        'radius_deleted' => $radiusDeleted,
                                    ],
                                    'Automatic deletion of expired voucher'
                                );

                                $voucher->delete();
                                $deletedCount++;
                            } catch (\Exception $e) {
                                Log::error('Failed to delete expired voucher', [
                                    'voucher_id' => $voucher->id,
                                    'error' => $e->getMessage(),
                                ]);
                                $failedCount++;
                            }
                        }
                    });

                $this->info("✓ Successfully deleted {$deletedCount} expired voucher(s) from RADTik database");
                
                if ($radiusDeletedCount > 0) {
                    $this->info("✓ Deleted {$radiusDeletedCount} voucher(s) from RADIUS server");
                }

                if ($failedCount > 0) {
                    $this->warn("⚠ Failed to delete {$failedCount} voucher(s)");
                }

                // Log summary
                Log::info('Expired vouchers deleted', [
                    'total_deleted' => $deletedCount,
                    'radius_deleted' => $radiusDeletedCount,
                    'failed' => $failedCount,
                    'timestamp' => $now->toDateTimeString(),
                    'samples' => $sampleVouchers->map(fn($v) => [
                        'username' => $v->username,
                        'batch' => $v->batch,
                        'expired_at' => $v->expires_at->toDateTimeString(),
                        'router' => $v->router?->name,
                        'creator' => $v->creator?->name,
                    ])->toArray(),
                ]);
            } else {
                // Dry run - just show what would be deleted
                $sampleVouchers = $expiredVouchersQuery->limit(10)
                    ->with(['router:id,name'])
                    ->get(['id', 'username', 'batch', 'expires_at', 'router_id']);

                $this->table(
                    ['ID', 'Username', 'Batch', 'Expired At', 'Router'],
                    $sampleVouchers->map(fn($v) => [
                        $v->id,
                        $v->username,
                        $v->batch ?? 'N/A',
                        $v->expires_at->format('Y-m-d H:i:s'),
                        $v->router?->name ?? 'N/A',
                    ])
                );

                if ($count > 10) {
                    $this->info("... and " . ($count - 10) . " more");
                }

                $this->info("[DRY RUN] Would delete {$count} expired voucher(s)");
            }

            $duration = round(microtime(true) - $startTime, 2);
            $this->info("✅ Cleanup process completed in {$duration} seconds");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error during cleanup: ' . $e->getMessage());
            Log::error('Expired voucher cleanup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return self::FAILURE;
        }
    }
}
