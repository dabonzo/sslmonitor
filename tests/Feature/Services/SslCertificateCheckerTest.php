<?php

use App\Models\Website;
use App\Services\SslCertificateChecker;
use Carbon\Carbon;
use Spatie\SslCertificate\SslCertificate;

test('ssl certificate checker can fetch certificate from valid URL', function () {
    $website = Website::factory()->create(['url' => 'https://github.com']);
    $checker = new SslCertificateChecker();

    $result = $checker->checkCertificate($website);

    expect($result)->toBeArray()
        ->and($result['status'])->toBeIn(['valid', 'expiring_soon', 'expired', 'invalid', 'error'])
        ->and($result['issuer'])->not->toBeEmpty()
        ->and($result['expires_at'])->toBeInstanceOf(Carbon::class)
        ->and($result['subject'])->toContain('github.com')
        ->and($result['days_until_expiry'])->toBeInt()
        ->and($result['error_message'])->toBeNull();

    // If the certificate is actually valid, check that it returns the expected values
    if ($result['status'] === 'valid') {
        expect($result['is_valid'])->toBeTrue();
    }
});

test('ssl certificate checker handles network errors gracefully', function () {
    $website = Website::factory()->create(['url' => 'https://non-existent-domain-12345.com']);
    $checker = new SslCertificateChecker();

    $result = $checker->checkCertificate($website);

    expect($result)->toBeArray()
        ->and($result['status'])->toBe('error')
        ->and($result['is_valid'])->toBeFalse()
        ->and($result['error_message'])->not->toBeEmpty()
        ->and($result['expires_at'])->toBeNull()
        ->and($result['days_until_expiry'])->toBeNull();
});

test('ssl certificate checker can determine if certificate is expiring soon', function () {
    $checker = new SslCertificateChecker();

    // Mock a certificate that expires in 5 days
    $expirationTime = Carbon::now()->addDays(5)->timestamp;
    $mockCert = Mockery::mock(SslCertificate::class);
    $mockCert->shouldReceive('getRawCertificateFields')->andReturn(['validTo_time_t' => $expirationTime]);
    $mockCert->shouldReceive('getIssuer')->andReturn('Let\'s Encrypt');
    $mockCert->shouldReceive('getDomain')->andReturn('github.com');
    $mockCert->shouldReceive('getSerialNumber')->andReturn('12345ABC');
    $mockCert->shouldReceive('getSignatureAlgorithm')->andReturn('SHA256withRSA');
    $mockCert->shouldReceive('isValid')->andReturn(true);

    $result = $checker->parseCertificateData($mockCert);

    expect($result['status'])->toBe('expiring_soon')
        ->and($result['days_until_expiry'])->toBeGreaterThan(0)
        ->and($result['days_until_expiry'])->toBeLessThanOrEqual(14)
        ->and($result['is_valid'])->toBeTrue();
});

test('ssl certificate checker can determine if certificate is expired', function () {
    $checker = new SslCertificateChecker();

    // Mock an expired certificate
    $expirationTime = Carbon::now()->subDays(5)->timestamp;
    $mockCert = Mockery::mock(SslCertificate::class);
    $mockCert->shouldReceive('getRawCertificateFields')->andReturn(['validTo_time_t' => $expirationTime]);
    $mockCert->shouldReceive('getIssuer')->andReturn('Let\'s Encrypt');
    $mockCert->shouldReceive('getDomain')->andReturn('expired.example.com');
    $mockCert->shouldReceive('getSerialNumber')->andReturn('EXPIRED123');
    $mockCert->shouldReceive('getSignatureAlgorithm')->andReturn('SHA256withRSA');
    $mockCert->shouldReceive('isValid')->andReturn(false);

    $result = $checker->parseCertificateData($mockCert);

    expect($result['status'])->toBe('expired')
        ->and($result['days_until_expiry'])->toBe(-5)
        ->and($result['is_valid'])->toBeFalse();
});

