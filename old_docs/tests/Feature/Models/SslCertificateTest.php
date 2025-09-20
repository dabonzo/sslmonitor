<?php

use App\Models\SslCertificate;
use App\Models\Website;
use App\Models\User;
use Carbon\Carbon;

test('ssl certificate can be created with valid data', function () {
    $website = Website::factory()->create();
    
    $certificate = SslCertificate::create([
        'website_id' => $website->id,
        'issuer' => 'Let\'s Encrypt',
        'expires_at' => Carbon::now()->addDays(90),
        'subject' => 'example.com',
        'serial_number' => '12345abcdef',
        'signature_algorithm' => 'SHA256withRSA',
        'is_valid' => true,
    ]);

    expect($certificate)->toBeInstanceOf(SslCertificate::class)
        ->and($certificate->website_id)->toBe($website->id)
        ->and($certificate->issuer)->toBe('Let\'s Encrypt')
        ->and($certificate->subject)->toBe('example.com')
        ->and($certificate->is_valid)->toBeTrue();
});

test('ssl certificate belongs to website', function () {
    $website = Website::factory()->create();
    $certificate = SslCertificate::factory()->create(['website_id' => $website->id]);

    expect($certificate->website)->toBeInstanceOf(Website::class)
        ->and($certificate->website->id)->toBe($website->id);
});

test('ssl certificate calculates days until expiry', function () {
    $expiryDate = Carbon::now()->addDays(30);
    $certificate = SslCertificate::factory()->create([
        'expires_at' => $expiryDate
    ]);

    expect($certificate->getDaysUntilExpiry())->toBe(30);
});

test('ssl certificate returns negative days for expired certificates', function () {
    $expiryDate = Carbon::now()->subDays(10);
    $certificate = SslCertificate::factory()->create([
        'expires_at' => $expiryDate
    ]);

    expect($certificate->getDaysUntilExpiry())->toBe(-10);
});

test('ssl certificate determines if it is expired', function () {
    $expiredCertificate = SslCertificate::factory()->create([
        'expires_at' => Carbon::now()->subDay()
    ]);
    
    $validCertificate = SslCertificate::factory()->create([
        'expires_at' => Carbon::now()->addDays(30)
    ]);

    expect($expiredCertificate->isExpired())->toBeTrue()
        ->and($validCertificate->isExpired())->toBeFalse();
});

test('ssl certificate determines if it is expiring soon', function () {
    $expiringSoonCertificate = SslCertificate::factory()->create([
        'expires_at' => Carbon::now()->addDays(5)
    ]);
    
    $validCertificate = SslCertificate::factory()->create([
        'expires_at' => Carbon::now()->addDays(60)
    ]);

    expect($expiringSoonCertificate->isExpiringSoon())->toBeTrue()
        ->and($validCertificate->isExpiringSoon())->toBeFalse();
});

test('ssl certificate can customize expiring soon threshold', function () {
    $certificate = SslCertificate::factory()->create([
        'expires_at' => Carbon::now()->addDays(20)
    ]);

    expect($certificate->isExpiringSoon(30))->toBeTrue()
        ->and($certificate->isExpiringSoon(10))->toBeFalse();
});

test('ssl certificate gets current status', function () {
    $expiredCert = SslCertificate::factory()->create([
        'expires_at' => Carbon::now()->subDay(),
        'is_valid' => false
    ]);
    
    $expiringSoonCert = SslCertificate::factory()->create([
        'expires_at' => Carbon::now()->addDays(5),
        'is_valid' => true
    ]);
    
    $validCert = SslCertificate::factory()->create([
        'expires_at' => Carbon::now()->addDays(60),
        'is_valid' => true
    ]);
    
    $invalidCert = SslCertificate::factory()->create([
        'expires_at' => Carbon::now()->addDays(60),
        'is_valid' => false
    ]);

    expect($expiredCert->getStatus())->toBe('expired')
        ->and($expiringSoonCert->getStatus())->toBe('expiring_soon')
        ->and($validCert->getStatus())->toBe('valid')
        ->and($invalidCert->getStatus())->toBe('invalid');
});

test('ssl certificate has proper fillable attributes', function () {
    $certificate = new SslCertificate();
    
    expect($certificate->getFillable())->toContain('website_id')
        ->and($certificate->getFillable())->toContain('issuer')
        ->and($certificate->getFillable())->toContain('expires_at')
        ->and($certificate->getFillable())->toContain('subject')
        ->and($certificate->getFillable())->toContain('serial_number')
        ->and($certificate->getFillable())->toContain('signature_algorithm')
        ->and($certificate->getFillable())->toContain('is_valid');
});

test('ssl certificate casts expires_at to datetime', function () {
    $certificate = SslCertificate::factory()->create([
        'expires_at' => '2025-12-31 23:59:59'
    ]);

    expect($certificate->expires_at)->toBeInstanceOf(Carbon::class);
});

test('ssl certificate scopes work correctly', function () {
    // Create test certificates
    SslCertificate::factory()->create(['is_valid' => true, 'expires_at' => Carbon::now()->addDays(60)]);
    SslCertificate::factory()->create(['is_valid' => false, 'expires_at' => Carbon::now()->addDays(60)]);
    SslCertificate::factory()->create(['is_valid' => true, 'expires_at' => Carbon::now()->subDay()]);
    SslCertificate::factory()->create(['is_valid' => true, 'expires_at' => Carbon::now()->addDays(5)]);

    expect(SslCertificate::valid()->count())->toBe(3)
        ->and(SslCertificate::invalid()->count())->toBe(1)
        ->and(SslCertificate::expired()->count())->toBe(1)
        ->and(SslCertificate::expiringSoon()->count())->toBe(1);
});