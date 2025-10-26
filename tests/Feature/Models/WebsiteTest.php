<?php

use App\Models\Website;
use Tests\Traits\MocksSslCertificateAnalysis;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class, MocksSslCertificateAnalysis::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
    $this->setUpMocksSslCertificateAnalysis();

    // Mock MonitorIntegrationService to prevent observer overhead
    $this->mock(\App\Services\MonitorIntegrationService::class, function ($mock) {
        $mock->shouldReceive('createOrUpdateMonitorForWebsite')->andReturn(null);
        $mock->shouldReceive('removeMonitorForWebsite')->andReturn(null);
    });
});

test('website stores and retrieves certificate data', function () {
    $certificateData = [
        'subject' => 'example.com, www.example.com',
        'issuer' => "Let's Encrypt",
        'serial_number' => '0x123456789',
        'key_size' => 2048,
        'valid_from' => now()->subDays(60)->toIso8601String(),
        'valid_until' => now()->addDays(90)->toIso8601String(),
        'days_remaining' => 90,
        'is_expired' => false,
        'expires_soon' => false,
        'security_score' => 95,
    ];

    $website = Website::withoutEvents(fn() => Website::factory()->create([
        'latest_ssl_certificate' => $certificateData,
        'ssl_certificate_analyzed_at' => now(),
    ]));

    expect($website->latest_ssl_certificate)->toBeArray()
        ->and($website->latest_ssl_certificate['subject'])->toBe('example.com, www.example.com')
        ->and($website->latest_ssl_certificate['issuer'])->toBe("Let's Encrypt")
        ->and($website->latest_ssl_certificate['key_size'])->toBe(2048)
        ->and($website->certificate)->toBe($website->latest_ssl_certificate);
});

test('website certificate accessor returns latest_ssl_certificate', function () {
    $certificateData = [
        'subject' => 'test.com',
        'issuer' => 'Test CA',
        'key_size' => 4096,
    ];

    $website = Website::withoutEvents(fn() => Website::factory()->create([
        'latest_ssl_certificate' => $certificateData,
    ]));

    expect($website->certificate)->toBe($website->latest_ssl_certificate)
        ->and($website->certificate)->toBeArray()
        ->and($website->certificate['subject'])->toBe('test.com');
});

test('website detects stale certificate data after 24 hours', function () {
    $website = Website::withoutEvents(fn() => Website::factory()->create([
        'ssl_certificate_analyzed_at' => now()->subDays(2),
    ]));

    expect($website->isCertificateDataStale())->toBeTrue();
});

test('website detects fresh certificate data within 24 hours', function () {
    $website = Website::withoutEvents(fn() => Website::factory()->create([
        'ssl_certificate_analyzed_at' => now()->subHours(12),
    ]));

    expect($website->isCertificateDataStale())->toBeFalse();
});

test('website with no analysis timestamp is considered stale', function () {
    $website = Website::withoutEvents(fn() => Website::factory()->create([
        'ssl_certificate_analyzed_at' => null,
    ]));

    expect($website->isCertificateDataStale())->toBeTrue();
});

test('website exactly at 24 hour boundary is considered stale', function () {
    $website = Website::withoutEvents(fn() => Website::factory()->create([
        'ssl_certificate_analyzed_at' => now()->subDay(),
    ]));

    // At exactly 24 hours or more, it should be stale (not less than 24 hours ago means stale)
    expect($website->isCertificateDataStale())->toBeTrue();
});

test('website just over 24 hours is considered stale', function () {
    $website = Website::withoutEvents(fn() => Website::factory()->create([
        'ssl_certificate_analyzed_at' => now()->subDay()->subMinute(),
    ]));

    expect($website->isCertificateDataStale())->toBeTrue();
});

