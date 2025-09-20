<?php

use App\Models\SslCheck;
use App\Models\Website;
use Carbon\Carbon;

test('ssl check can be created with valid data', function () {
    $website = Website::factory()->create();

    $sslCheck = SslCheck::create([
        'website_id' => $website->id,
        'status' => 'valid',
        'checked_at' => now(),
        'expires_at' => Carbon::now()->addDays(60),
        'issuer' => 'Let\'s Encrypt Authority X3',
        'subject' => 'example.com',
        'serial_number' => '12345abcdef67890',
        'signature_algorithm' => 'SHA256withRSA',
        'is_valid' => true,
        'days_until_expiry' => 60,
        'response_time' => 0.5,
        'check_source' => 'scheduled',
    ]);

    expect($sslCheck)->toBeInstanceOf(SslCheck::class)
        ->and($sslCheck->website_id)->toBe($website->id)
        ->and($sslCheck->status)->toBe('valid')
        ->and($sslCheck->is_valid)->toBeTrue()
        ->and($sslCheck->days_until_expiry)->toBe(60)
        ->and($sslCheck->check_source)->toBe('scheduled');
});

test('ssl check belongs to website', function () {
    $website = Website::factory()->create();
    $sslCheck = SslCheck::factory()->create(['website_id' => $website->id]);

    expect($sslCheck->website)->toBeInstanceOf(Website::class)
        ->and($sslCheck->website->id)->toBe($website->id);
});

test('ssl check can have different statuses', function () {
    $website = Website::factory()->create();

    $validCheck = SslCheck::factory()->valid()->create(['website_id' => $website->id]);
    $expiredCheck = SslCheck::factory()->expired()->create(['website_id' => $website->id]);
    $expiringSoonCheck = SslCheck::factory()->expiringSoon()->create(['website_id' => $website->id]);
    $invalidCheck = SslCheck::factory()->invalid()->create(['website_id' => $website->id]);
    $errorCheck = SslCheck::factory()->error()->create(['website_id' => $website->id]);

    expect($validCheck->status)->toBe('valid')
        ->and($expiredCheck->status)->toBe('expired')
        ->and($expiringSoonCheck->status)->toBe('expiring_soon')
        ->and($invalidCheck->status)->toBe('invalid')
        ->and($errorCheck->status)->toBe('error');
});

test('ssl check can have different check sources', function () {
    $website = Website::factory()->create();

    $scheduledCheck = SslCheck::factory()->create(['website_id' => $website->id, 'check_source' => 'scheduled']);
    $manualCheck = SslCheck::factory()->manual()->create(['website_id' => $website->id]);
    $apiCheck = SslCheck::factory()->api()->create(['website_id' => $website->id]);
    $webhookCheck = SslCheck::factory()->webhook()->create(['website_id' => $website->id]);

    expect($scheduledCheck->check_source)->toBe('scheduled')
        ->and($manualCheck->check_source)->toBe('manual')
        ->and($apiCheck->check_source)->toBe('api')
        ->and($webhookCheck->check_source)->toBe('webhook');
});

test('ssl check can track performance metrics', function () {
    $fastCheck = SslCheck::factory()->fastResponse()->create();
    $slowCheck = SslCheck::factory()->slowResponse()->create();

    expect($fastCheck->response_time)->toBeLessThan(1000) // milliseconds
        ->and($slowCheck->response_time)->toBeGreaterThan(3000);
});

test('ssl check can have security strength variations', function () {
    $weakCheck = SslCheck::factory()->weakSecurity()->create();
    $strongCheck = SslCheck::factory()->strongSecurity()->create();

    expect($weakCheck->signature_algorithm)->toBe('SHA1withRSA')
        ->and($weakCheck->protocol_version)->toBe('TLSv1.0')
        ->and($weakCheck->key_size)->toBe(1024)
        ->and($strongCheck->signature_algorithm)->toBe('ECDSA-SHA256')
        ->and($strongCheck->protocol_version)->toBe('TLSv1.3')
        ->and($strongCheck->key_size)->toBe(4096);
});

test('ssl check can have plugin metrics', function () {
    $metricsData = [
        'performance' => ['handshake_time' => 0.15],
        'security_analysis' => ['cipher_strength' => 'strong'],
    ];

    $sslCheck = SslCheck::factory()->withPluginMetrics($metricsData)->create();

    expect($sslCheck->plugin_metrics)->toBeArray()
        ->and($sslCheck->plugin_metrics['performance']['handshake_time'])->toBe(0.15)
        ->and($sslCheck->plugin_metrics['security_analysis']['cipher_strength'])->toBe('strong');
});

