<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('password'),
            'job_number' => str_pad(fake()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'device_token' => null,
            'department' => fake()->randomElement(['IT', 'Business', 'Engineering', 'Arch']),
            'avatar' => null,
            'daily_limit_hours' => 2,
            'accepted_terms_at' => null,
            'status' => 'active',
            'warnings' => 0,
            'role_type' => 'staff',
        ];
    }
}
