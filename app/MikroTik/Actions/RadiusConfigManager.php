<?php

namespace App\MikroTik\Actions;

use App\MikroTik\Client\RouterClient;
use App\Models\Router;
use RouterOS\Query;
use Illuminate\Support\Facades\Log;

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
                (new Query('/radius/print'))->equal('.proplist', '.id,service,address,authentication-port,secret,timeout,disabled')
            );

            $radiusConfigured = false;
            $radiusDetails = null;

            foreach ($radiusResp as $rad) {
                if (str_contains($rad['service'] ?? '', 'hotspot')) {
                    $radiusConfigured = true;
                    $radiusDetails = [
                        'id' => $rad['.id'] ?? null,
                        'address' => $rad['address'] ?? '',
                        'authentication-port' => $rad['authentication-port'] ?? '',
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
                // Check if RADIUS server address and port match (stored separately in MikroTik)
                $expectedHost = $radiusServer->host;
                $expectedPort = (string) $radiusServer->auth_port;
                $actualAddress = $radiusDetails['address'] ?? '';
                $actualPort = $radiusDetails['authentication-port'] ?? $radiusDetails['port'] ?? '';
                
                if ($actualAddress !== $expectedHost || $actualPort !== $expectedPort) {
                    $issues[] = "RADIUS address mismatch: '{$actualAddress}:{$actualPort}' should be '{$expectedHost}:{$expectedPort}'";
                }

                // Check timeout (handle both 'ms' and 's' formats)
                $expectedTimeout = ($radiusServer->timeout ?? 3) * 1000; // Convert to milliseconds
                $timeoutStr = $radiusDetails['timeout'];
                
                // Parse timeout - can be in format: "5s", "5000ms", "300ms", etc.
                if (str_ends_with($timeoutStr, 's')) {
                    if (str_ends_with($timeoutStr, 'ms')) {
                        // Already in milliseconds: "5000ms"
                        $currentTimeout = (int) str_replace('ms', '', $timeoutStr);
                    } else {
                        // In seconds: "5s" - convert to milliseconds
                        $currentTimeout = (int) str_replace('s', '', $timeoutStr) * 1000;
                    }
                } else {
                    $currentTimeout = (int) $timeoutStr;
                }
                
                Log::debug('RADIUS timeout check', [
                    'raw_timeout' => $timeoutStr,
                    'parsed_timeout_ms' => $currentTimeout,
                    'expected_timeout_ms' => $expectedTimeout,
                ]);
                
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
                (new Query('/ip/hotspot/profile/print'))->equal('.proplist', '.id,name,use-radius,radius-accounting')
            );

            $radiusEnabledInProfile = false;
            $profileDetails = [];

            foreach ($profileResp as $prof) {
                // MikroTik returns 'yes', 'true', or '1' for enabled, 'no', 'false', or '0' for disabled
                $useRadius = in_array(strtolower($prof['use-radius'] ?? 'no'), ['yes', 'true', '1']);
                
                // Debug logging
                Log::debug('Checking hotspot profile RADIUS status', [
                    'profile_name' => $prof['name'] ?? 'unknown',
                    'use_radius_raw' => $prof['use-radius'] ?? 'not set',
                    'use_radius_parsed' => $useRadius,
                ]);
                
                $profileDetails[] = [
                    'name' => $prof['name'] ?? '',
                    'use_radius' => $useRadius,
                ];

                if ($useRadius) {
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
                'errors' => ['Router has no RADIUS server configured in database'],
            ];
        }

        $radiusServer = $router->radiusServer;
        $steps = [];
        $errors = [];

        // Validate RADIUS server data
        if (empty($radiusServer->host)) {
            return [
                'success' => false,
                'message' => 'RADIUS server configuration is incomplete',
                'steps' => [],
                'errors' => ['RADIUS server host is empty'],
            ];
        }

        if (empty($radiusServer->secret)) {
            return [
                'success' => false,
                'message' => 'RADIUS server configuration is incomplete',
                'steps' => [],
                'errors' => ['RADIUS server secret is empty'],
            ];
        }

        $steps[] = "ℹ️ RADIUS Server Details: {$radiusServer->name}";
        $steps[] = "ℹ️ Address: {$radiusServer->host}:{$radiusServer->auth_port}";
        $steps[] = "ℹ️ NAS Identifier: {$router->nas_identifier}";

        Log::info('Starting RADIUS configuration', [
            'router_id' => $router->id,
            'router_name' => $router->name,
            'radius_server_id' => $radiusServer->id,
            'radius_server_name' => $radiusServer->name,
            'radius_host' => $radiusServer->host,
            'radius_port' => $radiusServer->auth_port,
            'nas_identifier' => $router->nas_identifier,
        ]);

        try {
            $ros = $this->client->make($router);

            // Step 1: Set System Identity
            try {
                Log::info('Setting system identity', ['identity' => $router->nas_identifier]);
                $this->client->safeRead(
                    $ros,
                    (new Query('/system/identity/set'))
                        ->equal('name', $router->nas_identifier)
                );
                $steps[] = "✓ Set system identity to '{$router->nas_identifier}'";
                Log::info('System identity set successfully');
            } catch (\Throwable $e) {
                Log::error('Failed to set system identity', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $errors[] = "Failed to set identity: " . $e->getMessage();
            }

            // Step 2: Remove existing RADIUS hotspot configurations
            try {
                Log::info('Checking for existing RADIUS configurations');
                $radiusResp = $this->client->safeRead(
                    $ros,
                    (new Query('/radius/print'))->equal('.proplist', '.id,service')
                );

                Log::info('Existing RADIUS configurations before cleanup', [
                    'count' => count($radiusResp),
                    'configs' => $radiusResp,
                ]);

                foreach ($radiusResp as $rad) {
                    if (str_contains($rad['service'] ?? '', 'hotspot')) {
                        Log::info('Removing existing RADIUS hotspot config', ['id' => $rad['.id']]);
                        $this->client->safeRead(
                            $ros,
                            (new Query('/radius/remove'))->equal('.id', $rad['.id'])
                        );
                        $steps[] = "✓ Removed existing RADIUS hotspot configuration";
                        Log::info('Successfully removed RADIUS config', ['id' => $rad['.id']]);
                    }
                }
            } catch (\Throwable $e) {
                // Not critical if no existing config
                Log::info('No existing RADIUS config to remove or removal failed', ['error' => $e->getMessage()]);
                $steps[] = "⚠ No existing RADIUS config to remove";
            }

            // Step 3: Add new RADIUS server
            try {
                $radiusAddress = $radiusServer->host . ':' . $radiusServer->auth_port;
                $radiusSecret = $radiusServer->secret;
                $radiusTimeout = ($radiusServer->timeout ?? 3) * 1000; // Convert seconds to milliseconds
                
                $steps[] = "🔄 Attempting to add RADIUS server with address: {$radiusAddress}";
                $steps[] = "🔄 Timeout: {$radiusTimeout}ms, Secret length: " . strlen($radiusSecret) . " chars";
                
                Log::info('Adding RADIUS server to MikroTik', [
                    'address' => $radiusAddress,
                    'service' => 'hotspot',
                    'timeout' => $radiusTimeout . 'ms',
                    'secret_length' => strlen($radiusSecret),
                    'secret_empty' => empty($radiusSecret),
                ]);
                
                // Try adding RADIUS server without any query parameters first - just print to see what's already there
                Log::info('First, checking existing RADIUS configuration');
                $existingRadius = $this->client->safeRead($ros, new Query('/radius/print'));
                Log::info('Existing RADIUS entries', ['entries' => $existingRadius]);
                
                // Now try a completely different approach - use terminal command style via RouterOS
                Log::info('Attempting RADIUS add via direct command without Query builder');
                
                // Try 1: /radius/incoming/add (some RouterOS versions use this path)
                Log::info('Attempt 1: Using /radius/incoming/add path');
                $addQuery1 = (new Query('/radius/incoming/add'))
                    ->equal('address', $radiusAddress)
                    ->equal('secret', $radiusSecret)
                    ->equal('service', 'hotspot');
                
                $addResult = $this->client->safeRead($ros, $addQuery1);
                Log::info('Attempt 1 result', ['result' => $addResult]);
                
                // Try 2: With 'services' instead of 'service' on /radius/incoming
                if (isset($addResult['after']['message'])) {
                    Log::info('Attempt 2: Using /radius/incoming/add with services (plural)');
                    $addQuery2 = (new Query('/radius/incoming/add'))
                        ->equal('address', $radiusAddress)
                        ->equal('secret', $radiusSecret)
                        ->equal('services', 'hotspot');
                    
                    $addResult = $this->client->safeRead($ros, $addQuery2);
                    Log::info('Attempt 2 result', ['result' => $addResult]);
                }
                
                // Try 3: Split address into host and port separately
                if (isset($addResult['after']['message'])) {
                    Log::info('Attempt 3: Splitting address into address + port');
                    list($host, $port) = explode(':', $radiusAddress);
                    $addQuery3 = (new Query('/radius/add'))
                        ->equal('address', $host)
                        ->equal('authentication-port', $port)
                        ->equal('secret', $radiusSecret)
                        ->equal('service', 'hotspot');
                    
                    $addResult = $this->client->safeRead($ros, $addQuery3);
                    Log::info('Attempt 3 result', ['result' => $addResult]);
                }
                
                // Try 4: Use /radius/incoming with split address
                if (isset($addResult['after']['message'])) {
                    Log::info('Attempt 4: Using /radius/incoming/add with split address');
                    list($host, $port) = explode(':', $radiusAddress);
                    $addQuery4 = (new Query('/radius/incoming/add'))
                        ->equal('address', $host)
                        ->equal('authentication-port', $port)
                        ->equal('secret', $radiusSecret)
                        ->equal('service', 'hotspot');
                    
                    $addResult = $this->client->safeRead($ros, $addQuery4);
                    Log::info('Attempt 4 result', ['result' => $addResult]);
                }
                
                // Try 5: Check if we need to use 'src-address' instead
                if (isset($addResult['after']['message'])) {
                    Log::info('Attempt 5: Using /radius/add with src-address');
                    $addQuery5 = (new Query('/radius/add'))
                        ->equal('src-address', $radiusAddress)
                        ->equal('secret', $radiusSecret)
                        ->equal('service', 'hotspot');
                    
                    $addResult = $this->client->safeRead($ros, $addQuery5);
                    Log::info('Attempt 5 result', ['result' => $addResult]);
                }
                
                Log::info('RADIUS server add command executed', [
                    'result' => $addResult,
                    'result_type' => gettype($addResult),
                    'result_count' => is_array($addResult) ? count($addResult) : 'N/A',
                ]);
                
                // If successful, try to set timeout separately
                if (empty($addResult) || !isset($addResult['after']['message'])) {
                    $steps[] = "✓ Successfully added RADIUS server: {$radiusAddress}";
                    Log::info('RADIUS server added successfully, now attempting to set timeout');
                    
                    // Get the ID of the newly added RADIUS server
                    try {
                        $radiusList = $this->client->safeRead(
                            $ros,
                            new Query('/radius/print')
                        );
                        
                        Log::info('Retrieved RADIUS list after add', ['count' => count($radiusList), 'list' => $radiusList]);
                        
                        // Split address to compare separately
                        list($expectedHost, $expectedPort) = explode(':', $radiusAddress);
                        
                        // Find our newly added server
                        foreach ($radiusList as $rad) {
                            $radHost = $rad['address'] ?? '';
                            $radPort = $rad['authentication-port'] ?? '';
                            
                            if ($radHost === $expectedHost && $radPort === $expectedPort && str_contains($rad['service'] ?? '', 'hotspot')) {
                                $radiusId = $rad['.id'];
                                Log::info('Found newly added RADIUS server', ['id' => $radiusId]);
                                
                                // Set timeout
                                $this->client->safeRead(
                                    $ros,
                                    (new Query('/radius/set'))
                                        ->equal('.id', $radiusId)
                                        ->equal('timeout', $radiusTimeout . 'ms')
                                );
                                
                                $steps[] = "✓ Set RADIUS timeout to {$radiusTimeout}ms";
                                Log::info('RADIUS timeout set successfully');
                                break;
                            }
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Failed to set timeout, but RADIUS server was added', ['error' => $e->getMessage()]);
                        $steps[] = "⚠️ Timeout not set, but RADIUS server added successfully";
                    }
                } else {
                    Log::error('RADIUS add returned error', ['result' => $addResult]);
                    throw new \Exception('MikroTik rejected RADIUS add: ' . ($addResult['after']['message'] ?? 'Unknown error'));
                }
                
                // Verify it was added - Query ALL properties to debug
                Log::info('Querying all RADIUS configurations for verification');
                $verifyResp = $this->client->safeRead(
                    $ros,
                    new Query('/radius/print') // No proplist - get everything
                );
                
                Log::info('RADIUS verification response - ALL configs', [
                    'response' => $verifyResp,
                    'count' => count($verifyResp),
                ]);
                
                // Split address to compare separately (MikroTik stores address and port in separate fields)
                list($expectedHost, $expectedPort) = explode(':', $radiusAddress);
                
                Log::info('Looking for RADIUS server with', [
                    'expected_host' => $expectedHost,
                    'expected_port' => $expectedPort,
                    'expected_service_contains' => 'hotspot',
                ]);
                
                $found = false;
                foreach ($verifyResp as $index => $rad) {
                    $radHost = $rad['address'] ?? '';
                    $radPort = $rad['authentication-port'] ?? '';
                    
                    Log::info("Checking RADIUS config #{$index}", [
                        'address' => $radHost,
                        'authentication-port' => $radPort,
                        'service' => $rad['service'] ?? 'N/A',
                        'disabled' => $rad['disabled'] ?? 'N/A',
                    ]);
                    
                    if ($radHost === $expectedHost && $radPort === $expectedPort && str_contains($rad['service'] ?? '', 'hotspot')) {
                        $found = true;
                        $steps[] = "✓ Verified RADIUS server is configured at {$radHost}:{$radPort} and " . (($rad['disabled'] ?? 'false') === 'false' ? 'enabled' : 'disabled');
                        Log::info('RADIUS server verified', ['host' => $radHost, 'port' => $radPort, 'service' => $rad['service']]);
                        break;
                    }
                }
                
                if (!$found) {
                    Log::warning('RADIUS server verification failed', [
                        'all_radius_configs' => $verifyResp,
                        'count' => count($verifyResp),
                        'looking_for_address' => $radiusAddress,
                    ]);
                    $errors[] = "RADIUS server was added but verification failed - not found in configuration";
                }
                
            } catch (\Throwable $e) {
                $errorMsg = $e->getMessage();
                Log::error('Failed to add RADIUS server', [
                    'error' => $errorMsg,
                    'exception_class' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $errors[] = "❌ Failed to add RADIUS server: {$errorMsg}";
                $steps[] = "⚠️ RADIUS server addition failed - check MikroTik logs";
            }

            // Step 4: Enable RADIUS in all hotspot server profiles
            try {
                Log::info('Fetching hotspot profiles');
                $profileResp = $this->client->safeRead(
                    $ros,
                    (new Query('/ip/hotspot/profile/print'))->equal('.proplist', '.id,name')
                );

                Log::info('Found hotspot profiles', ['count' => count($profileResp), 'profiles' => $profileResp]);

                foreach ($profileResp as $prof) {
                    Log::info('Enabling RADIUS for profile', ['profile_id' => $prof['.id'], 'profile_name' => $prof['name']]);
                    $this->client->safeRead(
                        $ros,
                        (new Query('/ip/hotspot/profile/set'))
                            ->equal('.id', $prof['.id'])
                            ->equal('use-radius', 'yes')
                            ->equal('radius-accounting', 'yes')
                    );
                    $steps[] = "✓ Enabled RADIUS authentication and accounting in profile '{$prof['name']}'";
                    Log::info('RADIUS enabled for profile', ['profile_name' => $prof['name']]);
                }
            } catch (\Throwable $e) {
                Log::error('Failed to enable RADIUS in profiles', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $errors[] = "Failed to enable RADIUS in profiles: " . $e->getMessage();
            }

            $success = empty($errors);
            $message = $success
                ? 'RADIUS configuration applied successfully'
                : 'RADIUS configuration completed with errors';

            Log::info('RADIUS configuration process completed', [
                'success' => $success,
                'error_count' => count($errors),
                'step_count' => count($steps),
                'errors' => $errors,
            ]);

            return [
                'success' => $success,
                'message' => $message,
                'steps' => $steps,
                'errors' => $errors,
            ];

        } catch (\Throwable $e) {
            Log::error('RADIUS configuration process failed with exception', [
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
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
