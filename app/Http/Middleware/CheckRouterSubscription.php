<?php

namespace App\Http\Middleware;

use App\Models\Router;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRouterSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the token from the request query parameter
        $token = $request->query('token');

        if (!$token) {
            return response()->json(['error' => 'Token is required'], 403);
        }

        // Find the router by the token (app_key)
        $router = Router::where('app_key', $token)->first();

        if (!$router) {
            return response()->json(['error' => 'Invalid token'], 403);
        }

        // Check if the router's owner (admin) has an active subscription
        $user = $router->user;

        if (!$user || !$user->hasActiveSubscription()) {
            // No active admin subscription
            return response()->json(['error' => 'No active subscription'], 403);
        }

        // Subscription is valid, proceed to the controller
        return $next($request);
    }
}
