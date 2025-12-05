<?php

namespace App\Services\Radius;

use App\Models\RadiusServer;
use App\RadiusServer\RadiusServerClient;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class RadiusService
{
    /**
     * Test connection to a RADIUS server.
     */
    public function testServer(RadiusServer $server): bool
    {
        try {
            $client = new RadiusServerClient($server);

            return $client->testConnection();
        } catch (Throwable $e) {
            Log::error('RadiusService: Server test failed', [
                'server_id' => $server->id,
                'host' => $server->host,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Sync a voucher to the RADIUS server.
     *
     * @param  RadiusServer  $server  The RADIUS server instance
     * @param  array  $voucher  Voucher data (username, password, profile, etc.)
     *
     * @throws RuntimeException
     */
    public function syncVoucherToRadius(RadiusServer $server, array $voucher): void
    {
        try {
            $client = new RadiusServerClient($server);

            // Add the user to FreeRADIUS
            $client->addUser([
                'username' => $voucher['username'] ?? $voucher['code'] ?? null,
                'password' => $voucher['password'] ?? $voucher['code'] ?? null,
                'profile' => $voucher['profile'] ?? null,
                'reply_items' => $voucher['reply_items'] ?? [],
            ]);

            // Reload FreeRADIUS to apply changes
            $client->reloadRadius();

            Log::info('RadiusService: Voucher synced to RADIUS', [
                'server_id' => $server->id,
                'username' => $voucher['username'] ?? $voucher['code'] ?? 'unknown',
            ]);
        } catch (Throwable $e) {
            Log::error('RadiusService: Failed to sync voucher', [
                'server_id' => $server->id,
                'host' => $server->host,
                'voucher' => $voucher['username'] ?? $voucher['code'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Failed to sync voucher to RADIUS: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Delete a voucher from the RADIUS server.
     *
     * @param  RadiusServer  $server  The RADIUS server instance
     * @param  string  $username  The username to remove
     *
     * @throws RuntimeException
     */
    public function deleteVoucherFromRadius(RadiusServer $server, string $username): void
    {
        try {
            $client = new RadiusServerClient($server);

            // Remove the user from FreeRADIUS
            $client->removeUser($username);

            // Reload FreeRADIUS to apply changes
            $client->reloadRadius();

            Log::info('RadiusService: Voucher deleted from RADIUS', [
                'server_id' => $server->id,
                'username' => $username,
            ]);
        } catch (Throwable $e) {
            Log::error('RadiusService: Failed to delete voucher', [
                'server_id' => $server->id,
                'host' => $server->host,
                'username' => $username,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Failed to delete voucher from RADIUS: '.$e->getMessage(), 0, $e);
        }
    }
}
