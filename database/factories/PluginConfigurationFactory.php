<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PluginConfiguration>
 */
class PluginConfigurationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $pluginTypes = ['agent', 'webhook', 'external_service'];
        $pluginType = fake()->randomElement($pluginTypes);

        return [
            'user_id' => User::factory(),
            'plugin_type' => $pluginType,
            'plugin_name' => $this->generatePluginName($pluginType),
            'configuration' => $this->generateConfiguration($pluginType),
            'authentication' => $this->generateAuthentication($pluginType),
            'endpoints' => $this->generateEndpoints($pluginType),
            'status' => fake()->randomElement(['active', 'inactive', 'error', 'pending']),
            'status_message' => null,
            'last_contacted_at' => fake()->optional(0.7)->dateTimeBetween('-1 week', 'now'),
            'is_enabled' => fake()->boolean(80),
        ];
    }

    public function agent(): static
    {
        return $this->state([
            'plugin_type' => 'agent',
            'plugin_name' => fake()->randomElement([
                'system_metrics_agent',
                'disk_space_monitor',
                'memory_usage_tracker',
                'cpu_performance_monitor',
                'network_interface_monitor',
                'ssl_certificate_scanner',
            ]) . '_' . fake()->unique()->numberBetween(1000, 9999),
            'configuration' => [
                'collection_interval' => fake()->randomElement([60, 300, 600, 1800, 3600]),
                'metrics_to_collect' => fake()->randomElements([
                    'cpu_usage',
                    'memory_usage',
                    'disk_space',
                    'network_io',
                    'ssl_certificates',
                    'running_processes',
                ], fake()->numberBetween(2, 4)),
                'alert_thresholds' => [
                    'cpu_usage' => fake()->numberBetween(70, 90),
                    'memory_usage' => fake()->numberBetween(80, 95),
                    'disk_space' => fake()->numberBetween(85, 95),
                ],
                'data_retention_days' => fake()->randomElement([7, 14, 30, 90]),
            ],
            'endpoints' => [
                'data_ingestion' => '/api/v1/agents/data',
                'health_check' => '/api/v1/agents/health',
                'configuration' => '/api/v1/agents/config',
            ],
        ]);
    }

    public function webhook(): static
    {
        return $this->state([
            'plugin_type' => 'webhook',
            'plugin_name' => fake()->randomElement([
                'slack_notifications',
                'discord_alerts',
                'microsoft_teams_webhook',
                'custom_notification_webhook',
                'pagerduty_integration',
            ]) . '_' . fake()->unique()->numberBetween(1000, 9999),
            'configuration' => [
                'webhook_url' => fake()->url(),
                'notification_events' => fake()->randomElements([
                    'ssl_certificate_expiring',
                    'ssl_certificate_expired',
                    'website_down',
                    'website_slow_response',
                    'agent_disconnected',
                ], fake()->numberBetween(2, 4)),
                'retry_attempts' => fake()->numberBetween(1, 5),
                'timeout_seconds' => fake()->numberBetween(5, 30),
                'custom_headers' => [
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'SSL-Monitor-v4/1.0',
                ],
            ],
            'endpoints' => [
                'webhook_url' => fake()->url(),
            ],
        ]);
    }

    public function externalService(): static
    {
        $serviceName = fake()->randomElement([
            'grafana_metrics',
            'datadog_integration',
            'new_relic_monitoring',
            'prometheus_exporter',
            'elastic_search_logs',
        ]);

        return $this->state([
            'plugin_type' => 'external_service',
            'plugin_name' => $serviceName . '_' . fake()->unique()->numberBetween(1000, 9999),
            'configuration' => [
                'api_endpoint' => fake()->url(),
                'data_format' => fake()->randomElement(['json', 'xml', 'prometheus', 'influxdb']),
                'sync_interval' => fake()->randomElement([300, 600, 1800, 3600]),
                'batch_size' => fake()->numberBetween(10, 100),
                'compression' => fake()->boolean(60),
            ],
            'endpoints' => [
                'api_endpoint' => fake()->url(),
                'auth_endpoint' => fake()->url(),
                'metrics_endpoint' => fake()->url() . '/metrics',
            ],
        ]);
    }

    public function active(): static
    {
        return $this->state([
            'status' => 'active',
            'status_message' => null,
            'last_contacted_at' => fake()->dateTimeBetween('-1 hour', 'now'),
            'is_enabled' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state([
            'status' => 'inactive',
            'status_message' => 'Plugin disabled by user',
            'last_contacted_at' => fake()->optional(0.3)->dateTimeBetween('-1 week', '-1 day'),
            'is_enabled' => false,
        ]);
    }

    public function error(): static
    {
        return $this->state([
            'status' => 'error',
            'status_message' => fake()->randomElement([
                'Authentication failed',
                'Connection timeout',
                'Invalid configuration',
                'API rate limit exceeded',
                'Certificate validation failed',
                'Network unreachable',
            ]),
            'last_contacted_at' => fake()->dateTimeBetween('-1 day', '-1 hour'),
            'is_enabled' => true,
        ]);
    }

    public function pending(): static
    {
        return $this->state([
            'status' => 'pending',
            'status_message' => 'Waiting for initial connection',
            'last_contacted_at' => null,
            'is_enabled' => true,
        ]);
    }

    public function withCustomConfiguration(array $config): static
    {
        return $this->state([
            'configuration' => $config,
        ]);
    }

    public function withAuthentication(array $auth): static
    {
        return $this->state([
            'authentication' => $auth,
        ]);
    }

    protected function generatePluginName(string $pluginType): string
    {
        $baseName = match ($pluginType) {
            'agent' => fake()->randomElement([
                'system_metrics_agent',
                'disk_space_monitor',
                'memory_usage_tracker',
                'cpu_performance_monitor',
                'network_interface_monitor',
                'ssl_certificate_scanner',
            ]),
            'webhook' => fake()->randomElement([
                'slack_notifications',
                'discord_alerts',
                'microsoft_teams_webhook',
                'custom_notification_webhook',
                'pagerduty_integration',
            ]),
            'external_service' => fake()->randomElement([
                'grafana_metrics',
                'datadog_integration',
                'new_relic_monitoring',
                'prometheus_exporter',
                'elastic_search_logs',
            ]),
            default => 'unknown_plugin',
        };

        return $baseName . '_' . fake()->unique()->numberBetween(1000, 9999);
    }

    protected function generateConfiguration(string $pluginType): array
    {
        return match ($pluginType) {
            'agent' => [
                'collection_interval' => fake()->randomElement([60, 300, 600, 1800, 3600]),
                'metrics_to_collect' => fake()->randomElements([
                    'cpu_usage', 'memory_usage', 'disk_space', 'network_io'
                ], fake()->numberBetween(2, 4)),
                'alert_thresholds' => [
                    'cpu_usage' => fake()->numberBetween(70, 90),
                    'memory_usage' => fake()->numberBetween(80, 95),
                ],
            ],
            'webhook' => [
                'webhook_url' => fake()->url(),
                'notification_events' => fake()->randomElements([
                    'ssl_certificate_expiring', 'ssl_certificate_expired', 'website_down'
                ], fake()->numberBetween(1, 3)),
                'retry_attempts' => fake()->numberBetween(1, 5),
            ],
            'external_service' => [
                'api_endpoint' => fake()->url(),
                'data_format' => fake()->randomElement(['json', 'xml', 'prometheus']),
                'sync_interval' => fake()->randomElement([300, 600, 1800, 3600]),
            ],
            default => [],
        };
    }

    protected function generateAuthentication(string $pluginType): ?array
    {
        if (fake()->boolean(70)) {
            return match ($pluginType) {
                'agent' => [
                    'type' => 'api_key',
                    'api_key' => fake()->sha256(),
                    'secret' => fake()->sha256(),
                ],
                'webhook' => [
                    'type' => 'bearer_token',
                    'token' => fake()->sha256(),
                ],
                'external_service' => [
                    'type' => fake()->randomElement(['api_key', 'oauth2', 'basic_auth']),
                    'credentials' => fake()->sha256(),
                ],
                default => null,
            };
        }

        return null;
    }

    protected function generateEndpoints(string $pluginType): ?array
    {
        return match ($pluginType) {
            'agent' => [
                'data_ingestion' => '/api/v1/agents/data',
                'health_check' => '/api/v1/agents/health',
                'configuration' => '/api/v1/agents/config',
            ],
            'webhook' => [
                'webhook_url' => fake()->url(),
            ],
            'external_service' => [
                'api_endpoint' => fake()->url(),
                'auth_endpoint' => fake()->url(),
            ],
            default => null,
        };
    }
}