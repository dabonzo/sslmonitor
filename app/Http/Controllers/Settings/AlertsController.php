<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Website;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AlertsController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        // Get user's websites
        $websites = Website::where('user_id', $user->id)->get();

        // Mock alert configurations for now - in a real app, these would come from database
        $alertConfigurations = [
            [
                'id' => 1,
                'alert_type' => 'ssl_expiry',
                'alert_type_label' => 'SSL Certificate Expiry',
                'enabled' => true,
                'alert_level' => 'critical',
                'alert_level_color' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                'threshold_days' => 7,
                'threshold_response_time' => null,
                'notification_channels' => ['email', 'slack'],
                'custom_message' => 'SSL certificate expires in {days} days for {website}',
                'last_triggered_at' => null,
            ],
            [
                'id' => 2,
                'alert_type' => 'uptime_check',
                'alert_type_label' => 'Website Uptime Check',
                'enabled' => true,
                'alert_level' => 'urgent',
                'alert_level_color' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                'threshold_days' => null,
                'threshold_response_time' => 5000,
                'notification_channels' => ['email'],
                'custom_message' => 'Website {website} is down or responding slowly',
                'last_triggered_at' => null,
            ],
        ];

        // Default alert configurations that users can enable
        $defaultConfigurations = [
            [
                'alert_type' => 'ssl_expiry',
                'enabled' => true,
                'threshold_days' => 7,
                'alert_level' => 'critical',
                'notification_channels' => ['email'],
            ],
            [
                'alert_type' => 'ssl_expiry_warning',
                'enabled' => false,
                'threshold_days' => 30,
                'alert_level' => 'warning',
                'notification_channels' => ['email'],
            ],
            [
                'alert_type' => 'uptime_check',
                'enabled' => true,
                'threshold_days' => 0,
                'alert_level' => 'urgent',
                'notification_channels' => ['email', 'slack'],
            ],
            [
                'alert_type' => 'response_time',
                'enabled' => false,
                'threshold_days' => 0,
                'alert_level' => 'warning',
                'notification_channels' => ['email'],
            ],
        ];

        // Group alerts by website
        $alertsByWebsite = $websites->map(function ($website) use ($alertConfigurations) {
            return [
                'website' => [
                    'id' => $website->id,
                    'name' => $website->name,
                    'url' => $website->url,
                ],
                'alerts' => $alertConfigurations, // In real app, filter by website
            ];
        });

        return Inertia::render('Settings/Alerts', [
            'alertConfigurations' => $alertConfigurations,
            'alertsByWebsite' => $alertsByWebsite,
            'websites' => $websites->map(function ($website) {
                return [
                    'id' => $website->id,
                    'name' => $website->name,
                    'url' => $website->url,
                ];
            }),
            'defaultConfigurations' => $defaultConfigurations,
            'alertTypes' => [
                'ssl_expiry' => 'SSL Certificate Expiry',
                'ssl_expiry_warning' => 'SSL Certificate Warning',
                'uptime_check' => 'Website Uptime Check',
                'response_time' => 'Response Time Monitoring',
                'security_scan' => 'Security Vulnerability Scan',
            ],
            'notificationChannels' => [
                'email' => 'Email',
                'slack' => 'Slack',
                'webhook' => 'Webhook',
                'sms' => 'SMS',
            ],
            'alertLevels' => [
                'critical' => 'Critical',
                'urgent' => 'Urgent',
                'warning' => 'Warning',
                'info' => 'Info',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'alert_type' => 'required|string|in:ssl_expiry,ssl_expiry_warning,uptime_check,response_time,security_scan',
            'alert_level' => 'required|string|in:critical,urgent,warning,info',
            'threshold_days' => 'nullable|integer|min:1|max:365',
        ]);

        // In a real implementation, you would create the alert configuration in the database
        // For now, we'll just redirect back with success message

        return redirect()->back()->with('success', 'Alert configuration created successfully.');
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'ssl_expiry_enabled' => 'boolean',
            'ssl_expiry_days' => 'integer|min:1|max:90',
            'uptime_monitoring_enabled' => 'boolean',
            'email_notifications_enabled' => 'boolean',
            'slack_notifications_enabled' => 'boolean',
            'webhook_notifications_enabled' => 'boolean',
        ]);

        // In a real implementation, you would save these settings to the database
        // For now, we'll just redirect back with success message

        return redirect()->back()->with('success', 'Alert settings updated successfully.');
    }
}
