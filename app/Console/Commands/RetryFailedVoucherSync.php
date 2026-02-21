<?php

namespace App\Console\Commands;

use App\Jobs\RetryFailedVoucherSyncJob;
use App\Models\Router;
use Illuminate\Console\Command;

class RetryFailedVoucherSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'radius:retry-failed-vouchers 
                            {--router= : Specific router ID to retry. If not provided, retries all routers}
                            {--limit=1000 : Maximum number of vouchers to retry per router}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry syncing failed vouchers to RADIUS servers';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $routerId = $this->option('router');
        $limit = (int) $this->option('limit');

        if ($routerId) {
            // Retry specific router
            $router = Router::with('radiusServer')->find($routerId);

            if (!$router) {
                $this->error("Router with ID {$routerId} not found.");
                return self::FAILURE;
            }

            if (!$router->radiusServer) {
                $this->warn("Router '{$router->name}' has no RADIUS server configured.");
                return self::FAILURE;
            }

            $this->info("Dispatching retry job for router: {$router->name}");
            RetryFailedVoucherSyncJob::dispatch($router->id, $limit);

            $this->info("✓ Retry job dispatched successfully!");
            return self::SUCCESS;
        }

        // Retry all routers that have RADIUS servers configured
        $routers = Router::with('radiusServer')
            ->whereHas('radiusServer')
            ->get();

        if ($routers->isEmpty()) {
            $this->warn('No routers with RADIUS server configuration found.');
            return self::SUCCESS;
        }

        $this->info("Found {$routers->count()} router(s) with RADIUS configuration.");

        foreach ($routers as $router) {
            $this->line("Dispatching retry job for: {$router->name}");
            RetryFailedVoucherSyncJob::dispatch($router->id, $limit);
        }

        $this->info("✓ All retry jobs dispatched successfully!");
        $this->comment("Jobs are running in the background queue.");

        return self::SUCCESS;
    }
}
