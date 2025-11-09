<?php

namespace Database\Factories;

use App\Models\Monitor;
use App\Models\MonitoringCheckSummary;
use App\Models\Website;
use Illuminate\Database\Eloquent\Factories\Factory;

class MonitoringCheckSummaryFactory extends Factory
{
    protected $model = MonitoringCheckSummary::class;

    public function definition(): array
    {
        $totalChecks = fake()->numberBetween(10, 100);
        $successfulUptimeChecks = fake()->numberBetween(8, $totalChecks);
        $totalUptimeChecks = $totalChecks;
        $failedUptimeChecks = $totalUptimeChecks - $successfulUptimeChecks;

        return [
            'monitor_id' => Monitor::factory(),
            'website_id' => Website::factory(),
            'summary_period' => 'daily',
            'period_start' => fake()->dateTimeBetween('-30 days', 'now'),
            'period_end' => fn(array $attributes) => now()->parse($attributes['period_start'])->endOfDay(),
            'total_checks' => $totalChecks,
            'total_uptime_checks' => $totalUptimeChecks,
            'successful_uptime_checks' => $successfulUptimeChecks,
            'failed_uptime_checks' => $failedUptimeChecks,
            'uptime_percentage' => round(($successfulUptimeChecks / $totalUptimeChecks) * 100, 2),
            'average_response_time_ms' => fake()->numberBetween(50, 500),
            'min_response_time_ms' => fake()->numberBetween(20, 100),
            'max_response_time_ms' => fake()->numberBetween(500, 2000),
            'p95_response_time_ms' => fake()->numberBetween(200, 800),
            'p99_response_time_ms' => fake()->numberBetween(400, 1500),
            'total_ssl_checks' => $totalChecks,
            'successful_ssl_checks' => fake()->numberBetween(8, $totalChecks),
            'failed_ssl_checks' => fn(array $attributes) => $attributes['total_ssl_checks'] - $attributes['successful_ssl_checks'],
            'certificates_expiring' => fake()->numberBetween(0, 2),
            'certificates_expired' => 0,
            'total_content_validations' => fake()->numberBetween(0, $totalChecks),
            'successful_content_validations' => fn(array $attributes) => fake()->numberBetween(0, $attributes['total_content_validations']),
            'failed_content_validations' => fn(array $attributes) => $attributes['total_content_validations'] - $attributes['successful_content_validations'],
            'total_check_duration_ms' => fake()->numberBetween(1000, 10000),
            'average_check_duration_ms' => fake()->numberBetween(100, 1000),
        ];
    }

    public function hourly(): static
    {
        return $this->state(fn (array $attributes) => [
            'summary_period' => 'hourly',
            'period_start' => now()->parse(fake()->dateTimeBetween('-7 days', 'now'))->startOfHour(),
            'period_end' => fn(array $attributes) => now()->parse($attributes['period_start'])->endOfHour(),
        ]);
    }

    public function daily(): static
    {
        return $this->state(fn (array $attributes) => [
            'summary_period' => 'daily',
            'period_start' => now()->parse(fake()->dateTimeBetween('-30 days', 'now'))->startOfDay(),
            'period_end' => fn(array $attributes) => now()->parse($attributes['period_start'])->endOfDay(),
        ]);
    }

    public function weekly(): static
    {
        return $this->state(fn (array $attributes) => [
            'summary_period' => 'weekly',
            'period_start' => now()->parse(fake()->dateTimeBetween('-12 weeks', 'now'))->startOfWeek(),
            'period_end' => fn(array $attributes) => now()->parse($attributes['period_start'])->endOfWeek(),
        ]);
    }

    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'summary_period' => 'monthly',
            'period_start' => now()->parse(fake()->dateTimeBetween('-6 months', 'now'))->startOfMonth(),
            'period_end' => fn(array $attributes) => now()->parse($attributes['period_start'])->endOfMonth(),
        ]);
    }

    public function highUptime(): static
    {
        return $this->state(fn (array $attributes) => [
            'uptime_percentage' => fake()->randomFloat(2, 99.0, 100.0),
            'successful_uptime_checks' => 99,
            'failed_uptime_checks' => 1,
            'total_uptime_checks' => 100,
        ]);
    }

    public function lowUptime(): static
    {
        return $this->state(fn (array $attributes) => [
            'uptime_percentage' => fake()->randomFloat(2, 50.0, 80.0),
            'successful_uptime_checks' => fake()->numberBetween(50, 80),
            'failed_uptime_checks' => fn(array $attributes) => 100 - $attributes['successful_uptime_checks'],
            'total_uptime_checks' => 100,
        ]);
    }
}