test('ssl certificate checker can determine if certificate is invalid', function () {
    $checker = new SslCertificateChecker();

    // Mock an invalid certificate (not expired but invalid)
    $expirationTime = Carbon::now()->addDays(30)->timestamp;
    $mockCert = Mockery::mock(SslCertificate::class);
    $mockCert->shouldReceive('getRawCertificateFields')->andReturn(['validTo_time_t' => $expirationTime]);
    $mockCert->shouldReceive('getIssuer')->andReturn('Unknown CA');
    $mockCert->shouldReceive('getDomain')->andReturn('invalid.example.com');
    $mockCert->shouldReceive('getSerialNumber')->andReturn('INVALID123');
    $mockCert->shouldReceive('getSignatureAlgorithm')->andReturn('SHA1withRSA');
    $mockCert->shouldReceive('isValid')->andReturn(false);

    $result = $checker->parseCertificateData($mockCert);

    expect($result['status'])->toBe('invalid')
        ->and($result['days_until_expiry'])->toBeGreaterThan(14)
        ->and($result['is_valid'])->toBeFalse();
});

test('ssl certificate checker returns valid status for good certificate', function () {
    $checker = new SslCertificateChecker();

    // Mock a valid certificate
    $expirationTime = Carbon::now()->addDays(90)->timestamp;
    $mockCert = Mockery::mock(SslCertificate::class);
    $mockCert->shouldReceive('getRawCertificateFields')->andReturn(['validTo_time_t' => $expirationTime]);
    $mockCert->shouldReceive('getIssuer')->andReturn('Let\'s Encrypt');
    $mockCert->shouldReceive('getDomain')->andReturn('valid.example.com');
    $mockCert->shouldReceive('getSerialNumber')->andReturn('VALID123');
    $mockCert->shouldReceive('getSignatureAlgorithm')->andReturn('SHA256withRSA');
    $mockCert->shouldReceive('isValid')->andReturn(true);

    $result = $checker->parseCertificateData($mockCert);

    expect($result['status'])->toBe('valid')
        ->and($result['days_until_expiry'])->toBeGreaterThan(14)
        ->and($result['is_valid'])->toBeTrue()
        ->and($result['error_message'])->toBeNull();
});

test('ssl certificate checker handles timeout errors', function () {
    $website = Website::factory()->create(['url' => 'https://10.255.255.1']); // Non-routable IP that will timeout
    $checker = new SslCertificateChecker();

    $result = $checker->checkCertificate($website, 1); // 1 second timeout

    expect($result['status'])->toBe('error')
        ->and($result['is_valid'])->toBeFalse()
        ->and($result['error_message'])->not->toBeEmpty();
});

test('ssl certificate checker can parse certificate data correctly', function () {
    $checker = new SslCertificateChecker();

    $expirationTime = Carbon::parse('2025-12-31 23:59:59')->timestamp;
    $mockCert = Mockery::mock(SslCertificate::class);
    $mockCert->shouldReceive('getRawCertificateFields')->andReturn(['validTo_time_t' => $expirationTime]);
    $mockCert->shouldReceive('getIssuer')->andReturn('DigiCert Inc');
    $mockCert->shouldReceive('getDomain')->andReturn('example.com');
    $mockCert->shouldReceive('getSerialNumber')->andReturn('ABC123DEF456');
    $mockCert->shouldReceive('getSignatureAlgorithm')->andReturn('SHA256withRSA');
    $mockCert->shouldReceive('isValid')->andReturn(true);

    $result = $checker->parseCertificateData($mockCert);

    expect($result)->toBeArray()
        ->and($result['issuer'])->toBe('DigiCert Inc')
        ->and($result['expires_at'])->toBeInstanceOf(Carbon::class)
        ->and($result['subject'])->toBe('example.com')
        ->and($result['serial_number'])->toBe('ABC123DEF456')
        ->and($result['signature_algorithm'])->toBe('SHA256withRSA')
        ->and($result['is_valid'])->toBeTrue();
});

test('ssl certificate checker can handle self-signed certificates', function () {
    $website = Website::factory()->create(['url' => 'https://self-signed.badssl.com']);
    $checker = new SslCertificateChecker();

    $result = $checker->checkCertificate($website);

    expect($result['status'])->toBeIn(['invalid', 'error'])
        ->and($result['is_valid'])->toBeFalse();
});

test('ssl certificate checker creates ssl check record for website', function () {
    $website = Website::factory()->create(['url' => 'https://github.com']);
    $checker = new SslCertificateChecker();

    $checker->checkAndStoreCertificate($website);

    expect($website->sslChecks()->count())->toBe(1);
    
    $sslCheck = $website->sslChecks()->first();
    expect($sslCheck->status)->toBeIn(['valid', 'expiring_soon', 'expired', 'invalid', 'error'])
        ->and($sslCheck->checked_at)->toBeInstanceOf(Carbon::class)
        ->and($sslCheck->website_id)->toBe($website->id);
});