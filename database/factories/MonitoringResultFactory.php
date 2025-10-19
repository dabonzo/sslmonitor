<?php

namespace Database\Factories;

use App\Models\Monitor;
use App\Models\MonitoringResult;
use App\Models\Website;
use Illuminate\Database\Eloquent\Factories\Factory;

class MonitoringResultFactory extends Factory
{
    protected $model = MonitoringResult::class;

    public function definition(): array
    {
        return [
            'monitor_id' => Monitor::factory(),
            'website_id' => Website::factory(),
            'check_type' => 'combined',
            'trigger_type' => 'scheduled',
            'started_at' => now(),
            'completed_at' => now()->addSeconds(2),
            'duration_ms' => fake()->numberBetween(100, 3000),
            'status' => 'success',
            'error_message' => null,
            'uptime_status' => 'up',
            'http_status_code' => 200,
            'response_time_ms' => fake()->numberBetween(100, 3000),
            'response_body_size_bytes' => fake()->numberBetween(1000, 50000),
            'redirect_count' => 0,
            'ssl_status' => 'valid',
            'certificate_issuer' => "Let's Encrypt Authority X3",
            'certificate_subject' => 'example.com',
            'certificate_expiration_date' => now()->addDays(90),
            'certificate_valid_from_date' => now()->subDays(30),
            'days_until_expiration' => 90,
            'content_validation_enabled' => false,
            'javascript_rendered' => false,
            'check_method' => 'GET',
        ];
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'uptime_status' => 'down',
            'http_status_code' => 500,
            'error_message' => 'Connection timeout',
        ]);
    }

    public function sslExpiring(): static
    {
        return $this->state(fn (array $attributes) => [
            'ssl_status' => 'expires_soon',
            'days_until_expiration' => fake()->numberBetween(1, 7),
            'certificate_expiration_date' => now()->addDays(fake()->numberBetween(1, 7)),
        ]);
    }

    public function sslInvalid(): static
    {
        return $this->state(fn (array $attributes) => [
            'ssl_status' => 'invalid',
            'days_until_expiration' => null,
        ]);
    }

    public function slowResponse(): static
    {
        return $this->state(fn (array $attributes) => [
            'response_time_ms' => fake()->numberBetween(5000, 10000),
        ]);
    }
}
