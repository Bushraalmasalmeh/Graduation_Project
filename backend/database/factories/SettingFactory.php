<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'daily_limit_hours' => 2,
            'opening_time' => '07:00',
            'closing_time' => '22:00',
            'maintenance_mode' => false,
            'max_warnings' => 3,
            'grace_period_minutes' => 10,
            'notes' => 'Default system notes',
        ];
    }
}
