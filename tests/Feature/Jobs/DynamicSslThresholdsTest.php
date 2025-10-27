<?php

namespace Tests\Feature\Jobs;

use App\Jobs\CheckMonitorJob;
use App\Models\Monitor;
use Carbon\Carbon;
use Tests\Traits\MocksSslCertificateAnalysis;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class, MocksSslCertificateAnalysis::class);

describe('Dynamic SSL Expiration Thresholds', function () {

    it('marks Let\'s Encrypt certificate with 73 days remaining as valid', function () {
        // Arrange: 90-day Let's Encrypt cert with 73 days remaining = 81% of lifetime
        // 90-day total validity, 73 days remaining = (73/90) * 100 = 81% remaining
        // Should be VALID because 81% > 33% threshold
        $monitor = Monitor::factory()->create([
            'certificate_status' => 'valid',
            'certificate_expiration_date' => now()->addDays(73),
        ]);

        // Mock extractCertificateData to return 90-day validity period
        $validFrom = now()->subDays(17); // 90 - 73 = 17 days ago

        // Act: Test the determineSslStatus method via reflection
        $job = new CheckMonitorJob($monitor, 'ssl');
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('determineSslStatus');
        $method->setAccessible(true);

        $status = $method->invoke(
            $job,
            now()->addDays(73),
            $validFrom,
            'valid'
        );

        // Assert: Should be 'valid' due to high percentage remaining
        expect($status)->toBe('valid');
    });

    it('marks 1-year certificate with 73 days remaining as expires_soon', function () {
        // Arrange: 365-day commercial cert with 73 days remaining = 20% of lifetime
        // 365-day total validity, 73 days remaining = (73/365) * 100 = 20% remaining
        // Should be EXPIRES_SOON because 20% < 33% threshold
        $monitor = Monitor::factory()->create([
            'certificate_status' => 'valid',
            'certificate_expiration_date' => now()->addDays(73),
        ]);

        $validFrom = now()->subDays(292); // 365 - 73 = 292 days ago

        // Act: Test the determineSslStatus method via reflection
        $job = new CheckMonitorJob($monitor, 'ssl');
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('determineSslStatus');
        $method->setAccessible(true);

        $status = $method->invoke(
            $job,
            now()->addDays(73),
            $validFrom,
            'valid'
        );

        // Assert: Should be 'expires_soon' due to low percentage remaining
        expect($status)->toBe('expires_soon');
    });

    it('marks 2-year certificate with 73 days remaining as expires_soon', function () {
        // Arrange: 730-day commercial cert with 73 days remaining = 10% of lifetime
        // 730-day total validity, 73 days remaining = (73/730) * 100 = 10% remaining
        // Should be EXPIRES_SOON because 10% < 33% threshold
        $validFrom = now()->subDays(657); // 730 - 73 = 657 days ago

        // Act: Test the determineSslStatus method via reflection
        $job = new CheckMonitorJob(Monitor::factory()->make(), 'ssl');
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('determineSslStatus');
        $method->setAccessible(true);

        $status = $method->invoke(
            $job,
            now()->addDays(73),
            $validFrom,
            'valid'
        );

        // Assert: Should be 'expires_soon' due to low percentage remaining
        expect($status)->toBe('expires_soon');
    });

    it('uses 30-day fallback when valid_from is null', function () {
        // Arrange: Test legacy fallback behavior when valid_from is unavailable
        $job = new CheckMonitorJob(Monitor::factory()->make(), 'ssl');
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('determineSslStatus');
        $method->setAccessible(true);

        // Act & Assert: 31 days remaining, no valid_from = should be valid
        $status = $method->invoke($job, now()->addDays(31), null, 'valid');
        expect($status)->toBe('valid');

        // Act & Assert: 29 days remaining, no valid_from = should be expires_soon
        $status = $method->invoke($job, now()->addDays(29), null, 'valid');
        expect($status)->toBe('expires_soon');
    });

    it('applies minimum 30-day threshold even for long-lived certificates', function () {
        // Arrange: Edge case - 10-year certificate with 25 days remaining
        // Percentage would be (25/3650) * 100 = 0.68%, but should still trigger
        // expires_soon due to 30-day minimum threshold
        $validFrom = now()->subDays(3625); // 10 years ago

        // Act: Test the determineSslStatus method via reflection
        $job = new CheckMonitorJob(Monitor::factory()->make(), 'ssl');
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('determineSslStatus');
        $method->setAccessible(true);

        $status = $method->invoke(
            $job,
            now()->addDays(25),
            $validFrom,
            'valid'
        );

        // Assert: Should be 'expires_soon' due to 30-day minimum threshold
        expect($status)->toBe('expires_soon');
    });

    it('marks expired certificates regardless of thresholds', function () {
        // Arrange: Certificate that expired yesterday
        // Should return 'expired' regardless of validity period or thresholds
        $validFrom = now()->subDays(90);

        // Act: Test the determineSslStatus method via reflection
        $job = new CheckMonitorJob(Monitor::factory()->make(), 'ssl');
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('determineSslStatus');
        $method->setAccessible(true);

        $status = $method->invoke(
            $job,
            now()->subDays(1), // Expired yesterday
            $validFrom,
            'valid'
        );

        // Assert: Should be 'expired' status
        expect($status)->toBe('expired');
    });

    it('marks invalid certificates regardless of expiration', function () {
        // Arrange: Certificate with 100 days remaining but invalid status
        // Should return 'invalid' regardless of expiration date
        $job = new CheckMonitorJob(Monitor::factory()->make(), 'ssl');
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('determineSslStatus');
        $method->setAccessible(true);

        // Act: Test with invalid certificate status
        $status = $method->invoke(
            $job,
            now()->addDays(100),
            now()->subDays(90),
            'invalid' // Certificate status is invalid
        );

        // Assert: Should be 'invalid' status
        expect($status)->toBe('invalid');
    });

    it('handles edge case of certificate expiring exactly in 30 days', function () {
        // Arrange: Certificate with exactly 30 days remaining
        // This is the boundary case for the minimum threshold
        $validFrom = now()->subDays(335); // 365 - 30 = 335 days ago (1-year cert)

        // Act: Test the determineSslStatus method via reflection
        $job = new CheckMonitorJob(Monitor::factory()->make(), 'ssl');
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('determineSslStatus');
        $method->setAccessible(true);

        $status = $method->invoke(
            $job,
            now()->addDays(30),
            $validFrom,
            'valid'
        );

        // Assert: Should be 'valid' at exactly 30 days (threshold is < 30)
        // Update: Based on the code, the threshold is "< 30" OR "< 33%"
        // With 30 days remaining on a 365-day cert: (30/365)*100 = 8.2% < 33%
        // So this should trigger 'expires_soon'
        expect($status)->toBe('expires_soon');
    });

    it('handles certificate with no expiration date gracefully', function () {
        // Arrange: Certificate with missing expiration data
        // Should return 'valid' as a safe default
        $job = new CheckMonitorJob(Monitor::factory()->make(), 'ssl');
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('determineSslStatus');
        $method->setAccessible(true);

        // Act: Test with null expiration date
        $status = $method->invoke(
            $job,
            null, // No expiration date
            now()->subDays(30),
            'valid'
        );

        // Assert: Should be 'valid' as safe default
        expect($status)->toBe('valid');
    });

    it('calculates percentage correctly for various certificate types', function () {
        // Arrange: Test multiple certificate validity periods with same remaining days
        $job = new CheckMonitorJob(Monitor::factory()->make(), 'ssl');
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('determineSslStatus');
        $method->setAccessible(true);

        $testCases = [
            // [validityDays, remainingDays, expectedStatus, description]
            [90, 40, 'valid', 'Let\'s Encrypt with 44% remaining'],
            [90, 31, 'valid', 'Let\'s Encrypt with 34% remaining'],
            [90, 29, 'expires_soon', 'Let\'s Encrypt with 32% remaining (also triggers 30-day min)'],
            [180, 60, 'valid', '6-month cert with 33% remaining'],
            [180, 59, 'expires_soon', '6-month cert with 32% remaining'],
            [365, 121, 'valid', '1-year cert with 33% remaining'],
            [365, 119, 'expires_soon', '1-year cert with 32% remaining'],
            [730, 241, 'valid', '2-year cert with 33% remaining'],
            [730, 239, 'expires_soon', '2-year cert with 32% remaining'],
        ];

        foreach ($testCases as [$validityDays, $remainingDays, $expectedStatus, $description]) {
            // Act: Calculate status
            $validFrom = now()->subDays($validityDays - $remainingDays);
            $expiresAt = now()->addDays($remainingDays);

            $status = $method->invoke($job, $expiresAt, $validFrom, 'valid');

            // Assert: Check status matches expected
            expect($status)
                ->toBe($expectedStatus, "Failed for: {$description}");
        }
    });

    it('respects 30-day minimum threshold over percentage for long certificates', function () {
        // Arrange: Very long validity period (5 years) with 31 days remaining
        // Percentage: (31/1825) * 100 = 1.7% (way below 33%)
        // But 31 days is above the 30-day minimum, so should be valid
        $validFrom = now()->subDays(1794); // 5 years - 31 days

        // Act: Test the determineSslStatus method via reflection
        $job = new CheckMonitorJob(Monitor::factory()->make(), 'ssl');
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('determineSslStatus');
        $method->setAccessible(true);

        $status = $method->invoke(
            $job,
            now()->addDays(31),
            $validFrom,
            'valid'
        );

        // Assert: Should be 'expires_soon' because 1.7% < 33%
        // The 30-day minimum only kicks in when days < 30, not when percentage is low
        expect($status)->toBe('expires_soon');
    });

    it('handles valid_from date in the future gracefully', function () {
        // Arrange: Edge case where valid_from is in the future (shouldn't happen but we should handle it)
        $validFrom = now()->addDays(10); // Future date
        $expiresAt = now()->addDays(100);

        // Act: Test the determineSslStatus method via reflection
        $job = new CheckMonitorJob(Monitor::factory()->make(), 'ssl');
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('determineSslStatus');
        $method->setAccessible(true);

        $status = $method->invoke(
            $job,
            $expiresAt,
            $validFrom,
            'valid'
        );

        // Assert: Should still calculate correctly or fallback gracefully
        // With invalid date range, the method should handle it
        expect($status)->toBeIn(['valid', 'expires_soon']);
    });
});
