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
        return [
            'website_id' => Website::factory(),
            'issuer' => fake()->randomElement(['Let\'s Encrypt', 'DigiCert', 'Comodo', 'GeoTrust']),
            'expires_at' => Carbon::now()->addDays(90),
            'subject' => fake()->domainName(),
            'serial_number' => strtoupper(fake()->bothify('??##??##??##??##')),
            'signature_algorithm' => fake()->randomElement(['SHA256withRSA', 'SHA1withRSA', 'SHA384withRSA']),
            'is_valid' => true,
        ];
    }

    public function expired(): static
    {
        return $this->state([
            'expires_at' => Carbon::now()->subDays(rand(1, 30)),
            'is_valid' => false,
        ]);
    }

    public function expiringSoon(): static
    {
        return $this->state([
            'expires_at' => Carbon::now()->addDays(rand(1, 14)),
            'is_valid' => true,
        ]);
    }

    public function invalid(): static
    {
        return $this->state([
            'is_valid' => false,
        ]);
    }
}
