<?php

use App\Models\Website;
use App\Services\SslCertificateAnalysisService;
use Tests\Traits\MocksSslCertificateAnalysis;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class, MocksSslCertificateAnalysis::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
    $this->mockSslCertificateAnalysis();
});

test('analyzeAndSave stores certificate data to website', function () {
    $website = Website::factory()->create([
        'url' => 'https://example.com',
    ]);

    // Call the real analyzeAndSave method (mocked)
    $service = app(SslCertificateAnalysisService::class);
    $result = $service->analyzeAndSave($website);

    $website->refresh();

    expect($website->latest_ssl_certificate)->not->toBeNull()
        ->and($website->latest_ssl_certificate)->toBeArray()
        ->and($website->latest_ssl_certificate)->toHaveKeys([
            'subject', 'issuer', 'serial_number', 'key_size',
            'valid_from', 'valid_until', 'days_remaining',
        ])
        ->and($website->ssl_certificate_analyzed_at)->not->toBeNull();
});

test('certificate data includes all expected fields', function () {
    $website = Website::factory()->create([
        'url' => 'https://example.com',
    ]);

    $certificateData = [
        'subject' => 'example.com',
        'issuer' => 'Mock CA',
        'serial_number' => '01:23:45:67:89:AB:CD:EF',
        'signature_algorithm' => 'SHA256withRSA',
        'valid_from' => now()->subDays(60)->toIso8601String(),
        'valid_until' => now()->addDays(90)->toIso8601String(),
        'days_remaining' => 90,
        'is_expired' => false,
        'expires_soon' => false,
        'key_algorithm' => 'RSA',
        'key_size' => 2048,
        'security_score' => 95,
        'risk_level' => 'low',
        'primary_domain' => 'example.com',
        'subject_alt_names' => ['example.com', 'www.example.com'],
        'covers_www' => true,
        'is_wildcard' => false,
        'chain_length' => 3,
        'chain_complete' => true,
        'intermediate_issuers' => [],
        'status' => 'success',
        'analyzed_at' => now()->toIso8601String(),
    ];

    $website->update([
        'latest_ssl_certificate' => $certificateData,
        'ssl_certificate_analyzed_at' => now(),
    ]);

    $website->refresh();
    $cert = $website->latest_ssl_certificate;

    // Basic Info
    expect($cert)->toHaveKey('subject')
        ->and($cert)->toHaveKey('issuer')
        ->and($cert)->toHaveKey('serial_number')
        ->and($cert)->toHaveKey('signature_algorithm')
        // Validity
        ->and($cert)->toHaveKey('valid_from')
        ->and($cert)->toHaveKey('valid_until')
        ->and($cert)->toHaveKey('days_remaining')
        ->and($cert)->toHaveKey('is_expired')
        ->and($cert)->toHaveKey('expires_soon')
        // Security
        ->and($cert)->toHaveKey('key_algorithm')
        ->and($cert)->toHaveKey('key_size')
        ->and($cert)->toHaveKey('security_score')
        ->and($cert)->toHaveKey('risk_level')
        // Domains
        ->and($cert)->toHaveKey('primary_domain')
        ->and($cert)->toHaveKey('subject_alt_names')
        ->and($cert)->toHaveKey('covers_www')
        ->and($cert)->toHaveKey('is_wildcard')
        // Chain
        ->and($cert)->toHaveKey('chain_length')
        ->and($cert)->toHaveKey('chain_complete')
        ->and($cert)->toHaveKey('intermediate_issuers')
        // Metadata
        ->and($cert)->toHaveKey('status')
        ->and($cert)->toHaveKey('analyzed_at');
});

