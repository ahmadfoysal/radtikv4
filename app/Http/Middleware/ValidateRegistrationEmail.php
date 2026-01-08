<?php

namespace App\Http\Middleware;

use App\Rules\PopularEmailDomain;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;

class ValidateRegistrationEmail
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply to registration routes
        if ($request->routeIs('tyro-login.register.submit')) {
            $validator = Validator::make($request->all(), [
                'email' => ['required', 'email', new PopularEmailDomain()],
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }
        }

        return $next($request);
    }
}
