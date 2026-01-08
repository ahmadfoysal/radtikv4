<?php

namespace App\Providers;

use App\Rules\PopularEmailDomain;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;

class CustomAuthValidationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Extend the validator to add custom email domain validation
        Validator::extend('popular_email_domain', function ($attribute, $value, $parameters, $validator) {
            $rule = new PopularEmailDomain();
            $passes = true;

            $rule->validate($attribute, $value, function ($message) use (&$passes) {
                $passes = false;
            });

            return $passes;
        }, 'Please use a popular email provider like Gmail, Yahoo, or Outlook for registration.');

        // Hook into tyro-login registration request validation
        $this->app->resolving(\HasinHayder\TyroLogin\Http\Requests\RegisterRequest::class, function ($request) {
            // Add the popular email domain rule to the email field
            $request->merge([
                'email' => $request->email,
            ]);

            // Override the rules method
            $request->setValidator(
                Validator::make($request->all(), array_merge($request->rules(), [
                    'email' => array_merge(
                        is_array($request->rules()['email']) ? $request->rules()['email'] : ['required', 'email'],
                        [new PopularEmailDomain()]
                    ),
                ]))
            );
        });
    }
}
