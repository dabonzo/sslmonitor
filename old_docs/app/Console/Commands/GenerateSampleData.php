<?php

namespace App\Console\Commands;

use App\Models\SslCheck;
use App\Models\User;
use App\Models\Website;
use Illuminate\Console\Command;

class GenerateSampleData extends Command
{
    protected $signature = 'sample:generate {--user-email=demo@example.com}';

    protected $description = 'Generate sample SSL monitoring data for testing the dashboard';

    public function handle(): int
    {
        $userEmail = $this->option('user-email');

        // Find or create demo user
        $user = User::where('email', $userEmail)->first();

        if (! $user) {
            $this->error("User with email {$userEmail} not found.");
            $this->info('Please register a user account first, or specify an existing user email with --user-email=your@email.com');

            return Command::FAILURE;
        }

        $this->info("Generating sample data for user: {$user->email}");

        // Clear existing data for this user
        $user->websites()->delete();

        // Create sample websites with different SSL statuses
        $sampleWebsites = [
            [
                'name' => 'My Main Website',
                'url' => 'https://example.com',
                'status' => 'valid',
                'days_until_expiry' => 45,
            ],
            [
                'name' => 'E-commerce Store',
                'url' => 'https://shop.example.com',
                'status' => 'expiring_soon',
                'days_until_expiry' => 7,
            ],
            [
                'name' => 'Blog Platform',
                'url' => 'https://blog.example.org',
                'status' => 'expired',
                'days_until_expiry' => -5,
            ],
            [
                'name' => 'API Service',
                'url' => 'https://api.myservice.com',
                'status' => 'error',
                'error_message' => 'Connection timeout - unable to connect to server',
            ],
            [
                'name' => 'Development Site',
                'url' => 'https://dev.myproject.io',
                'status' => 'valid',
                'days_until_expiry' => 89,
            ],
            [
                'name' => 'Landing Page',
                'url' => 'https://landing.example.net',
                'status' => 'expiring_soon',
                'days_until_expiry' => 12,
            ],
        ];

        $this->withProgressBar($sampleWebsites, function ($sampleWebsite) use ($user) {
            // Create website
            $website = $user->websites()->create([
                'name' => $sampleWebsite['name'],
                'url' => $sampleWebsite['url'],
            ]);

            // Create SSL check with the specified status
            $checkData = [
                'website_id' => $website->id,
                'status' => $sampleWebsite['status'],
                'checked_at' => now()->subMinutes(rand(5, 120)),
            ];

            if ($sampleWebsite['status'] !== 'error') {
                $expiresAt = now()->addDays($sampleWebsite['days_until_expiry']);
                $checkData = array_merge($checkData, [
                    'expires_at' => $expiresAt,
                    'days_until_expiry' => $sampleWebsite['days_until_expiry'],
                    'issuer' => 'Let\'s Encrypt Authority X3',
                    'subject' => 'CN='.parse_url($sampleWebsite['url'], PHP_URL_HOST),
                    'is_valid' => $sampleWebsite['status'] === 'valid',
                ]);
            } else {
                $checkData['error_message'] = $sampleWebsite['error_message'];
            }

            SslCheck::factory()->create($checkData);
        });

        $this->newLine();
        $this->info('✅ Sample data generated successfully!');
        $this->info("Created {$user->websites()->count()} websites with SSL checks");

        $this->newLine();
        $this->info('🌐 Visit your dashboard at: http://localhost/dashboard');
        $this->info('📊 You should see:');
        $this->info('   • 2 Valid certificates');
        $this->info('   • 2 Expiring Soon certificates');
        $this->info('   • 1 Expired certificate');
        $this->info('   • 1 Error certificate');

        return Command::SUCCESS;
    }
}
