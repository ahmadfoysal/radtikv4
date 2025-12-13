<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Router>
 */
class RouterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' Router',
            'address' => fake()->ipv4(),
            'login_address' => fake()->ipv4(),
            'port' => '8728',
            'ssh_port' => '22',
            'username' => 'admin',
            'password' => Crypt::encryptString('password'),
            'note' => fake()->sentence(),
            'user_id' => User::factory(),
            'zone_id' => null,
            'app_key' => Str::random(32),
            'monthly_expense' => fake()->randomFloat(2, 0, 1000),
            'logo' => null,
            'voucher_template_id' => null,
            'package' => null,
        ];
    }
}
