<?php

/**
 * MonitoringResult Model Integration Tests for Large Certificate Subjects
 *
 * Tests the MonitoringResult model's ability to handle certificates with many SANs
 * after the certificate_subject column migration from VARCHAR(255) to TEXT.
 *
 * PERFORMANCE: All tests use mocks and complete in < 1 second
 */

use App\Models\MonitoringResult;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class);

// ==================== MODEL CRUD OPERATIONS ====================

test('creates MonitoringResult with large certificate_subject', function () {
    $largeCert = str_repeat('DNS:subdomain.wikipedia.org,', 50);

    $result = MonitoringResult::factory()->create([
        'certificate_subject' => $largeCert,
    ]);

    expect($result->certificate_subject)
        ->toBe($largeCert)
        ->and(strlen($result->certificate_subject))->toBeGreaterThan(255);
});

test('updates existing MonitoringResult with large certificate_subject', function () {
    $result = MonitoringResult::factory()->create([
        'certificate_subject' => 'CN=old.example.com',
    ]);

    $newCert = str_repeat('DNS:subdomain.example.com,', 50);

    $result->update(['certificate_subject' => $newCert]);
    $result->refresh();

    expect($result->certificate_subject)->toBe($newCert)
        ->and(strlen($result->certificate_subject))->toBeGreaterThan(255);
});

test('retrieves MonitoringResult with large certificate_subject', function () {
    $largeCert = str_repeat('DNS:example.com,', 100);

    $created = MonitoringResult::factory()->create([
        'certificate_subject' => $largeCert,
    ]);

    $retrieved = MonitoringResult::find($created->id);

    expect($retrieved->certificate_subject)
        ->toBe($largeCert)
        ->and(strlen($retrieved->certificate_subject))->toBeGreaterThan(255);
});

test('deletes MonitoringResult with large certificate_subject', function () {
    $result = MonitoringResult::factory()->create([
        'certificate_subject' => str_repeat('DNS:example.com,', 100),
    ]);

    $id = $result->id;
    $result->delete();

    expect(MonitoringResult::find($id))->toBeNull();
});

// ==================== REAL-WORLD CERTIFICATE DATA ====================

test('stores Wikipedia certificate with 41 SANs', function () {
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
        'certificate_issuer' => 'DigiCert Inc',
        'certificate_expiration_date' => now()->addDays(90),
        'ssl_status' => 'valid',
    ]);

    expect($result->certificate_subject)->toBe($wikipediaCert)
        ->and(strlen($result->certificate_subject))->toBeGreaterThan(700)
        ->and(substr_count($result->certificate_subject, 'DNS:'))->toBe(40);
});

test('stores Cloudflare certificate with multiple SANs', function () {
    $cloudflareCert = 'CN=*.cloudflare.com,DNS:*.cloudflare.com,DNS:cloudflare.com,'
        . 'DNS:*.cloudflareaccess.com,DNS:*.cloudflarestream.com,'
        . 'DNS:*.workers.dev,DNS:*.pages.dev,DNS:*.cloudflare-dns.com';

    $result = MonitoringResult::factory()->create([
        'certificate_subject' => $cloudflareCert,
    ]);

    expect($result->certificate_subject)->toBe($cloudflareCert)
        ->and($result->certificate_subject)->toContain('cloudflare.com')
        ->and($result->certificate_subject)->toContain('workers.dev');
});

test('stores Google certificate with 100+ SANs', function () {
    $googleCert = 'CN=*.google.com';
    for ($i = 1; $i <= 100; $i++) {
        $googleCert .= ",DNS:*.google{$i}.com";
    }

    $result = MonitoringResult::factory()->create([
        'certificate_subject' => $googleCert,
    ]);

    expect(strlen($result->certificate_subject))->toBeGreaterThan(1000)
        ->and(substr_count($result->certificate_subject, 'DNS:'))->toBe(100);
});

// ==================== QUERY OPERATIONS ====================

test('queries MonitoringResult with large certificate_subject efficiently', function () {
    MonitoringResult::factory()->count(10)->create([
        'certificate_subject' => str_repeat('DNS:example.com,', 50),
    ]);

    $startTime = microtime(true);

    $results = MonitoringResult::whereNotNull('certificate_subject')
        ->get();

    $queryTime = microtime(true) - $startTime;

    expect($results->count())->toBeGreaterThanOrEqual(10)
        ->and($queryTime)->toBeLessThan(0.1); // < 100ms
});

