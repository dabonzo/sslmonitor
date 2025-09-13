<?php

declare(strict_types=1);

use App\Jobs\CheckSslCertificateJob;
use App\Models\SslCheck;
use App\Models\User;
use App\Models\Website;
use App\Services\SslCertificateChecker;
use App\Services\SslStatusCalculator;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->website = Website::factory()->for($this->user)->create([
        'url' => 'https://example.com',
    ]);
});

test('ssl certificate job can be queued', function () {
    Queue::fake();

    CheckSslCertificateJob::dispatch($this->website);

    Queue::assertPushed(CheckSslCertificateJob::class, function ($job) {
        return $job->website->id === $this->website->id;
    });
});

test('ssl certificate job runs successfully', function () {
    // Create an old SSL check to establish previous status but not trigger recent check logic
    SslCheck::factory()->for($this->website)->create([
        'status' => 'expired',
        'checked_at' => now()->subHours(2), // Old check, outside 1-hour window
    ]);

    // Create the SSL check that the mock will return (but don't persist it yet)
    $sslCheck = SslCheck::factory()->for($this->website)->make([
        'status' => SslStatusCalculator::STATUS_VALID,
        'days_until_expiry' => 45,
    ]);
    
    // Simulate what checkAndStoreCertificate would do - create and return the check
    $mockChecker = $this->mock(SslCertificateChecker::class);
    $mockChecker->shouldReceive('checkAndStoreCertificate')
        ->once()
        ->with($this->website)
        ->andReturnUsing(function () use ($sslCheck) {
            // Persist the check when the service is called
            $sslCheck->save();
            return $sslCheck;
        });

    $job = new CheckSslCertificateJob($this->website);
    $job->handle($mockChecker);

    // Job completed successfully
    expect(true)->toBeTrue();
});

test('ssl certificate job handles checker exceptions', function () {
    $mockChecker = $this->mock(SslCertificateChecker::class);
    $mockChecker->shouldReceive('checkAndStoreCertificate')
        ->once()
        ->with($this->website)
        ->andThrow(new \Exception('Connection failed'));

    $job = new CheckSslCertificateJob($this->website);

    expect(fn () => $job->handle($mockChecker))->toThrow(\Exception::class);

    // Should create error record
    $this->assertDatabaseHas('ssl_checks', [
        'website_id' => $this->website->id,
        'status' => 'error',
        'is_valid' => false,
    ]);
});

test('ssl certificate job has retry configuration', function () {
    $job = new CheckSslCertificateJob($this->website);

    expect($job->tries)->toBe(3);
    expect($job->timeout)->toBe(60);
    expect($job->backoff())->toBe([30, 60, 120]);
});

test('ssl certificate job handles deleted website gracefully', function () {
    $this->website->delete();

    $mockChecker = $this->mock(SslCertificateChecker::class);
    $mockChecker->shouldNotReceive('checkAndStoreCertificate');

    $job = new CheckSslCertificateJob($this->website);

    // Should handle gracefully without exception
    expect(fn () => $job->handle($mockChecker))->not->toThrow(\Exception::class);
});

test('ssl certificate job skips recent checks', function () {
    // Create recent SSL check (within last hour)
    SslCheck::factory()->for($this->website)->create([
        'checked_at' => now()->subMinutes(30),
    ]);

    $mockChecker = $this->mock(SslCertificateChecker::class);
    $mockChecker->shouldNotReceive('checkAndStoreCertificate');

    $job = new CheckSslCertificateJob($this->website);
    $job->handle($mockChecker);

    expect(true)->toBeTrue(); // Job completed successfully
});

test('ssl certificate job runs for old checks', function () {
    // Create old SSL check (more than 1 hour ago)
    SslCheck::factory()->for($this->website)->create([
        'checked_at' => now()->subHours(2),
    ]);

    $sslCheck = SslCheck::factory()->for($this->website)->make([
        'status' => SslStatusCalculator::STATUS_VALID,
    ]);

    $mockChecker = $this->mock(SslCertificateChecker::class);
    $mockChecker->shouldReceive('checkAndStoreCertificate')
        ->once()
        ->with($this->website)
        ->andReturn($sslCheck);

    $job = new CheckSslCertificateJob($this->website);
    $job->handle($mockChecker);

    expect(true)->toBeTrue(); // Job completed successfully
});

test('ssl certificate job uses correct queue', function () {
    $job = new CheckSslCertificateJob($this->website);

    // Jobs now use default queue for simplicity and reliability
    expect($job->queue)->toBeNull(); // Default queue
});

test('ssl certificate job has proper configuration', function () {
    $job = new CheckSslCertificateJob($this->website);

    expect($job->website)->toBe($this->website);
    expect($job->tries)->toBeInt();
    expect($job->timeout)->toBeInt();
    expect($job->backoff())->toBeArray();
});
