<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AlertConfiguration>
 */
class AlertConfigurationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'team_id' => null,
            'alert_type' => fake()->randomElement(['ssl_expiry', 'uptime_down', 'ssl_invalid']),
            'threshold_days' => fake()->randomElement([3, 7, 14, 30]),
            'notification_channels' => ['email'],
            'enabled' => true,
            'alert_level' => fake()->randomElement(['critical', 'urgent', 'warning', 'info']),
        ];
    }
}
