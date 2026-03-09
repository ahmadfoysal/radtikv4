<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RadiusServer;
use App\Models\Voucher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RadiusVoucherVerificationController extends Controller
{
    /**
     * Verify voucher existence in RADTik database
     * Used by RADIUS server to cleanup orphaned vouchers
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function verify(Request $request): JsonResponse
    {
        try {
            // Get Bearer token from request
            $providedToken = $request->bearerToken();
            
            if (!$providedToken) {
                Log::warning('Missing Bearer token in voucher verification request', [
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
                Log::warning('Invalid RADIUS verification token', [
                    'ip' => $request->ip(),
                    'token_preview' => substr($providedToken, 0, 10) . '...'
                ]);
                
                return response()->json([
                    'error' => 'Invalid or inactive RADIUS server token'
                ], 401);
            }
            
            // Validate request data
            $validated = $request->validate([
                'usernames' => 'required|array|min:1|max:5000',
                'usernames.*' => 'required|string|max:255',
            ]);
            
            $requestedUsernames = $validated['usernames'];
            $requestCount = count($requestedUsernames);
            
            // Get routers linked to this RADIUS server
            $linkedRouterIds = \App\Models\Router::where('radius_server_id', $radiusServer->id)
                ->pluck('id')
                ->toArray();
            
            // Find existing vouchers in RADTik database for these routers
            $existingVouchers = Voucher::whereIn('router_id', $linkedRouterIds)
                ->whereIn('username', $requestedUsernames)
                ->pluck('username')
                ->toArray();
            
            // Determine which usernames should be deleted (don't exist in RADTik)
            $toDelete = array_values(array_diff($requestedUsernames, $existingVouchers));
            
            Log::info('Voucher verification completed', [
                'radius_server_id' => $radiusServer->id,
                'radius_server_name' => $radiusServer->name,
                'requested_count' => $requestCount,
                'valid_count' => count($existingVouchers),
                'orphaned_count' => count($toDelete),
                'source_ip' => $request->ip(),
            ]);
            
            return response()->json([
                'success' => true,
                'valid_usernames' => $existingVouchers,
                'orphaned_usernames' => $toDelete,
                'stats' => [
                    'requested' => $requestCount,
                    'valid' => count($existingVouchers),
                    'orphaned' => count($toDelete),
                ],
                'verified_at' => now()->toIso8601String(),
            ], 200);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Invalid voucher verification data received', [
                'errors' => $e->errors(),
                'ip' => $request->ip(),
            ]);
            
            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Failed to process voucher verification request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
            ]);
            
            return response()->json([
                'error' => 'Internal server error',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }
}
