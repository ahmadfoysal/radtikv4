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
        // Log incoming callback for security audit
        Log::info('Cryptomus callback received', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'order_id' => $request->input('order_id'),
        ]);

        try {
            $gateway = PaymentGateway::where('name', 'Cryptomus')->firstOrFail();
            $handler = app($gateway->class, ['gateway' => $gateway]);
            
            $handler->handleCallback($request);
            
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Cryptomus callback error', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
                'data' => $request->all(),
            ]);
            
            return response()->json(['status' => 'error', 'message' => 'Payment processing failed'], 500);
        }
    }

    /**
     * Handle PayStation callback
     */
    public function paystation(Request $request): JsonResponse
    {
        // Log incoming callback for security audit
        Log::info('PayStation callback received', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        try {
            $gateway = PaymentGateway::where('name', 'PayStation')->firstOrFail();
            $handler = app($gateway->class, ['gateway' => $gateway]);
            
            $handler->handleCallback($request);
            
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('PayStation callback error', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
                'data' => $request->all(),
            ]);
            
            return response()->json(['status' => 'error', 'message' => 'Payment processing failed'], 500);
        }
    }
}

