<?php

namespace Database\Factories;

use App\Models\Website;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SslCertificate>
 */
class SslCertificateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $expiresAt = Carbon::now()->addDays(fake()->numberBetween(30, 365));

        return [
            'website_id' => Website::factory(),
            'issuer' => fake()->randomElement([
                'Let\'s Encrypt Authority X3',
                'DigiCert SHA2 Secure Server CA',
                'Comodo RSA Domain Validation Secure Server CA',
                'GeoTrust RSA CA 2018',
                'GlobalSign RSA OV SSL CA 2018',
                'Amazon RSA 2048 M01',
            ]),
            'subject' => fake()->domainName(),
            'expires_at' => $expiresAt,
            'serial_number' => strtoupper(fake()->bothify('??##??##??##??##??##??##')),
            'signature_algorithm' => fake()->randomElement([
                'SHA256withRSA',
                'SHA384withRSA',
                'SHA1withRSA',
                'ECDSA-SHA256',
                'ECDSA-SHA384',
            ]),
            'certificate_hash' => fake()->sha256(),
            'is_valid' => true,
            'status' => 'valid',
            'certificate_chain' => [
                'certificate' => '-----BEGIN CERTIFICATE-----'."\n".fake()->text(1000)."\n".'-----END CERTIFICATE-----',
                'intermediate' => '-----BEGIN CERTIFICATE-----'."\n".fake()->text(800)."\n".'-----END CERTIFICATE-----',
                'root' => '-----BEGIN CERTIFICATE-----'."\n".fake()->text(600)."\n".'-----END CERTIFICATE-----',
            ],
            'security_metrics' => [
                'key_size' => fake()->randomElement([2048, 3072, 4096]),
                'cipher_suites' => fake()->randomElements([
                    'TLS_AES_256_GCM_SHA384',
                    'TLS_CHACHA20_POLY1305_SHA256',
                    'TLS_AES_128_GCM_SHA256',
                    'ECDHE-RSA-AES256-GCM-SHA384',
                    'ECDHE-RSA-CHACHA20-POLY1305',
                ], fake()->numberBetween(2, 4)),
                'protocol_versions' => ['TLSv1.2', 'TLSv1.3'],
                'ocsp_stapling' => fake()->boolean(80),
                'sct_support' => fake()->boolean(70),
            ],
            'plugin_analysis' => [],
        ];
    }

    public function expired(): static
    {
        $expiresAt = Carbon::now()->subDays(fake()->numberBetween(1, 90));

        return $this->state([
            'expires_at' => $expiresAt,
            'is_valid' => true, // Keep valid=true so getStatus() can check expiry
            'status' => 'expired',
        ]);
    }

    public function expiringSoon(): static
    {
        $expiresAt = Carbon::now()->addDays(fake()->numberBetween(1, 14));

        return $this->state([
            'expires_at' => $expiresAt,
            'is_valid' => true,
            'status' => 'expiring_soon',
        ]);
    }

    public function invalid(): static
    {
        return $this->state([
            'is_valid' => false,
            'status' => 'invalid',
        ]);
    }

    public function selfSigned(): static
    {
        $domain = fake()->domainName();

        return $this->state([
            'subject' => $domain,
            'issuer' => $domain, // Self-signed: issuer = subject
            'is_valid' => false,
            'status' => 'invalid',
        ]);
    }

    public function letsEncrypt(): static
    {
        return $this->state([
            'issuer' => 'Let\'s Encrypt Authority X3',
            'expires_at' => Carbon::now()->addDays(90),
            'status' => 'valid',
        ]);
    }

    public function commercial(): static
    {
        return $this->state([
            'issuer' => fake()->randomElement([
                'DigiCert SHA2 Secure Server CA',
                'Comodo RSA Domain Validation Secure Server CA',
                'GeoTrust RSA CA 2018',
            ]),
            'expires_at' => Carbon::now()->addDays(365),
            'status' => 'valid',
        ]);
    }

    public function withPluginAnalysis(array $analysis): static
    {
        return $this->state([
            'plugin_analysis' => $analysis,
        ]);
    }

    public function weakSecurity(): static
    {
        return $this->state([
            'signature_algorithm' => 'SHA1withRSA',
            'security_metrics' => [
                'key_size' => 1024,
                'cipher_suites' => ['TLS_RSA_WITH_AES_128_CBC_SHA'],
                'protocol_versions' => ['TLSv1.0'],
                'ocsp_stapling' => false,
                'sct_support' => false,
            ],
        ]);
    }

    public function strongSecurity(): static
    {
        return $this->state([
            'signature_algorithm' => 'ECDSA-SHA256',
            'security_metrics' => [
                'key_size' => 4096,
                'cipher_suites' => [
                    'TLS_AES_256_GCM_SHA384',
                    'TLS_CHACHA20_POLY1305_SHA256',
                    'ECDHE-RSA-AES256-GCM-SHA384',
                ],
                'protocol_versions' => ['TLSv1.2', 'TLSv1.3'],
                'ocsp_stapling' => true,
                'sct_support' => true,
            ],
        ]);
    }
}
