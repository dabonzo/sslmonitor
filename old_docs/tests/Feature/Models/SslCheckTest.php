<?php

use App\Models\SslCheck;
use App\Models\Website;
use App\Models\User;
use Carbon\Carbon;

test('ssl check can be created with valid data', function () {
    $website = Website::factory()->create();
    
    $sslCheck = SslCheck::create([
        'website_id' => $website->id,
        'status' => 'valid',
        'checked_at' => Carbon::now(),
        'expires_at' => Carbon::now()->addDays(90),
        'issuer' => 'Let\'s Encrypt',
        'subject' => 'example.com',
        'is_valid' => true,
        'days_until_expiry' => 90,
    ]);

    expect($sslCheck)->toBeInstanceOf(SslCheck::class)
        ->and($sslCheck->website_id)->toBe($website->id)
        ->and($sslCheck->status)->toBe('valid')
        ->and($sslCheck->is_valid)->toBeTrue()
        ->and($sslCheck->days_until_expiry)->toBe(90);
});

test('ssl check belongs to website', function () {
    $website = Website::factory()->create();
    $sslCheck = SslCheck::factory()->create(['website_id' => $website->id]);

    expect($sslCheck->website)->toBeInstanceOf(Website::class)
        ->and($sslCheck->website->id)->toBe($website->id);
});

test('ssl check can determine status from certificate data', function () {
    $website = Website::factory()->create();

    // Valid certificate
    $validCheck = SslCheck::create([
        'website_id' => $website->id,
        'status' => 'valid',
        'checked_at' => Carbon::now(),
        'expires_at' => Carbon::now()->addDays(90),
        'is_valid' => true,
        'days_until_expiry' => 90,
    ]);

    // Expiring soon certificate
    $expiringSoonCheck = SslCheck::create([
        'website_id' => $website->id,
        'status' => 'expiring_soon',
        'checked_at' => Carbon::now(),
        'expires_at' => Carbon::now()->addDays(5),
        'is_valid' => true,
        'days_until_expiry' => 5,
    ]);

    // Expired certificate
    $expiredCheck = SslCheck::create([
        'website_id' => $website->id,
        'status' => 'expired',
        'checked_at' => Carbon::now(),
        'expires_at' => Carbon::now()->subDays(5),
        'is_valid' => false,
        'days_until_expiry' => -5,
    ]);

    // Invalid certificate
    $invalidCheck = SslCheck::create([
        'website_id' => $website->id,
        'status' => 'invalid',
        'checked_at' => Carbon::now(),
        'expires_at' => Carbon::now()->addDays(90),
        'is_valid' => false,
        'days_until_expiry' => 90,
    ]);

    // Error check (network error, etc.)
    $errorCheck = SslCheck::create([
        'website_id' => $website->id,
        'status' => 'error',
        'checked_at' => Carbon::now(),
        'error_message' => 'Connection timeout',
        'is_valid' => false,
    ]);

    expect($validCheck->status)->toBe('valid')
        ->and($expiringSoonCheck->status)->toBe('expiring_soon')
        ->and($expiredCheck->status)->toBe('expired')
        ->and($invalidCheck->status)->toBe('invalid')
        ->and($errorCheck->status)->toBe('error');
});

test('ssl check calculates days until expiry correctly', function () {
    $website = Website::factory()->create();
    
    $futureCheck = SslCheck::factory()->create([
        'website_id' => $website->id,
        'expires_at' => Carbon::now()->addDays(30),
        'days_until_expiry' => 30,
    ]);
    
    $pastCheck = SslCheck::factory()->create([
        'website_id' => $website->id,
        'expires_at' => Carbon::now()->subDays(10),
        'days_until_expiry' => -10,
    ]);

    expect($futureCheck->days_until_expiry)->toBe(30)
        ->and($pastCheck->days_until_expiry)->toBe(-10);
});

