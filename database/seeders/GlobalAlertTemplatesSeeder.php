<?php

namespace Database\Seeders;

use App\Models\AlertConfiguration;
use App\Models\User;
use Illuminate\Database\Seeder;

class GlobalAlertTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all users
        $users = User::all();

        foreach ($users as $user) {
            $this->createGlobalAlertsForUser($user);
        }
    }

    /**
     * Create global alert templates for a specific user
     */
    private function createGlobalAlertsForUser(User $user): void
    {
        // Check if user already has global templates
        $existingGlobalAlerts = AlertConfiguration::where('user_id', $user->id)
            ->whereNull('website_id')
            ->count();

        if ($existingGlobalAlerts > 0) {
            return; // Skip users who already have global templates
        }

        // Get default configurations
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

        $this->command->info("Created global alert templates for user: {$user->email}");
    }

    /**
     * Create global alert templates for a specific user ID
     * Useful for individual user operations
     */
    public static function createForUser(int $userId): void
    {
        $user = User::find($userId);
        if (!$user) {
            return;
        }

        $seeder = new self();
        $seeder->createGlobalAlertsForUser($user);
    }
}