<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_for_packages(): void
    {
        $this->get('/packages')->assertRedirect('/login');
    }

    public function test_guests_are_redirected_to_login_for_package_create(): void
    {
        $this->get('/package/add')->assertRedirect('/login');
    }
}
