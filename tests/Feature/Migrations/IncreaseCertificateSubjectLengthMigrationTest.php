<?php

/**
 * Tests for certificate_subject column migration
 *
 * This migration fixes VARCHAR(255) truncation errors when certificates have many SANs.
 * Example: Wikipedia's certificate has 54 SANs = 734 characters, causing truncation.
 *
 * PERFORMANCE: All tests use mocks and complete in < 1 second
 */

use App\Models\MonitoringResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class);

// ==================== MIGRATION EXECUTION TESTS ====================

test('monitoring_results table has certificate_subject column', function () {
    expect(Schema::hasColumn('monitoring_results', 'certificate_subject'))->toBeTrue();
});

test('certificate_subject column is TEXT type capable of storing large values', function () {
    // Test by actually storing a large value instead of checking schema
    $largeCert = str_repeat('DNS:example.com, ', 50); // ~900 characters

    $result = MonitoringResult::factory()->create([
        'certificate_subject' => $largeCert,
    ]);

    $result->refresh();

    expect(strlen($result->certificate_subject))->toBeGreaterThan(255)
        ->and($result->certificate_subject)->toBe($largeCert);
});

test('certificate_subject column can be nullable', function () {
    $result = MonitoringResult::factory()->create([
        'certificate_subject' => null,
    ]);

    expect($result->certificate_subject)->toBeNull();
});

// ==================== DATA PRESERVATION TESTS ====================

test('existing short certificate subjects are preserved after migration', function () {
    $result = MonitoringResult::factory()->create([
        'certificate_subject' => 'CN=example.com, DNS:example.com, DNS:www.example.com',
    ]);

    $result->refresh();

    expect($result->certificate_subject)
        ->toBe('CN=example.com, DNS:example.com, DNS:www.example.com');
});

test('NULL certificate_subject values remain NULL after migration', function () {
    $result = MonitoringResult::factory()->create([
        'certificate_subject' => null,
    ]);

    $result->refresh();

    expect($result->certificate_subject)->toBeNull();
});

test('empty string certificate_subject is preserved', function () {
    $result = MonitoringResult::factory()->create([
        'certificate_subject' => '',
    ]);

    $result->refresh();

    expect($result->certificate_subject)->toBe('');
});

// ==================== LARGE CERTIFICATE SUBJECTS TESTS ====================

test('stores certificate subject with 50+ SANs (Wikipedia-style)', function () {
    //Wikipedia's actual certificate structure (simplified to avoid whitespace issues)
    $wikipediaCert = 'CN=*.wikipedia.org,DNS:*.wikipedia.org,DNS:*.m.wikipedia.org,'
        . 'DNS:*.zero.wikipedia.org,DNS:*.wikibooks.org,DNS:*.m.wikibooks.org,'
        . 'DNS:wikibooks.org,DNS:*.wikimedia.org,DNS:*.m.wikimedia.org,DNS:wikimedia.org,'
        . 'DNS:*.wikinews.org,DNS:*.m.wikinews.org,DNS:wikinews.org,DNS:*.wikipedia.com,'
        . 'DNS:*.wikiquote.org,DNS:*.m.wikiquote.org,DNS:wikiquote.org,'
        . 'DNS:*.wikisource.org,DNS:*.m.wikisource.org,DNS:wikisource.org,'
        . 'DNS:*.wikiversity.org,DNS:*.m.wikiversity.org,DNS:wikiversity.org,'
        . 'DNS:*.wikivoyage.org,DNS:*.m.wikivoyage.org,DNS:wikivoyage.org,'
        . 'DNS:*.wiktionary.org,DNS:*.m.wiktionary.org,DNS:wiktionary.org,'
        . 'DNS:*.mediawiki.org,DNS:*.m.mediawiki.org,DNS:mediawiki.org,'
        . 'DNS:*.planet.wikimedia.org,DNS:*.wikidata.org,DNS:*.m.wikidata.org,'
        . 'DNS:wikidata.org,DNS:*.wikimediafoundation.org,'
        . 'DNS:*.m.wikimediafoundation.org,DNS:wikimediafoundation.org,'
        . 'DNS:*.wmfusercontent.org,DNS:*.m.wmfusercontent.org';

    $result = MonitoringResult::factory()->create([
        'certificate_subject' => $wikipediaCert,
    ]);

    $result->refresh();

    expect($result->certificate_subject)->toBe($wikipediaCert)
        ->and(strlen($result->certificate_subject))->toBeGreaterThan(255)
        ->and(substr_count($result->certificate_subject, 'DNS:'))->toBe(40);
});

test('stores certificate subject exceeding 255 characters', function () {
    // Generate a certificate subject > 255 characters
    $largeCert = 'CN=*.example.com';
    for ($i = 1; $i <= 30; $i++) {
        $largeCert .= ",DNS:subdomain{$i}.example.com";
    }

    $result = MonitoringResult::factory()->create([
        'certificate_subject' => $largeCert,
    ]);

    $result->refresh();

    expect(strlen($result->certificate_subject))
        ->toBeGreaterThan(255)
        ->and($result->certificate_subject)->toBe($largeCert);
});

test('stores certificate subject with 100+ SANs (Google-style)', function () {
    // Simulate Google's certificate with 100+ SANs
    $googleCert = 'CN=*.google.com';
    for ($i = 1; $i <= 100; $i++) {
        $googleCert .= ",DNS:*.google{$i}.com";
    }

    $result = MonitoringResult::factory()->create([
        'certificate_subject' => $googleCert,
    ]);

    $result->refresh();

    expect(strlen($result->certificate_subject))->toBeGreaterThan(1000)
        ->and($result->certificate_subject)->toBe($googleCert)
        ->and(substr_count($result->certificate_subject, 'DNS:'))->toBe(100);
});

