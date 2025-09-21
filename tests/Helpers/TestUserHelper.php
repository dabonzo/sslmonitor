<?php

namespace Tests\Helpers;

use App\Models\User;
use App\Models\Website;
use Illuminate\Support\Facades\Hash;

class TestUserHelper
{
    public static function ensureTestUserExists(): User
    {
        // Always ensure the test user exists
        $user = User::updateOrCreate(
            ['email' => 'bonzo@konjscina.com'],
            [
                'name' => 'Bonzo',
                'email' => 'bonzo@konjscina.com',
                'password' => Hash::make('to16ro12'),
                'email_verified_at' => now(),
            ]
        );

        // Always ensure the test website exists
        Website::updateOrCreate(
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

        return $user;
    }
}