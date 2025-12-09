<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserProfile>
 */
class UserProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true) . ' Profile',
            'rate_limit' => '10M/10M',
            'validity' => '30d',
            'mac_binding' => false,
            'price' => fake()->randomFloat(2, 5, 100),
            'user_id' => User::factory(),
            'description' => fake()->sentence(),
        ];
    }
}
