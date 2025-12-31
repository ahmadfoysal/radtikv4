<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckGracePeriodEnded
{
    /**
     * Handle an incoming request.
     * 
     * More restrictive middleware - only allows if subscription is ACTIVE (not in grace period).
     * Used for MikroTik API interactions to ensure no RouterOS communication after expiry.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Allow superadmin to bypass
        if ($user && $user->hasRole('superadmin')) {
            return $next($request);
        }

        // Check if user has active subscription
        $subscription = $user?->activeSubscription();

        if (!$subscription) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Active subscription required',
                    'message' => 'MikroTik API access requires an active subscription.',
                    'subscription_required' => true
                ], 403);
            }

            abort(403, 'Active subscription required for MikroTik operations');
        }

        // Check if grace period has ended (past end_date + grace_period_days)
        $now = now();
        $endDate = $subscription->end_date;
        $gracePeriodDays = $subscription->package->grace_period_days ?? 0;
        $gracePeriodEndDate = $endDate->copy()->addDays($gracePeriodDays);

        // Allow MikroTik operations during grace period, block only after grace period ends
        if ($now->gt($gracePeriodEndDate)) {
            // Grace period ended - block all MikroTik API access
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Grace period ended',
                    'message' => 'Your subscription and grace period have ended. All MikroTik operations are blocked. Please renew immediately.',
                    'grace_period_ended' => true
                ], 403);
            }

            abort(403, 'Your subscription and grace period have ended. All MikroTik operations are blocked. Please renew immediately.');
        }

        return $next($request);
    }
}
