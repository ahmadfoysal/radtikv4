<?php

namespace App\Http\Middleware;

use App\Models\GeneralSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyUserSettings
{
    /**
     * Handle an incoming request.
     * Apply authenticated user's timezone and other settings
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            GeneralSetting::applyUserConfig(auth()->id());
        }

        return $next($request);
    }
}
