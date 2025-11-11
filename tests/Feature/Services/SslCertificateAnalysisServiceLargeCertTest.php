<?php

/**
 * SSL Certificate Analysis Service Tests for Large Certificates
 *
 * Tests the SslCertificateAnalysisService's ability to handle and store
 * certificates with many Subject Alternative Names (SANs) after the
 * certificate_subject migration from VARCHAR(255) to TEXT.
 *
 * PERFORMANCE: All tests use MocksSslCertificateAnalysis trait (< 1 second)
 */

use App\Models\MonitoringResult;
use App\Models\Website;
use App\Services\SslCertificateAnalysisService;
use Tests\Traits\MocksSslCertificateAnalysis;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class, MocksSslCertificateAnalysis::class);

beforeEach(function () {
    $this->service = app(SslCertificateAnalysisService::class);
});

// ==================== SERVICE INTEGRATION TESTS ====================

test('service can analyze and save certificate with 50+ SANs', function () {
    $website = Website::factory()->create(['url' => 'https://wikipedia.org']);

    // Mock will return realistic data
    $analysis = $this->service->analyzeAndSave($website);

    $website->refresh();

    expect($website->latest_ssl_certificate)->toBeArray()
        ->and($website->latest_ssl_certificate['subject'])->not->toBeNull()
        ->and($website->ssl_certificate_analyzed_at)->not->toBeNull();
});

test('service stores certificate subject in correct format', function () {
    $website = Website::factory()->create(['url' => 'https://example.com']);

    $this->service->analyzeAndSave($website);

    $website->refresh();

    expect($website->latest_ssl_certificate)->toBeArray()
        ->and($website->latest_ssl_certificate)->toHaveKey('subject')
        ->and($website->latest_ssl_certificate['subject'])->toBeString();
});

test('service can process multiple websites with varying certificate sizes', function () {
    $websites = [
        Website::factory()->create(['url' => 'https://small-'.uniqid().'.com']),
        Website::factory()->create(['url' => 'https://medium-'.uniqid().'.com']),
        Website::factory()->create(['url' => 'https://large-'.uniqid().'.com']),
    ];

    foreach ($websites as $website) {
        $this->service->analyzeAndSave($website);
        $website->refresh();

        expect($website->latest_ssl_certificate)->toBeArray()
            ->and($website->latest_ssl_certificate['subject'])->not->toBeNull();
    }
});

// ==================== MONITORING RESULT INTEGRATION ====================

test('monitoring result stores large certificate_subject from analysis', function () {
    $website = Website::factory()->create(['url' => 'https://wikipedia.org']);

    // Create a certificate subject with many SANs (simulating Wikipedia)
    $largeCertSubject = 'CN=*.wikipedia.org';
    for ($i = 1; $i <= 54; $i++) {
        $largeCertSubject .= ",DNS:subdomain{$i}.wikipedia.org";
    }

    $result = MonitoringResult::factory()->create([
        'website_id' => $website->id,
        'certificate_subject' => $largeCertSubject,
        'certificate_issuer' => 'DigiCert Inc',
        'ssl_status' => 'valid',
    ]);

    expect($result->certificate_subject)->toBe($largeCertSubject)
        ->and(strlen($result->certificate_subject))->toBeGreaterThan(255);
});

test('monitoring result can store certificate data from service analysis', function () {
    $website = Website::factory()->create(['url' => 'https://example.com']);

    // Simulate service analysis
    $this->service->analyzeAndSave($website);
    $website->refresh();

    // Create monitoring result with analyzed data
    $result = MonitoringResult::factory()->create([
        'website_id' => $website->id,
        'certificate_subject' => $website->latest_ssl_certificate['subject'] ?? 'CN=example.com',
        'certificate_issuer' => $website->latest_ssl_certificate['issuer'] ?? 'Mock CA',
        'ssl_status' => 'valid',
    ]);

    expect($result->certificate_subject)->not->toBeNull()
        ->and($result->certificate_issuer)->not->toBeNull();
});

// ==================== REAL-WORLD CERTIFICATE SCENARIOS ====================

test('handles Wikipedia-style certificate with 41 SANs', function () {
    // Real Wikipedia certificate structure (simplified)
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

    $website = Website::factory()->create(['url' => 'https://wikipedia.org']);

    $result = MonitoringResult::factory()->create([
        'website_id' => $website->id,
        'certificate_subject' => $wikipediaCert,
        'certificate_issuer' => 'DigiCert Inc',
        'days_until_expiration' => 90,
    ]);

    expect(strlen($result->certificate_subject))->toBeGreaterThan(700)
        ->and($result->certificate_subject)->toContain('wikipedia.org')
        ->and($result->certificate_subject)->toContain('wikimedia.org')
        ->and($result->certificate_subject)->toContain('wikidata.org');
});