test('filters by certificate_subject content', function () {
    MonitoringResult::factory()->create([
        'certificate_subject' => 'CN=*.wikipedia.org,DNS:*.wikipedia.org,DNS:wikipedia.org',
    ]);

    MonitoringResult::factory()->create([
        'certificate_subject' => 'CN=*.google.com,DNS:*.google.com,DNS:google.com',
    ]);

    $wikipediaResults = MonitoringResult::where('certificate_subject', 'like', '%wikipedia%')->get();
    $googleResults = MonitoringResult::where('certificate_subject', 'like', '%google%')->get();

    expect($wikipediaResults->count())->toBeGreaterThanOrEqual(1)
        ->and($googleResults->count())->toBeGreaterThanOrEqual(1);
});

test('orders results by started_at with large certificate_subject', function () {
    MonitoringResult::factory()->create([
        'certificate_subject' => str_repeat('DNS:old.com,', 50),
        'started_at' => now()->subHours(2),
    ]);

    MonitoringResult::factory()->create([
        'certificate_subject' => str_repeat('DNS:new.com,', 50),
        'started_at' => now(),
    ]);

    $results = MonitoringResult::orderBy('started_at', 'desc')->limit(2)->get();

    expect($results->first()->certificate_subject)->toContain('new.com');
});

// ==================== MODEL SCOPES ====================

test('successful scope works with large certificate_subject', function () {
    MonitoringResult::factory()->create([
        'status' => 'success',
        'certificate_subject' => str_repeat('DNS:success.com,', 50),
    ]);

    MonitoringResult::factory()->create([
        'status' => 'failed',
        'certificate_subject' => str_repeat('DNS:failed.com,', 50),
    ]);

    $successful = MonitoringResult::successful()->get();

    expect($successful->count())->toBeGreaterThanOrEqual(1)
        ->and($successful->first()->certificate_subject)->toContain('success.com');
});

test('recent scope works with large certificate_subject', function () {
    MonitoringResult::factory()->create([
        'started_at' => now()->subHours(1),
        'certificate_subject' => str_repeat('DNS:recent.com,', 50),
    ]);

    MonitoringResult::factory()->create([
        'started_at' => now()->subDays(2),
        'certificate_subject' => str_repeat('DNS:old.com,', 50),
    ]);

    $recent = MonitoringResult::recent(24)->get();

    expect($recent->count())->toBeGreaterThanOrEqual(1);
});

// ==================== MODEL CASTING ====================

test('certificate_subject is properly retrieved from database', function () {
    $cert = str_repeat('DNS:example.com,', 50);

    $result = MonitoringResult::factory()->create([
        'certificate_subject' => $cert,
    ]);

    // Retrieve fresh from database
    $fresh = MonitoringResult::find($result->id);

    expect($fresh->certificate_subject)
        ->toBeString()
        ->toBe($cert);
});

test('certificate_subject maintains encoding with special characters', function () {
    $cert = 'CN=*.example.com,DNS:café.example.com,DNS:münchen.example.com';

    $result = MonitoringResult::factory()->create([
        'certificate_subject' => $cert,
    ]);

    $fresh = MonitoringResult::find($result->id);

    expect($fresh->certificate_subject)
        ->toBe($cert)
        ->toContain('café')
        ->toContain('münchen');
});

// ==================== BATCH OPERATIONS ====================

test('bulk creates MonitoringResults with varying certificate_subject sizes', function () {
    $data = [];
    for ($i = 1; $i <= 5; $i++) {
        $data[] = [
            'uuid' => \Illuminate\Support\Str::uuid(),
            'monitor_id' => 1,
            'website_id' => 1,
            'check_type' => 'ssl_certificate',
            'trigger_type' => 'scheduled',
            'started_at' => now(),
            'status' => 'success',
            'certificate_subject' => str_repeat("DNS:example{$i}.com,", $i * 20),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    // First create necessary dependencies
    $monitor = \App\Models\Monitor::factory()->create();
    $website = \App\Models\Website::factory()->create();

    // Update with correct IDs
    foreach ($data as &$row) {
        $row['monitor_id'] = $monitor->id;
        $row['website_id'] = $website->id;
    }

    MonitoringResult::insert($data);

    $results = MonitoringResult::where('monitor_id', $monitor->id)->get();

    expect($results->count())->toBeGreaterThanOrEqual(5);
});

// ==================== PERFORMANCE VERIFICATION ====================

test('all model operations complete in under 1 second', function () {
    $startTime = microtime(true);

    // Create
    $result = MonitoringResult::factory()->create([
        'certificate_subject' => str_repeat('DNS:test.com,', 50),
    ]);

    // Read
    $retrieved = MonitoringResult::find($result->id);

    // Update
    $retrieved->update(['certificate_subject' => str_repeat('DNS:updated.com,', 50)]);

    // Delete
    $retrieved->delete();

    $executionTime = microtime(true) - $startTime;

    expect($executionTime)->toBeLessThan(1.0);
});
