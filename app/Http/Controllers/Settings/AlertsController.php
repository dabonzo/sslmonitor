<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AlertConfiguration;
use App\Models\User;
use App\Models\Website;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AlertsController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        // Get global alert templates (website_id is null)
        $globalAlerts = AlertConfiguration::where('user_id', $user->id)
            ->whereNull('website_id')
            ->get();

        // If no global templates exist, create default ones
        if ($globalAlerts->isEmpty()) {
            $this->createDefaultGlobalAlerts($user);
            $globalAlerts = AlertConfiguration::where('user_id', $user->id)
                ->whereNull('website_id')
                ->get();
        }

        // Get all user's websites
        $websites = Website::where('user_id', $user->id)->get();

        // Get all website-specific alert configurations
        $websiteAlerts = AlertConfiguration::where('user_id', $user->id)
            ->whereNotNull('website_id')
            ->get();

        // Group alerts by website
        $alertsByWebsite = $websites->map(function ($website) use ($websiteAlerts) {
            return [
                'website' => [
                    'id' => $website->id,
                    'name' => $website->name,
                    'url' => $website->url,
                ],
                'alerts' => $websiteAlerts->where('website_id', $website->id)->map(function ($alert) {
                    return [
                        'id' => $alert->id,
                        'alert_type' => $alert->alert_type,
                        'alert_type_label' => $alert->getAlertTypeLabel(),
                        'enabled' => $alert->enabled,
                        'alert_level' => $alert->alert_level,
                        'threshold_days' => $alert->threshold_days,
                        'threshold_response_time' => $alert->threshold_response_time,
                        'notification_channels' => $alert->notification_channels ?? [],
                        'custom_message' => $alert->custom_message,
                    ];
                })->values()->toArray(),
            ];
        })->values()->toArray();

        // Group global alerts by type for the UI
        $globalAlertsData = [
            'sslExpiryAlerts' => $globalAlerts->filter(function ($alert) {
                return in_array($alert->alert_type, [
                    AlertConfiguration::ALERT_SSL_EXPIRY,
                    AlertConfiguration::ALERT_SSL_INVALID,
                ]);
            })->map(function ($alert) {
                return [
                    'id' => $alert->id,
                    'alert_type' => $alert->alert_type,
                    'alert_type_label' => $alert->getAlertTypeLabel(),
                    'enabled' => $alert->enabled,
                    'alert_level' => $alert->alert_level,
                    'threshold_days' => $alert->threshold_days,
                    'threshold_response_time' => $alert->threshold_response_time,
                    'notification_channels' => $alert->notification_channels ?? [],
                    'custom_message' => $alert->custom_message,
                ];
            })->values()->toArray(),

            'uptimeAlerts' => $globalAlerts->filter(function ($alert) {
                return in_array($alert->alert_type, [
                    AlertConfiguration::ALERT_UPTIME_DOWN,
                    AlertConfiguration::ALERT_UPTIME_UP,
                ]);
            })->map(function ($alert) {
                return [
                    'id' => $alert->id,
                    'alert_type' => $alert->alert_type,
                    'alert_type_label' => $alert->getAlertTypeLabel(),
                    'enabled' => $alert->enabled,
                    'alert_level' => $alert->alert_level,
                    'threshold_days' => $alert->threshold_days,
                    'threshold_response_time' => $alert->threshold_response_time,
                    'notification_channels' => $alert->notification_channels ?? [],
                    'custom_message' => $alert->custom_message,
                ];
            })->values()->toArray(),

            'responseTimeAlerts' => $globalAlerts->filter(function ($alert) {
                return $alert->alert_type === AlertConfiguration::ALERT_RESPONSE_TIME;
            })->map(function ($alert) {
                return [
                    'id' => $alert->id,
                    'alert_type' => $alert->alert_type,
                    'alert_type_label' => $alert->getAlertTypeLabel(),
                    'enabled' => $alert->enabled,
                    'alert_level' => $alert->alert_level,
                    'threshold_days' => $alert->threshold_days,
                    'threshold_response_time' => $alert->threshold_response_time,
                    'notification_channels' => $alert->notification_channels ?? [],
                    'custom_message' => $alert->custom_message,
                ];
            })->values()->toArray(),
        ];

        // Get default configurations
        $defaultConfigurations = AlertConfiguration::getDefaultConfigurations();

        // Define alert types
        $alertTypes = [
            AlertConfiguration::ALERT_SSL_EXPIRY => 'SSL Certificate Expiry',
            AlertConfiguration::ALERT_SSL_INVALID => 'SSL Certificate Invalid',
            AlertConfiguration::ALERT_UPTIME_DOWN => 'Website Down',
            AlertConfiguration::ALERT_RESPONSE_TIME => 'Response Time Monitoring',
        ];

        // Define notification channels
        $notificationChannels = [
            AlertConfiguration::CHANNEL_EMAIL => 'Email',
            AlertConfiguration::CHANNEL_SLACK => 'Slack',
            AlertConfiguration::CHANNEL_DASHBOARD => 'Dashboard',
        ];

        // Define alert levels
        $alertLevels = [
            AlertConfiguration::LEVEL_CRITICAL => 'Critical',
            AlertConfiguration::LEVEL_URGENT => 'Urgent',
            AlertConfiguration::LEVEL_WARNING => 'Warning',
            AlertConfiguration::LEVEL_INFO => 'Info',
        ];

        return Inertia::render('Settings/Alerts', [
            'globalAlerts' => $globalAlertsData,
            'alertConfigurations' => $globalAlerts->map(function ($alert) {
                return [
                    'id' => $alert->id,
                    'alert_type' => $alert->alert_type,
                    'alert_type_label' => $alert->getAlertTypeLabel(),
                    'enabled' => $alert->enabled,
                    'alert_level' => $alert->alert_level,
                    'threshold_days' => $alert->threshold_days,
                    'threshold_response_time' => $alert->threshold_response_time,
                    'notification_channels' => $alert->notification_channels ?? [],
                    'custom_message' => $alert->custom_message,
                ];
            })->toArray(),
            'defaultConfigurations' => $defaultConfigurations,
            'websites' => $websites->map(function ($website) {
                return [
                    'id' => $website->id,
                    'name' => $website->name,
                    'url' => $website->url,
                ];
            })->toArray(),
            'alertsByWebsite' => $alertsByWebsite,
            'alertTypes' => $alertTypes,
            'notificationChannels' => $notificationChannels,
            'alertLevels' => $alertLevels,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'alert_type' => 'required|string|in:' . implode(',', [
                AlertConfiguration::ALERT_SSL_EXPIRY,
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

    public function updateGlobal(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'alert_type' => 'required|string',
            'threshold_days' => 'nullable|integer|min:0|max:365',
            'enabled' => 'required|boolean',
        ]);

        // Convert validated 'enabled' from string to proper boolean
        $validated['enabled'] = filter_var($validated['enabled'], FILTER_VALIDATE_BOOLEAN);

        // Find or create the global alert template
        $query = AlertConfiguration::where('user_id', $user->id)
            ->where('alert_type', $validated['alert_type'])
            ->whereNull('website_id');

        // For SSL expiry alerts, also match on threshold_days
        if ($validated['alert_type'] === 'ssl_expiry' && isset($validated['threshold_days'])) {
            $query->where('threshold_days', $validated['threshold_days']);
        }

        // For response_time alerts, we need to find by ID since they don't use threshold_days
        if ($validated['alert_type'] === 'response_time' && $request->has('alert_id')) {
            $query->where('id', $request->input('alert_id'));
        }

        $alert = $query->first();

        if (!$alert) {
            // Create a new global template if it doesn't exist
            $defaults = AlertConfiguration::getDefaultConfigurations();
            $defaultConfig = collect($defaults)->firstWhere('alert_type', $validated['alert_type']);

            if (!$defaultConfig) {
                return response()->json(['error' => 'Invalid alert type'], 400);
            }

            $alert = AlertConfiguration::create([
                'user_id' => $user->id,
                'website_id' => null, // Global template
                'alert_type' => $validated['alert_type'],
                'alert_level' => $defaultConfig['alert_level'],
                'threshold_days' => $validated['threshold_days'],
                'threshold_response_time' => $defaultConfig['threshold_response_time'] ?? null,
                'enabled' => $validated['enabled'],
                'notification_channels' => $defaultConfig['notification_channels'],
                'custom_message' => $defaultConfig['custom_message'] ?? null,
            ]);
        } else {
            // Update existing global template
            $updateData = ['enabled' => $validated['enabled']];

            // Only update threshold_days if it's provided (not for response_time alerts)
            if (isset($validated['threshold_days'])) {
                $updateData['threshold_days'] = $validated['threshold_days'];
            }

            $alert->update($updateData);
        }

        // For Inertia requests, we want to return a redirect back to the page
      // The page will reload with the updated data automatically
        return redirect()->back()->with('success', 'Alert template updated successfully.');
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
            AlertConfiguration::ALERT_SSL_INVALID => 'SSL Certificate Invalid',
            AlertConfiguration::ALERT_UPTIME_DOWN => 'Website Down',
            AlertConfiguration::ALERT_RESPONSE_TIME => 'Response Time Monitoring',
            default => 'Unknown Alert',
        };
    }

    /**
     * Create default alert configurations for a specific website
     * Inherits from user's global template settings instead of static defaults
     */
    private function createDefaultAlertsForWebsite(Website $website): void
    {
        // Get user's global template settings (website_id is null)
        $globalTemplates = AlertConfiguration::where('user_id', $website->user_id)
            ->whereNull('website_id')
            ->get();

        // If user has no global templates, fall back to static defaults
        if ($globalTemplates->isEmpty()) {
            $user = User::find($website->user_id);
            $this->createDefaultGlobalAlerts($user);
            $globalTemplates = AlertConfiguration::where('user_id', $website->user_id)
                ->whereNull('website_id')
                ->get();
        }

        // Create website-specific alerts by copying from global templates
        foreach ($globalTemplates as $template) {
            AlertConfiguration::create([
                'user_id' => $website->user_id,
                'website_id' => $website->id, // Website-specific alerts
                'alert_type' => $template->alert_type,
                'alert_level' => $template->alert_level,
                'threshold_days' => $template->threshold_days,
                'threshold_response_time' => $template->threshold_response_time,
                'enabled' => $template->enabled, // Inherit enabled state from global template
                'notification_channels' => $template->notification_channels,
                'custom_message' => $template->custom_message,
            ]);
        }
    }

    /**
     * Create default global alert templates for a user
     */
    private function createDefaultGlobalAlerts(User $user): void
    {
        $defaults = AlertConfiguration::getDefaultConfigurations();

        foreach ($defaults as $default) {
            AlertConfiguration::create([
                'user_id' => $user->id,
                'website_id' => null, // Global templates
                'alert_type' => $default['alert_type'],
                'alert_level' => $default['alert_level'],
                'threshold_days' => $default['threshold_days'],
                'threshold_response_time' => $default['threshold_response_time'] ?? null,
                'enabled' => $default['enabled'],
                'notification_channels' => $default['notification_channels'],
                'custom_message' => $default['custom_message'] ?? null,
            ]);
        }
    }

    /**
     * Get alert configurations for a specific website
     */
    public function getWebsiteAlerts(Request $request, Website $website)
    {
        // Ensure user owns this website
        if ($website->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        // Create default alerts if none exist
        $existingAlerts = AlertConfiguration::where('user_id', $request->user()->id)
            ->where('website_id', $website->id)
            ->count();

        if ($existingAlerts === 0) {
            $this->createDefaultAlertsForWebsite($website);
        }

        // Get alert configurations
        $alertConfigurations = AlertConfiguration::where('user_id', $request->user()->id)
            ->where('website_id', $website->id)
            ->get()
            ->map(function ($alert) {
                return [
                    'id' => $alert->id,
                    'alert_type' => $alert->alert_type,
                    'alert_type_label' => $alert->getAlertTypeLabel(),
                    'enabled' => $alert->enabled,
                    'alert_level' => $alert->alert_level,
                    'alert_level_label' => ucfirst($alert->alert_level),
                    'alert_level_color' => $this->getAlertLevelColorClass($alert->alert_level),
                    'threshold_days' => $alert->threshold_days,
                    'threshold_response_time' => $alert->threshold_response_time,
                    'notification_channels' => $alert->notification_channels ?? [],
                    'custom_message' => $alert->custom_message,
                    'last_triggered_at' => $alert->last_triggered_at?->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'alertConfigurations' => $alertConfigurations,
        ]);
    }

    /**
     * Send a test alert for a specific alert configuration
     */
    public function testAlert(Request $request, AlertConfiguration $alertConfiguration)
    {
        // Ensure user owns this alert configuration
        if ($alertConfiguration->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        // Create a test alert event
        $alertService = app(\App\Services\AlertService::class);

        try {
            $alertService->sendTestAlert($alertConfiguration);

            return response()->json(['message' => 'Test alert sent successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to send test alert: ' . $e->getMessage()], 500);
        }
    }
}