<?php

namespace App\Http\Controllers;

use App\Models\PaymentGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentCallbackController extends Controller
{
    /**
     * Handle Cryptomus callback
     */
    public function cryptomus(Request $request): JsonResponse
    {
        try {
            $gateway = PaymentGateway::where('name', 'Cryptomus')->firstOrFail();
            $handler = app($gateway->class, ['gateway' => $gateway]);
            
            $handler->handleCallback($request);
            
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Cryptomus callback error', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);
            
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle PayStation callback
     */
    public function paystation(Request $request): JsonResponse
    {
        try {
            $gateway = PaymentGateway::where('name', 'PayStation')->firstOrFail();
            $handler = app($gateway->class, ['gateway' => $gateway]);
            
            $handler->handleCallback($request);
            
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('PayStation callback error', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);
            
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}

