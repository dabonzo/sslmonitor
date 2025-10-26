<?php

use App\Jobs\AnalyzeSslCertificateJob;
use App\Models\Website;
use App\Services\SslCertificateAnalysisService;
use App\Support\AutomationLogger;
use Illuminate\Support\Facades\Log;
use Tests\Traits\MocksSslCertificateAnalysis;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class, MocksSslCertificateAnalysis::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
    $this->mockSslCertificateAnalysis();
});

test('job analyzes and saves certificate data', function () {
    $website = Website::factory()->create([
        'url' => 'https://example.com',
    ]);

    $job = new AnalyzeSslCertificateJob($website);
    $job->handle($this->app->make(SslCertificateAnalysisService::class));

    $website->refresh();

    expect($website->latest_ssl_certificate)->toBeArray()
        ->and($website->latest_ssl_certificate)->not->toBeNull()
        ->and($website->ssl_certificate_analyzed_at)->not->toBeNull()
        ->and($website->ssl_certificate_analyzed_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

test('job logs start message', function () {
    $website = Website::factory()->create([
        'url' => 'https://example.com',
    ]);

    Log::spy();

    $job = new AnalyzeSslCertificateJob($website);
    $job->handle($this->app->make(SslCertificateAnalysisService::class));

    Log::shouldHaveReceived('info')
        ->atLeast()->once()
        ->withArgs(function ($message, $context) use ($website) {
            return str_contains($message, 'Starting SSL certificate analysis')
                && str_contains($message, $website->url)
                && $context['website_id'] === $website->id;
        });
});

test('job logs completion message', function () {
    $website = Website::factory()->create([
        'url' => 'https://example.com',
    ]);

    Log::spy();

    $job = new AnalyzeSslCertificateJob($website);
    $job->handle($this->app->make(SslCertificateAnalysisService::class));

    Log::shouldHaveReceived('info')
        ->atLeast()->once()
        ->withArgs(function ($message, $context) use ($website) {
            return str_contains($message, 'Completed SSL certificate analysis')
                && str_contains($message, $website->url)
                && $context['website_id'] === $website->id;
        });
});

test('job handles failures and re-throws exception', function () {
    $website = Website::factory()->create([
        'url' => 'https://example.com',
    ]);

    // Mock service to throw exception
    $this->mock(SslCertificateAnalysisService::class, function ($mock) {
        $mock->shouldReceive('analyzeAndSave')
            ->andThrow(new \Exception('SSL analysis failed'));
    });

    $job = new AnalyzeSslCertificateJob($website);

    // Verify exception is thrown
    expect(fn () => $job->handle($this->app->make(SslCertificateAnalysisService::class)))
        ->toThrow(\Exception::class, 'SSL analysis failed');
});

test('retryUntil returns correct time window', function () {
    $website = Website::factory()->create();

    $job = new AnalyzeSslCertificateJob($website);

    $beforeCall = now();
    $retryUntil = $job->retryUntil();
    $expectedTime = now()->addMinutes(5);

    expect($retryUntil)->toBeInstanceOf(\Carbon\Carbon::class)
        ->and($retryUntil->timestamp)->toBeGreaterThanOrEqual($beforeCall->addMinutes(5)->timestamp)
        ->and($retryUntil->timestamp)->toBeLessThanOrEqual($expectedTime->addSeconds(5)->timestamp);
});

test('job processes multiple websites sequentially', function () {
    $websites = Website::factory()->count(3)->create();

    foreach ($websites as $website) {
        $job = new AnalyzeSslCertificateJob($website);
        $job->handle($this->app->make(SslCertificateAnalysisService::class));

        $website->refresh();

        expect($website->latest_ssl_certificate)->toBeArray()
            ->and($website->ssl_certificate_analyzed_at)->not->toBeNull();
    }
});

test('job updates certificate data for website with existing data', function () {
    $website = Website::factory()->create([
        'url' => 'https://example.com',
        'latest_ssl_certificate' => [
            'subject' => 'old-data.com',
            'analyzed_at' => now()->subDays(10)->toIso8601String(),
        ],
        'ssl_certificate_analyzed_at' => now()->subDays(10),
    ]);

    $oldTimestamp = $website->ssl_certificate_analyzed_at;

    $job = new AnalyzeSslCertificateJob($website);
    $job->handle($this->app->make(SslCertificateAnalysisService::class));

    $website->refresh();

    expect($website->ssl_certificate_analyzed_at)->toBeGreaterThan($oldTimestamp)
        ->and($website->latest_ssl_certificate['subject'])->not->toBe('old-data.com');
});

test('failed method can be called without throwing exception', function () {
    $website = Website::factory()->create();

    $job = new AnalyzeSslCertificateJob($website);
    $exception = new \Exception('Test failure');

    // Verify the failed method can be called without throwing exception
    // AutomationLogger will handle the actual logging
    expect(fn () => $job->failed($exception))->not->toThrow(\Exception::class);
});

test('job completes in under 1 second with mocked service', function () {
    $website = Website::factory()->create([
        'url' => 'https://example.com',
    ]);

    $startTime = microtime(true);

    $job = new AnalyzeSslCertificateJob($website);
    $job->handle($this->app->make(SslCertificateAnalysisService::class));

    $executionTime = microtime(true) - $startTime;

    expect($executionTime)->toBeLessThan(1.0);
});
