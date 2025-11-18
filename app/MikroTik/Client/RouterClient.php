<?php

namespace App\MikroTik\Client;

use App\Models\Router;
use RouterOS\Client;
use RouterOS\Query;
use Throwable;

class RouterClient
{
    public function __construct(
        private int $timeout       = 4,  // connect timeout (s)
        private int $socketTimeout = 3,  // read timeout (s)
        private int $attempts      = 1,  // reconnect attempts
        private int $retryDelay    = 0,  // seconds
    ) {}

    /**
     * Build a RouterOS client using credentials from the Router model
     */
    public function make(Router $router): Client
    {
        $host   = $router->address;              // prefer IP
        $port   = (int)($router->port ?: 8728);  // ensure correct field
        $useSSL = ($port === 8729) || (bool)($router->use_tls ?? false);

        $options = [
            'host'           => $host,
            'user'           => $router->username,
            'pass'           => $router->decryptedPassword(),
            'port'           => $port,
            'timeout'        => $this->timeout,
            'socket_timeout' => $this->socketTimeout,
            'attempts'       => $this->attempts,
            'delay'          => $this->retryDelay,
        ];

        if ($useSSL) {
            $options['ssl'] = true;
            // self-signed dev/edge cases; in prod prefer proper cert
            $options['ssl_context'] = stream_context_create([
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
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
        $port = (int)($router->port ?: 8728);

        $errno  = 0;
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