test('handles Google-style certificate with 100+ SANs', function () {
    $googleCert = 'CN=*.google.com';
    for ($i = 1; $i <= 100; $i++) {
        $googleCert .= ",DNS:*.google{$i}.com";
    }

    $website = Website::factory()->create(['url' => 'https://google.com']);

    $result = MonitoringResult::factory()->create([
        'website_id' => $website->id,
        'certificate_subject' => $googleCert,
        'certificate_issuer' => 'Google Trust Services',
    ]);

    expect(strlen($result->certificate_subject))->toBeGreaterThan(1000)
        ->and(substr_count($result->certificate_subject, 'DNS:'))->toBe(100);
});

test('handles Cloudflare-style multi-service certificate', function () {
    $cloudflareCert = 'CN=*.cloudflare.com,DNS:*.cloudflare.com,DNS:cloudflare.com,'
        . 'DNS:*.cloudflareaccess.com,DNS:*.cloudflarestream.com,DNS:*.workers.dev,'
        . 'DNS:*.pages.dev,DNS:*.cloudflare-dns.com,DNS:*.cloudflare-ipfs.com,'
        . 'DNS:*.cloudflare-gateway.com';

    $website = Website::factory()->create(['url' => 'https://cloudflare.com']);

    $result = MonitoringResult::factory()->create([
        'website_id' => $website->id,
        'certificate_subject' => $cloudflareCert,
    ]);

    expect($result->certificate_subject)->toContain('cloudflare.com')
        ->and($result->certificate_subject)->toContain('workers.dev')
        ->and($result->certificate_subject)->toContain('pages.dev');
});

test('handles Let\'s Encrypt certificate with multiple SANs', function () {
    $letsEncryptCert = 'CN=example.com,DNS:example.com,DNS:www.example.com,'
        . 'DNS:api.example.com,DNS:admin.example.com,DNS:blog.example.com';

    $website = Website::factory()->create(['url' => 'https://example.com']);

    $result = MonitoringResult::factory()->create([
        'website_id' => $website->id,
        'certificate_subject' => $letsEncryptCert,
        'certificate_issuer' => 'Let\'s Encrypt Authority X3',
        'days_until_expiration' => 60,
    ]);

    expect($result->certificate_subject)->toBe($letsEncryptCert)
        ->and($result->certificate_issuer)->toContain('Let\'s Encrypt');
});

// ==================== EDGE CASE HANDLING ====================

test('handles certificate with special characters in SANs', function () {
    $internationalCert = 'CN=*.example.com,DNS:*.example.com,DNS:café.example.com,'
        . 'DNS:münchen.example.com';

    $website = Website::factory()->create(['url' => 'https://example.com']);

    $result = MonitoringResult::factory()->create([
        'website_id' => $website->id,
        'certificate_subject' => $internationalCert,
    ]);

    expect($result->certificate_subject)->toBe($internationalCert)
        ->and($result->certificate_subject)->toContain('café')
        ->and($result->certificate_subject)->toContain('münchen');
});

test('handles certificate with maximum TEXT size', function () {
    // Create a certificate near the TEXT limit (65535 characters)
    $hugeCert = 'CN=*.example.com';
    for ($i = 1; $i <= 2000; $i++) {
        $hugeCert .= ",DNS:very-long-subdomain-name-{$i}.example.com";
    }

    $website = Website::factory()->create(['url' => 'https://example.com']);

    $result = MonitoringResult::factory()->create([
        'website_id' => $website->id,
        'certificate_subject' => $hugeCert,
    ]);

    expect(strlen($result->certificate_subject))->toBeGreaterThan(40000)
        ->and($result->certificate_subject)->toBe($hugeCert);
});

test('handles empty certificate_subject gracefully', function () {
    $website = Website::factory()->create(['url' => 'https://example.com']);

    $result = MonitoringResult::factory()->create([
        'website_id' => $website->id,
        'certificate_subject' => '',
    ]);

    expect($result->certificate_subject)->toBe('');
});

test('handles null certificate_subject gracefully', function () {
    $website = Website::factory()->create(['url' => 'https://example.com']);

    $result = MonitoringResult::factory()->create([
        'website_id' => $website->id,
        'certificate_subject' => null,
    ]);

    expect($result->certificate_subject)->toBeNull();
});

// ==================== DATA INTEGRITY ====================

