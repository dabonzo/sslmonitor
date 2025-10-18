<?php

namespace App\Services;

use App\Models\Monitor;
use App\Models\Website;
use Illuminate\Support\Facades\Log;

class MonitorIntegrationService
{
    /**
     * Create or update a Monitor for a Website
     * Syncs ALL monitoring settings including content validation
     */
    public function createOrUpdateMonitorForWebsite(Website $website): Monitor
    {
        $config = $website->monitoring_config ?? [];

        // Convert empty arrays to null for proper database storage
        $expectedStrings = ! empty($config['content_expected_strings']) ? $config['content_expected_strings'] : null;
        $forbiddenStrings = ! empty($config['content_forbidden_strings']) ? $config['content_forbidden_strings'] : null;
        $regexPatterns = ! empty($config['content_regex_patterns']) ? $config['content_regex_patterns'] : null;

        // Only use EnhancedContentChecker if content validation is configured
        $hasContentValidation = $expectedStrings || $forbiddenStrings || $regexPatterns;

        $monitor = Monitor::updateOrCreate(
            ['url' => $website->url],
            [
                // Basic monitoring settings
                'uptime_check_enabled' => $website->uptime_monitoring_enabled,
                'certificate_check_enabled' => $website->ssl_monitoring_enabled,
                'uptime_check_interval_in_minutes' => $this->getCheckIntervalInMinutes($config),
                'look_for_string' => $config['expected_content'] ?? '',
                'uptime_check_method' => $config['http_method'] ?? 'get',
                'uptime_check_additional_headers' => $this->formatHeaders($config),

                // Content validation settings
                'content_expected_strings' => $expectedStrings,
                'content_forbidden_strings' => $forbiddenStrings,
                'content_regex_patterns' => $regexPatterns,

                // JavaScript rendering settings
                'javascript_enabled' => $config['javascript_enabled'] ?? false,
                'javascript_wait_seconds' => $config['javascript_wait_seconds'] ?? 5,

                // Response checker (EnhancedContentChecker if content validation configured)
                'uptime_check_response_checker' => $hasContentValidation
                    ? \App\Services\UptimeMonitor\ResponseCheckers\EnhancedContentChecker::class
                    : null,
            ]
        );

        Log::info('Monitor synchronized for website', [
            'website_id' => $website->id,
            'monitor_id' => $monitor->id,
            'url' => $website->url,
            'uptime_enabled' => $website->uptime_monitoring_enabled,
            'ssl_enabled' => $website->ssl_monitoring_enabled,
            'content_validation_enabled' => $hasContentValidation,
            'javascript_enabled' => $config['javascript_enabled'] ?? false,
        ]);

        return $monitor;
    }

    /**
     * Remove Monitor when Website is deleted
     */
    public function removeMonitorForWebsite(Website $website): bool
    {
        $monitor = Monitor::where('url', $website->url)->first();

        if ($monitor) {
            $monitor->delete();

            Log::info('Monitor removed for website', [
                'website_id' => $website->id,
                'url' => $website->url,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Sync all websites with monitors
     */
    public function syncAllWebsitesWithMonitors(): array
    {
        $websites = Website::where('uptime_monitoring_enabled', true)
            ->orWhere('ssl_monitoring_enabled', true)
            ->get();

        $synced = [];
        $errors = [];

        foreach ($websites as $website) {
            try {
                $monitor = $this->createOrUpdateMonitorForWebsite($website);
                $synced[] = [
                    'website_id' => $website->id,
                    'monitor_id' => $monitor->id,
                    'url' => $website->url,
                ];
            } catch (\Exception $e) {
                $errors[] = [
                    'website_id' => $website->id,
                    'url' => $website->url,
                    'error' => $e->getMessage(),
                ];

                Log::error('Failed to sync website with monitor', [
                    'website_id' => $website->id,
                    'url' => $website->url,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'synced' => $synced,
            'errors' => $errors,
            'total_websites' => $websites->count(),
            'synced_count' => count($synced),
            'error_count' => count($errors),
        ];
    }

    /**
     * Get the Monitor for a Website
     */
    public function getMonitorForWebsite(Website $website): ?Monitor
    {
        return Monitor::where('url', $website->url)->first();
    }

    /**
     * Check if Website monitoring is in sync with Monitor
     */
    public function isWebsiteInSync(Website $website): bool
    {
        $monitor = $this->getMonitorForWebsite($website);

        if (! $monitor) {
            return ! $website->uptime_monitoring_enabled && ! $website->ssl_monitoring_enabled;
        }

        return $monitor->uptime_check_enabled === $website->uptime_monitoring_enabled &&
               $monitor->certificate_check_enabled === $website->ssl_monitoring_enabled;
    }

    /**
     * Get monitoring status summary for a Website
     */
    public function getMonitoringStatusForWebsite(Website $website): array
    {
        $monitor = $this->getMonitorForWebsite($website);

        if (! $monitor) {
            return [
                'has_monitor' => false,
                'uptime_status' => 'not_monitored',
                'certificate_status' => 'not_monitored',
                'last_uptime_check' => null,
                'last_certificate_check' => null,
                'is_synced' => $this->isWebsiteInSync($website),
            ];
        }

        return [
            'has_monitor' => true,
            'uptime_status' => $monitor->uptime_status,
            'certificate_status' => $monitor->certificate_status,
            'last_uptime_check' => $monitor->uptime_last_check_date,
            'last_certificate_check' => $monitor->updated_at, // Spatie doesn't track cert check date separately
            'uptime_failure_reason' => $monitor->uptime_check_failure_reason,
            'certificate_failure_reason' => $monitor->certificate_check_failure_reason,
            'certificate_expiration_date' => $monitor->certificate_expiration_date,
            'certificate_issuer' => $monitor->certificate_issuer,
            'consecutive_failures' => $monitor->uptime_check_times_failed_in_a_row,
            'is_synced' => $this->isWebsiteInSync($website),
        ];
    }

    /**
     * Convert check interval from seconds to minutes
     */
    private function getCheckIntervalInMinutes(array $config): int
    {
        // Use config default if not specified in monitoring_config
        $defaultMinutes = config('uptime-monitor.uptime_check.run_interval_in_minutes', 5);
        $intervalSeconds = $config['check_interval'] ?? ($defaultMinutes * 60);

        return max(1, (int) ceil($intervalSeconds / 60)); // Minimum 1 minute
    }

    /**
     * Format additional headers for Spatie Monitor
     */
    private function formatHeaders(array $config): array
    {
        $headers = $config['additional_headers'] ?? [];

        if (empty($headers)) {
            return [];
        }

        if (is_array($headers)) {
            return $headers;
        }

        // Try to decode if it's a JSON string
        if (is_string($headers)) {
            $decoded = json_decode($headers, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }
}
