<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckSuspendedUser
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only check if session exists and user is authenticated
        if ($request->hasSession() && Auth::check() && Auth::user()->isSuspended()) {
            Auth::logout();

            return redirect()->route('tyro-login.login', ['error' => 'account_suspended'])
                ->with('error', 'Your account has been suspended. Please contact support.');
        }

        return $next($request);
    }
}
