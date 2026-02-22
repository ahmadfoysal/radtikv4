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
            
            // Verify token against radius_servers table
            $radiusServer = RadiusServer::where('auth_token', $providedToken)
                ->where('is_active', true)
                ->first();
            
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
            
            // Dispatch job to process activations in background
            ProcessVoucherActivations::dispatch($validated['activations']);
            
            return response()->json([
                'success' => true,
                'message' => 'Activations queued for processing',
                'received' => $activationCount,
                'queued_at' => now()->toIso8601String(),
            ], 200);
            
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
}
