<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PopularEmailDomain implements ValidationRule
{
    /**
     * Popular email domains that are allowed for registration
     */
    protected array $allowedDomains = [
        'gmail.com',
        'yahoo.com',
        'outlook.com',
        'hotmail.com',
        'live.com',
        'icloud.com',
        'protonmail.com',
        'aol.com',
        'mail.com',
        'zoho.com',
        'yandex.com',
        'gmx.com',
    ];

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Extract domain from email
        $domain = strtolower(substr(strrchr($value, "@"), 1));

        // Check if domain is in allowed list
        if (!in_array($domain, $this->allowedDomains)) {
            $fail('Please use a popular email provider like Gmail, Yahoo, or Outlook for registration.');
        }
    }

    /**
     * Get the list of allowed domains (useful for displaying to users)
     */
    public function getAllowedDomains(): array
    {
        return $this->allowedDomains;
    }
}
