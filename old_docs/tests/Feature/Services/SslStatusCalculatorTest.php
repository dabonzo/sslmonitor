<?php

use App\Models\SslCertificate;
use App\Models\Website;
use App\Services\SslStatusCalculator;
use Carbon\Carbon;

test('ssl status calculator determines valid certificate status', function () {
    $calculator = new SslStatusCalculator();
    
    // Certificate that expires in 30 days (valid)
    $expirationDate = Carbon::now()->addDays(30);
    $daysUntilExpiry = 30;
    
    $status = $calculator->calculateStatus($expirationDate, true, $daysUntilExpiry);
    
    expect($status)->toBe(SslStatusCalculator::STATUS_VALID);
});

test('ssl status calculator determines expiring soon certificate status', function () {
    $calculator = new SslStatusCalculator();
    
    // Certificate that expires in 7 days (expiring soon)
    $expirationDate = Carbon::now()->addDays(7);
    $daysUntilExpiry = 7;
    
    $status = $calculator->calculateStatus($expirationDate, true, $daysUntilExpiry);
    
    expect($status)->toBe(SslStatusCalculator::STATUS_EXPIRING_SOON);
});

test('ssl status calculator determines expired certificate status', function () {
    $calculator = new SslStatusCalculator();
    
    // Certificate that expired 5 days ago
    $expirationDate = Carbon::now()->subDays(5);
    $daysUntilExpiry = -5;
    
    $status = $calculator->calculateStatus($expirationDate, false, $daysUntilExpiry);
    
    expect($status)->toBe(SslStatusCalculator::STATUS_EXPIRED);
});

test('ssl status calculator determines invalid certificate status', function () {
    $calculator = new SslStatusCalculator();
    
    // Certificate that is not valid but not expired
    $expirationDate = Carbon::now()->addDays(30);
    $daysUntilExpiry = 30;
    
    $status = $calculator->calculateStatus($expirationDate, false, $daysUntilExpiry);
    
    expect($status)->toBe(SslStatusCalculator::STATUS_INVALID);
});

test('ssl status calculator can calculate days until expiry from carbon date', function () {
    $calculator = new SslStatusCalculator();
    
    $futureDate = Carbon::now()->addDays(45);
    $pastDate = Carbon::now()->subDays(10);
    
    $futureDays = $calculator->calculateDaysUntilExpiry($futureDate);
    $pastDays = $calculator->calculateDaysUntilExpiry($pastDate);
    
    expect($futureDays)->toBeGreaterThan(40)
        ->and($futureDays)->toBeLessThanOrEqual(45)
        ->and($pastDays)->toBeGreaterThanOrEqual(-10)
        ->and($pastDays)->toBeLessThan(0);
});

test('ssl status calculator can customize expiring soon threshold', function () {
    $calculator = new SslStatusCalculator();
    
    $expirationDate = Carbon::now()->addDays(20);
    $daysUntilExpiry = 20;
    
    // With default threshold (14 days) - should be valid
    $statusDefault = $calculator->calculateStatus($expirationDate, true, $daysUntilExpiry);
    
    // With custom threshold (30 days) - should be expiring soon
    $statusCustom = $calculator->calculateStatus($expirationDate, true, $daysUntilExpiry, 30);
    
    expect($statusDefault)->toBe(SslStatusCalculator::STATUS_VALID)
        ->and($statusCustom)->toBe(SslStatusCalculator::STATUS_EXPIRING_SOON);
});

test('ssl status calculator has proper status constants', function () {
    expect(SslStatusCalculator::STATUS_VALID)->toBe('valid')
        ->and(SslStatusCalculator::STATUS_EXPIRING_SOON)->toBe('expiring_soon')
        ->and(SslStatusCalculator::STATUS_EXPIRED)->toBe('expired')
        ->and(SslStatusCalculator::STATUS_INVALID)->toBe('invalid')
        ->and(SslStatusCalculator::STATUS_ERROR)->toBe('error');
});

