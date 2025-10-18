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
        $this->mock(SslCertificateAnalysisService::class, function ($mock) {
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
