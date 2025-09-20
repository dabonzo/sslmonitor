<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NotificationPreference>
 */
class NotificationPreferenceFactory extends Factory
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
            'email_enabled' => $this->faker->boolean(80), // 80% chance of being enabled
            'email_address' => $this->faker->email(),
            'expiry_days_notice' => $this->faker->randomElement([
                [7, 14, 30],
                [7, 30],
                [14],
                [7, 14],
            ]),
            'error_alerts' => $this->faker->boolean(90), // 90% chance of being enabled
            'daily_digest' => $this->faker->boolean(30), // 30% chance of being enabled
        ];
    }

    /**
     * Configure the factory for email-disabled preferences
     */
    public function emailDisabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_enabled' => false,
        ]);
    }

    /**
     * Configure the factory for all notifications enabled
     */
    public function allEnabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_enabled' => true,
            'error_alerts' => true,
            'daily_digest' => true,
        ]);
    }
}
