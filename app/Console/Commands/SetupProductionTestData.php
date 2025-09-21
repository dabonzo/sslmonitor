<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Website;
use App\Services\SslCertificateChecker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SetupProductionTestData extends Command
{
    protected $signature = 'ssl:setup-production-data {--force : Force recreation of existing data}';

    protected $description = 'Set up production test data with real SSL monitoring for bonzo@konjscina.com';

    public function handle(): int
    {
        $this->info('Setting up production test data for SSL Monitor v4...');

        // Check if user exists
        $user = User::where('email', 'bonzo@konjscina.com')->first();

        if ($user && !$this->option('force')) {
            $this->info('User bonzo@konjscina.com already exists.');

            if (!$this->confirm('Do you want to continue and add/update websites?')) {
                $this->comment('Operation cancelled.');
                return self::SUCCESS;
            }
        } else {
            // Create or update user
            $user = User::updateOrCreate(
                ['email' => 'bonzo@konjscina.com'],
                [
                    'name' => 'Bonzo',
                    'email' => 'bonzo@konjscina.com',
                    'password' => Hash::make('to16ro12'),
                    'email_verified_at' => now(),
                ]
            );

            $this->info("✅ User created/updated: {$user->email}");
        }

        // Define real-world websites
        $websites = [
            [
                'name' => 'Office Manager Pro',
                'url' => 'https://omp.office-manager-pro.com',
                'description' => 'Office management platform'
            ],
            [
                'name' => 'Redgas Austria',
                'url' => 'https://www.redgas.at',
                'description' => 'Austrian gas services website'
            ]
        ];

        $this->info('Processing websites...');
        $this->newLine();

        foreach ($websites as $websiteData) {
            $this->comment("Processing: {$websiteData['name']} ({$websiteData['url']})");

            // Create or update website
            $website = Website::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'url' => $websiteData['url']
                ],
                [
                    'name' => $websiteData['name'],
                    'ssl_monitoring_enabled' => true,
                    'uptime_monitoring_enabled' => true,
                    'monitoring_config' => [
                        'check_interval' => 3600, // 1 hour
                        'timeout' => 30,
                        'description' => $websiteData['description']
                    ]
                ]
            );

            $this->info("✅ Website: {$website->name}");

            // Perform real SSL check
            $this->comment('   Checking SSL certificate...');

            try {
                $sslChecker = new SslCertificateChecker();
                $sslCheck = $sslChecker->checkAndStoreCertificate($website);

                if ($sslCheck->status === 'valid' || $sslCheck->status === 'expiring') {
                    $this->info("   ✅ SSL Certificate: {$sslCheck->issuer}");
                    $this->info("   ✅ Expires: {$sslCheck->expires_at->format('Y-m-d H:i:s')}");
                    $this->info("   ✅ Days until expiry: {$sslCheck->days_until_expiry}");

                    if ($sslCheck->days_until_expiry < 30) {
                        $this->warn("   ⚠️  Certificate expires soon!");
                    }
                } else {
                    $this->error("   ❌ SSL Check failed: {$sslCheck->error_message}");
                }

                $this->info("   ✅ Response time: {$sslCheck->response_time}ms");
                $this->info("   ✅ SSL data stored in database (Check ID: {$sslCheck->id})");

            } catch (\Exception $e) {
                $this->error("   ❌ SSL check failed: {$e->getMessage()}");
            }

            $this->newLine();
        }

        $this->info('📊 Final Statistics:');
        $this->table(['Metric', 'Count'], [
            ['Total Users', User::count()],
            ['Total Websites', Website::count()],
            ['Bonzo\'s Websites', Website::where('user_id', $user->id)->count()],
            ['SSL Certificates', \App\Models\SslCertificate::count()],
            ['SSL Checks', \App\Models\SslCheck::count()],
        ]);

        $this->newLine();
        $this->info('🎯 Production test data setup complete!');
        $this->comment('You can now log in with:');
        $this->comment('Email: bonzo@konjscina.com');
        $this->comment('Password: to16ro12');

        $this->newLine();
        $this->comment('Run this command again with --force to recreate data.');

        return self::SUCCESS;
    }
}
