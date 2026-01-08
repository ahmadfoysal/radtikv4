<?php

namespace Tests\Feature;

use App\Rules\PopularEmailDomain;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RegistrationEmailValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that popular email domains are accepted
     */
    public function test_accepts_popular_email_domains(): void
    {
        $popularEmails = [
            'test@gmail.com',
            'test@yahoo.com',
            'test@outlook.com',
            'test@hotmail.com',
            'test@icloud.com',
        ];

        foreach ($popularEmails as $email) {
            $rule = new PopularEmailDomain();
            $passes = true;

            $rule->validate('email', $email, function ($message) use (&$passes) {
                $passes = false;
            });

            $this->assertTrue($passes, "Email {$email} should be accepted");
        }
    }

    /**
     * Test that non-popular email domains are rejected
     */
    public function test_rejects_non_popular_email_domains(): void
    {
        $nonPopularEmails = [
            'test@company.com',
            'test@mydomain.net',
            'test@random.org',
        ];

        foreach ($nonPopularEmails as $email) {
            $rule = new PopularEmailDomain();
            $passes = true;

            $rule->validate('email', $email, function ($message) use (&$passes) {
                $passes = false;
            });

            $this->assertFalse($passes, "Email {$email} should be rejected");
        }
    }
}