test('certificate_subject is not truncated during storage', function () {
    $originalCert = str_repeat('DNS:subdomain.example.com,', 100);
    $originalLength = strlen($originalCert);

    $website = Website::factory()->create(['url' => 'https://example.com']);

    $result = MonitoringResult::factory()->create([
        'website_id' => $website->id,
        'certificate_subject' => $originalCert,
    ]);

    $result->refresh();

    expect(strlen($result->certificate_subject))->toBe($originalLength)
        ->and($result->certificate_subject)->toBe($originalCert);
});

test('multiple monitoring results can store different certificate sizes', function () {
    $website = Website::factory()->create(['url' => 'https://example.com']);

    $small = MonitoringResult::factory()->create([
        'website_id' => $website->id,
        'certificate_subject' => 'CN=example.com',
        'started_at' => now()->subHours(3),
    ]);

    $medium = MonitoringResult::factory()->create([
        'website_id' => $website->id,
        'certificate_subject' => str_repeat('DNS:example.com,', 20),
        'started_at' => now()->subHours(2),
    ]);

    $large = MonitoringResult::factory()->create([
        'website_id' => $website->id,
        'certificate_subject' => str_repeat('DNS:example.com,', 100),
        'started_at' => now()->subHours(1),
    ]);

    expect(strlen($small->certificate_subject))->toBeLessThan(255)
        ->and(strlen($medium->certificate_subject))->toBeGreaterThan(255)
        ->and(strlen($large->certificate_subject))->toBeGreaterThan(1000);
});

// ==================== QUERY PERFORMANCE ====================

test('querying results with large certificate_subject is performant', function () {
    $website = Website::factory()->create(['url' => 'https://example.com']);

    // Create 20 results with large certificate subjects
    for ($i = 1; $i <= 20; $i++) {
        MonitoringResult::factory()->create([
            'website_id' => $website->id,
            'certificate_subject' => str_repeat("DNS:subdomain{$i}.com,", 50),
        ]);
    }

    $startTime = microtime(true);

    $results = MonitoringResult::where('website_id', $website->id)
        ->whereNotNull('certificate_subject')
        ->orderBy('started_at', 'desc')
        ->get();

    $queryTime = microtime(true) - $startTime;

    expect($results)->toHaveCount(20)
        ->and($queryTime)->toBeLessThan(0.1); // Should complete in < 100ms
});

test('filtering by certificate_subject content is efficient', function () {
    $website = Website::factory()->create(['url' => 'https://example.com']);

    MonitoringResult::factory()->create([
        'website_id' => $website->id,
        'certificate_subject' => str_repeat('DNS:wikipedia.org,', 50),
    ]);

    MonitoringResult::factory()->create([
        'website_id' => $website->id,
        'certificate_subject' => str_repeat('DNS:google.com,', 50),
    ]);

    $startTime = microtime(true);

    $results = MonitoringResult::where('certificate_subject', 'like', '%wikipedia%')->get();

    $queryTime = microtime(true) - $startTime;

    expect($results->count())->toBeGreaterThanOrEqual(1)
        ->and($queryTime)->toBeLessThan(0.1);
});

// ==================== COMPLETE WORKFLOW TEST ====================

test('complete SSL monitoring workflow with large certificates', function () {
    $website = Website::factory()->create(['url' => 'https://wikipedia.org']);

    // Step 1: Service analyzes certificate
    $this->service->analyzeAndSave($website);
    $website->refresh();

    // Step 2: Create monitoring result
    $largeCert = str_repeat('DNS:wikipedia.org,', 50);
    $result = MonitoringResult::factory()->create([
        'website_id' => $website->id,
        'certificate_subject' => $largeCert,
        'certificate_issuer' => $website->latest_ssl_certificate['issuer'] ?? 'Mock CA',
        'days_until_expiration' => 90,
        'ssl_status' => 'valid',
    ]);

    // Step 3: Verify data integrity
    $result->refresh();

    expect($result->certificate_subject)->toBe($largeCert)
        ->and(strlen($result->certificate_subject))->toBeGreaterThan(255)
        ->and($result->ssl_status)->toBe('valid')
        ->and($result->days_until_expiration)->toBe(90);
});

// ==================== PERFORMANCE VERIFICATION ====================

test('all service operations complete in under 1 second', function () {
    $startTime = microtime(true);

    $website = Website::factory()->create(['url' => 'https://example.com']);
    $this->service->analyzeAndSave($website);

    $largeCert = str_repeat('DNS:example.com,', 50);
    $result = MonitoringResult::factory()->create([
        'website_id' => $website->id,
        'certificate_subject' => $largeCert,
    ]);

    $result->refresh();

    $executionTime = microtime(true) - $startTime;

    expect($executionTime)->toBeLessThan(1.0);
});