test('website stores complex certificate data structure', function () {
    $certificateData = [
        // Basic Info
        'subject' => 'example.com',
        'issuer' => 'DigiCert',
        'serial_number' => '01:23:45:67:89:AB:CD:EF',
        'signature_algorithm' => 'SHA256withRSA',

        // Validity
        'valid_from' => now()->subDays(60)->toIso8601String(),
        'valid_until' => now()->addDays(90)->toIso8601String(),
        'days_remaining' => 90,
        'is_expired' => false,
        'expires_soon' => false,

        // Security
        'key_algorithm' => 'RSA',
        'key_size' => 2048,
        'security_score' => 95,
        'risk_level' => 'low',

        // Domains
        'primary_domain' => 'example.com',
        'subject_alt_names' => ['example.com', 'www.example.com', 'api.example.com'],
        'covers_www' => true,
        'is_wildcard' => false,

        // Chain
        'chain_length' => 3,
        'chain_complete' => true,
        'intermediate_issuers' => ['DigiCert SHA2 Secure Server CA', 'DigiCert Global Root CA'],

        // Metadata
        'status' => 'success',
        'analyzed_at' => now()->toIso8601String(),
    ];

    $website = Website::withoutEvents(fn() => Website::factory()->create([
        'latest_ssl_certificate' => $certificateData,
        'ssl_certificate_analyzed_at' => now(),
    ]));

    $website->refresh();

    expect($website->latest_ssl_certificate)->toBeArray()
        ->and($website->latest_ssl_certificate)->toHaveKeys([
            'subject', 'issuer', 'serial_number', 'signature_algorithm',
            'valid_from', 'valid_until', 'days_remaining', 'is_expired', 'expires_soon',
            'key_algorithm', 'key_size', 'security_score', 'risk_level',
            'primary_domain', 'subject_alt_names', 'covers_www', 'is_wildcard',
            'chain_length', 'chain_complete', 'intermediate_issuers',
            'status', 'analyzed_at',
        ])
        ->and($website->latest_ssl_certificate['subject_alt_names'])->toBeArray()
        ->and($website->latest_ssl_certificate['subject_alt_names'])->toHaveCount(3)
        ->and($website->latest_ssl_certificate['intermediate_issuers'])->toBeArray()
        ->and($website->latest_ssl_certificate['intermediate_issuers'])->toHaveCount(2);
});

test('website certificate data persists across reloads', function () {
    $certificateData = [
        'subject' => 'persistent.com',
        'issuer' => 'Test CA',
        'key_size' => 4096,
        'security_score' => 100,
    ];

    $website = Website::withoutEvents(fn() => Website::factory()->create([
        'latest_ssl_certificate' => $certificateData,
        'ssl_certificate_analyzed_at' => now(),
    ]));

    $websiteId = $website->id;

    // Clear model instance
    unset($website);

    // Reload from database
    $reloaded = Website::find($websiteId);

    expect($reloaded->latest_ssl_certificate)->toBeArray()
        ->and($reloaded->latest_ssl_certificate['subject'])->toBe('persistent.com')
        ->and($reloaded->latest_ssl_certificate['issuer'])->toBe('Test CA')
        ->and($reloaded->latest_ssl_certificate['key_size'])->toBe(4096)
        ->and($reloaded->ssl_certificate_analyzed_at)->not->toBeNull();
});

test('website can store and retrieve updated certificate data', function () {
    $website = Website::withoutEvents(fn() => Website::factory()->create([
        'latest_ssl_certificate' => null,
        'ssl_certificate_analyzed_at' => null,
    ]));

    // First update - store initial certificate data
    $firstCert = [
        'subject' => 'first-cert.com',
        'issuer' => 'First CA',
        'key_size' => 2048,
    ];

    $website->latest_ssl_certificate = $firstCert;
    $website->ssl_certificate_analyzed_at = now()->subDays(5);
    $website->save();

    $website = Website::find($website->id);

    expect($website->latest_ssl_certificate['subject'])->toBe('first-cert.com')
        ->and($website->latest_ssl_certificate['key_size'])->toBe(2048);

    // Second update - update with new certificate data
    $secondCert = [
        'subject' => 'second-cert.com',
        'issuer' => 'Second CA',
        'key_size' => 4096,
    ];

    $website->latest_ssl_certificate = $secondCert;
    $website->ssl_certificate_analyzed_at = now();
    $website->save();

    $website = Website::find($website->id);

    expect($website->latest_ssl_certificate['subject'])->toBe('second-cert.com')
        ->and($website->latest_ssl_certificate['issuer'])->toBe('Second CA')
        ->and($website->latest_ssl_certificate['key_size'])->toBe(4096);
});

