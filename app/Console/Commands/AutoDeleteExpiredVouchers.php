<?php

namespace App\Console\Commands;

use App\Models\Voucher;
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

                // Delete expired vouchers (this will trigger the model's deleting event for logging)
                $deletedCount = 0;

                // Process in chunks to avoid memory issues with large datasets
                Voucher::where('expires_at', '<', $now)
                    ->whereNotNull('expires_at')
                    ->chunkById(100, function ($vouchers) use (&$deletedCount) {
                        foreach ($vouchers as $voucher) {
                            try {
                                $voucher->delete(); // Uses model event for logging
                                $deletedCount++;
                            } catch (\Exception $e) {
                                Log::error('Failed to delete expired voucher', [
                                    'voucher_id' => $voucher->id,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }
                    });

                $this->info("✓ Successfully deleted {$deletedCount} expired voucher(s)");

                // Log summary
                Log::info('Expired vouchers deleted', [
                    'count' => $deletedCount,
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
