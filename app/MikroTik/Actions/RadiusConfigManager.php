<?php

namespace App\MikroTik\Actions;

use App\MikroTik\Client\RouterClient;
use App\Models\Router;
use RouterOS\Query;

class RadiusConfigManager
{
    public function __construct(
        private RouterClient $client,
    ) {}

    /**
     * Check RADIUS configuration status on MikroTik
     */
    public function checkRadiusConfig(Router $router): array
    {
        if (!$router->radiusServer) {
            return [
                'configured' => false,
                'issues' => ['No RADIUS server assigned to this router'],
                'details' => [],
            ];
        }

        $radiusServer = $router->radiusServer;
        $issues = [];
        $details = [];

        try {
            $ros = $this->client->make($router);

            // 1. Check System Identity
            $identityResp = $this->client->safeRead($ros, new Query('/system/identity/print'));
            $currentIdentity = $this->client->firstRow($identityResp)['name'] ?? '';
            $expectedIdentity = $router->nas_identifier;

            $identityMatch = $currentIdentity === $expectedIdentity;
            $details['identity'] = [
                'current' => $currentIdentity,
                'expected' => $expectedIdentity,
                'match' => $identityMatch,
            ];

            if (!$identityMatch) {
                $issues[] = "Identity mismatch: current '{$currentIdentity}' should be '{$expectedIdentity}'";
            }

            // 2. Check RADIUS server configuration
            $radiusResp = $this->client->safeRead(
                $ros,
                (new Query('/radius/print'))->equal('.proplist', '.id,service,address,secret,timeout,disabled')
            );

            $radiusConfigured = false;
            $radiusDetails = null;

            foreach ($radiusResp as $rad) {
                if (str_contains($rad['service'] ?? '', 'hotspot')) {
                    $radiusConfigured = true;
                    $radiusDetails = [
                        'id' => $rad['.id'] ?? null,
                        'address' => $rad['address'] ?? '',
                        'timeout' => $rad['timeout'] ?? '',
                        'disabled' => ($rad['disabled'] ?? 'false') === 'true',
                        'service' => $rad['service'] ?? '',
                    ];
                    break;
                }
            }

            $details['radius_server'] = $radiusDetails;

            if (!$radiusConfigured) {
                $issues[] = 'No RADIUS server configured for hotspot service';
            } else {
                // Check if RADIUS server address matches
                $expectedAddress = $radiusServer->host . ':' . $radiusServer->auth_port;
                if ($radiusDetails['address'] !== $expectedAddress) {
                    $issues[] = "RADIUS address mismatch: '{$radiusDetails['address']}' should be '{$expectedAddress}'";
                }

                // Check timeout
                $expectedTimeout = 3000; // 3000ms = 3s
                $currentTimeout = (int) str_replace('ms', '', $radiusDetails['timeout']);
                if ($currentTimeout !== $expectedTimeout) {
                    $issues[] = "RADIUS timeout mismatch: {$currentTimeout}ms should be {$expectedTimeout}ms";
                }

                // Check if disabled
                if ($radiusDetails['disabled']) {
                    $issues[] = 'RADIUS server is disabled';
                }
            }

            // 3. Check Hotspot Server Profile for RADIUS
            $profileResp = $this->client->safeRead(
                $ros,
                (new Query('/ip/hotspot/profile/print'))->equal('.proplist', '.id,name,use-radius')
            );

            $radiusEnabledInProfile = false;
            $profileDetails = [];

            foreach ($profileResp as $prof) {
                $profileDetails[] = [
                    'name' => $prof['name'] ?? '',
                    'use_radius' => ($prof['use-radius'] ?? 'no') === 'yes',
                ];

                if (($prof['use-radius'] ?? 'no') === 'yes') {
                    $radiusEnabledInProfile = true;
                }
            }

            $details['hotspot_profiles'] = $profileDetails;

            if (!$radiusEnabledInProfile) {
                $issues[] = 'RADIUS not enabled in any hotspot server profile';
            }

            // Overall status
            $configured = empty($issues);

            return [
                'configured' => $configured,
                'issues' => $issues,
                'details' => $details,
            ];

        } catch (\Throwable $e) {
            return [
                'configured' => false,
                'issues' => ['Failed to check RADIUS config: ' . $e->getMessage()],
                'details' => [],
            ];
        }
    }

