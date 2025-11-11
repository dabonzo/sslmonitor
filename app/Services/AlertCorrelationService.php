<?php

namespace App\Services;

use App\Models\MonitoringAlert;
use App\Models\MonitoringResult;

class AlertCorrelationService
{
    /**
     * Check if alert conditions are met and create alerts
     */
    public function checkAndCreateAlerts(MonitoringResult $result): void
    {
        // Check SSL invalid alert
        if ($result->ssl_status === 'invalid') {
            $this->checkSslInvalidAlert($result);
        }

        // Check SSL expiration alert
        if ($result->ssl_status && $result->days_until_expiration !== null) {
            $this->checkSslExpirationAlert($result);
        }

        // Check uptime alert
        if ($result->uptime_status === 'down') {
            $this->checkUptimeAlert($result);
        }

        // Check response time alert
        if ($result->response_time_ms && $result->response_time_ms > 5000) {
            $this->checkResponseTimeAlert($result);
        }
    }

    protected function checkSslInvalidAlert(MonitoringResult $result): void
    {
        // Check if alert already exists for this monitor
        $existingAlert = MonitoringAlert::where('monitor_id', $result->monitor_id)
            ->where('alert_type', 'ssl_invalid')
            ->whereNull('resolved_at')
            ->first();

        if (! $existingAlert) {
            MonitoringAlert::create([
                'monitor_id' => $result->monitor_id,
                'website_id' => $result->website_id,
                'affected_check_result_id' => $result->id,
                'alert_type' => 'ssl_invalid',
                'alert_severity' => 'critical',
                'alert_title' => 'SSL Certificate Invalid',
                'alert_message' => $result->error_message ?? 'SSL certificate validation failed',
                'trigger_value' => [
                    'error_message' => $result->error_message,
                    'certificate_issuer' => $result->certificate_issuer,
                    'certificate_expiration_date' => $result->certificate_expiration_date?->toIso8601String(),
                ],
                'threshold_value' => null,
                'first_detected_at' => now(),
                'last_occurred_at' => now(),
            ]);
        } else {
            // Update existing alert with latest occurrence
            $existingAlert->update([
                'last_occurred_at' => now(),
                'occurrence_count' => $existingAlert->occurrence_count + 1,
                'affected_check_result_id' => $result->id,
                'trigger_value' => [
                    'error_message' => $result->error_message,
                    'certificate_issuer' => $result->certificate_issuer,
                    'certificate_expiration_date' => $result->certificate_expiration_date?->toIso8601String(),
                ],
            ]);
        }
    }

    protected function checkSslExpirationAlert(MonitoringResult $result): void
    {
        if ($result->days_until_expiration <= 7) {
            // Check if alert already exists for this monitor
            $existingAlert = MonitoringAlert::where('monitor_id', $result->monitor_id)
                ->where('alert_type', 'ssl_expiring')
                ->whereNull('resolved_at')
                ->first();

            if (! $existingAlert) {
                MonitoringAlert::create([
                    'monitor_id' => $result->monitor_id,
                    'website_id' => $result->website_id,
                    'affected_check_result_id' => $result->id,
                    'alert_type' => 'ssl_expiring',
                    'alert_severity' => $result->days_until_expiration <= 3 ? 'critical' : 'warning',
                    'alert_title' => 'SSL Certificate Expiring Soon',
                    'alert_message' => "SSL certificate expires in {$result->days_until_expiration} days",
                    'trigger_value' => [
                        'days_until_expiration' => $result->days_until_expiration,
                        'certificate_expiration_date' => $result->certificate_expiration_date?->toIso8601String(),
                        'certificate_issuer' => $result->certificate_issuer,
                    ],
                    'threshold_value' => [
                        'warning_days' => 7,
                        'critical_days' => 3,
                    ],
                    'first_detected_at' => now(),
                    'last_occurred_at' => now(),
                ]);
            }
        }
    }

