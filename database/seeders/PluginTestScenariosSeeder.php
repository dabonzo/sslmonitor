<?php

namespace Database\Seeders;

use App\Models\PluginConfiguration;
use App\Models\User;
use Illuminate\Database\Seeder;

class PluginTestScenariosSeeder extends Seeder
{
    /**
     * Seed plugin test scenarios for v1.1.0 architecture testing.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Plugin Test User',
            'email' => 'plugin-test@example.com',
        ]);

        // Agent plugin scenarios
        $this->createAgentScenarios($user);

        // Webhook plugin scenarios
        $this->createWebhookScenarios($user);

        // External service scenarios
        $this->createExternalServiceScenarios($user);

        // Edge case scenarios
        $this->createEdgeCaseScenarios($user);

        $this->command->info('Plugin test scenarios seeded successfully!');
    }

    protected function createAgentScenarios(User $user): void
    {
        // Active system metrics agent
        PluginConfiguration::factory()->agent()->active()->withCustomConfiguration([
            'collection_interval' => 300,
            'metrics_to_collect' => [
                'cpu_usage',
                'memory_usage',
                'disk_space',
                'network_io',
                'ssl_certificates',
            ],
            'alert_thresholds' => [
                'cpu_usage' => 80,
                'memory_usage' => 90,
                'disk_space' => 85,
            ],
            'data_retention_days' => 30,
            'compression_enabled' => true,
            'batch_size' => 50,
        ])->withAuthentication([
            'type' => 'api_key',
            'api_key' => 'test_system_metrics_key_'.fake()->sha256(),
            'secret' => 'test_secret_'.fake()->sha256(),
            'encryption' => 'aes256',
        ])->create([
            'user_id' => $user->id,
            'plugin_name' => 'system_metrics_agent_test',
        ]);

        // SSL certificate scanner agent
        PluginConfiguration::factory()->agent()->active()->withCustomConfiguration([
            'scan_interval' => 3600,
            'scan_depth' => 'deep',
            'certificate_chain_validation' => true,
            'vulnerability_scanning' => true,
            'compliance_checks' => ['pci_dss', 'hipaa', 'sox'],
            'notification_threshold' => 'medium',
            'auto_remediation' => false,
        ])->create([
            'user_id' => $user->id,
            'plugin_name' => 'ssl_certificate_scanner_test',
        ]);

        // Disk space monitor (with error state)
        PluginConfiguration::factory()->agent()->error()->create([
            'user_id' => $user->id,
            'plugin_name' => 'disk_space_monitor_test',
            'status_message' => 'Agent authentication failed - invalid API key',
        ]);

        // Network interface monitor (pending)
        PluginConfiguration::factory()->agent()->pending()->create([
            'user_id' => $user->id,
            'plugin_name' => 'network_interface_monitor_test',
            'status_message' => 'Waiting for initial connection from agent',
        ]);
    }

    protected function createWebhookScenarios(User $user): void
    {
        // Slack notifications webhook
        PluginConfiguration::factory()->webhook()->active()->withCustomConfiguration([
            'webhook_url' => 'https://hooks.slack.com/services/TEST/WEBHOOK/URL',
            'notification_events' => [
                'ssl_certificate_expiring',
                'ssl_certificate_expired',
                'website_down',
                'agent_disconnected',
            ],
            'message_format' => 'detailed',
            'retry_attempts' => 3,
            'timeout_seconds' => 10,
            'custom_headers' => [
                'Content-Type' => 'application/json',
                'User-Agent' => 'SSL-Monitor-v4/1.0',
                'X-SSL-Monitor-Source' => 'webhook-integration',
            ],
            'rate_limiting' => [
                'max_requests_per_minute' => 30,
                'burst_allowance' => 5,
            ],
        ])->withAuthentication([
            'type' => 'bearer_token',
            'token' => 'xoxb-test-slack-token-'.fake()->sha256(),
        ])->create([
            'user_id' => $user->id,
            'plugin_name' => 'slack_notifications_test',
        ]);

        // Discord alerts webhook
        PluginConfiguration::factory()->webhook()->active()->withCustomConfiguration([
            'webhook_url' => 'https://discord.com/api/webhooks/test/webhook',
            'notification_events' => [
                'ssl_certificate_expiring',
                'website_slow_response',
            ],
            'message_format' => 'compact',
            'embed_enabled' => true,
            'retry_attempts' => 2,
            'timeout_seconds' => 15,
        ])->create([
            'user_id' => $user->id,
            'plugin_name' => 'discord_alerts_test',
        ]);

        // Microsoft Teams webhook (error state)
        PluginConfiguration::factory()->webhook()->error()->create([
            'user_id' => $user->id,
            'plugin_name' => 'microsoft_teams_webhook_test',
            'status_message' => 'Webhook URL returned 404 - endpoint not found',
        ]);

        // PagerDuty integration (inactive)
        PluginConfiguration::factory()->webhook()->inactive()->create([
            'user_id' => $user->id,
            'plugin_name' => 'pagerduty_integration_test',
            'status_message' => 'Disabled by user - using alternative alerting',
        ]);
    }

    protected function createExternalServiceScenarios(User $user): void
    {
        // Grafana metrics integration
        PluginConfiguration::factory()->externalService()->active()->withCustomConfiguration([
            'api_endpoint' => 'https://grafana.example.com/api/v1/metrics',
            'data_format' => 'prometheus',
            'sync_interval' => 600,
            'batch_size' => 100,
            'compression' => true,
            'metric_labels' => [
                'environment' => 'production',
                'service' => 'ssl-monitor',
                'version' => 'v4.0.0',
            ],
            'retention_policy' => '30d',
        ])->withAuthentication([
            'type' => 'api_key',
            'api_key' => 'grafana_api_key_'.fake()->sha256(),
            'organization_id' => 'org_'.fake()->uuid(),
        ])->create([
            'user_id' => $user->id,
            'plugin_name' => 'grafana_metrics_test',
        ]);

        // DataDog integration
        PluginConfiguration::factory()->externalService()->active()->withCustomConfiguration([
            'api_endpoint' => 'https://api.datadoghq.com/api/v1/series',
            'data_format' => 'json',
            'sync_interval' => 300,
            'batch_size' => 50,
            'tags' => [
                'service:ssl-monitor',
                'env:production',
                'team:infrastructure',
            ],
            'compression' => true,
        ])->withAuthentication([
            'type' => 'api_key',
            'api_key' => 'datadog_api_key_'.fake()->sha256(),
            'app_key' => 'datadog_app_key_'.fake()->sha256(),
        ])->create([
            'user_id' => $user->id,
            'plugin_name' => 'datadog_integration_test',
        ]);

        // New Relic monitoring (error state)
        PluginConfiguration::factory()->externalService()->error()->create([
            'user_id' => $user->id,
            'plugin_name' => 'new_relic_monitoring_test',
            'status_message' => 'API rate limit exceeded - too many requests',
        ]);

        // Elasticsearch logs (pending)
        PluginConfiguration::factory()->externalService()->pending()->create([
            'user_id' => $user->id,
            'plugin_name' => 'elasticsearch_logs_test',
            'status_message' => 'Waiting for Elasticsearch cluster availability',
        ]);
    }

    protected function createEdgeCaseScenarios(User $user): void
    {
        // Plugin with very large configuration
        PluginConfiguration::factory()->agent()->active()->withCustomConfiguration([
            'collection_interval' => 60,
            'metrics_to_collect' => [
                'cpu_usage', 'memory_usage', 'disk_space', 'network_io',
                'ssl_certificates', 'running_processes', 'open_ports',
                'file_system_changes', 'user_sessions', 'system_logs',
                'security_events', 'performance_counters', 'registry_changes',
                'service_status', 'hardware_health', 'temperature_sensors',
            ],
            'detailed_configuration' => [
                'cpu_monitoring' => [
                    'sample_rate' => 1,
                    'core_specific' => true,
                    'temperature_monitoring' => true,
                    'frequency_monitoring' => true,
                ],
                'memory_monitoring' => [
                    'swap_usage' => true,
                    'buffer_cache' => true,
                    'shared_memory' => true,
                    'process_memory' => true,
                ],
                'disk_monitoring' => [
                    'io_stats' => true,
                    'mount_points' => ['/var', '/tmp', '/home'],
                    'filesystem_types' => ['ext4', 'xfs', 'btrfs'],
                    'smart_monitoring' => true,
                ],
            ],
            'alert_configurations' => array_fill_keys(
                ['cpu', 'memory', 'disk', 'network', 'temperature'],
                ['warning' => 70, 'critical' => 90, 'escalation_delay' => 300]
            ),
        ])->create([
            'user_id' => $user->id,
            'plugin_name' => 'comprehensive_system_monitor_test',
        ]);

        // Plugin with minimal configuration
        PluginConfiguration::factory()->webhook()->active()->withCustomConfiguration([
            'webhook_url' => 'https://example.com/webhook',
        ])->create([
            'user_id' => $user->id,
            'plugin_name' => 'minimal_webhook_test',
        ]);

        // Plugin with authentication but no configuration
        PluginConfiguration::factory()->agent()->active()->withCustomConfiguration([])->withAuthentication([
            'type' => 'oauth2',
            'client_id' => 'oauth_client_'.fake()->uuid(),
            'client_secret' => 'oauth_secret_'.fake()->sha256(),
            'scope' => 'read:metrics write:alerts',
            'token_endpoint' => 'https://auth.example.com/token',
        ])->create([
            'user_id' => $user->id,
            'plugin_name' => 'oauth_agent_test',
        ]);

        // Plugin with no authentication or configuration (edge case)
        PluginConfiguration::factory()->externalService()->pending()->withCustomConfiguration([])->withAuthentication([])->create([
            'user_id' => $user->id,
            'plugin_name' => 'unconfigured_service_test',
            'status_message' => 'Plugin created but not yet configured',
        ]);
    }
}