    /**
     * Apply RADIUS configuration to MikroTik
     */
    public function applyRadiusConfig(Router $router): array
    {
        if (!$router->radiusServer) {
            return [
                'success' => false,
                'message' => 'No RADIUS server assigned to this router',
                'steps' => [],
            ];
        }

        $radiusServer = $router->radiusServer;
        $steps = [];
        $errors = [];

        try {
            $ros = $this->client->make($router);

            // Step 1: Set System Identity
            try {
                $this->client->safeRead(
                    $ros,
                    (new Query('/system/identity/set'))
                        ->equal('name', $router->nas_identifier)
                );
                $steps[] = "✓ Set system identity to '{$router->nas_identifier}'";
            } catch (\Throwable $e) {
                $errors[] = "Failed to set identity: " . $e->getMessage();
            }

            // Step 2: Remove existing RADIUS hotspot configurations
            try {
                $radiusResp = $this->client->safeRead(
                    $ros,
                    (new Query('/radius/print'))->equal('.proplist', '.id,service')
                );

                foreach ($radiusResp as $rad) {
                    if (str_contains($rad['service'] ?? '', 'hotspot')) {
                        $this->client->safeRead(
                            $ros,
                            (new Query('/radius/remove'))->equal('.id', $rad['.id'])
                        );
                        $steps[] = "✓ Removed existing RADIUS hotspot configuration";
                    }
                }
            } catch (\Throwable $e) {
                // Not critical if no existing config
                $steps[] = "⚠ No existing RADIUS config to remove";
            }

            // Step 3: Add new RADIUS server
            try {
                $this->client->safeRead(
                    $ros,
                    (new Query('/radius/add'))
                        ->equal('service', 'hotspot')
                        ->equal('address', $radiusServer->host . ':' . $radiusServer->auth_port)
                        ->equal('secret', $radiusServer->secret)
                        ->equal('timeout', '3000ms')
                        ->equal('disabled', 'no')
                );
                $steps[] = "✓ Added RADIUS server: {$radiusServer->host}:{$radiusServer->auth_port}";
            } catch (\Throwable $e) {
                $errors[] = "Failed to add RADIUS server: " . $e->getMessage();
            }

            // Step 4: Enable RADIUS in all hotspot server profiles
            try {
                $profileResp = $this->client->safeRead(
                    $ros,
                    (new Query('/ip/hotspot/profile/print'))->equal('.proplist', '.id,name')
                );

                foreach ($profileResp as $prof) {
                    $this->client->safeRead(
                        $ros,
                        (new Query('/ip/hotspot/profile/set'))
                            ->equal('.id', $prof['.id'])
                            ->equal('use-radius', 'yes')
                    );
                    $steps[] = "✓ Enabled RADIUS in profile '{$prof['name']}'";
                }
            } catch (\Throwable $e) {
                $errors[] = "Failed to enable RADIUS in profiles: " . $e->getMessage();
            }

            $success = empty($errors);
            $message = $success
                ? 'RADIUS configuration applied successfully'
                : 'RADIUS configuration completed with errors';

            return [
                'success' => $success,
                'message' => $message,
                'steps' => $steps,
                'errors' => $errors,
            ];

        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Failed to apply RADIUS config: ' . $e->getMessage(),
                'steps' => $steps,
                'errors' => array_merge($errors, [$e->getMessage()]),
            ];
        }
    }

    /**
     * Get current system identity
     */
    public function getSystemIdentity(Router $router): ?string
    {
        try {
            $ros = $this->client->make($router);
            $resp = $this->client->safeRead($ros, new Query('/system/identity/print'));
            return $this->client->firstRow($resp)['name'] ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Set system identity
     */
    public function setSystemIdentity(Router $router, string $identity): bool
    {
        try {
            $ros = $this->client->make($router);
            $this->client->safeRead(
                $ros,
                (new Query('/system/identity/set'))->equal('name', $identity)
            );
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