    protected function checkUptimeAlert(MonitoringResult $result): void
    {
        // Count consecutive failures within the last hour
        $consecutiveFailures = MonitoringResult::where('monitor_id', $result->monitor_id)
            ->where('started_at', '>=', now()->subHour())
            ->where('uptime_status', 'down')
            ->orderByDesc('started_at')
            ->count();

        if ($consecutiveFailures >= 3) {
            // Check if we already have an active uptime alert
            $existingAlert = MonitoringAlert::where('monitor_id', $result->monitor_id)
                ->where('alert_type', 'uptime_down')
                ->whereNull('resolved_at')
                ->first();

            if (! $existingAlert) {
                MonitoringAlert::create([
                    'monitor_id' => $result->monitor_id,
                    'website_id' => $result->website_id,
                    'affected_check_result_id' => $result->id,
                    'alert_type' => 'uptime_down',
                    'alert_severity' => 'critical',
                    'alert_title' => 'Website Down',
                    'alert_message' => "Website has been down for {$consecutiveFailures} consecutive checks",
                    'trigger_value' => [
                        'consecutive_failures' => $consecutiveFailures,
                        'error_message' => $result->error_message,
                        'http_status_code' => $result->http_status_code,
                    ],
                    'threshold_value' => [
                        'max_consecutive_failures' => 3,
                    ],
                    'first_detected_at' => now(),
                    'last_occurred_at' => now(),
                ]);
            } else {
                // Update existing alert with latest occurrence
                $existingAlert->update([
                    'last_occurred_at' => now(),
                    'occurrence_count' => $existingAlert->occurrence_count + 1,
                    'affected_check_result_id' => $result->id,
                    'trigger_value' => [
                        'consecutive_failures' => $consecutiveFailures,
                        'error_message' => $result->error_message,
                        'http_status_code' => $result->http_status_code,
                    ],
                ]);
            }
        }
    }

    protected function checkResponseTimeAlert(MonitoringResult $result): void
    {
        MonitoringAlert::create([
            'monitor_id' => $result->monitor_id,
            'website_id' => $result->website_id,
            'affected_check_result_id' => $result->id,
            'alert_type' => 'performance_degradation',
            'alert_severity' => 'warning',
            'alert_title' => 'Slow Response Time',
            'alert_message' => "Response time of {$result->response_time_ms}ms exceeds threshold",
            'trigger_value' => [
                'response_time_ms' => $result->response_time_ms,
            ],
            'threshold_value' => [
                'max_response_time_ms' => 5000,
            ],
            'first_detected_at' => now(),
            'last_occurred_at' => now(),
        ]);
    }

    /**
     * Mark alert as acknowledged
     */
    public function acknowledgeAlert(MonitoringAlert $alert, int $userId, ?string $note = null): void
    {
        $alert->update([
            'acknowledged_at' => now(),
            'acknowledged_by_user_id' => $userId,
            'acknowledgment_note' => $note,
        ]);
    }

    /**
     * Mark alert as resolved
     */
    public function resolveAlert(MonitoringAlert $alert, ?string $resolution = null): void
    {
        $alert->update([
            'resolved_at' => now(),
            'acknowledgment_note' => $resolution ? ($alert->acknowledgment_note ? $alert->acknowledgment_note."\n\nResolution: ".$resolution : "Resolution: {$resolution}") : $alert->acknowledgment_note,
        ]);
    }

    /**
     * Auto-resolve alerts when conditions improve
     */
    public function autoResolveAlerts(MonitoringResult $result): void
    {
        // Auto-resolve SSL invalid alerts if certificate becomes valid
        if ($result->ssl_status === 'valid') {
            MonitoringAlert::where('monitor_id', $result->monitor_id)
                ->where('alert_type', 'ssl_invalid')
                ->whereNull('resolved_at')
                ->update([
                    'resolved_at' => now(),
                    'acknowledgment_note' => 'SSL certificate now valid - auto-resolved',
                ]);
        }

        // Auto-resolve SSL expiration alerts if certificate is renewed
        if ($result->ssl_status === 'valid' && $result->days_until_expiration > 30) {
            MonitoringAlert::where('monitor_id', $result->monitor_id)
                ->where('alert_type', 'ssl_expiring')
                ->whereNull('resolved_at')
                ->update([
                    'resolved_at' => now(),
                    'acknowledgment_note' => 'SSL certificate renewed - auto-resolved',
                ]);
        }

        // Auto-resolve uptime alerts if site is back up
        if ($result->uptime_status === 'up') {
            MonitoringAlert::where('monitor_id', $result->monitor_id)
                ->where('alert_type', 'uptime_down')
                ->whereNull('resolved_at')
                ->update([
                    'resolved_at' => now(),
                    'acknowledgment_note' => 'Website back online - auto-resolved',
                ]);
        }
    }
}
