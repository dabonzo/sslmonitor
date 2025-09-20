<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Website>
 */
class WebsiteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'url' => 'https://'.fake()->unique()->domainName(),
            'user_id' => User::factory(),
            'ssl_monitoring_enabled' => true,
            'uptime_monitoring_enabled' => fake()->boolean(70),
            'monitoring_config' => [
                'timeout' => fake()->numberBetween(10, 30),
                'retries' => fake()->numberBetween(1, 3),
                'follow_redirects' => fake()->boolean(80),
                'verify_ssl' => true,
                'alert_days_before_expiry' => fake()->randomElement([7, 14, 30]),
                'check_interval' => fake()->randomElement([3600, 7200, 14400, 21600, 43200, 86400]),
                'is_active' => true,
            ],
            'plugin_data' => [],
        ];
    }

    public function inactive(): static
    {
        return $this->state([
            'monitoring_config' => array_merge($this->definition()['monitoring_config'], [
                'is_active' => false,
            ]),
        ]);
    }

    public function withSslOnly(): static
    {
        return $this->state([
            'ssl_monitoring_enabled' => true,
            'uptime_monitoring_enabled' => false,
        ]);
    }

    public function withUptimeOnly(): static
    {
        return $this->state([
            'ssl_monitoring_enabled' => false,
            'uptime_monitoring_enabled' => true,
        ]);
    }

    public function withBothMonitoring(): static
    {
        return $this->state([
            'ssl_monitoring_enabled' => true,
            'uptime_monitoring_enabled' => true,
        ]);
    }

    public function withCustomInterval(int $seconds): static
    {
        return $this->state([
            'monitoring_config' => array_merge($this->definition()['monitoring_config'], [
                'check_interval' => $seconds,
            ]),
        ]);
    }

    public function withPluginData(array $pluginData): static
    {
        return $this->state([
            'plugin_data' => $pluginData,
        ]);
    }
}