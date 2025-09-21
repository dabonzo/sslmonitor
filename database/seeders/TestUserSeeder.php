<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Website;
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

        // Create test websites with SSL monitoring
        $websites = [
            [
                'name' => 'Office Manager Pro',
                'url' => 'https://omp.office-manager-pro.com',
                'ssl_status' => 'valid',
                'cert_expiry_days' => 90,
            ],
            [
                'name' => 'RedGas Austria',
                'url' => 'https://www.redgas.at',
                'ssl_status' => 'valid',
                'cert_expiry_days' => 7, // Expiring soon
            ],
            [
                'name' => 'Fairnando',
                'url' => 'https://www.fairnando.at',
                'ssl_status' => 'invalid',
                'cert_expiry_days' => -1, // Expired
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
                ]
            );
        }

        $this->command->info('Created test user: bonzo@konjscina.com (password: to16ro12)');
        $this->command->info('Created test websites with SSL monitoring:');
        $this->command->info('- https://omp.office-manager-pro.com (valid, expires in 90 days)');
        $this->command->info('- https://www.redgas.at (valid, expires in 7 days)');
        $this->command->info('- https://www.fairnando.at (invalid/expired)');
    }
}