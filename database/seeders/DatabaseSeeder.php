<?php

namespace Database\Seeders;

use App\Models\PluginConfiguration;
use App\Models\SslCertificate;
use App\Models\SslCheck;
use App\Models\User;
use App\Models\Website;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test users
        $testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $adminUser = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@sslmonitor.local',
        ]);

        // Create additional users for testing
        $regularUsers = User::factory(8)->create();

        // Collect all users
        $allUsers = collect([$testUser, $adminUser])->merge($regularUsers);

        // Create websites for users
        $websites = collect();

        // Test user gets specific demo websites
        $testWebsites = Website::factory(5)->create([
            'user_id' => $testUser->id,
        ]);

        // Create some specific demo websites with known patterns
        $demoWebsites = collect([
            Website::factory()->withSslOnly()->create([
                'user_id' => $testUser->id,
                'name' => 'Production Website',
                'url' => 'https://example.com',
            ]),
            Website::factory()->withBothMonitoring()->create([
                'user_id' => $testUser->id,
                'name' => 'E-commerce Store',
                'url' => 'https://shop.example.com',
            ]),
            Website::factory()->withUptimeOnly()->create([
                'user_id' => $testUser->id,
                'name' => 'API Service',
                'url' => 'https://api.example.com',
            ]),
        ]);

        $websites = $websites->merge($testWebsites)->merge($demoWebsites);

        // Admin user gets websites with different monitoring configs
        $adminWebsites = Website::factory(3)->create([
            'user_id' => $adminUser->id,
        ]);
        $websites = $websites->merge($adminWebsites);

        // Regular users get random websites
        $regularUsers->each(function ($user) use (&$websites) {
            $userWebsites = Website::factory(rand(1, 4))->create([
                'user_id' => $user->id,
            ]);
            $websites = $websites->merge($userWebsites);
        });

        // Create SSL certificates for websites
        $websites->each(function ($website) {
            // Each website gets 1-3 historical SSL certificates
            SslCertificate::factory(rand(1, 3))->create([
                'website_id' => $website->id,
            ]);

            // Some websites get expired certificates for testing
            if (rand(1, 10) <= 3) {
                SslCertificate::factory()->expired()->create([
                    'website_id' => $website->id,
                ]);
            }

            // Some websites get certificates expiring soon
            if (rand(1, 10) <= 4) {
                SslCertificate::factory()->expiringSoon()->create([
                    'website_id' => $website->id,
                ]);
            }
        });

        // Create SSL checks for websites (recent check history)
        $websites->each(function ($website) {
            // Create recent check history (last 7 days)
            for ($i = 0; $i < 7; $i++) {
                $checkDate = now()->subDays($i);

                // Most checks are valid
                if (rand(1, 10) <= 8) {
                    SslCheck::factory()->valid()->create([
                        'website_id' => $website->id,
                        'checked_at' => $checkDate,
                    ]);
                } else {
                    // Some checks have issues
                    $factory = match (rand(1, 4)) {
                        1 => SslCheck::factory()->expired(),
                        2 => SslCheck::factory()->expiringSoon(),
                        3 => SslCheck::factory()->invalid(),
                        4 => SslCheck::factory()->error(),
                    };

                    $factory->create([
                        'website_id' => $website->id,
                        'checked_at' => $checkDate,
                    ]);
                }
            }

            // Add some manual checks
            if (rand(1, 10) <= 3) {
                SslCheck::factory()->manual()->valid()->create([
                    'website_id' => $website->id,
                    'checked_at' => now()->subHours(rand(1, 12)),
                ]);
            }
        });

        // Create plugin configurations for testing v1.1.0 architecture

        // Test user gets comprehensive plugin setup
        PluginConfiguration::factory()->agent()->active()->create([
            'user_id' => $testUser->id,
            'plugin_name' => 'system_metrics_agent',
        ]);

        PluginConfiguration::factory()->webhook()->active()->create([
            'user_id' => $testUser->id,
            'plugin_name' => 'slack_notifications',
        ]);

        PluginConfiguration::factory()->externalService()->active()->create([
            'user_id' => $testUser->id,
            'plugin_name' => 'grafana_metrics',
        ]);

        // Admin user gets advanced plugins
        PluginConfiguration::factory()->agent()->active()->create([
            'user_id' => $adminUser->id,
            'plugin_name' => 'ssl_certificate_scanner',
        ]);

        PluginConfiguration::factory()->webhook()->error()->create([
            'user_id' => $adminUser->id,
            'plugin_name' => 'discord_alerts',
        ]);

        // Some regular users get basic plugins
        $regularUsers->take(4)->each(function ($user) {
            // Each gets 1-2 random plugins
            PluginConfiguration::factory(rand(1, 2))->create([
                'user_id' => $user->id,
            ]);
        });

        // Create some pending and inactive plugins for testing
        PluginConfiguration::factory()->pending()->create([
            'user_id' => $testUser->id,
            'plugin_name' => 'new_relic_monitoring',
        ]);

        PluginConfiguration::factory()->inactive()->create([
            'user_id' => $adminUser->id,
            'plugin_name' => 'datadog_integration',
        ]);

        $this->command->info('Database seeded successfully!');
        $this->command->info("Created {$allUsers->count()} users");
        $this->command->info("Created {$websites->count()} websites");
        $this->command->info("Created " . SslCertificate::count() . " SSL certificates");
        $this->command->info("Created " . SslCheck::count() . " SSL checks");
        $this->command->info("Created " . PluginConfiguration::count() . " plugin configurations");
    }
}