test('website handles null certificate data gracefully', function () {
    $website = Website::withoutEvents(fn() => Website::factory()->create([
        'latest_ssl_certificate' => null,
        'ssl_certificate_analyzed_at' => null,
    ]));

    expect($website->latest_ssl_certificate)->toBeNull()
        ->and($website->ssl_certificate_analyzed_at)->toBeNull()
        ->and($website->isCertificateDataStale())->toBeTrue();
});

test('website casts certificate data as array', function () {
    $certificateData = [
        'subject' => 'cast-test.com',
        'issuer' => 'Cast Test CA',
    ];

    $website = Website::withoutEvents(fn() => Website::factory()->create([
        'latest_ssl_certificate' => $certificateData,
    ]));

    expect($website->latest_ssl_certificate)->toBeArray()
        ->and($website->latest_ssl_certificate)->not->toBeString()
        ->and($website->latest_ssl_certificate)->not->toBeNull();
});

test('website casts ssl_certificate_analyzed_at as datetime', function () {
    $website = Website::withoutEvents(fn() => Website::factory()->create([
        'ssl_certificate_analyzed_at' => now(),
    ]));

    expect($website->ssl_certificate_analyzed_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
        ->and($website->ssl_certificate_analyzed_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('website with expired certificate data', function () {
    $certificateData = [
        'subject' => 'expired.com',
        'issuer' => 'Test CA',
        'valid_from' => now()->subDays(400)->toIso8601String(),
        'valid_until' => now()->subDays(10)->toIso8601String(),
        'days_remaining' => 0,
        'is_expired' => true,
        'expires_soon' => false,
    ];

    $website = Website::withoutEvents(fn() => Website::factory()->create([
        'latest_ssl_certificate' => $certificateData,
        'ssl_certificate_analyzed_at' => now(),
    ]));

    expect($website->latest_ssl_certificate['is_expired'])->toBeTrue()
        ->and($website->latest_ssl_certificate['days_remaining'])->toBe(0)
        ->and($website->latest_ssl_certificate['valid_until'])->toContain(now()->subDays(10)->format('Y-m-d'));
});

test('website with expiring soon certificate data', function () {
    $certificateData = [
        'subject' => 'expiring-soon.com',
        'issuer' => 'Test CA',
        'valid_from' => now()->subDays(60)->toIso8601String(),
        'valid_until' => now()->addDays(15)->toIso8601String(),
        'days_remaining' => 15,
        'is_expired' => false,
        'expires_soon' => true,
    ];

    $website = Website::withoutEvents(fn() => Website::factory()->create([
        'latest_ssl_certificate' => $certificateData,
        'ssl_certificate_analyzed_at' => now(),
    ]));

    expect($website->latest_ssl_certificate['is_expired'])->toBeFalse()
        ->and($website->latest_ssl_certificate['expires_soon'])->toBeTrue()
        ->and($website->latest_ssl_certificate['days_remaining'])->toBe(15);
});

test('website with wildcard certificate', function () {
    $certificateData = [
        'subject' => '*.example.com',
        'primary_domain' => '*.example.com',
        'is_wildcard' => true,
        'subject_alt_names' => ['*.example.com', 'example.com'],
    ];

    $website = Website::withoutEvents(fn() => Website::factory()->create([
        'latest_ssl_certificate' => $certificateData,
    ]));

    expect($website->latest_ssl_certificate['is_wildcard'])->toBeTrue()
        ->and($website->latest_ssl_certificate['subject'])->toStartWith('*.')
        ->and($website->latest_ssl_certificate['subject_alt_names'])->toContain('*.example.com');
});

test('website certificate data json serialization', function () {
    $certificateData = [
        'subject' => 'json-test.com',
        'issuer' => 'JSON Test CA',
        'key_size' => 2048,
        'subject_alt_names' => ['json-test.com', 'www.json-test.com'],
        'chain_complete' => true,
    ];

    $website = Website::withoutEvents(fn() => Website::factory()->create([
        'latest_ssl_certificate' => $certificateData,
    ]));

    $json = $website->toJson();

    $decoded = json_decode($json, true);

    expect($decoded['latest_ssl_certificate'])->toBeArray()
        ->and($decoded['latest_ssl_certificate']['subject'])->toBe('json-test.com')
        ->and($decoded['latest_ssl_certificate']['subject_alt_names'])->toBeArray()
        ->and($decoded['latest_ssl_certificate']['subject_alt_names'])->toHaveCount(2);
});
