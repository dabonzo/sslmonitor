<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UptimeCheck>
 */
class UptimeCheckFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'website_id' => \App\Models\Website::factory(),
            'status' => $this->faker->randomElement(['up', 'down', 'slow', 'content_mismatch']),
            'http_status_code' => $this->faker->randomElement([200, 301, 302, 404, 500, 502, 503, 504]),
            'response_time_ms' => $this->faker->numberBetween(50, 5000),
            'response_size_bytes' => $this->faker->numberBetween(1024, 102400),
            'content_check_passed' => $this->faker->boolean(80), // 80% pass rate
            'content_check_error' => $this->faker->optional(0.2)->sentence(), // 20% have errors
            'error_message' => $this->faker->optional(0.3)->sentence(), // 30% have error messages
            'checked_at' => $this->faker->dateTimeBetween('-7 days'),
        ];
    }
}
