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
            'check_type' => fake()->randomElement(['both', 'ssl', 'uptime']),
            'trigger_type' => fake()->randomElement(['scheduled', 'manual_immediate', 'manual_bulk']),
            'triggered_by_user_id' => null,
            'started_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'completed_at' => fn(array $attributes) => $attributes['started_at']
                ? now()->parse($attributes['started_at'])->addSeconds(fake()->numberBetween(1, 5))
                : now()->addSeconds(2),
            'duration_ms' => fake()->numberBetween(100, 5000),
            'status' => 'success',
            'error_message' => null,
            'uptime_status' => fake()->randomElement(['up', 'down']),
            'http_status_code' => fake()->randomElement([200, 201, 301, 302, 404, 500, 502, 503]),
            'response_time_ms' => fake()->numberBetween(50, 10000),
            'response_body_size_bytes' => fake()->numberBetween(100, 1000000),
            'redirect_count' => fake()->numberBetween(0, 5),
            'final_url' => null,
            'ssl_status' => fake()->randomElement(['valid', 'invalid', 'expired', 'expires_soon', 'self_signed']),
            'certificate_issuer' => fake()->randomElement([
                "Let's Encrypt Authority X3",
                'DigiCert Inc',
                'GlobalSign nv-sa',
                'Sectigo Limited',
                'GoDaddy.com, Inc.',
            ]),
            'certificate_subject' => 'www.' . fake()->word() . '.com',
            'certificate_expiration_date' => fake()->dateTimeBetween('now', '+2 years'),
            'certificate_valid_from_date' => fake()->dateTimeBetween('-2 years', 'now'),
            'days_until_expiration' => fn(array $attributes) => $attributes['certificate_expiration_date']
                ? now()->diffInDays($attributes['certificate_expiration_date'])
                : null,
            'certificate_chain' => null,
            'content_validation_enabled' => fake()->boolean(70),
            'content_validation_status' => fn(array $attributes) => $attributes['content_validation_enabled']
                ? fake()->randomElement(['passed', 'failed', 'skipped'])
                : null,
            'expected_strings_found' => null,
            'forbidden_strings_found' => null,
            'regex_matches' => null,
            'javascript_rendered' => fake()->boolean(30),
            'javascript_wait_seconds' => fake()->numberBetween(1, 10),
            'content_hash' => null,
            'check_method' => fake()->randomElement(['GET', 'HEAD', 'POST']),
            'user_agent' => 'SSL Monitor v4 (+https://ssl-monitor.example.com/bot)',
            'request_headers' => [
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
            ],
            'response_headers' => [
                'Server' => fake()->randomElement(['nginx', 'Apache', 'cloudflare']),
                'Content-Type' => 'text/html; charset=utf-8',
                'Cache-Control' => 'public, max-age=3600',
            ],
            'ip_address' => fake()->ipv4(),
            'server_software' => fake()->randomElement(['nginx/1.18.0', 'Apache/2.4.41', 'cloudflare']),
            'monitor_config' => null,
            'check_interval_minutes' => fake()->randomElement([5, 10, 15, 30, 60]),
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

    public function uptimeDown(): static
    {
        return $this->state(fn (array $attributes) => [
            'uptime_status' => 'down',
            'status' => 'failed',
            'http_status_code' => fake()->randomElement([500, 502, 503, 504]),
            'error_message' => fake()->randomElement([
                'Connection timeout',
                'Connection refused',
                'Name resolution failed',
                'SSL handshake failed',
                'HTTP request failed',
            ]),
        ]);
    }

    public function httpError(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'http_status_code' => fake()->randomElement([400, 401, 403, 404, 500, 502, 503]),
            'error_message' => 'HTTP Error: ' . fake()->randomElement([
                'Bad Request',
                'Unauthorized',
                'Forbidden',
                'Not Found',
                'Internal Server Error',
                'Bad Gateway',
                'Service Unavailable',
            ]),
        ]);
    }

    public function contentValidationFailed(): static
    {
        return $this->state(fn (array $attributes) => [
            'content_validation_enabled' => true,
            'content_validation_status' => 'failed',
            'status' => 'failed',
            'error_message' => 'Content validation failed',
            'expected_strings_found' => [],
            'forbidden_strings_found' => ['forbidden text'],
        ]);
    }

    public function javascriptRendered(): static
    {
        return $this->state(fn (array $attributes) => [
            'javascript_rendered' => true,
            'javascript_wait_seconds' => fake()->numberBetween(3, 10),
            'response_time_ms' => fake()->numberBetween(3000, 8000),
            'duration_ms' => fake()->numberBetween(3000, 8000),
        ]);
    }

    public function manualTrigger(): static
    {
        return $this->state(fn (array $attributes) => [
            'trigger_type' => fake()->randomElement(['manual_immediate', 'manual_bulk']),
            'triggered_by_user_id' => 1, // Would need to be overridden in tests
            'started_at' => now(),
            'completed_at' => now()->addSeconds(fake()->numberBetween(1, 3)),
        ]);
    }

    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'started_at' => fake()->dateTimeBetween('-2 hours', 'now'),
            'completed_at' => fn(array $attributes) => now()->parse($attributes['started_at'])
                ->addSeconds(fake()->numberBetween(1, 3)),
        ]);
    }

    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'started_at' => fake()->dateTimeBetween('-60 days', '-30 days'),
            'completed_at' => fn(array $attributes) => now()->parse($attributes['started_at'])
                ->addSeconds(fake()->numberBetween(1, 3)),
        ]);
    }

    public function sslExpired(): static
    {
        return $this->state(fn (array $attributes) => [
            'ssl_status' => 'expired',
            'status' => 'failed',
            'days_until_expiration' => fake()->numberBetween(-30, -1),
            'certificate_expiration_date' => fake()->dateTimeBetween('-30 days', 'yesterday'),
            'error_message' => 'SSL certificate has expired',
        ]);
    }

    public function sslSelfSigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'ssl_status' => 'self_signed',
            'certificate_issuer' => 'Self-Signed Certificate',
            'status' => 'failed',
            'error_message' => 'SSL certificate is self-signed',
        ]);
    }

    public function redirect(): static
    {
        return $this->state(fn (array $attributes) => [
            'http_status_code' => fake()->randomElement([301, 302, 303, 307, 308]),
            'redirect_count' => fake()->numberBetween(1, 5),
            'final_url' => 'https://www.' . fake()->word() . '.com/final-destination',
        ]);
    }
}
