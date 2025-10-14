<?php

namespace Database\Seeders;

use App\Models\AlertConfiguration;
use App\Models\User;
use Illuminate\Database\Seeder;

class FixMissingGlobalAlertsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all users
        $users = User::all();

        foreach ($users as $user) {
            $this->ensureCompleteGlobalAlerts($user);
        }
    }

    /**
     * Ensure user has all required global alert templates
     */
    private function ensureCompleteGlobalAlerts(User $user): void
    {
        // Get existing global alerts
        $existingAlerts = AlertConfiguration::where('user_id', $user->id)
            ->whereNull('website_id')
            ->get()
            ->keyBy(function ($alert) {
                return $alert->alert_type . '_' . ($alert->threshold_days ?? 'null');
            });

        // Get all default configurations
        $defaults = AlertConfiguration::getDefaultConfigurations();

        foreach ($defaults as $default) {
            $key = $default['alert_type'] . '_' . ($default['threshold_days'] ?? 'null');

            // Only create if this alert type/combination doesn't exist
            if (!$existingAlerts->has($key)) {
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

                $this->command->info("Added missing global alert for user {$user->email}: {$default['alert_type']} ({$default['threshold_days']} days)");
            }
        }

        $totalAlerts = AlertConfiguration::where('user_id', $user->id)
            ->whereNull('website_id')
            ->count();

        $this->command->info("User {$user->email} now has {$totalAlerts} global alert templates");
    }
}