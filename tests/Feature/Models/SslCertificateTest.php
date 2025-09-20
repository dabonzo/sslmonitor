<?php

use App\Models\SslCertificate;
use App\Models\Website;
use App\Models\User;
use Carbon\Carbon;

test('ssl certificate can be created with valid data', function () {
    $website = Website::factory()->create();

    $certificate = SslCertificate::create([
        'website_id' => $website->id,
        'issuer' => 'Let\'s Encrypt Authority X3',
        'expires_at' => Carbon::now()->addDays(90),
        'subject' => 'example.com',
        'serial_number' => '12345abcdef67890',
        'signature_algorithm' => 'SHA256withRSA',
        'certificate_hash' => hash('sha256', 'test-certificate-data'),
        'is_valid' => true,
        'is_expired' => false,
        'is_self_signed' => false,
    ]);

    expect($certificate)->toBeInstanceOf(SslCertificate::class)
        ->and($certificate->website_id)->toBe($website->id)
        ->and($certificate->issuer)->toBe('Let\'s Encrypt Authority X3')
        ->and($certificate->subject)->toBe('example.com')
        ->and($certificate->is_valid)->toBeTrue()
        ->and($certificate->is_expired)->toBeFalse()
        ->and($certificate->is_self_signed)->toBeFalse();
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
    $expiredCertificate = SslCertificate::factory()->expired()->create();
    $validCertificate = SslCertificate::factory()->create();

    expect($expiredCertificate->isExpired())->toBeTrue()
        ->and($validCertificate->isExpired())->toBeFalse();
});

test('ssl certificate determines if it is expiring soon', function () {
    $expiringSoonCertificate = SslCertificate::factory()->expiringSoon()->create();
    $validCertificate = SslCertificate::factory()->create();

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
    $expiredCert = SslCertificate::factory()->expired()->create();
    $expiringSoonCert = SslCertificate::factory()->expiringSoon()->create();
    $validCert = SslCertificate::factory()->create();
    $invalidCert = SslCertificate::factory()->invalid()->create();

    expect($expiredCert->getStatus())->toBe('expired')
        ->and($expiringSoonCert->getStatus())->toBe('expiring_soon')
        ->and($validCert->getStatus())->toBe('valid')
        ->and($invalidCert->getStatus())->toBe('invalid');
});

test('ssl certificate has proper fillable attributes', function () {
    $certificate = new SslCertificate();

    $fillable = $certificate->getFillable();

    expect($fillable)->toContain('website_id')
        ->and($fillable)->toContain('issuer')
        ->and($fillable)->toContain('expires_at')
        ->and($fillable)->toContain('subject')
        ->and($fillable)->toContain('serial_number')
        ->and($fillable)->toContain('signature_algorithm')
        ->and($fillable)->toContain('certificate_hash')
        ->and($fillable)->toContain('is_valid')
        ->and($fillable)->toContain('is_expired')
        ->and($fillable)->toContain('is_self_signed')
        ->and($fillable)->toContain('days_until_expiry')
        ->and($fillable)->toContain('certificate_chain')
        ->and($fillable)->toContain('security_metrics')
        ->and($fillable)->toContain('plugin_analysis');
});

test('ssl certificate casts expires_at to datetime', function () {
    $certificate = SslCertificate::factory()->create([
        'expires_at' => '2025-12-31 23:59:59'
    ]);

    expect($certificate->expires_at)->toBeInstanceOf(Carbon::class);
});

test('ssl certificate casts json fields correctly', function () {
    $certificate = SslCertificate::factory()->create([
        'certificate_chain' => ['cert' => 'data'],
        'security_metrics' => ['key_size' => 2048],
        'plugin_analysis' => ['grade' => 'A+'],
    ]);

    expect($certificate->certificate_chain)->toBeArray()
        ->and($certificate->security_metrics)->toBeArray()
        ->and($certificate->plugin_analysis)->toBeArray();
});

test('ssl certificate scopes work correctly', function () {
    // Create test certificates
    SslCertificate::factory()->create(['is_valid' => true, 'is_expired' => false]);
    SslCertificate::factory()->invalid()->create();
    SslCertificate::factory()->expired()->create();
    SslCertificate::factory()->expiringSoon()->create();

    expect(SslCertificate::valid()->count())->toBeGreaterThanOrEqual(2)
        ->and(SslCertificate::invalid()->count())->toBeGreaterThanOrEqual(1)
        ->and(SslCertificate::expired()->count())->toBeGreaterThanOrEqual(1)
        ->and(SslCertificate::expiringSoon()->count())->toBeGreaterThanOrEqual(1);
});

test('ssl certificate can have plugin analysis data', function () {
    $analysisData = [
        'ssl_labs_grade' => 'A+',
        'security_headers' => ['hsts' => true, 'csp' => true],
        'vulnerabilities' => [],
    ];

    $certificate = SslCertificate::factory()->withPluginAnalysis($analysisData)->create();

    expect($certificate->getPluginAnalysis('ssl_labs_grade'))->toBe('A+')
        ->and($certificate->getPluginAnalysis('security_headers'))->toBeArray()
        ->and($certificate->getPluginAnalysis('security_headers', 'hsts'))->toBeTrue();
});

test('ssl certificate can update plugin analysis', function () {
    $certificate = SslCertificate::factory()->create();

    $certificate->setPluginAnalysis('test_plugin', ['result' => 'passed']);
    $certificate->save();

    expect($certificate->getPluginAnalysis('test_plugin'))->toBe(['result' => 'passed'])
        ->and($certificate->getPluginAnalysis('test_plugin', 'result'))->toBe('passed');
});

test('ssl certificate has security strength variants', function () {
    $weakCert = SslCertificate::factory()->weakSecurity()->create();
    $strongCert = SslCertificate::factory()->strongSecurity()->create();

    expect($weakCert->signature_algorithm)->toBe('SHA1withRSA')
        ->and($weakCert->security_metrics['key_size'])->toBe(1024)
        ->and($strongCert->signature_algorithm)->toBe('ECDSA-SHA256')
        ->and($strongCert->security_metrics['key_size'])->toBe(4096);
});

test('ssl certificate has certificate authority variants', function () {
    $letsEncryptCert = SslCertificate::factory()->letsEncrypt()->create();
    $commercialCert = SslCertificate::factory()->commercial()->create();
    $selfSignedCert = SslCertificate::factory()->selfSigned()->create();

    expect($letsEncryptCert->issuer)->toBe('Let\'s Encrypt Authority X3')
        ->and($letsEncryptCert->days_until_expiry)->toBe(90)
        ->and($commercialCert->days_until_expiry)->toBe(365)
        ->and($selfSignedCert->is_self_signed)->toBeTrue()
        ->and($selfSignedCert->is_valid)->toBeFalse();
});

test('ssl certificate hash is properly stored', function () {
    $hash = hash('sha256', 'certificate-data');
    $certificate = SslCertificate::factory()->create([
        'certificate_hash' => $hash,
    ]);

    expect($certificate->certificate_hash)->toBe($hash)
        ->and(strlen($certificate->certificate_hash))->toBe(64); // SHA256 length
});

test('ssl certificate tracks expiry status', function () {
    $certificate = SslCertificate::factory()->create([
        'expires_at' => Carbon::now()->addDays(30),
        'is_expired' => false,
    ]);

    expect($certificate->is_expired)->toBeFalse();

    // Update to expired
    $certificate->update([
        'expires_at' => Carbon::now()->subDay(),
        'is_expired' => true,
    ]);

    expect($certificate->is_expired)->toBeTrue();
});