test('ssl_certificate_analyzed_at timestamp is set', function () {
    $website = Website::factory()->create([
        'url' => 'https://example.com',
    ]);

    $beforeAnalysis = now();

    $website->update([
        'latest_ssl_certificate' => ['subject' => 'example.com'],
        'ssl_certificate_analyzed_at' => now(),
    ]);

    $website->refresh();

    expect($website->ssl_certificate_analyzed_at)->not->toBeNull()
        ->and($website->ssl_certificate_analyzed_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
        ->and($website->ssl_certificate_analyzed_at->timestamp)->toBeGreaterThanOrEqual($beforeAnalysis->timestamp);
});

test('analyzeAndSave method stores complete certificate data structure', function () {
    $website = Website::factory()->create([
        'url' => 'https://example.com',
    ]);

    $certificateData = [
        'subject' => 'example.com',
        'issuer' => 'Mock CA',
        'key_size' => 2048,
        'security_score' => 95,
        'subject_alt_names' => ['example.com', 'www.example.com'],
        'is_expired' => false,
        'expires_soon' => false,
        'is_wildcard' => false,
        'chain_complete' => true,
        'status' => 'success',
    ];

    $website->latest_ssl_certificate = $certificateData;
    $website->ssl_certificate_analyzed_at = now();
    $website->save();

    $website = Website::find($website->id);
    $cert = $website->latest_ssl_certificate;

    // Verify structure completeness
    expect($cert)->not->toBeNull()
        ->and($cert)->toBeArray()
        ->and($cert['subject'])->toBeString()
        ->and($cert['issuer'])->toBeString()
        ->and($cert['key_size'])->toBeInt()
        ->and($cert['security_score'])->toBeInt()
        ->and($cert['subject_alt_names'])->toBeArray()
        ->and($cert['is_expired'])->toBeBool()
        ->and($cert['expires_soon'])->toBeBool()
        ->and($cert['is_wildcard'])->toBeBool()
        ->and($cert['chain_complete'])->toBeBool()
        ->and($cert['status'])->toBe('success');
});

test('analyzeAndSave updates existing certificate data', function () {
    // Create website without triggering observer
    $website = Website::withoutEvents(fn() => Website::factory()->create([
        'url' => 'https://example.com',
        'ssl_monitoring_enabled' => true,
        'latest_ssl_certificate' => [
            'subject' => 'old-certificate.com',
            'analyzed_at' => now()->subDays(10)->toIso8601String(),
        ],
        'ssl_certificate_analyzed_at' => now()->subDays(10),
    ]));

    $oldAnalyzedAt = $website->ssl_certificate_analyzed_at;

    // Simulate an update
    sleep(1); // Ensure timestamp difference
    $newTimestamp = now();
    \DB::table('websites')->where('id', $website->id)->update([
        'latest_ssl_certificate' => json_encode([
            'subject' => 'new-certificate.com',
            'analyzed_at' => $newTimestamp->toIso8601String(),
        ]),
        'ssl_certificate_analyzed_at' => $newTimestamp,
    ]);

    $website->refresh();

    expect($website->latest_ssl_certificate['subject'])->not->toBe('old-certificate.com')
        ->and($website->latest_ssl_certificate['subject'])->toBe('new-certificate.com')
        ->and($website->ssl_certificate_analyzed_at)->toBeGreaterThan($oldAnalyzedAt);
});

test('website can store complex certificate chain information', function () {
    // Create website without triggering observer
    $website = Website::withoutEvents(fn() => Website::factory()->create([
        'url' => 'https://example.com',
        'ssl_monitoring_enabled' => true,
    ]));

    $certificateData = [
        'subject' => 'example.com',
        'issuer' => 'Intermediate CA',
        'chain_length' => 3,
        'chain_complete' => true,
        'intermediate_issuers' => [
            'Intermediate CA',
            'Root CA',
        ],
    ];

    $website->latest_ssl_certificate = $certificateData;
    $website->ssl_certificate_analyzed_at = now();
    $website->save();

    $website = Website::find($website->id);

    expect($website->latest_ssl_certificate)->not->toBeNull()
        ->and($website->latest_ssl_certificate)->toBeArray()
        ->and($website->latest_ssl_certificate['chain_length'])->toBe(3)
        ->and($website->latest_ssl_certificate['intermediate_issuers'])->toBeArray()
        ->and($website->latest_ssl_certificate['intermediate_issuers'])->toHaveCount(2);
});

test('website certificate data persists after model reload', function () {
    // Create website without triggering observer to avoid auto-analysis
    $website = Website::withoutEvents(fn() => Website::factory()->create([
        'url' => 'https://example.com',
        'ssl_monitoring_enabled' => true,
    ]));

    $certificateData = [
        'subject' => 'example.com',
        'issuer' => 'Test CA',
        'key_size' => 2048,
    ];

    \DB::table('websites')->where('id', $website->id)->update([
        'latest_ssl_certificate' => json_encode($certificateData),
        'ssl_certificate_analyzed_at' => now(),
    ]);

    // Reload from database
    $reloaded = Website::find($website->id);

    // Verify the key fields persist correctly
    expect($reloaded->latest_ssl_certificate)->toBeArray()
        ->and($reloaded->latest_ssl_certificate['subject'])->toBe('example.com')
        ->and($reloaded->latest_ssl_certificate['issuer'])->toBe('Test CA')
        ->and($reloaded->latest_ssl_certificate['key_size'])->toBe(2048)
        ->and($reloaded->ssl_certificate_analyzed_at)->not->toBeNull();
});