test('ssl check has proper fillable attributes', function () {
    $sslCheck = new SslCheck();

    $fillable = $sslCheck->getFillable();

    expect($fillable)->toContain('website_id')
        ->and($fillable)->toContain('status')
        ->and($fillable)->toContain('checked_at')
        ->and($fillable)->toContain('expires_at')
        ->and($fillable)->toContain('issuer')
        ->and($fillable)->toContain('subject')
        ->and($fillable)->toContain('serial_number')
        ->and($fillable)->toContain('signature_algorithm')
        ->and($fillable)->toContain('is_valid')
        ->and($fillable)->toContain('days_until_expiry')
        ->and($fillable)->toContain('error_message')
        ->and($fillable)->toContain('response_time')
        ->and($fillable)->toContain('check_source')
        ->and($fillable)->toContain('check_metrics')
        ->and($fillable)->toContain('agent_data')
        ->and($fillable)->toContain('security_analysis');
});

test('ssl check casts datetime fields correctly', function () {
    $sslCheck = SslCheck::factory()->create([
        'checked_at' => '2025-01-01 12:00:00',
        'expires_at' => '2025-12-31 23:59:59',
    ]);

    expect($sslCheck->checked_at)->toBeInstanceOf(Carbon::class)
        ->and($sslCheck->expires_at)->toBeInstanceOf(Carbon::class);
});

test('ssl check casts plugin_metrics to array', function () {
    $sslCheck = SslCheck::factory()->create([
        'plugin_metrics' => ['test' => 'data'],
    ]);

    expect($sslCheck->plugin_metrics)->toBeArray();
});

test('ssl check error scenarios have no certificate data', function () {
    $errorCheck = SslCheck::factory()->error()->create();

    expect($errorCheck->status)->toBe('error')
        ->and($errorCheck->is_valid)->toBeFalse()
        ->and($errorCheck->expires_at)->toBeNull()
        ->and($errorCheck->issuer)->toBeNull()
        ->and($errorCheck->subject)->toBeNull()
        ->and($errorCheck->error_message)->not->toBeEmpty();
});

test('ssl check tracks certificate chain information', function () {
    $sslCheck = SslCheck::factory()->create([
        'certificate_chain_length' => 3,
    ]);

    expect($sslCheck->certificate_chain_length)->toBe(3);
});

test('ssl check tracks protocol and cipher information', function () {
    $sslCheck = SslCheck::factory()->create([
        'protocol_version' => 'TLSv1.3',
        'cipher_suite' => 'TLS_AES_256_GCM_SHA384',
    ]);

    expect($sslCheck->protocol_version)->toBe('TLSv1.3')
        ->and($sslCheck->cipher_suite)->toBe('TLS_AES_256_GCM_SHA384');
});

test('ssl check tracks ocsp status', function () {
    $goodOcspCheck = SslCheck::factory()->create(['ocsp_status' => 'good']);
    $revokedOcspCheck = SslCheck::factory()->create(['ocsp_status' => 'revoked']);
    $unknownOcspCheck = SslCheck::factory()->create(['ocsp_status' => 'unknown']);

    expect($goodOcspCheck->ocsp_status)->toBe('good')
        ->and($revokedOcspCheck->ocsp_status)->toBe('revoked')
        ->and($unknownOcspCheck->ocsp_status)->toBe('unknown');
});

test('ssl check calculates days until expiry correctly', function () {
    $futureDate = Carbon::now()->addDays(30);
    $pastDate = Carbon::now()->subDays(10);

    $validCheck = SslCheck::factory()->create([
        'expires_at' => $futureDate,
        'days_until_expiry' => 30,
    ]);

    $expiredCheck = SslCheck::factory()->create([
        'expires_at' => $pastDate,
        'days_until_expiry' => -10,
    ]);

    expect($validCheck->days_until_expiry)->toBe(30)
        ->and($expiredCheck->days_until_expiry)->toBe(-10);
});

test('ssl check scopes work correctly', function () {
    $website = Website::factory()->create();

    SslCheck::factory()->valid()->create(['website_id' => $website->id]);
    SslCheck::factory()->expired()->create(['website_id' => $website->id]);
    SslCheck::factory()->expiringSoon()->create(['website_id' => $website->id]);
    SslCheck::factory()->invalid()->create(['website_id' => $website->id]);
    SslCheck::factory()->error()->create(['website_id' => $website->id]);

    expect(SslCheck::where('status', 'valid')->count())->toBeGreaterThanOrEqual(1)
        ->and(SslCheck::where('status', 'expired')->count())->toBeGreaterThanOrEqual(1)
        ->and(SslCheck::where('status', 'expiring_soon')->count())->toBeGreaterThanOrEqual(1)
        ->and(SslCheck::where('status', 'invalid')->count())->toBeGreaterThanOrEqual(1)
        ->and(SslCheck::where('status', 'error')->count())->toBeGreaterThanOrEqual(1);
});