# Email Domain Validation for Registration

## Overview

This implementation restricts user registration to popular email providers only (Gmail, Yahoo, Outlook, etc.). This helps reduce spam registrations and ensures users provide legitimate email addresses.

## Files Created

1. **`app/Rules/PopularEmailDomain.php`**

    - Custom validation rule that checks if email domain is in the allowed list
    - Contains 12 popular email providers by default

2. **`app/Http/Middleware/ValidateRegistrationEmail.php`**

    - Middleware that intercepts registration requests
    - Validates email before tyro-login processes it

3. **`tests/Feature/RegistrationEmailValidationTest.php`**
    - Test cases to verify the validation works correctly

## Files Modified

1. **`bootstrap/app.php`**

    - Registered the middleware globally for web routes

2. **`resources/views/vendor/tyro-login/register.blade.php`**
    - Added helpful hint text below email field

## Allowed Email Domains

Currently, these email domains are allowed:

-   gmail.com
-   yahoo.com
-   outlook.com
-   hotmail.com
-   live.com
-   icloud.com
-   protonmail.com
-   aol.com
-   mail.com
-   zoho.com
-   yandex.com
-   gmx.com

## Adding More Domains

To add more allowed domains, edit `app/Rules/PopularEmailDomain.php` and add domains to the `$allowedDomains` array:

```php
protected array $allowedDomains = [
    'gmail.com',
    'yahoo.com',
    // Add your domain here
    'newdomain.com',
];
```

## Testing

Run the tests to verify the validation works:

```bash
php artisan test --filter=RegistrationEmailValidationTest
```

## How It Works

1. User tries to register with an email
2. Middleware intercepts the registration request
3. Email domain is extracted and checked against allowed list
4. If domain is not allowed, user sees error message
5. If domain is allowed, registration proceeds normally

## Error Message

When a non-allowed domain is used, the user sees:

> "Please use a popular email provider like Gmail, Yahoo, or Outlook for registration."

## Disabling the Validation

If you need to temporarily disable this validation:

1. Comment out the middleware in `bootstrap/app.php`:

```php
// \App\Http\Middleware\ValidateRegistrationEmail::class,
```

2. Or remove the rule from the middleware in `app/Http/Middleware/ValidateRegistrationEmail.php`
