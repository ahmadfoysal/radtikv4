<?php

namespace App\MikroTik\Client;

use App\Models\Router;
use RouterOS\Client;
use RouterOS\Query;
use Throwable;

class RouterClient
{
    public function __construct(
        private int $timeout = 60,  // connect timeout (s)
        private int $socketTimeout = 60,  // read timeout (s)
        private int $attempts = 1,  // reconnect attempts
        private int $retryDelay = 0,  // seconds
    ) {}

    /**
     * Build a RouterOS client using credentials from the Router model
     */
    public function make(Router $router): Client
    {
        // Check if demo mode is enabled
        if (env('DEMO_MODE', false)) {
            throw new \Exception("🚫 Demo Mode: Router interactions are disabled. This is a demonstration environment and all router operations are blocked.");
        }

        // Check if the router's owner is suspended
        if ($router->user && $router->user->isSuspended()) {
            throw new \Exception("Access denied: User account is suspended. Reason: " . ($router->user->suspension_reason ?? 'No reason provided'));
        }

        // Check subscription status for MikroTik API access
        if ($router->user && !$router->user->hasRole('superadmin')) {
            $subscription = $router->user->activeSubscription();

            if (!$subscription) {
                throw new \Exception("MikroTik API access denied: Active subscription required. Please subscribe to a package.");
            }

            // Check if grace period has ended (allow API access during grace period)
            $now = now();
            $endDate = $subscription->end_date;
            $gracePeriodDays = $subscription->package->grace_period_days ?? 0;
            $gracePeriodEndDate = $endDate->copy()->addDays($gracePeriodDays);

            // Block only after grace period ends (allow during grace period)
            if ($now->gt($gracePeriodEndDate)) {
                throw new \Exception("MikroTik API access blocked: Your subscription and grace period have ended. All router operations are disabled. Please renew immediately.");
            }
        }

        // Parse connection details
        $host = $router->address;
        $port = (int) ($router->port ?: 8728);
        $useSSL = $router->use_ssl ?? false;

        $options = [
            'host' => $host,
            'user' => $router->username,
            'pass' => $router->decryptedPassword(),
            'port' => $port,
            'timeout' => $this->timeout,
            'socket_timeout' => $this->socketTimeout,
            'attempts' => $this->attempts,
            'delay' => $this->retryDelay,
        ];

        if ($useSSL) {
            $options['ssl'] = true;
            // self-signed dev/edge cases; in prod prefer proper cert
            $options['ssl_context'] = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ]);
        }

        return new Client($options);
    }

    /**
     * Quick TCP reachability pre-check to avoid long hangs
     */
    public function reachable(Router $router): bool
    {
        $host = $router->address;
        $port = (int) ($router->port ?: 8728);

        $errno = 0;
        $errstr = '';
        $fp = @fsockopen($host, $port, $errno, $errstr, 1.2); // ~1s

        if ($fp) {
            fclose($fp);

            return true;
        }

        return false;
    }

    /**
     * Safe query wrapper with clear exceptions
     */
    public function safeRead(Client $client, Query $query): array
    {
        try {
            $resp = $client->query($query)->read();

            return is_array($resp) ? $resp : [];
        } catch (Throwable $e) {
            $msg = $e->getMessage() ?: 'RouterOS read failed';
            throw new \RuntimeException($msg, 0, $e);
        }
    }

    /**
     * Return first row from response (helper)
     */
    public function firstRow(mixed $response): array
    {
        return (is_array($response) && isset($response[0]) && is_array($response[0]))
            ? $response[0]
            : [];
    }

    /**
     * Resolve /ip/hotspot/user .id by name
     */
    public function resolveUserIdByName(Client $client, string $name): ?string
    {
        $q = (new Query('/ip/hotspot/user/print'))
            ->where('name', $name)
            ->equal('.proplist', '.id');

        $row = $this->firstRow($this->safeRead($client, $q));

        return $row['.id'] ?? null;
    }

    /**
     * Resolve /ip/hotspot/active .id by username or given .id
     */
    public function resolveActiveUserId(Client $client, string $usernameOrActiveId): ?string
    {
        if (str_starts_with($usernameOrActiveId, '*')) {
            return $usernameOrActiveId;
        }

        $q = (new Query('/ip/hotspot/active/print'))
            ->where('user', $usernameOrActiveId)
            ->equal('.proplist', '.id');

        $row = $this->firstRow($this->safeRead($client, $q));

        return $row['.id'] ?? null;
    }
}
