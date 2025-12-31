<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckActiveSubscription
{
    /**
     * Handle an incoming request.
     * 
     * Ensures user has an active subscription (active OR within grace period).
     * Used for features like adding routers, generating vouchers, etc.
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
                    'message' => 'You need an active subscription to access this feature.',
                    'redirect' => route('subscription.index')
                ], 403);
            }

            return redirect()
                ->route('subscription.index')
                ->with('error', 'You need an active subscription to access this feature. Please subscribe to a package.');
        }

        // Check if subscription has expired (past end_date)
        // Block adding routers and generating vouchers even during grace period
        $now = now();
        $endDate = $subscription->end_date;
        $gracePeriodDays = $subscription->package->grace_period_days ?? 0;
        
        if ($now->gt($endDate)) {
            // Subscription expired - block feature access immediately
            $gracePeriodEndDate = $endDate->copy()->addDays($gracePeriodDays);
            $daysRemaining = max(0, (int) $now->diffInDays($gracePeriodEndDate));
            
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Subscription expired',
                    'message' => "Your subscription has expired. You cannot add routers or generate vouchers during the grace period. Please renew within {$daysRemaining} day(s).",
                    'grace_period_active' => $daysRemaining > 0,
                    'days_remaining' => $daysRemaining,
                    'redirect' => route('subscription.index')
                ], 403);
            }

            return redirect()
                ->route('subscription.index')
                ->with('error', "Your subscription has expired. You cannot add routers or generate vouchers during the grace period. Please renew within {$daysRemaining} day(s) to restore full access.");
