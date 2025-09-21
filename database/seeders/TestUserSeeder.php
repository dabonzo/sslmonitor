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

        // Create or update the test website - real SSL data will be collected by the monitoring system
        $website = Website::updateOrCreate(
            [
                'user_id' => $user->id,
                'url' => 'https://omp.office-manager-pro.com'
            ],
            [
                'name' => 'Office Manager Pro',
                'url' => 'https://omp.office-manager-pro.com',
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

        $this->command->info('Created test user: bonzo@konjscina.com (password: to16ro12)');
        $this->command->info('Created test website: https://omp.office-manager-pro.com');
        $this->command->info('Real SSL monitoring will collect actual certificate data');
    }
}