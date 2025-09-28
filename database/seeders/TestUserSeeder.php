<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Website;
use App\Models\Team;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        // Create or update the test user
        $user = User::updateOrCreate(
            ['email' => 'bonzo@konjscina.com'],
            [
                'name' => 'Bonzo',
                'email' => 'bonzo@konjscina.com',
                'password' => Hash::make('to16ro12'),
                'email_verified_at' => now(),
            ]
        );

        // Create a test team
        $team = Team::updateOrCreate(
            [
                'name' => 'Development Team',
                'created_by_user_id' => $user->id
            ],
            [
                'name' => 'Development Team',
                'description' => 'Team for managing development and staging websites',
                'created_by_user_id' => $user->id
            ]
        );

        // Add user to team as owner
        $team->members()->syncWithoutDetaching([
            $user->id => [
                'role' => 'OWNER',
                'joined_at' => now(),
                'invited_by_user_id' => $user->id
            ]
        ]);

        // Create test websites with SSL monitoring
        $websites = [
            [
                'name' => 'Office Manager Pro',
                'url' => 'https://omp.office-manager-pro.com',
                'ssl_status' => 'valid',
                'cert_expiry_days' => 90,
                'team_id' => $team->id, // Assign to team
            ],
            [
                'name' => 'RedGas Austria',
                'url' => 'https://www.redgas.at',
                'ssl_status' => 'valid',
                'cert_expiry_days' => 7, // Expiring soon
                'team_id' => $team->id, // Assign to team
            ],
            [
                'name' => 'Fairnando',
                'url' => 'https://www.fairnando.at',
                'ssl_status' => 'invalid',
                'cert_expiry_days' => -1, // Expired
                'team_id' => null, // Keep as personal
            ],
            [
                'name' => 'Gebrauchte',
                'url' => 'https://www.gebrauchte.at',
                'ssl_status' => 'valid',
                'cert_expiry_days' => 30,
                'team_id' => null, // Keep as personal
            ],
        ];

        foreach ($websites as $websiteData) {
            $website = Website::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'url' => $websiteData['url']
                ],
                [
                    'name' => $websiteData['name'],
                    'url' => $websiteData['url'],
                    'team_id' => $websiteData['team_id'],
                    'ssl_monitoring_enabled' => true,
                    'uptime_monitoring_enabled' => true,
                    'monitoring_config' => [
                        'timeout' => 30,
                        'retries' => 3,
                        'follow_redirects' => true,
                        'verify_ssl' => true,
                        'alert_days_before_expiry' => 30,
                        'check_interval' => 3600,
                        'is_active' => true,
                    ],
                    'plugin_data' => [],
                ]
            );

            // Create corresponding Spatie monitor
            \Spatie\UptimeMonitor\Models\Monitor::updateOrCreate(
                ['url' => $websiteData['url']],
                [
                    'certificate_check_enabled' => true,
                    'certificate_status' => $websiteData['ssl_status'],
                    'certificate_expiration_date' => $websiteData['cert_expiry_days'] > 0
                        ? now()->addDays($websiteData['cert_expiry_days'])
                        : now()->subDays(abs($websiteData['cert_expiry_days'])),
                    'uptime_check_enabled' => true,
                    'uptime_status' => 'up',
                ]
            );
        }

        // Create default alert configurations for the user
        $alertConfigurations = [
            [
                'user_id' => $user->id,
                'alert_type' => \App\Models\AlertConfiguration::ALERT_SSL_EXPIRY,
                'enabled' => true,
                'threshold_days' => 7,
                'alert_level' => \App\Models\AlertConfiguration::LEVEL_URGENT,
                'notification_channels' => [\App\Models\AlertConfiguration::CHANNEL_EMAIL, \App\Models\AlertConfiguration::CHANNEL_DASHBOARD],
                'custom_message' => 'SSL certificate for {website} expires in {days} days!',
            ],
            [
                'user_id' => $user->id,
                'alert_type' => \App\Models\AlertConfiguration::ALERT_UPTIME_DOWN,
                'enabled' => true,
                'alert_level' => \App\Models\AlertConfiguration::LEVEL_CRITICAL,
                'notification_channels' => [\App\Models\AlertConfiguration::CHANNEL_EMAIL, \App\Models\AlertConfiguration::CHANNEL_DASHBOARD],
                'custom_message' => 'Website {website} is down!',
            ],
        ];

        foreach ($alertConfigurations as $alertData) {
            \App\Models\AlertConfiguration::updateOrCreate(
                [
                    'user_id' => $alertData['user_id'],
                    'alert_type' => $alertData['alert_type']
                ],
                $alertData
            );
        }

        $this->command->info('Created test user: bonzo@konjscina.com (password: to16ro12)');
        $this->command->info('Created team: Development Team');
        $this->command->info('Created test websites with SSL monitoring:');
        $this->command->info('- https://omp.office-manager-pro.com (valid, expires in 90 days) [TEAM]');
        $this->command->info('- https://www.redgas.at (valid, expires in 7 days) [TEAM]');
        $this->command->info('- https://www.fairnando.at (invalid/expired) [PERSONAL]');
        $this->command->info('- https://www.gebrauchte.at (valid, expires in 30 days) [PERSONAL]');
        $this->command->info('Created alert configurations: SSL Expiry (7 days) + Website Down');
    }
}