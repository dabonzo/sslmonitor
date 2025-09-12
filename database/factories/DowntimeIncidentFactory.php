<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DowntimeIncident>
 */
class DowntimeIncidentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = $this->faker->dateTimeBetween('-7 days', '-1 hour');
        $hasEnded = $this->faker->boolean(70); // 70% of incidents are resolved
        $endedAt = $hasEnded ? $this->faker->dateTimeBetween($startedAt, 'now') : null;

        return [
            'website_id' => \App\Models\Website::factory(),
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'duration_minutes' => $hasEnded ? $this->faker->numberBetween(5, 480) : null, // 5 minutes to 8 hours
            'max_response_time_ms' => $this->faker->numberBetween(5000, 30000), // 5-30 seconds
            'incident_type' => $this->faker->randomElement(['timeout', 'http_error', 'content_mismatch']),
            'error_details' => $this->faker->optional(0.8)->sentence(),
            'resolved_automatically' => $hasEnded ? $this->faker->boolean(60) : false, // 60% auto-resolved
        ];
    }
}
