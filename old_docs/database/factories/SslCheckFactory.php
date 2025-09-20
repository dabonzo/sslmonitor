<?php

namespace Database\Factories;

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
        $expiresAt = fake()->dateTimeBetween('now', '+120 days');
        $daysUntilExpiry = now()->diffInDays($expiresAt, false);

        return [
            'website_id' => \App\Models\Website::factory(),
            'status' => 'valid',
            'checked_at' => now(),
            'expires_at' => $expiresAt,
            'issuer' => fake()->randomElement(['Let\'s Encrypt', 'DigiCert', 'Comodo', 'GeoTrust']),
            'subject' => fake()->domainName(),
            'serial_number' => strtoupper(fake()->bothify('??##??##??##??##')),
            'signature_algorithm' => fake()->randomElement(['SHA256withRSA', 'SHA1withRSA', 'SHA384withRSA']),
            'is_valid' => true,
            'days_until_expiry' => (int) $daysUntilExpiry,
            'error_message' => null,
        ];
    }

    public function valid(): static
    {
        $expiresAt = fake()->dateTimeBetween('+30 days', '+120 days');
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
        $expiresAt = fake()->dateTimeBetween('-30 days', '-1 day');
        $daysUntilExpiry = now()->diffInDays($expiresAt, false);

        return $this->state([
            'status' => 'expired',
            'expires_at' => $expiresAt,
            'is_valid' => false,
            'days_until_expiry' => (int) $daysUntilExpiry,
            'error_message' => null,
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
        $expiresAt = fake()->dateTimeBetween('+30 days', '+120 days');
        $daysUntilExpiry = now()->diffInDays($expiresAt, false);

        return $this->state([
            'status' => 'invalid',
            'expires_at' => $expiresAt,
            'is_valid' => false,
            'days_until_expiry' => (int) $daysUntilExpiry,
            'error_message' => null,
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
                'Certificate chain incomplete',
                'Network error',
            ]),
        ]);
    }
}