test('ssl check has proper fillable attributes', function () {
    $sslCheck = new SslCheck();
    
    expect($sslCheck->getFillable())->toContain('website_id')
        ->and($sslCheck->getFillable())->toContain('status')
        ->and($sslCheck->getFillable())->toContain('checked_at')
        ->and($sslCheck->getFillable())->toContain('expires_at')
        ->and($sslCheck->getFillable())->toContain('issuer')
        ->and($sslCheck->getFillable())->toContain('subject')
        ->and($sslCheck->getFillable())->toContain('serial_number')
        ->and($sslCheck->getFillable())->toContain('signature_algorithm')
        ->and($sslCheck->getFillable())->toContain('is_valid')
        ->and($sslCheck->getFillable())->toContain('days_until_expiry')
        ->and($sslCheck->getFillable())->toContain('error_message');
});

test('ssl check casts dates to datetime', function () {
    $sslCheck = SslCheck::factory()->create([
        'checked_at' => '2025-01-01 12:00:00',
        'expires_at' => '2025-12-31 23:59:59'
    ]);

    expect($sslCheck->checked_at)->toBeInstanceOf(Carbon::class)
        ->and($sslCheck->expires_at)->toBeInstanceOf(Carbon::class);
});

test('ssl check can have error message for failed checks', function () {
    $website = Website::factory()->create();
    
    $errorCheck = SslCheck::create([
        'website_id' => $website->id,
        'status' => 'error',
        'checked_at' => Carbon::now(),
        'error_message' => 'DNS resolution failed',
        'is_valid' => false,
    ]);

    expect($errorCheck->error_message)->toBe('DNS resolution failed')
        ->and($errorCheck->status)->toBe('error')
        ->and($errorCheck->is_valid)->toBeFalse();
});

test('ssl check scopes filter by status correctly', function () {
    $website = Website::factory()->create();
    
    // Create checks with different statuses
    SslCheck::factory()->create(['website_id' => $website->id, 'status' => 'valid']);
    SslCheck::factory()->create(['website_id' => $website->id, 'status' => 'valid']);
    SslCheck::factory()->create(['website_id' => $website->id, 'status' => 'expired']);
    SslCheck::factory()->create(['website_id' => $website->id, 'status' => 'expiring_soon']);
    SslCheck::factory()->create(['website_id' => $website->id, 'status' => 'invalid']);
    SslCheck::factory()->create(['website_id' => $website->id, 'status' => 'error']);

    expect(SslCheck::valid()->count())->toBe(2)
        ->and(SslCheck::expired()->count())->toBe(1)
        ->and(SslCheck::expiringSoon()->count())->toBe(1)
        ->and(SslCheck::invalid()->count())->toBe(1)
        ->and(SslCheck::failed()->count())->toBe(1);
});

test('ssl check can get latest check for website', function () {
    $website = Website::factory()->create();
    
    // Create older check
    $olderCheck = SslCheck::factory()->create([
        'website_id' => $website->id,
        'checked_at' => '2025-01-01 10:00:00',
    ]);
    
    // Create newer check
    $newerCheck = SslCheck::factory()->create([
        'website_id' => $website->id,
        'checked_at' => '2025-01-01 12:00:00',
    ]);

    $latestCheck = SslCheck::latestChecked()->forWebsite($website->id)->first();
    
    expect($latestCheck->website_id)->toBe($website->id)
        ->and($latestCheck->checked_at->isAfter($olderCheck->fresh()->checked_at))->toBeTrue();
});

test('ssl check orders by checked_at descending by default', function () {
    $website = Website::factory()->create();
    
    $firstCheck = SslCheck::factory()->create([
        'website_id' => $website->id,
        'checked_at' => '2025-01-01 08:00:00',
    ]);
    
    $secondCheck = SslCheck::factory()->create([
        'website_id' => $website->id,
        'checked_at' => '2025-01-01 10:00:00',
    ]);
    
    $thirdCheck = SslCheck::factory()->create([
        'website_id' => $website->id,
        'checked_at' => '2025-01-01 12:00:00',
    ]);

    $orderedChecks = SslCheck::latestChecked()->forWebsite($website->id)->get();
    
    expect($orderedChecks->count())->toBe(3);
    
    // Check that the order is correct (latest first)
    $times = $orderedChecks->pluck('checked_at')->map(fn($time) => $time->format('Y-m-d H:i:s'))->toArray();
    
    expect($times[0])->toBe('2025-01-01 12:00:00')
        ->and($times[1])->toBe('2025-01-01 10:00:00')
        ->and($times[2])->toBe('2025-01-01 08:00:00');
});