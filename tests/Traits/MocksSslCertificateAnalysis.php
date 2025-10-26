<?php

/**
 * SSL Certificate Analysis Mock Trait
 *
 * This trait eliminates real SSL certificate connections during testing,
 * providing 99% performance improvement (30s+ → 0.20s per test).
 *
 * @see docs/TESTING_INSIGHTS.md for usage patterns
 */

namespace Tests\Traits;

use App\Services\SslCertificateAnalysisService;

trait MocksSslCertificateAnalysis
{
    /**
     * Mock SSL Certificate Analysis Service to avoid real network calls
     *
     * This method replaces the SslCertificateAnalysisService with a mock
     * that returns realistic SSL certificate data without making real
     * network connections.
     */
    protected function mockSslCertificateAnalysis(): void
    {
        test()->mock(SslCertificateAnalysisService::class, function ($mock) {
            $mock->shouldReceive('analyzeCertificate')
                ->andReturnUsing(function ($domain) {
                    return $this->getMockSslAnalysis($domain);
                });

            $mock->shouldReceive('analyzeWebsite')
                ->andReturnUsing(function ($website) {
                    return [
                        'status' => 'valid',
                        'expires_at' => now()->addDays(90),
                        'issuer' => 'Mock CA',
                        'response_time' => 150,
                    ];
                });

            $mock->shouldReceive('analyzeAndSave')
                ->zeroOrMoreTimes()
                ->andReturnUsing(function ($website) {
                    $domain = parse_url($website->url, PHP_URL_HOST) ?? $website->url;
                    $analysis = $this->getMockSslAnalysis($domain);

                    // Save the certificate data to website (simulate real behavior)
                    $certificateData = [
                        // Basic Info
                        'subject' => $analysis['basic_info']['domain'] ?? $domain,
                        'issuer' => $analysis['certificate_authority']['ca_name'] ?? 'Mock CA',
                        'serial_number' => '01:23:45:67:89:AB:CD:EF',
                        'signature_algorithm' => $analysis['security']['signature_algorithm'] ?? 'SHA256withRSA',

                        // Validity
                        'valid_from' => $analysis['validity']['issued_at'] ?? now()->subDays(60)->toIso8601String(),
                        'valid_until' => $analysis['validity']['expires_at'] ?? now()->addDays(90)->toIso8601String(),
                        'days_remaining' => $analysis['validity']['days_until_expiry'] ?? 90,
                        'is_expired' => $analysis['validity']['is_expired'] ?? false,
                        'expires_soon' => $analysis['validity']['expires_soon'] ?? false,

                        // Security
                        'key_algorithm' => $analysis['security']['key_algorithm'] ?? 'RSA',
                        'key_size' => $analysis['security']['key_size'] ?? 2048,
                        'security_score' => $analysis['security']['security_score'] ?? 95,
                        'risk_level' => $analysis['risk_assessment']['risk_level'] ?? 'low',

                        // Domains
                        'primary_domain' => $analysis['domains']['common_name'] ?? $domain,
                        'subject_alt_names' => $analysis['domains']['san_domains'] ?? [$domain, 'www.'.$domain],
                        'covers_www' => true,
                        'is_wildcard' => $analysis['domains']['wildcard_supported'] ?? false,

                        // Chain
                        'chain_length' => $analysis['chain_info']['chain_length'] ?? 3,
                        'chain_complete' => true,
                        'intermediate_issuers' => [],

                        // Metadata
                        'status' => 'success',
                        'analyzed_at' => now()->toIso8601String(),
                    ];

                    $website->latest_ssl_certificate = $certificateData;
                    $website->ssl_certificate_analyzed_at = now();
                    $website->save();
                    $website->refresh();

                    return $analysis;
                });
        });
    }

    /**
     * Get mock SSL analysis data
     */
    private function getMockSslAnalysis(string $domain): array
    {
        $now = now();

        return [
            'basic_info' => [
                'domain' => $domain,
                'is_valid' => true,
                'checked_at' => $now->toISOString(),
                'response_time' => 0.05,
            ],
            'validity' => [
                'issued_at' => $now->subDays(60)->toISOString(),
                'expires_at' => $now->addDays(90)->toISOString(),
                'days_until_expiry' => 90,
                'is_expired' => false,
                'expires_soon' => false,
            ],
            'domains' => [
                'common_name' => $domain,
                'san_domains' => [
                    $domain,
                    'www.'.$domain,
                ],
                'wildcard_supported' => false,
            ],
            'security' => [
                'key_algorithm' => 'RSA',
                'key_size' => 2048,
                'signature_algorithm' => 'SHA256withRSA',
                'weak_signature' => false,
                'weak_key' => false,
                'security_score' => 95,
            ],
            'certificate_authority' => [
                'is_lets_encrypt' => str_contains($domain, 'example') ? true : false,
                'ca_name' => 'Mock Certificate Authority',
                'ca_organization' => 'Mock CA Organization',
                'ca_country' => 'US',
            ],
            'chain_info' => [
                'chain_length' => 3,
                'root_ca' => 'Mock Root CA',
                'intermediate_ca' => 'Mock Intermediate CA',
            ],
            'risk_assessment' => [
                'risk_level' => 'low',
                'warnings' => [],
                'recommendations' => [],
            ],
        ];
    }

    /**
     * Setup SSL mock in test setUp
     */
    protected function setUpMocksSslCertificateAnalysis(): void
    {
        $this->mockSslCertificateAnalysis();
    }
}
