<?php

namespace Database\Factories;

use App\Models\Monitor;
use App\Models\MonitoringResult;
use App\Models\User;
use App\Models\Website;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MonitoringAlert>
 */
class MonitoringAlertFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $alertType = fake()->randomElement(['ssl_expiring', 'ssl_invalid', 'uptime_down', 'uptime_up', 'performance_degradation']);
        $severity = fake()->randomElement(['critical', 'warning', 'info']);

        return [
            'monitor_id' => Monitor::factory(),
            'website_id' => Website::factory(),
            'alert_type' => $alertType,
            'alert_severity' => $severity,
            'alert_title' => $this->generateTitle($alertType),
            'alert_message' => $this->generateMessage($alertType),
            'first_detected_at' => now(),
            'last_occurred_at' => now(),
            'trigger_value' => $this->generateTriggerValue($alertType),
            'threshold_value' => $this->generateThresholdValue($alertType),
            'notification_channels' => null,
            'notification_status' => null,
            'notifications_sent' => null,
            'occurrence_count' => 1,
            'suppressed' => false,
            'suppressed_until' => null,
            'acknowledged_at' => null,
            'acknowledged_by_user_id' => null,
            'acknowledgment_note' => null,
            'resolved_at' => null,
            'affected_check_result_id' => null,
        ];
    }

    /**
     * Alert type: SSL Expiring
     */
    public function sslExpiring(int $daysRemaining = 7): static
    {
        return $this->state(fn (array $attributes) => [
            'alert_type' => 'ssl_expiring',
            'alert_severity' => $daysRemaining <= 3 ? 'critical' : 'warning',
            'alert_title' => 'SSL Certificate Expiring Soon',
            'alert_message' => "SSL certificate expires in {$daysRemaining} days",
            'trigger_value' => [
                'days_until_expiration' => $daysRemaining,
                'certificate_expiration_date' => now()->addDays($daysRemaining)->toIso8601String(),
                'certificate_issuer' => 'Let\'s Encrypt',
                'ssl_days_remaining' => $daysRemaining,
            ],
            'threshold_value' => [
                'warning_days' => 7,
                'critical_days' => 3,
            ],
        ]);
    }

    /**
     * Alert type: SSL Invalid
     */
    public function sslInvalid(): static
    {
        return $this->state(fn (array $attributes) => [
            'alert_type' => 'ssl_invalid',
            'alert_severity' => 'critical',
            'alert_title' => 'SSL Certificate Invalid',
            'alert_message' => 'SSL certificate validation failed',
            'trigger_value' => [
                'error_message' => 'Certificate has expired',
                'certificate_issuer' => 'Unknown',
                'certificate_expiration_date' => now()->subDays(10)->toIso8601String(),
            ],
            'threshold_value' => null,
        ]);
    }

    /**
     * Alert type: Uptime Down
     */
    public function uptimeDown(): static
    {
        return $this->state(fn (array $attributes) => [
            'alert_type' => 'uptime_down',
            'alert_severity' => 'critical',
            'alert_title' => 'Website Down',
            'alert_message' => 'Website has been down for 3 consecutive checks',
            'trigger_value' => [
                'consecutive_failures' => 3,
                'error_message' => 'Connection timeout',
                'http_status_code' => null,
            ],
            'threshold_value' => [
                'max_consecutive_failures' => 3,
            ],
        ]);
    }

    /**
     * Alert type: Uptime Up (Recovered)
     */
    public function uptimeUp(): static
    {
        return $this->state(fn (array $attributes) => [
            'alert_type' => 'uptime_up',
            'alert_severity' => 'info',
            'alert_title' => 'Website Recovered',
            'alert_message' => 'Website is now online',
            'trigger_value' => [
                'consecutive_successes' => 1,
                'downtime_duration_minutes' => 15,
            ],
            'threshold_value' => null,
        ]);
    }

    /**
     * Alert type: Performance Degradation
     */
    public function performanceDegradation(): static
    {
        return $this->state(fn (array $attributes) => [
            'alert_type' => 'performance_degradation',
            'alert_severity' => 'warning',
            'alert_title' => 'Slow Response Time',
            'alert_message' => 'Response time of 5500ms exceeds threshold',
            'trigger_value' => [
                'response_time_ms' => 5500,
            ],
            'threshold_value' => [
                'max_response_time_ms' => 5000,
            ],
        ]);
    }

    /**
     * Set alert as resolved
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'resolved_at' => now(),
            'acknowledgment_note' => 'Issue resolved automatically',
        ]);
    }

    /**
     * Set alert as acknowledged
     */
    public function acknowledged(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'acknowledged_at' => now(),
            'acknowledged_by_user_id' => $user?->id ?? User::factory(),
            'acknowledgment_note' => 'Acknowledged by user',
        ]);
    }

    /**
     * Set alert with affected check result
     */
    public function withAffectedCheckResult(): static
    {
        return $this->state(fn (array $attributes) => [
            'affected_check_result_id' => MonitoringResult::factory(),
        ]);
    }

    /**
     * Generate title based on alert type
     */
    private function generateTitle(string $alertType): string
    {
        return match ($alertType) {
            'ssl_expiring' => 'SSL Certificate Expiring Soon',
            'ssl_invalid' => 'SSL Certificate Invalid',
            'uptime_down' => 'Website Down',
            'uptime_up' => 'Website Recovered',
            'performance_degradation' => 'Slow Response Time',
            default => 'Alert',
        };
    }

    /**
     * Generate message based on alert type
     */
    private function generateMessage(string $alertType): string
    {
        return match ($alertType) {
            'ssl_expiring' => 'SSL certificate expires in 7 days',
            'ssl_invalid' => 'SSL certificate validation failed',
            'uptime_down' => 'Website has been down for 3 consecutive checks',
            'uptime_up' => 'Website is now online',
            'performance_degradation' => 'Response time exceeds threshold',
            default => 'Alert condition detected',
        };
    }

    /**
     * Generate trigger value based on alert type
     */
    private function generateTriggerValue(string $alertType): array
    {
        return match ($alertType) {
            'ssl_expiring' => [
                'days_until_expiration' => 7,
                'certificate_expiration_date' => now()->addDays(7)->toIso8601String(),
                'certificate_issuer' => 'Let\'s Encrypt',
            ],
            'ssl_invalid' => [
                'error_message' => 'Certificate has expired',
                'certificate_issuer' => 'Unknown',
            ],
            'uptime_down' => [
                'consecutive_failures' => 3,
                'error_message' => 'Connection timeout',
            ],
            'uptime_up' => [
                'consecutive_successes' => 1,
                'downtime_duration_minutes' => 15,
            ],
            'performance_degradation' => [
                'response_time_ms' => 5500,
            ],
            default => [],
        };
    }

    /**
     * Generate threshold value based on alert type
     */
    private function generateThresholdValue(string $alertType): ?array
    {
        return match ($alertType) {
            'ssl_expiring' => [
                'warning_days' => 7,
                'critical_days' => 3,
            ],
            'uptime_down' => [
                'max_consecutive_failures' => 3,
            ],
            'performance_degradation' => [
                'max_response_time_ms' => 5000,
            ],
            default => null,
        };
    }
}
