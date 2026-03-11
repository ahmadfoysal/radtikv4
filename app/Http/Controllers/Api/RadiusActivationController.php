<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessVoucherActivations;
use App\Models\RadiusServer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RadiusActivationController extends Controller
{
    /**
     * Receive activation data from RADIUS server
     * Validates token against radius_servers table
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Get Bearer token from request
            $providedToken = $request->bearerToken();
            
            if (!$providedToken) {
                Log::warning('Missing Bearer token in activation request', [
                    'ip' => $request->ip()
                ]);
                
                return response()->json([
                    'error' => 'Missing Authorization header'
                ], 401);
            }
            
            // Find RADIUS server by auth_token
            // Token is encrypted in DB but provided in plain text, so we must:
            // 1. Fetch all active servers
            // 2. Use model accessor to decrypt tokens
            // 3. Find the one that matches
            $radiusServer = RadiusServer::where('is_active', true)
                ->get()
                ->first(function ($server) use ($providedToken) {
                    return $server->auth_token === $providedToken;
                });
            
            if (!$radiusServer) {
                Log::warning('Invalid RADIUS activation token', [
                    'ip' => $request->ip(),
                    'token_preview' => substr($providedToken, 0, 10) . '...'
                ]);
                
                return response()->json([
                    'error' => 'Invalid or inactive RADIUS server token'
                ], 401);
            }
            
            // Validate request data
            $validated = $request->validate([
                'activations' => 'required|array|min:1',
                'activations.*.username' => 'required|string',
                'activations.*.nas_identifier' => 'nullable|string',
                'activations.*.calling_station_id' => 'nullable|string',
                'activations.*.authenticated_at' => 'required|date',
            ]);
            
            $activationCount = count($validated['activations']);
            
            Log::info('Received activation data from RADIUS', [
                'count' => $activationCount,
                'radius_server_id' => $radiusServer->id,
                'radius_server_name' => $radiusServer->name,
                'source_ip' => $request->ip(),
            ]);
            
            // Process activations synchronously and collect MAC binding information
            $macBindings = $this->processActivationsAndGetBindings($validated['activations']);
            
            // Dispatch job for async logging and other tasks if needed
            ProcessVoucherActivations::dispatch($validated['activations']);
            
            $response = [
                'success' => true,
                'message' => 'Activations processed',
                'received' => $activationCount,
                'processed_at' => now()->toIso8601String(),
            ];
            
            // Include MAC bindings if any vouchers require binding
            if (!empty($macBindings)) {
                $response['mac_bindings'] = $macBindings;
                Log::info('Returning MAC bindings for RADIUS sync', [
                    'count' => count($macBindings),
                ]);
            }
            
            return response()->json($response, 200);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Invalid activation data received', [
                'errors' => $e->errors(),
                'ip' => $request->ip(),
            ]);
            
            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Failed to process activation request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
            ]);
            
            return response()->json([
                'error' => 'Internal server error',
                'message' => 'Failed to process activations'
            ], 500);
        }
    }
    
    /**
     * Process activations synchronously and return MAC binding information
     * for vouchers where profile has mac_binding enabled
     * 
     * @param array $activations
     * @return array MAC bindings array with username and mac_address
     */
    private function processActivationsAndGetBindings(array $activations): array
    {
        $macBindings = [];
        
        foreach ($activations as $activation) {
            try {
                $username = $activation['username'];
                $macAddress = $activation['calling_station_id'] ?? null;
                $nasIdentifier = $activation['nas_identifier'] ?? null;
                $authenticatedAt = \Carbon\Carbon::parse($activation['authenticated_at']);
                
                if (!$macAddress) {
                    continue;
                }
                
                // Find voucher
                $voucherQuery = \App\Models\Voucher::where('username', $username);
                
                if ($nasIdentifier) {
                    $voucherQuery->whereHas('router', function ($query) use ($nasIdentifier) {
                        $query->where('nas_identifier', $nasIdentifier);
                    });
                }
                
                $voucher = $voucherQuery->with('profile')->first();
                
                if (!$voucher || !$voucher->profile) {
                    continue;
                }
                
                // Check if this is first activation (no activated_at set)
                $isFirstActivation = is_null($voucher->activated_at);
                
                // Only return binding info if:
                // 1. Profile has mac_binding enabled
                // 2. This is the first activation
                // 3. MAC address is provided
                if ($isFirstActivation && $voucher->profile->mac_binding && $macAddress) {
                    $macBindings[] = [
                        'username' => $username,
                        'mac_address' => $macAddress,
                        'nas_identifier' => $nasIdentifier,
                    ];
                    
                    Log::info('MAC binding required for voucher', [
                        'username' => $username,
                        'mac_address' => $macAddress,
                        'profile' => $voucher->profile->name,
                    ]);
                }
                
            } catch (\Exception $e) {
                Log::error('Failed to process activation for binding', [
                    'username' => $activation['username'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return $macBindings;
    }
}
