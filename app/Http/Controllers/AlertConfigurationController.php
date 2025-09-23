<?php

namespace App\Http\Controllers;

use App\Models\AlertConfiguration;
use App\Models\Website;
use App\Services\AlertService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
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
                AlertConfiguration::ALERT_LETS_ENCRYPT_RENEWAL => 'Let\'s Encrypt Renewal',
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
            ]
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
            ]
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

        if (!$website) {
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
}
