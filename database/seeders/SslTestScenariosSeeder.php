<?php

namespace Database\Seeders;

use App\Models\SslCertificate;
use App\Models\SslCheck;
use App\Models\User;
use App\Models\Website;
use Illuminate\Database\Seeder;

class SslTestScenariosSeeder extends Seeder
{
    /**
     * Seed SSL test scenarios for comprehensive testing.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'SSL Test User',
            'email' => 'ssl-test@example.com',
        ]);

        // Scenario 1: Certificate expiring in different timeframes
        $this->createExpiringCertificateScenarios($user);

        // Scenario 2: Different certificate authorities
        $this->createCertificateAuthorityScenarios($user);

        // Scenario 3: Security strength scenarios
        $this->createSecurityScenarios($user);

        // Scenario 4: Error and edge cases
        $this->createErrorScenarios($user);

        // Scenario 5: Plugin integration scenarios
        $this->createPluginScenarios($user);

        $this->command->info('SSL test scenarios seeded successfully!');
    }

    protected function createExpiringCertificateScenarios(User $user): void
    {
        // Expired certificate (30 days ago)
        $expiredSite = Website::factory()->create([
            'user_id' => $user->id,
            'name' => 'Expired Certificate Site',
            'url' => 'https://expired.ssl-test.local',
        ]);

        SslCertificate::factory()->expired()->create([
            'website_id' => $expiredSite->id,
        ]);

        SslCheck::factory()->expired()->create([
            'website_id' => $expiredSite->id,
            'checked_at' => now(),
        ]);

        // Expiring soon (7 days)
        $expiringSoonSite = Website::factory()->create([
            'user_id' => $user->id,
            'name' => 'Expiring Soon Site',
            'url' => 'https://expiring-soon.ssl-test.local',
        ]);

        SslCertificate::factory()->expiringSoon()->create([
            'website_id' => $expiringSoonSite->id,
        ]);

        SslCheck::factory()->expiringSoon()->create([
            'website_id' => $expiringSoonSite->id,
            'checked_at' => now(),
        ]);

        // Valid long-term certificate
        $validSite = Website::factory()->create([
            'user_id' => $user->id,
            'name' => 'Valid Long-term Site',
            'url' => 'https://valid.ssl-test.local',
        ]);

        SslCertificate::factory()->commercial()->create([
            'website_id' => $validSite->id,
        ]);

        SslCheck::factory()->valid()->create([
            'website_id' => $validSite->id,
            'checked_at' => now(),
        ]);
    }

    protected function createCertificateAuthorityScenarios(User $user): void
    {
        // Let's Encrypt certificate
        $letsEncryptSite = Website::factory()->create([
            'user_id' => $user->id,
            'name' => 'Let\'s Encrypt Site',
            'url' => 'https://letsencrypt.ssl-test.local',
        ]);

        SslCertificate::factory()->letsEncrypt()->create([
            'website_id' => $letsEncryptSite->id,
        ]);

        // Commercial certificate (DigiCert)
        $commercialSite = Website::factory()->create([
            'user_id' => $user->id,
            'name' => 'Commercial Certificate Site',
            'url' => 'https://commercial.ssl-test.local',
        ]);

        SslCertificate::factory()->commercial()->create([
            'website_id' => $commercialSite->id,
        ]);

        // Self-signed certificate
        $selfSignedSite = Website::factory()->create([
            'user_id' => $user->id,
            'name' => 'Self-signed Certificate Site',
            'url' => 'https://selfsigned.ssl-test.local',
        ]);

        SslCertificate::factory()->selfSigned()->create([
            'website_id' => $selfSignedSite->id,
        ]);

        SslCheck::factory()->invalid()->create([
            'website_id' => $selfSignedSite->id,
            'checked_at' => now(),
            'error_message' => 'Self-signed certificate',
        ]);
    }

    protected function createSecurityScenarios(User $user): void
    {
        // Weak security certificate
        $weakSecuritySite = Website::factory()->create([
            'user_id' => $user->id,
            'name' => 'Weak Security Site',
            'url' => 'https://weak.ssl-test.local',
        ]);

        SslCertificate::factory()->weakSecurity()->create([
            'website_id' => $weakSecuritySite->id,
        ]);

        SslCheck::factory()->weakSecurity()->create([
            'website_id' => $weakSecuritySite->id,
            'checked_at' => now(),
        ]);

        // Strong security certificate
        $strongSecuritySite = Website::factory()->create([
            'user_id' => $user->id,
            'name' => 'Strong Security Site',
            'url' => 'https://strong.ssl-test.local',
        ]);

        SslCertificate::factory()->strongSecurity()->create([
            'website_id' => $strongSecuritySite->id,
        ]);

        SslCheck::factory()->strongSecurity()->create([
            'website_id' => $strongSecuritySite->id,
            'checked_at' => now(),
        ]);
    }

    protected function createErrorScenarios(User $user): void
    {
        // Connection timeout
        $timeoutSite = Website::factory()->create([
            'user_id' => $user->id,
            'name' => 'Timeout Error Site',
            'url' => 'https://timeout.ssl-test.local',
        ]);

        SslCheck::factory()->error()->create([
            'website_id' => $timeoutSite->id,
            'checked_at' => now(),
            'error_message' => 'Connection timeout',
        ]);

        // DNS resolution failed
        $dnsSite = Website::factory()->create([
            'user_id' => $user->id,
            'name' => 'DNS Error Site',
            'url' => 'https://nonexistent.ssl-test.local',
        ]);

        SslCheck::factory()->error()->create([
            'website_id' => $dnsSite->id,
            'checked_at' => now(),
            'error_message' => 'DNS resolution failed',
        ]);

        // SSL handshake failed
        $handshakeSite = Website::factory()->create([
            'user_id' => $user->id,
            'name' => 'Handshake Error Site',
            'url' => 'https://handshake-error.ssl-test.local',
        ]);

        SslCheck::factory()->error()->create([
            'website_id' => $handshakeSite->id,
            'checked_at' => now(),
            'error_message' => 'SSL handshake failed',
        ]);
    }

    protected function createPluginScenarios(User $user): void
    {
        // Website with plugin analysis data
        $pluginSite = Website::factory()->withPluginData([
            'system_metrics_agent' => [
                'server_location' => 'us-east-1',
                'server_type' => 'web',
                'last_scan' => now()->toISOString(),
            ],
            'ssl_scanner_plugin' => [
                'vulnerability_scan' => 'passed',
                'compliance_check' => 'pci_dss_compliant',
                'last_updated' => now()->subHours(2)->toISOString(),
            ],
        ])->create([
            'user_id' => $user->id,
            'name' => 'Plugin Enhanced Site',
            'url' => 'https://plugin-enhanced.ssl-test.local',
        ]);

        // Certificate with plugin analysis
        SslCertificate::factory()->withPluginAnalysis([
            'ssl_labs_grade' => 'A+',
            'security_headers' => [
                'hsts' => true,
                'hpkp' => false,
                'csp' => true,
            ],
            'vulnerabilities' => [],
            'compliance' => [
                'pci_dss' => true,
                'hipaa' => false,
                'sox' => true,
            ],
        ])->create([
            'website_id' => $pluginSite->id,
        ]);

        // SSL check with plugin metrics
        SslCheck::factory()->withPluginMetrics([
            'performance' => [
                'handshake_time' => 0.15,
                'certificate_validation_time' => 0.05,
                'total_connection_time' => 0.89,
            ],
            'security_analysis' => [
                'cipher_strength' => 'strong',
                'protocol_security' => 'excellent',
                'certificate_transparency' => true,
            ],
            'compliance_status' => [
                'pci_dss' => 'compliant',
                'gdpr' => 'compliant',
                'sox' => 'compliant',
            ],
        ])->create([
            'website_id' => $pluginSite->id,
            'checked_at' => now(),
        ]);
    }
}