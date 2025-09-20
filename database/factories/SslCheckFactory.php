<?php

namespace Database\Factories;

use App\Models\Website;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SslCheck>
 */
class SslCheckFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $expiresAt = fake()->dateTimeBetween('now', '+365 days');
        $daysUntilExpiry = now()->diffInDays($expiresAt, false);

        return [
            'website_id' => Website::factory(),
            'status' => 'valid',
            'checked_at' => now(),
            'expires_at' => $expiresAt,
            'issuer' => fake()->randomElement([
                'Let\'s Encrypt Authority X3',
                'DigiCert SHA2 Secure Server CA',
                'Comodo RSA Domain Validation Secure Server CA',
                'GeoTrust RSA CA 2018',
                'GlobalSign RSA OV SSL CA 2018',
            ]),
            'subject' => fake()->domainName(),
            'serial_number' => strtoupper(fake()->bothify('??##??##??##??##??##??##')),
            'signature_algorithm' => fake()->randomElement([
                'SHA256withRSA',
                'SHA384withRSA',
                'ECDSA-SHA256',
                'ECDSA-SHA384',
            ]),
            'is_valid' => true,
            'days_until_expiry' => (int) $daysUntilExpiry,
            'error_message' => null,
            'response_time' => fake()->randomFloat(3, 0.1, 2.0),
            'check_source' => 'scheduled',
            'certificate_chain_length' => fake()->numberBetween(2, 4),
            'protocol_version' => fake()->randomElement(['TLSv1.2', 'TLSv1.3']),
            'cipher_suite' => fake()->randomElement([
                'TLS_AES_256_GCM_SHA384',
                'TLS_CHACHA20_POLY1305_SHA256',
                'ECDHE-RSA-AES256-GCM-SHA384',
                'ECDHE-RSA-CHACHA20-POLY1305',
            ]),
            'key_size' => fake()->randomElement([2048, 3072, 4096]),
            'ocsp_status' => fake()->randomElement(['good', 'revoked', 'unknown', null]),
            'plugin_metrics' => [],
        ];
    }

    public function valid(): static
    {
        $expiresAt = fake()->dateTimeBetween('+30 days', '+365 days');
        $daysUntilExpiry = now()->diffInDays($expiresAt, false);

        return $this->state([
            'status' => 'valid',
            'expires_at' => $expiresAt,
            'is_valid' => true,
            'days_until_expiry' => (int) $daysUntilExpiry,
            'error_message' => null,
        ]);
    }

    public function expired(): static
    {
        $expiresAt = fake()->dateTimeBetween('-90 days', '-1 day');
        $daysUntilExpiry = now()->diffInDays($expiresAt, false);

        return $this->state([
            'status' => 'expired',
            'expires_at' => $expiresAt,
            'is_valid' => false,
            'days_until_expiry' => (int) $daysUntilExpiry,
            'error_message' => 'SSL certificate has expired',
        ]);
    }

    public function expiringSoon(): static
    {
        $expiresAt = fake()->dateTimeBetween('+1 day', '+14 days');
        $daysUntilExpiry = now()->diffInDays($expiresAt, false);

        return $this->state([
            'status' => 'expiring_soon',
            'expires_at' => $expiresAt,
            'is_valid' => true,
            'days_until_expiry' => (int) $daysUntilExpiry,
            'error_message' => null,
        ]);
    }

    public function invalid(): static
    {
        $expiresAt = fake()->dateTimeBetween('+30 days', '+365 days');
        $daysUntilExpiry = now()->diffInDays($expiresAt, false);

        return $this->state([
            'status' => 'invalid',
            'expires_at' => $expiresAt,
            'is_valid' => false,
            'days_until_expiry' => (int) $daysUntilExpiry,
            'error_message' => fake()->randomElement([
                'Self-signed certificate',
                'Certificate chain incomplete',
                'Hostname mismatch',
                'Certificate revoked',
                'Weak signature algorithm',
            ]),
        ]);
    }

    public function error(): static
    {
        return $this->state([
            'status' => 'error',
            'expires_at' => null,
            'issuer' => null,
            'subject' => null,
            'serial_number' => null,
            'signature_algorithm' => null,
            'is_valid' => false,
            'days_until_expiry' => null,
            'error_message' => fake()->randomElement([
                'Connection timeout',
                'DNS resolution failed',
                'SSL handshake failed',
                'Network unreachable',
                'Certificate chain incomplete',
                'Connection refused',
                'SSL protocol error',
            ]),
            'response_time' => null,
            'certificate_chain_length' => null,
            'protocol_version' => null,
            'cipher_suite' => null,
            'key_size' => null,
            'ocsp_status' => null,
        ]);
    }

    public function manual(): static
    {
        return $this->state([
            'check_source' => 'manual',
        ]);
    }

    public function api(): static
    {
        return $this->state([
            'check_source' => 'api',
        ]);
    }

    public function webhook(): static
    {
        return $this->state([
            'check_source' => 'webhook',
        ]);
    }

    public function slowResponse(): static
    {
        return $this->state([
            'response_time' => fake()->randomFloat(3, 3.0, 10.0),
        ]);
    }

    public function fastResponse(): static
    {
        return $this->state([
            'response_time' => fake()->randomFloat(3, 0.1, 0.5),
        ]);
    }

    public function withPluginMetrics(array $metrics): static
    {
        return $this->state([
            'plugin_metrics' => $metrics,
        ]);
    }

    public function weakSecurity(): static
    {
        return $this->state([
            'signature_algorithm' => 'SHA1withRSA',
            'protocol_version' => 'TLSv1.0',
            'cipher_suite' => 'TLS_RSA_WITH_AES_128_CBC_SHA',
            'key_size' => 1024,
        ]);
    }

    public function strongSecurity(): static
    {
        return $this->state([
            'signature_algorithm' => 'ECDSA-SHA256',
            'protocol_version' => 'TLSv1.3',
            'cipher_suite' => 'TLS_AES_256_GCM_SHA384',
            'key_size' => 4096,
            'ocsp_status' => 'good',
        ]);
    }
}