test('ssl status calculator can determine status from ssl certificate model', function () {
    $calculator = new SslStatusCalculator();
    $website = Website::factory()->create();
    
    // Create a valid certificate
    $validCert = SslCertificate::factory()->create([
        'website_id' => $website->id,
        'expires_at' => Carbon::now()->addDays(60),
        'is_valid' => true,
    ]);
    
    $status = $calculator->calculateStatusFromCertificate($validCert);
    
    expect($status)->toBe(SslStatusCalculator::STATUS_VALID);
});

test('ssl status calculator can determine status from expiring certificate model', function () {
    $calculator = new SslStatusCalculator();
    $website = Website::factory()->create();
    
    // Create an expiring soon certificate
    $expiringSoonCert = SslCertificate::factory()->expiringSoon()->create([
        'website_id' => $website->id,
    ]);
    
    $status = $calculator->calculateStatusFromCertificate($expiringSoonCert);
    
    expect($status)->toBe(SslStatusCalculator::STATUS_EXPIRING_SOON);
});

test('ssl status calculator can determine status from expired certificate model', function () {
    $calculator = new SslStatusCalculator();
    $website = Website::factory()->create();
    
    // Create an expired certificate
    $expiredCert = SslCertificate::factory()->expired()->create([
        'website_id' => $website->id,
    ]);
    
    $status = $calculator->calculateStatusFromCertificate($expiredCert);
    
    expect($status)->toBe(SslStatusCalculator::STATUS_EXPIRED);
});

test('ssl status calculator can determine status from invalid certificate model', function () {
    $calculator = new SslStatusCalculator();
    $website = Website::factory()->create();
    
    // Create an invalid certificate
    $invalidCert = SslCertificate::factory()->invalid()->create([
        'website_id' => $website->id,
    ]);
    
    $status = $calculator->calculateStatusFromCertificate($invalidCert);
    
    expect($status)->toBe(SslStatusCalculator::STATUS_INVALID);
});

test('ssl status calculator validates status constants are used correctly', function () {
    $calculator = new SslStatusCalculator();
    
    $validStatuses = [
        SslStatusCalculator::STATUS_VALID,
        SslStatusCalculator::STATUS_EXPIRING_SOON,
        SslStatusCalculator::STATUS_EXPIRED,
        SslStatusCalculator::STATUS_INVALID,
        SslStatusCalculator::STATUS_ERROR,
    ];
    
    foreach ($validStatuses as $status) {
        expect($calculator->isValidStatus($status))->toBeTrue();
    }
    
    expect($calculator->isValidStatus('unknown'))->toBeFalse()
        ->and($calculator->isValidStatus(''))->toBeFalse()
        ->and($calculator->isValidStatus(null))->toBeFalse();
});

test('ssl status calculator can get status priority for sorting', function () {
    $calculator = new SslStatusCalculator();
    
    // Error should have highest priority (lowest number)
    expect($calculator->getStatusPriority(SslStatusCalculator::STATUS_ERROR))->toBe(1)
        ->and($calculator->getStatusPriority(SslStatusCalculator::STATUS_EXPIRED))->toBe(2)
        ->and($calculator->getStatusPriority(SslStatusCalculator::STATUS_EXPIRING_SOON))->toBe(3)
        ->and($calculator->getStatusPriority(SslStatusCalculator::STATUS_INVALID))->toBe(4)
        ->and($calculator->getStatusPriority(SslStatusCalculator::STATUS_VALID))->toBe(5);
});

test('ssl status calculator handles edge case of exactly at threshold', function () {
    $calculator = new SslStatusCalculator();
    
    // Certificate that expires exactly at the threshold (14 days)
    $expirationDate = Carbon::now()->addDays(14);
    $daysUntilExpiry = 14;
    
    $status = $calculator->calculateStatus($expirationDate, true, $daysUntilExpiry);
    
    expect($status)->toBe(SslStatusCalculator::STATUS_EXPIRING_SOON);
});

test('ssl status calculator handles leap year calculations correctly', function () {
    $calculator = new SslStatusCalculator();
    
    // Test during a leap year
    $leapYearDate = Carbon::create(2024, 2, 28)->addDay(); // Feb 29, 2024
    $futureDate = $leapYearDate->copy()->addDays(100);
    
    $days = $calculator->calculateDaysUntilExpiry($futureDate, $leapYearDate);
    
    expect($days)->toBe(100);
});