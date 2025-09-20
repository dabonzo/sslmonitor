<?php

namespace App\Services;

use App\Models\Website;
use Spatie\UptimeMonitor\Models\Monitor;
use Illuminate\Support\Facades\Log;

class MonitorIntegrationService
{
    /**
     * Create or update a Monitor for a Website
     */
    public function createOrUpdateMonitorForWebsite(Website $website): Monitor
    {
        $config = $website->monitoring_config ?? [];

        $monitor = Monitor::updateOrCreate(
            ['url' => $website->url],
            [
                'uptime_check_enabled' => $website->uptime_monitoring_enabled,
                'certificate_check_enabled' => $website->ssl_monitoring_enabled,
                'uptime_check_interval_in_minutes' => $this->getCheckIntervalInMinutes($config),
                'look_for_string' => $config['expected_content'] ?? '',
                'uptime_check_method' => $config['http_method'] ?? 'get',
                'uptime_check_additional_headers' => $this->formatHeaders($config),
            ]
        );

        Log::info('Monitor synchronized for website', [
            'website_id' => $website->id,
            'monitor_id' => $monitor->id,
            'url' => $website->url,
            'uptime_enabled' => $website->uptime_monitoring_enabled,
            'ssl_enabled' => $website->ssl_monitoring_enabled,
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

        if (!$monitor) {
            return !$website->uptime_monitoring_enabled && !$website->ssl_monitoring_enabled;
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

        if (!$monitor) {
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
        $intervalSeconds = $config['check_interval'] ?? 3600; // Default 1 hour
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