<?php

namespace App\Console\Commands;

use App\MikroTik\Actions\HotspotProfileManager;
use App\MikroTik\Actions\HotspotUserManager;
use App\MikroTik\Client\RouterClient;
use App\Models\Router;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RouterOS\Query;

class SyncMacBindings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'radtik:sync-mac-bindings 
                            {--router= : Sync specific router ID only}
                            {--dry-run : Show what would be synced without sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync MAC address bindings from MikroTik to RADIUS servers';

    /**
     * Execute the console command.
     */
    public function handle(RouterClient $routerClient): int
    {
        $this->info('Starting MAC binding synchronization...');

        // Get routers to process
        $query = Router::with('radiusServer')
            ->whereNotNull('radius_server_id')
            ->where(function ($q) {
                $q->whereNull('is_nas_device')
                    ->orWhere('is_nas_device', false);
            });

        if ($this->option('router')) {
            $query->where('id', $this->option('router'));
        }

        $routers = $query->get();

        if ($routers->isEmpty()) {
            $this->warn('No routers found with RADIUS servers configured.');
            return self::SUCCESS;
        }

        $this->info("Found {$routers->count()} router(s) to process");

        $totalSynced = 0;
        $totalErrors = 0;

        foreach ($routers as $router) {
            $this->line('');
            $this->info("Processing: {$router->name} (ID: {$router->id})");

            try {
                // Check if router is reachable
                if (!$routerClient->reachable($router)) {
                    $this->warn("  ⚠ Router unreachable, skipping");
                    $totalErrors++;
                    continue;
                }

                // Check if RADIUS server is active
                if (!$router->radiusServer || !$router->radiusServer->is_active) {
                    $this->warn("  ⚠ RADIUS server not active, skipping");
                    $totalErrors++;
                    continue;
                }

                // Get MAC binding data from MikroTik
                $macBindings = $this->getMacBindingsFromMikroTik($routerClient, $router);

                if (empty($macBindings)) {
                    $this->line("  No MAC bindings found");
                    continue;
                }

                $bindingCount = count($macBindings);
                $this->line("  Found {$bindingCount} user(s) with MAC binding");

                // Show bindings if dry-run
                if ($this->option('dry-run')) {
                    foreach ($macBindings as $binding) {
                        $this->line("    - {$binding['username']} → {$binding['mac_address']}");
                    }
                    continue;
                }

                // Sync to RADIUS server
                $synced = $this->syncToRadiusServer($router->radiusServer, $macBindings);

                if ($synced) {
                    $this->info("  ✓ Synced {$bindingCount} bindings successfully");
                    $totalSynced += $bindingCount;
                } else {
                    $this->error("  ✗ Failed to sync to RADIUS server");
                    $totalErrors++;
                }

            } catch (\Exception $e) {
                $this->error("  ✗ Error: {$e->getMessage()}");
                Log::error('MAC binding sync failed', [
                    'router_id' => $router->id,
                    'router_name' => $router->name,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $totalErrors++;
            }
        }

        $this->line('');
        $this->info("=== Synchronization Complete ===");
        
        if ($this->option('dry-run')) {
            $this->info("Dry run completed (no changes made)");
        } else {
            $this->info("Total synced: {$totalSynced}");
        }
        
        if ($totalErrors > 0) {
            $this->warn("Errors: {$totalErrors}");
        }

        return self::SUCCESS;
    }

    /**
     * Get MAC bindings from MikroTik router
     */
    protected function getMacBindingsFromMikroTik(RouterClient $client, Router $router): array
    {
        $ros = $client->make($router);
        $bindings = [];

        // Get all hotspot users with their MAC addresses and profiles
        $query = (new Query('/ip/hotspot/user/print'))
            ->equal('.proplist', 'name,mac-address,profile');

        $users = $client->safeRead($ros, $query);

        if (!is_array($users) || empty($users)) {
            return [];
        }

        // Filter users that have MAC addresses set
        foreach ($users as $user) {
            $username = $user['name'] ?? null;
            $macAddress = $user['mac-address'] ?? null;
            $profile = $user['profile'] ?? null;

            // Skip if no username or no MAC address
            if (!$username || !$macAddress) {
                continue;
            }

            // Normalize MAC address (remove hyphens, convert to uppercase with colons)
            $macAddress = $this->normalizeMacAddress($macAddress);

            $bindings[] = [
                'username' => $username,
                'mac_address' => $macAddress,
                'profile' => $profile,
            ];
        }

        return $bindings;
    }

    /**
     * Sync MAC bindings to RADIUS server via API
     */
    protected function syncToRadiusServer($radiusServer, array $bindings): bool
    {
        try {
            $url = rtrim($radiusServer->api_url, '/') . '/sync-mac-bindings';

            $response = Http::timeout(30)
                ->withToken($radiusServer->auth_token)
                ->post($url, [
                    'bindings' => $bindings,
                ]);

            if ($response->successful()) {
                Log::info('MAC bindings synced to RADIUS', [
                    'radius_server_id' => $radiusServer->id,
                    'count' => count($bindings),
                ]);
                return true;
            }

            Log::error('RADIUS API returned error', [
                'radius_server_id' => $radiusServer->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error('Failed to sync MAC bindings to RADIUS', [
                'radius_server_id' => $radiusServer->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Normalize MAC address format
     * Converts formats like AA-BB-CC-DD-EE-FF or AABBCCDDFF to AA:BB:CC:DD:EE:FF
     */
    protected function normalizeMacAddress(string $mac): string
    {
        // Remove all separators
        $mac = str_replace([':', '-', '.', ' '], '', $mac);
        
        // Convert to uppercase
        $mac = strtoupper($mac);
        
        // Insert colons every 2 characters
        $mac = implode(':', str_split($mac, 2));
        
        return $mac;
    }
}
