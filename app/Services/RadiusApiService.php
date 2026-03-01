<?php

namespace App\Services;

use App\Models\RadiusServer;
use App\Models\Router;
use App\Models\Voucher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class RadiusApiService
{
    protected RadiusServer $server;

    public function __construct(RadiusServer $server)
    {
        $this->server = $server;
    }

    /**
     * Sync a batch of vouchers to RADIUS server
     * 
     * @param Collection $vouchers Collection of Voucher models
     * @param Router $router Router model for nas_identifier
     * @return array Response from RADIUS API
     * @throws Exception
     */
    public function syncBatch(Collection $vouchers, Router $router): array
    {
        // Validate server configuration
        if (!$this->server->isReady()) {
            throw new Exception('RADIUS server is not ready. Status: ' . $this->server->installation_status);
        }

        if (!$this->server->auth_token) {
            throw new Exception('RADIUS server auth token is not configured.');
        }

        $endpoint = $this->server->sync_endpoint;

        if (!$endpoint) {
            throw new Exception('RADIUS server API endpoint is not configured.');
        }

        // Transform vouchers to API format
        $payload = [
            'vouchers' => $vouchers->map(function ($voucher) use ($router) {
                return [
                    'username' => $voucher->username,
                    'password' => $voucher->password,
                    'mikrotik_rate_limit' => $voucher->profile->rate_limit ?? '10M/10M',
                    'nas_identifier' => $router->nas_identifier,
                ];
            })->toArray(),
        ];

        Log::info('Syncing vouchers to RADIUS', [
            'server_id' => $this->server->id,
            'server_name' => $this->server->name,
            'endpoint' => $endpoint,
            'voucher_count' => $vouchers->count(),
            'nas_identifier' => $router->nas_identifier,
        ]);

        try {
            // Send HTTP request to RADIUS server
            $response = Http::timeout(30)
                ->retry(2, 100) // Retry 2 times with 100ms delay
                ->withToken($this->server->auth_token)
                ->post($endpoint, $payload);

            if (!$response->successful()) {
                throw new Exception(
                    "RADIUS API returned error: [{$response->status()}] " . $response->body()
                );
            }

            $result = $response->json();

            Log::info('RADIUS sync completed', [
                'server_id' => $this->server->id,
                'response' => $result,
            ]);

            return $result;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('RADIUS API connection failed', [
                'server_id' => $this->server->id,
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('Failed to connect to RADIUS server: ' . $e->getMessage());
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('RADIUS API request failed', [
                'server_id' => $this->server->id,
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('RADIUS API request failed: ' . $e->getMessage());
        }
    }

    /**
     * Delete a voucher from RADIUS server
     * 
     * @param string $username Voucher username
     * @return array Response from RADIUS API
     * @throws Exception
     */
    public function deleteVoucher(string $username): array
    {
        if (!$this->server->isReady()) {
            throw new Exception('RADIUS server is not ready.');
        }

        $endpoint = $this->server->api_url . '/delete/voucher';

        Log::info('Deleting voucher from RADIUS', [
            'server_id' => $this->server->id,
            'username' => $username,
        ]);

        try {
            $response = Http::timeout(15)
                ->withToken($this->server->auth_token)
                ->delete($endpoint, ['username' => $username]);

            if (!$response->successful()) {
                throw new Exception(
                    "Failed to delete voucher: [{$response->status()}] " . $response->body()
                );
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('RADIUS voucher deletion failed', [
                'username' => $username,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Toggle voucher status (enable/disable) in RADIUS server
     * 
     * @param string $username Voucher username
     * @param string $status New status ('active' or 'disabled')
     * @return array Response from RADIUS API
     * @throws Exception
     */
    public function toggleVoucherStatus(string $username, string $status): array
    {
        if (!$this->server->isReady()) {
            throw new Exception('RADIUS server is not ready.');
        }

        if (!in_array($status, ['active', 'disabled'])) {
            throw new Exception('Invalid status. Must be "active" or "disabled"');
        }

        $endpoint = $this->server->api_url . '/toggle/voucher-status';

        Log::info('Toggling voucher status in RADIUS', [
            'server_id' => $this->server->id,
            'username' => $username,
            'status' => $status,
        ]);

        try {
            $response = Http::timeout(15)
                ->withToken($this->server->auth_token)
                ->post($endpoint, [
                    'username' => $username,
                    'status' => $status,
                ]);

            if (!$response->successful()) {
                throw new Exception(
                    "Failed to toggle voucher status: [{$response->status()}] " . $response->body()
                );
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('RADIUS voucher status toggle failed', [
                'username' => $username,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Test connection to RADIUS API
     * 
     * @return bool
     */
    public function testConnection(): bool
    {
        try {
            $endpoint = $this->server->api_url . '/health';

            $response = Http::timeout(5)
                ->withToken($this->server->auth_token)
                ->get($endpoint);

            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('RADIUS API health check failed', [
                'server_id' => $this->server->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
