<?php

namespace App\Services;

use App\Mail\SlowResponseTimeAlert;
use App\Mail\SslCertificateExpiryAlert;
use App\Mail\SslCertificateInvalidAlert;
use App\Mail\UptimeDownAlert;
use App\Models\AlertConfiguration;
use App\Models\Website;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AlertService
{
    public function __construct(
        private SslCertificateAnalysisService $analysisService
    ) {}

    /**
     * Check and trigger alerts for a specific website
     */
    public function checkAndTriggerAlerts(Website $website, bool $bypassCooldown = false): array
    {
        $triggeredAlerts = [];

        // Get alert configurations for this specific website only
        $alertConfigs = AlertConfiguration::where('user_id', $website->user_id)
            ->where('website_id', $website->id)
            ->where('enabled', true)
            ->get();

        if ($alertConfigs->isEmpty()) {
            // Create default alerts for new websites
            $this->createDefaultAlerts($website);

            return [];
        }

        // Get current website data for alert evaluation
        $checkData = $this->prepareCheckData($website);

        foreach ($alertConfigs as $alertConfig) {
            $shouldTrigger = $alertConfig->shouldTrigger($checkData, $bypassCooldown);

            if ($shouldTrigger) {
                $this->triggerAlert($alertConfig, $website, $checkData);
                $triggeredAlerts[] = [
                    'type' => $alertConfig->alert_type,
                    'level' => $alertConfig->alert_level,
                    'triggered_at' => now(),
                ];
            }
        }

        return $triggeredAlerts;
    }

    /**
     * Trigger all alerts for all websites (for scheduled checks)
     */
    public function checkAllWebsitesForAlerts(): array
    {
        $results = [];

        Website::with('user')
            ->where(function ($query) {
                $query->where('ssl_monitoring_enabled', true)
                    ->orWhere('uptime_monitoring_enabled', true);
            })
            ->chunk(50, function ($websites) use (&$results) {
                foreach ($websites as $website) {
                    try {
                        $alerts = $this->checkAndTriggerAlerts($website);
                        if (! empty($alerts)) {
                            $results[$website->id] = $alerts;
                        }
                    } catch (\Exception $e) {
                        Log::error("Alert check failed for website {$website->id}: ".$e->getMessage());
                    }
                }
            });

        return $results;
    }

    /**
     * Create default alert configurations for a website
     */
    public function createDefaultAlerts(Website $website): void
    {
        $defaults = AlertConfiguration::getDefaultConfigurations();

        foreach ($defaults as $default) {
            AlertConfiguration::firstOrCreate([
                'user_id' => $website->user_id,
                'website_id' => $website->id,
                'alert_type' => $default['alert_type'],
                'alert_level' => $default['alert_level'],
                'threshold_days' => $default['threshold_days'],
            ], $default);
        }
    }

    /**
     * Trigger a specific alert
     */
    private function triggerAlert(AlertConfiguration $alertConfig, Website $website, array $checkData): void
    {
        Log::info('Triggering alert', [
            'alert_type' => $alertConfig->alert_type,
            'website_id' => $website->id,
            'alert_level' => $alertConfig->alert_level,
        ]);

        // Send notifications based on configured channels
        foreach ($alertConfig->notification_channels as $channel) {
            match ($channel) {
                'email' => $this->sendEmailAlert($alertConfig, $website, $checkData),
                'dashboard' => $this->createDashboardNotification($alertConfig, $website, $checkData),
                'slack' => $this->sendSlackAlert($alertConfig, $website, $checkData),
                default => Log::warning("Unknown notification channel: {$channel}"),
            };
        }

        // Mark alert as triggered
        $alertConfig->markTriggered();
    }

    /**
     * Send email alert
     */
    private function sendEmailAlert(AlertConfiguration $alertConfig, Website $website, array $checkData): void
    {
        try {
            $user = $website->user;

            match ($alertConfig->alert_type) {
                AlertConfiguration::ALERT_SSL_EXPIRY => Mail::to($user->email)->send(new SslCertificateExpiryAlert($website, $alertConfig, $checkData)),

                AlertConfiguration::ALERT_SSL_INVALID => Mail::to($user->email)->send(new SslCertificateInvalidAlert($website, $checkData)),

                AlertConfiguration::ALERT_UPTIME_DOWN => Mail::to($user->email)->send(new UptimeDownAlert($website, $alertConfig, $checkData)),

                AlertConfiguration::ALERT_RESPONSE_TIME => Mail::to($user->email)->send(new SlowResponseTimeAlert($website, $checkData)),

                default => Log::warning("No email template for alert type: {$alertConfig->alert_type}"),
            };

            Log::info('Email alert sent', [
                'alert_type' => $alertConfig->alert_type,
                'recipient' => $user->email,
                'website' => $website->name,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send email alert: '.$e->getMessage(), [
                'alert_config_id' => $alertConfig->id,
                'website_id' => $website->id,
            ]);
        }
    }

    /**
     * Create dashboard notification (placeholder for future implementation)
     */
    private function createDashboardNotification(AlertConfiguration $alertConfig, Website $website, array $checkData): void
    {
        // TODO: Implement dashboard notifications in Phase 4
        Log::info('Dashboard notification created', [
            'alert_type' => $alertConfig->alert_type,
            'website_id' => $website->id,
        ]);
    }

    /**
     * Send Slack alert (placeholder for future implementation)
     */
    private function sendSlackAlert(AlertConfiguration $alertConfig, Website $website, array $checkData): void
    {
        // TODO: Implement Slack integration in Phase 5
        Log::info('Slack alert would be sent', [
            'alert_type' => $alertConfig->alert_type,
            'website_id' => $website->id,
        ]);
    }

    /**
     * Prepare check data for alert evaluation
     */
    private function prepareCheckData(Website $website): array
    {
        $monitor = $website->getSpatieMonitor();
        $checkData = [
            'ssl_status' => $monitor?->certificate_status ?? 'unknown',
            'uptime_status' => $monitor?->uptime_status ?? 'unknown',
            'response_time' => $monitor?->uptime_check_response_time_in_ms,
            'ssl_days_remaining' => null,
            'is_lets_encrypt' => false,
        ];

        // Check for active SSL debug overrides first
        $sslOverride = $website->getDebugOverride('ssl_expiry', $website->user_id);
        if ($sslOverride && $sslOverride->is_active && ! $sslOverride->isExpired()) {
            // Use effective expiry date from debug override
            $effectiveExpiryDate = $website->getEffectiveSslExpiryDate($website->user_id);
            if ($effectiveExpiryDate) {
                $daysRemaining = (int) \Carbon\Carbon::parse($effectiveExpiryDate)->diffInDays(now(), false);
                $checkData['ssl_days_remaining'] = $daysRemaining < 0 ? abs($daysRemaining) : $daysRemaining;
                $checkData['ssl_status'] = $daysRemaining < 0 ? 'expired' : 'valid';
            }
        } elseif ($monitor && $monitor->certificate_expiration_date) {
            // Calculate SSL days remaining and Let's Encrypt detection from original data
            $expirationDate = \Carbon\Carbon::parse($monitor->certificate_expiration_date);
            $daysRemaining = (int) $expirationDate->diffInDays(now(), false);
            $checkData['ssl_days_remaining'] = $daysRemaining < 0 ? abs($daysRemaining) : $daysRemaining;

            // Let's Encrypt detection
            $issuer = strtolower($monitor->certificate_issuer ?? '');
            $checkData['is_lets_encrypt'] = str_contains($issuer, "let's encrypt") ||
                                          str_contains($issuer, 'r3') ||
                                          str_contains($issuer, 'e1');
        }

        // Check for active uptime monitoring debug overrides
        $uptimeOverride = $website->getDebugOverride('uptime_monitoring', $website->user_id);
        if ($uptimeOverride && $uptimeOverride->is_active && ! $uptimeOverride->isExpired()) {
            $overrideData = $uptimeOverride->override_data;
            $checkData['uptime_status'] = $overrideData['uptime_status'] ?? $monitor?->uptime_status ?? 'unknown';
        }

        // Check for active response time debug overrides
        $responseTimeOverride = $website->getDebugOverride('response_time', $website->user_id);
        if ($responseTimeOverride && $responseTimeOverride->is_active && ! $responseTimeOverride->isExpired()) {
            $overrideData = $responseTimeOverride->override_data;
            $checkData['response_time'] = $overrideData['response_time'] ?? $monitor?->uptime_check_response_time_in_ms;
        }

        return $checkData;
    }

    /**
     * Test alert sending (for development/testing)
     */
    public function testAlert(Website $website, string $alertType = AlertConfiguration::ALERT_SSL_EXPIRY): bool
    {
        $alertConfig = AlertConfiguration::where('user_id', $website->user_id)
            ->where('website_id', $website->id)
            ->where('alert_type', $alertType)
            ->first();

        if (! $alertConfig) {
            // Create a test alert configuration
            $alertConfig = new AlertConfiguration([
                'user_id' => $website->user_id,
                'website_id' => $website->id,
                'alert_type' => $alertType,
                'enabled' => true,
                'threshold_days' => 30,
                'alert_level' => AlertConfiguration::LEVEL_WARNING,
                'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
            ]);
        }

        $checkData = $this->prepareCheckData($website);

        try {
            $this->triggerAlert($alertConfig, $website, $checkData);

            return true;
        } catch (\Exception $e) {
            Log::error('Test alert failed: '.$e->getMessage());

            return false;
        }
    }
}