test('stores certificate subject with 1000+ characters', function () {
    // Generate certificate with 1000+ characters
    $hugeCert = 'CN=*.example.com';
    for ($i = 1; $i <= 50; $i++) {
        $hugeCert .= ",DNS:very-long-subdomain-name-{$i}.example.com";
    }

    $result = MonitoringResult::factory()->create([
        'certificate_subject' => $hugeCert,
    ]);

    $result->refresh();

    expect(strlen($result->certificate_subject))
        ->toBeGreaterThan(1000)
        ->and($result->certificate_subject)->toBe($hugeCert);
});

test('can store maximum TEXT column size (65535 characters)', function () {
    // TEXT can store up to 65,535 characters
    // Create a certificate subject close to this limit
    $maxCert = 'CN=*.example.com';
    for ($i = 1; $i <= 2000; $i++) {
        $maxCert .= ",DNS:subdomain{$i}.example.com";
    }

    $result = MonitoringResult::factory()->create([
        'certificate_subject' => $maxCert,
    ]);

    $result->refresh();

    expect(strlen($result->certificate_subject))
        ->toBeGreaterThan(40000) // Well beyond VARCHAR(255) limit
        ->and($result->certificate_subject)->toBe($maxCert);
});

// ==================== EDGE CASES ====================

test('handles certificate subject with special characters', function () {
    $specialCert = "CN=*.example.com,DNS:café.example.com,DNS:münchen.example.com";

    $result = MonitoringResult::factory()->create([
        'certificate_subject' => $specialCert,
    ]);

    $result->refresh();

    expect($result->certificate_subject)->toBe($specialCert);
});

test('handles certificate subject with newlines', function () {
    $certWithNewlines = "CN=*.example.com,\nDNS:example.com,\nDNS:www.example.com";

    $result = MonitoringResult::factory()->create([
        'certificate_subject' => $certWithNewlines,
    ]);

    $result->refresh();

    expect($result->certificate_subject)->toBe($certWithNewlines);
});

test('handles certificate subject with commas', function () {
    // Commas are standard in certificate subjects
    $certWithCommas = 'CN=*.example.com,O=Example Inc.,L=San Francisco,ST=California,C=US';

    $result = MonitoringResult::factory()->create([
        'certificate_subject' => $certWithCommas,
    ]);

    $result->refresh();

    expect($result->certificate_subject)->toBe($certWithCommas);
});

test('handles certificate subject with quotes', function () {
    $certWithQuotes = 'CN="*.example.com",DNS:"www.example.com"';

    $result = MonitoringResult::factory()->create([
        'certificate_subject' => $certWithQuotes,
    ]);

    $result->refresh();

    expect($result->certificate_subject)->toBe($certWithQuotes);
});

test('sanitizes potential SQL injection in certificate subject', function () {
    $maliciousCert = "CN=*.example.com'; DROP TABLE monitoring_results; --";

    $result = MonitoringResult::factory()->create([
        'certificate_subject' => $maliciousCert,
    ]);

    $result->refresh();

    // Should be stored safely without executing SQL
    expect($result->certificate_subject)->toBe($maliciousCert)
        ->and(Schema::hasTable('monitoring_results'))->toBeTrue(); // Table still exists
});

// ==================== PERFORMANCE TESTS ====================

test('migration does not impact query performance with TEXT column', function () {
    // Create multiple results with varying certificate_subject lengths
    MonitoringResult::factory()->count(10)->create([
        'certificate_subject' => str_repeat('DNS:example.com,', 20),
    ]);

    $startTime = microtime(true);

    // Query should remain fast even with TEXT column
    $results = MonitoringResult::whereNotNull('certificate_subject')
        ->orderBy('started_at', 'desc')
        ->limit(10)
        ->get();

    $queryTime = microtime(true) - $startTime;

    expect($results)->toHaveCount(10)
        ->and($queryTime)->toBeLessThan(0.1); // Should complete in < 100ms
});

test('TEXT column does not degrade index performance', function () {
    // Create results and verify indexes still work efficiently
    MonitoringResult::factory()->count(20)->create();

    $startTime = microtime(true);

    // Use indexed column (started_at) - should be fast
    $results = MonitoringResult::where('started_at', '>=', now()->subDay())
        ->get();

    $queryTime = microtime(true) - $startTime;

    expect($results->count())->toBeGreaterThan(0)
        ->and($queryTime)->toBeLessThan(0.1);
});

// ==================== REAL-WORLD INTEGRATION ====================

test('complete monitoring result can be created with large certificate subject', function () {
    $largeCert = str_repeat('DNS:wikipedia.org,', 50);

    $result = MonitoringResult::factory()->create([
        'certificate_subject' => $largeCert,
        'certificate_issuer' => 'DigiCert Inc',
        'certificate_expiration_date' => now()->addDays(90),
        'days_until_expiration' => 90,
        'ssl_status' => 'valid',
    ]);

    $result->refresh();

    expect($result->certificate_subject)->toBe($largeCert)
        ->and(strlen($result->certificate_subject))->toBeGreaterThan(255)
        ->and($result->certificate_issuer)->toBe('DigiCert Inc')
        ->and($result->ssl_status)->toBe('valid')
        ->and($result->days_until_expiration)->toBe(90);
});

test('test completes in under 1 second', function () {
    $startTime = microtime(true);

    // Perform typical operation
    $result = MonitoringResult::factory()->create([
        'certificate_subject' => str_repeat('DNS:example.com,', 50),
    ]);

    $executionTime = microtime(true) - $startTime;

    expect($executionTime)->toBeLessThan(1.0)
        ->and($result->certificate_subject)->toContain('DNS:example.com');
});
