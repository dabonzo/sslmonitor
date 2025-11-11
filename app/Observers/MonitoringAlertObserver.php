<?php

namespace App\Observers;

use App\Mail\SslCertificateExpiryAlert;
use App\Mail\SslCertificateInvalidAlert;
use App\Mail\UptimeDownAlert;
use App\Mail\UptimeRecoveredAlert;
use App\Models\AlertConfiguration;
use App\Models\MonitoringAlert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MonitoringAlertObserver
{
    /**
     * Handle the MonitoringAlert "created" event.
     */
    public function created(MonitoringAlert $alert): void
    {
        // Set initial notification status
        $alert->update([
            'notification_status' => 'pending',
        ]);

        // Get alert configurations for this website to determine notification channels
        $alertConfigs = AlertConfiguration::where('website_id', $alert->website_id)
            ->where('enabled', true)
            ->get();

        if ($alertConfigs->isEmpty()) {
            Log::warning('No alert configurations found for website', [
                'website_id' => $alert->website_id,
                'alert_type' => $alert->alert_type,
            ]);

            return;
        }

        // Find matching alert configuration based on alert type
        $matchingConfig = $this->findMatchingAlertConfig($alertConfigs, $alert);

        if (! $matchingConfig) {
            Log::warning('No matching alert configuration found', [
                'website_id' => $alert->website_id,
                'alert_type' => $alert->alert_type,
            ]);

            return;
        }

        // Update notification channels from config
        $alert->update([
            'notification_channels' => implode(',', $matchingConfig->notification_channels),
        ]);

        // Send notifications based on configured channels
        $notificationsSent = [];

        foreach ($matchingConfig->notification_channels as $channel) {
            try {
                match ($channel) {
                    'email' => $this->sendEmailNotification($alert),
                    'dashboard' => $this->recordDashboardNotification($alert),
                    default => Log::warning("Unknown notification channel: {$channel}"),
                };

                $notificationsSent[] = [
                    'channel' => $channel,
                    'sent_at' => now()->toIso8601String(),
                    'status' => 'success',
                ];
            } catch (\Exception $e) {
                Log::error('Failed to send alert notification', [
                    'channel' => $channel,
                    'alert_id' => $alert->id,
                    'error' => $e->getMessage(),
                ]);

                $notificationsSent[] = [
                    'channel' => $channel,
                    'sent_at' => now()->toIso8601String(),
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Update alert with notification results
        $alert->update([
            'notifications_sent' => $notificationsSent,
            'notification_status' => $this->hasFailedNotifications($notificationsSent) ? 'partial' : 'sent',
        ]);
    }

    /**
     * Find matching alert configuration for the alert type
     */
    private function findMatchingAlertConfig($alertConfigs, MonitoringAlert $alert): ?AlertConfiguration
    {
        // Map MonitoringAlert types to AlertConfiguration types
        $typeMapping = [
            'ssl_expiring' => AlertConfiguration::ALERT_SSL_EXPIRY,
            'ssl_invalid' => AlertConfiguration::ALERT_SSL_INVALID,
            'uptime_down' => AlertConfiguration::ALERT_UPTIME_DOWN,
            'uptime_up' => AlertConfiguration::ALERT_UPTIME_UP,
            'performance_degradation' => AlertConfiguration::ALERT_RESPONSE_TIME,
        ];

        $configAlertType = $typeMapping[$alert->alert_type] ?? null;

        if (! $configAlertType) {
            return null;
        }

        return $alertConfigs->firstWhere('alert_type', $configAlertType);
    }

    /**
     * Send email notification for the alert
     */
    private function sendEmailNotification(MonitoringAlert $alert): void
    {
        $website = $alert->website;
        $user = $website->user;

        if (! $user || ! $user->email) {
            throw new \Exception('Website has no associated user or email');
        }

        // Prepare check data from alert
        $checkData = array_merge(
            $alert->trigger_value ?? [],
            [
                'alert_severity' => $alert->alert_severity,
                'alert_message' => $alert->alert_message,
                'error_message' => $alert->trigger_value['error_message'] ?? null,
            ]
        );

        // Get the matching alert configuration for proper email formatting
        $alertConfig = $this->findMatchingAlertConfig(
            AlertConfiguration::where('website_id', $alert->website_id)
                ->where('enabled', true)
                ->get(),
            $alert
        );

        // Send appropriate email based on alert type
        match ($alert->alert_type) {
            'ssl_expiring' => Mail::to($user->email)->send(
                new SslCertificateExpiryAlert(
                    $website,
                    $alertConfig ?? $this->createFallbackAlertConfig($alert),
                    $checkData
                )
            ),

            'ssl_invalid' => Mail::to($user->email)->send(
                new SslCertificateInvalidAlert(
                    $website,
                    $checkData
                )
            ),

            'uptime_down' => Mail::to($user->email)->send(
                new UptimeDownAlert(
                    $website,
                    $alertConfig ?? $this->createFallbackAlertConfig($alert),
                    $checkData
                )
            ),

            'uptime_up' => Mail::to($user->email)->send(
                new UptimeRecoveredAlert(
                    $website,
                    $checkData
                )
            ),

            default => throw new \Exception("No email template for alert type: {$alert->alert_type}"),
        };

        Log::info('Email alert sent via observer', [
            'alert_id' => $alert->id,
            'alert_type' => $alert->alert_type,
            'recipient' => $user->email,
            'website' => $website->name,
        ]);
    }

    /**
     * Create a fallback alert configuration object for email templates
     */
    private function createFallbackAlertConfig(MonitoringAlert $alert): AlertConfiguration
    {
        $config = new AlertConfiguration();
        $config->alert_type = $alert->alert_type;
        $config->alert_level = $alert->alert_severity;
        $config->threshold_days = $alert->threshold_value['warning_days'] ?? $alert->threshold_value['critical_days'] ?? null;
        $config->website_id = $alert->website_id;
        $config->enabled = true;
        $config->notification_channels = ['email', 'dashboard'];

        return $config;
    }

    /**
     * Record dashboard notification (already visible via MonitoringAlert model)
     */
    private function recordDashboardNotification(MonitoringAlert $alert): void
    {
        Log::info('Dashboard notification recorded', [
            'alert_id' => $alert->id,
            'alert_type' => $alert->alert_type,
            'website_id' => $alert->website_id,
        ]);

        // Dashboard notifications are automatically visible through the monitoring_alerts table
        // No additional action needed
    }

    /**
     * Check if any notifications failed
     */
    private function hasFailedNotifications(array $notificationsSent): bool
    {
        foreach ($notificationsSent as $notification) {
            if ($notification['status'] === 'failed') {
                return true;
            }
        }

        return false;
    }
}
