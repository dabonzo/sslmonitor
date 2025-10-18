<?php

namespace App\Http\Controllers;

use App\Models\AlertConfiguration;
use App\Models\Website;
use App\Services\AlertService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AlertConfigurationController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $user = $request->user();

        // Get all alert configurations for the user
        $alertConfigs = AlertConfiguration::with('website')
            ->where('user_id', $user->id)
            ->orderBy('website_id')
            ->orderBy('alert_type')
            ->get()
            ->groupBy('website_id');

        // Get user's websites for the dropdown
        $websites = Website::where('user_id', $user->id)
            ->orderBy('name')
            ->get(['id', 'name', 'url']);

        // Transform data for frontend
        $alertsByWebsite = [];
        foreach ($alertConfigs as $websiteId => $configs) {
            $website = $websites->firstWhere('id', $websiteId);
            $alertsByWebsite[] = [
                'website' => $website,
                'alerts' => $configs->map(function ($config) {
                    return [
                        'id' => $config->id,
                        'alert_type' => $config->alert_type,
                        'alert_type_label' => $config->getAlertTypeLabel(),
                        'enabled' => $config->enabled,
                        'alert_level' => $config->alert_level,
                        'alert_level_color' => $config->getAlertLevelColor(),
                        'threshold_days' => $config->threshold_days,
                        'threshold_response_time' => $config->threshold_response_time,
                        'notification_channels' => $config->notification_channels,
                        'custom_message' => $config->custom_message,
                        'last_triggered_at' => $config->last_triggered_at?->toISOString(),
                    ];
                })->toArray(),
            ];
        }

        // Get all alert configurations for the user (flat list for test compatibility)
        $alertConfigurations = AlertConfiguration::with('website')
            ->where('user_id', $user->id)
            ->orderBy('website_id')
            ->orderBy('alert_type')
            ->get();

        return Inertia::render('Alerts/Index', [
            'alertConfigurations' => $alertConfigurations,
            'alertsByWebsite' => $alertsByWebsite,
            'websites' => $websites,
            'defaultConfigurations' => AlertConfiguration::getDefaultConfigurations(),
            'alertTypes' => [
                AlertConfiguration::ALERT_SSL_EXPIRY => 'SSL Certificate Expiry',
                AlertConfiguration::ALERT_SSL_INVALID => 'SSL Certificate Invalid',
                AlertConfiguration::ALERT_UPTIME_DOWN => 'Website Down',
                AlertConfiguration::ALERT_RESPONSE_TIME => 'Slow Response Time',
            ],
            'notificationChannels' => [
                AlertConfiguration::CHANNEL_EMAIL => 'Email',
                AlertConfiguration::CHANNEL_DASHBOARD => 'Dashboard',
                AlertConfiguration::CHANNEL_SLACK => 'Slack (Coming Soon)',
            ],
            'alertLevels' => [
                AlertConfiguration::LEVEL_INFO => 'Info',
                AlertConfiguration::LEVEL_WARNING => 'Warning',
                AlertConfiguration::LEVEL_URGENT => 'Urgent',
                AlertConfiguration::LEVEL_CRITICAL => 'Critical',
            ],
        ]);
    }

    public function notifications(Request $request): Response
    {
        $user = $request->user();

        // Get recent notifications/alerts
        $recentAlerts = []; // TODO: Implement alert history model

        return Inertia::render('Alerts/Notifications', [
            'recentAlerts' => $recentAlerts,
            'notificationSettings' => [
                'email_enabled' => true,
                'slack_enabled' => false,
                'webhook_enabled' => false,
            ],
        ]);
    }

    public function history(Request $request): Response
    {
        $user = $request->user();

        // Get alert history
        $alertHistory = []; // TODO: Implement alert history model

        return Inertia::render('Alerts/History', [
            'alertHistory' => $alertHistory,
            'filters' => [
                'type' => $request->get('type', 'all'),
                'level' => $request->get('level', 'all'),
                'date_range' => $request->get('date_range', '7_days'),
            ],
        ]);
    }

    public function update(Request $request, AlertConfiguration $alertConfiguration): RedirectResponse
    {
        $this->authorize('update', $alertConfiguration);

        $validated = $request->validate([
            'enabled' => 'boolean',
            'threshold_days' => 'nullable|integer|min:1|max:365',
            'threshold_response_time' => 'nullable|integer|min:100|max:60000',
            'notification_channels' => 'array',
            'notification_channels.*' => 'in:email,dashboard,slack',
            'alert_level' => 'in:info,warning,urgent,critical',
            'custom_message' => 'nullable|string|max:500',
        ]);

        $alertConfiguration->update($validated);

        return redirect()
            ->back()
            ->with('success', 'Alert configuration updated successfully.');
    }

    public function testAlert(Request $request, AlertConfiguration $alertConfiguration): RedirectResponse
    {
        $this->authorize('update', $alertConfiguration);

        $alertService = app(AlertService::class);
        $website = $alertConfiguration->website;

        if (! $website) {
            return redirect()
                ->back()
                ->with('error', 'Cannot test alert: website not found.');
        }

        $success = $alertService->testAlert($website, $alertConfiguration->alert_type);

        if ($success) {
            return redirect()
                ->back()
                ->with('success', 'Test alert sent successfully! Check your email and Mailpit at http://localhost:8025');
        }

        return redirect()
            ->back()
            ->with('error', 'Failed to send test alert. Check the logs for details.');
    }

    public function testAllAlerts(Request $request): RedirectResponse
    {
        $user = $request->user();
        $alertService = app(AlertService::class);

        // Get one of the user's websites to use for test alerts
        $website = Website::where('user_id', $user->id)->first();

        if (! $website) {
            return redirect()
                ->back()
                ->with('error', 'You need at least one website to test alerts.');
        }

        $testLevels = [
            [
                'level' => AlertConfiguration::LEVEL_INFO,
                'days' => 30,
                'label' => 'Info (30 days)',
            ],
            [
                'level' => AlertConfiguration::LEVEL_WARNING,
                'days' => 14,
                'label' => 'Warning (14 days)',
            ],
            [
                'level' => AlertConfiguration::LEVEL_URGENT,
                'days' => 7,
                'label' => 'Urgent (7 days)',
            ],
            [
                'level' => AlertConfiguration::LEVEL_CRITICAL,
                'days' => 3,
                'label' => 'Critical (3 days)',
            ],
        ];

        $sentCount = 0;

        // Send SSL Certificate Expiry test alerts
        foreach ($testLevels as $testLevel) {
            try {
                $testConfig = new AlertConfiguration([
                    'user_id' => $user->id,
                    'website_id' => $website->id,
                    'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
                    'enabled' => true,
                    'threshold_days' => $testLevel['days'],
                    'alert_level' => $testLevel['level'],
                    'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
                    'custom_message' => "This is a test {$testLevel['label']} SSL certificate alert.",
                ]);

                $checkData = [
                    'ssl_status' => 'valid',
                    'uptime_status' => 'up',
                    'ssl_days_remaining' => $testLevel['days'],
                    'is_lets_encrypt' => false,
                ];

                \Illuminate\Support\Facades\Mail::to($user->email)->send(
                    new \App\Mail\SslCertificateExpiryAlert($website, $testConfig, $checkData)
                );

                $sentCount++;
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to send SSL test alert for level {$testLevel['level']}: ".$e->getMessage());
            }
        }

        // Send Uptime Down test alert
        try {
            $uptimeDownConfig = new AlertConfiguration([
                'user_id' => $user->id,
                'website_id' => $website->id,
                'alert_type' => AlertConfiguration::ALERT_UPTIME_DOWN,
                'enabled' => true,
                'alert_level' => AlertConfiguration::LEVEL_CRITICAL,
                'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
                'custom_message' => 'This is a test uptime down alert.',
            ]);

            $uptimeDownData = [
                'uptime_status' => 'down',
                'failure_reason' => 'Connection timeout - This is a test alert',
                'status_code' => null,
                'checked_at' => now(),
            ];

            \Illuminate\Support\Facades\Mail::to($user->email)->send(
                new \App\Mail\UptimeDownAlert($website, $uptimeDownConfig, $uptimeDownData)
            );

            $sentCount++;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send uptime down test alert: '.$e->getMessage());
        }

        // Send Uptime Recovered test alert
        try {
            $uptimeRecoveredConfig = new AlertConfiguration([
                'user_id' => $user->id,
                'website_id' => $website->id,
                'alert_type' => AlertConfiguration::ALERT_UPTIME_DOWN,
                'enabled' => true,
                'alert_level' => AlertConfiguration::LEVEL_INFO,
                'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
                'custom_message' => 'This is a test uptime recovered alert.',
            ]);

            $uptimeRecoveredData = [
                'uptime_status' => 'up',
                'response_time' => 287,
                'status_code' => 200,
                'checked_at' => now(),
            ];

            \Illuminate\Support\Facades\Mail::to($user->email)->send(
                new \App\Mail\UptimeRecoveredAlert($website, $uptimeRecoveredConfig, $uptimeRecoveredData, '15 minutes')
            );

            $sentCount++;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send uptime recovered test alert: '.$e->getMessage());
        }

        if ($sentCount > 0) {
            return redirect()
                ->back()
                ->with('success', "Sent {$sentCount} test alerts ({$sentCount} total: SSL + Uptime)! Check your email and Mailpit at http://localhost:8025");
        }

        return redirect()
            ->back()
            ->with('error', 'Failed to send test alerts. Check the logs for details.');
    }
}
