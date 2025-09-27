<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AlertConfiguration;
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

        // Get user's actual alert configurations from database
        $alertConfigurationsCollection = AlertConfiguration::where('user_id', $user->id)->get();

        $alertConfigurations = $alertConfigurationsCollection->map(function ($alert) {
                return [
                    'id' => $alert->id,
                    'alert_type' => $alert->alert_type,
                    'alert_type_label' => $alert->getAlertTypeLabel(),
                    'enabled' => $alert->enabled,
                    'alert_level' => $alert->alert_level,
                    'alert_level_color' => $this->getAlertLevelColorClass($alert->alert_level),
                    'threshold_days' => $alert->threshold_days,
                    'threshold_response_time' => $alert->threshold_response_time,
                    'notification_channels' => $alert->notification_channels ?? [],
                    'custom_message' => $alert->custom_message,
                    'last_triggered_at' => $alert->last_triggered_at?->format('Y-m-d H:i:s'),
                ];
            })
            ->toArray();

        // Get default alert configurations that users can enable
        $userAlertTypes = $alertConfigurationsCollection->pluck('alert_type')->toArray();
        $defaultConfigurations = collect(AlertConfiguration::getDefaultConfigurations())
            ->filter(function ($config) use ($userAlertTypes) {
                // Only show defaults that user hasn't customized yet
                return !in_array($config['alert_type'], $userAlertTypes);
            })
            ->map(function ($config) {
                return [
                    'alert_type' => $config['alert_type'],
                    'alert_type_label' => $this->getAlertTypeLabel($config['alert_type']),
                    'enabled' => $config['enabled'],
                    'threshold_days' => $config['threshold_days'],
                    'threshold_response_time' => $config['threshold_response_time'] ?? null,
                    'alert_level' => $config['alert_level'],
                    'notification_channels' => $config['notification_channels'],
                ];
            })
            ->values()
            ->toArray();

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
                AlertConfiguration::ALERT_SSL_EXPIRY => 'SSL Certificate Expiry',
                AlertConfiguration::ALERT_LETS_ENCRYPT_RENEWAL => 'Let\'s Encrypt Renewal',
                AlertConfiguration::ALERT_SSL_INVALID => 'SSL Certificate Invalid',
                AlertConfiguration::ALERT_UPTIME_DOWN => 'Website Down',
                AlertConfiguration::ALERT_RESPONSE_TIME => 'Response Time Monitoring',
            ],
            'notificationChannels' => [
                AlertConfiguration::CHANNEL_EMAIL => 'Email',
                AlertConfiguration::CHANNEL_SLACK => 'Slack',
                AlertConfiguration::CHANNEL_DASHBOARD => 'Dashboard',
            ],
            'alertLevels' => [
                AlertConfiguration::LEVEL_CRITICAL => 'Critical',
                AlertConfiguration::LEVEL_URGENT => 'Urgent',
                AlertConfiguration::LEVEL_WARNING => 'Warning',
                AlertConfiguration::LEVEL_INFO => 'Info',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'alert_type' => 'required|string|in:' . implode(',', [
                AlertConfiguration::ALERT_SSL_EXPIRY,
                AlertConfiguration::ALERT_LETS_ENCRYPT_RENEWAL,
                AlertConfiguration::ALERT_SSL_INVALID,
                AlertConfiguration::ALERT_UPTIME_DOWN,
                AlertConfiguration::ALERT_RESPONSE_TIME,
            ]),
            'alert_level' => 'required|string|in:' . implode(',', [
                AlertConfiguration::LEVEL_CRITICAL,
                AlertConfiguration::LEVEL_URGENT,
                AlertConfiguration::LEVEL_WARNING,
                AlertConfiguration::LEVEL_INFO,
            ]),
            'threshold_days' => 'nullable|integer|min:1|max:365',
            'threshold_response_time' => 'nullable|integer|min:100|max:30000',
        ]);

        // Check if user already has this alert type configured
        $existingAlert = AlertConfiguration::where('user_id', $user->id)
            ->where('alert_type', $validated['alert_type'])
            ->first();

        if ($existingAlert) {
            return redirect()->back()->with('error', 'You already have an alert configured for this type. Use the Configure button to modify it.');
        }

        // Create the alert configuration
        AlertConfiguration::create([
            'user_id' => $user->id,
            'alert_type' => $validated['alert_type'],
            'alert_level' => $validated['alert_level'],
            'threshold_days' => $validated['threshold_days'] ?? null,
            'threshold_response_time' => $validated['threshold_response_time'] ?? null,
            'enabled' => true,
            'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL, AlertConfiguration::CHANNEL_DASHBOARD],
        ]);

        return redirect()->back()->with('success', 'Alert configuration created successfully. It will now monitor all your websites.');
    }

    public function update(Request $request, AlertConfiguration $alertConfiguration)
    {
        // Ensure user owns this alert configuration
        if ($alertConfiguration->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'enabled' => 'required|boolean',
            'alert_level' => 'required|string|in:' . implode(',', [
                AlertConfiguration::LEVEL_CRITICAL,
                AlertConfiguration::LEVEL_URGENT,
                AlertConfiguration::LEVEL_WARNING,
                AlertConfiguration::LEVEL_INFO,
            ]),
            'threshold_days' => 'nullable|integer|min:1|max:365',
            'threshold_response_time' => 'nullable|integer|min:100|max:30000',
            'notification_channels' => 'required|array',
            'notification_channels.*' => 'string|in:' . implode(',', [
                AlertConfiguration::CHANNEL_EMAIL,
                AlertConfiguration::CHANNEL_SLACK,
                AlertConfiguration::CHANNEL_DASHBOARD,
            ]),
            'custom_message' => 'nullable|string|max:255',
        ]);

        $alertConfiguration->update($validated);

        return redirect()->back()->with('success', 'Alert configuration updated successfully.');
    }

    public function destroy(Request $request, AlertConfiguration $alertConfiguration)
    {
        // Ensure user owns this alert configuration
        if ($alertConfiguration->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $alertConfiguration->delete();

        return redirect()->back()->with('success', 'Alert configuration deleted successfully.');
    }

    private function getAlertLevelColorClass(string $level): string
    {
        return match($level) {
            AlertConfiguration::LEVEL_CRITICAL => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            AlertConfiguration::LEVEL_URGENT => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
            AlertConfiguration::LEVEL_WARNING => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            AlertConfiguration::LEVEL_INFO => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
        };
    }

    private function getAlertTypeLabel(string $type): string
    {
        return match($type) {
            AlertConfiguration::ALERT_SSL_EXPIRY => 'SSL Certificate Expiry',
            AlertConfiguration::ALERT_LETS_ENCRYPT_RENEWAL => 'Let\'s Encrypt Renewal',
            AlertConfiguration::ALERT_SSL_INVALID => 'SSL Certificate Invalid',
            AlertConfiguration::ALERT_UPTIME_DOWN => 'Website Down',
            AlertConfiguration::ALERT_RESPONSE_TIME => 'Response Time Monitoring',
            default => 'Unknown Alert',
        };
    }
}
