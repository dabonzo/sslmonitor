<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Website;
use App\Services\UptimeChecker;
use App\Services\UptimeCheckResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->website = Website::factory()->create([
        'user_id' => $this->user->id,
        'url' => 'https://example.com',
        'expected_status_code' => 200,
        'expected_content' => 'Welcome to our site',
        'forbidden_content' => 'Error 503',
        'max_response_time' => 5000,
        'follow_redirects' => true,
        'max_redirects' => 3,
    ]);

    $this->checker = new UptimeChecker;
});

describe('UptimeChecker Service', function () {
    test('can check website with successful response', function () {
        Http::fake([
            'https://example.com' => Http::response('Welcome to our site', 200, [
                'Content-Type' => 'text/html',
            ]),
        ]);

        $result = $this->checker->checkWebsite($this->website);

        expect($result)->toBeInstanceOf(UptimeCheckResult::class)
            ->and($result->status)->toBe('up')
            ->and($result->httpStatusCode)->toBe(200)
            ->and($result->responseTime)->toBeGreaterThan(0)
            ->and($result->responseSize)->toBeGreaterThan(0)
            ->and($result->contentCheckPassed)->toBeTrue()
            ->and($result->errorMessage)->toBeNull();
    });

    test('can detect website down with HTTP error', function () {
        Http::fake([
            'https://example.com' => Http::response('', 500),
        ]);

        $result = $this->checker->checkWebsite($this->website);

        expect($result->status)->toBe('down')
            ->and($result->httpStatusCode)->toBe(500)
            ->and($result->contentCheckPassed)->toBeFalse()
            ->and($result->errorMessage)->toContain('HTTP 500');
    });

    test('can detect website down with timeout', function () {
        Http::fake([
            'https://example.com' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timeout');
            },
        ]);

        $result = $this->checker->checkWebsite($this->website);

        expect($result->status)->toBe('down')
            ->and($result->httpStatusCode)->toBeNull()
            ->and($result->errorMessage)->toContain('Connection timeout');
    });

    test('can detect slow response time', function () {
        Http::fake([
            'https://example.com' => function () {
                usleep(150000); // Sleep for 150ms

                return Http::response('Welcome to our site', 200);
            },
        ]);

        // Mock slow response time
        $this->website->max_response_time = 100; // Very low threshold

        $result = $this->checker->checkWebsite($this->website);

        // Response time should be > 100ms, so should be marked as slow
        expect($result->status)->toBe('slow')
            ->and($result->httpStatusCode)->toBe(200)
            ->and($result->responseTime)->toBeGreaterThan($this->website->max_response_time);
    });

    test('can detect content mismatch with missing expected content', function () {
        Http::fake([
            'https://example.com' => Http::response('Different content here', 200),
        ]);

        $result = $this->checker->checkWebsite($this->website);

        expect($result->status)->toBe('content_mismatch')
            ->and($result->httpStatusCode)->toBe(200)
            ->and($result->contentCheckPassed)->toBeFalse()
            ->and($result->contentCheckError)->toContain('Expected content not found');
    });

    test('can detect content mismatch with forbidden content present', function () {
        Http::fake([
            'https://example.com' => Http::response('Welcome to our site but Error 503 occurred', 200),
        ]);

        $result = $this->checker->checkWebsite($this->website);

        expect($result->status)->toBe('content_mismatch')
            ->and($result->httpStatusCode)->toBe(200)
            ->and($result->contentCheckPassed)->toBeFalse()
            ->and($result->contentCheckError)->toContain('Forbidden content found');
    });

    test('can handle website without expected content configured', function () {
        $this->website->expected_content = null;
        $this->website->forbidden_content = null;

        Http::fake([
            'https://example.com' => Http::response('Any content', 200),
        ]);

        $result = $this->checker->checkWebsite($this->website);

        expect($result->status)->toBe('up')
            ->and($result->contentCheckPassed)->toBeTrue();
    });

    test('can follow redirects when enabled', function () {
        Http::fake([
            'https://example.com' => Http::response('', 301, ['Location' => 'https://example.com/new']),
            'https://example.com/new' => Http::response('Welcome to our site', 200),
        ]);

        $result = $this->checker->checkWebsite($this->website);

        expect($result->status)->toBe('up')
            ->and($result->httpStatusCode)->toBe(200)
            ->and($result->finalUrl)->toBe('https://example.com/new');
    });

    test('can detect too many redirects', function () {
        $this->website->max_redirects = 2;

        Http::fake([
            'https://example.com' => Http::response('', 301, ['Location' => 'https://example.com/1']),
            'https://example.com/1' => Http::response('', 301, ['Location' => 'https://example.com/2']),
            'https://example.com/2' => Http::response('', 301, ['Location' => 'https://example.com/3']),
        ]);

        $result = $this->checker->checkWebsite($this->website);

        expect($result->status)->toBe('down')
            ->and($result->errorMessage)->toContain('Too many redirects');
    });

    test('can handle redirects disabled', function () {
        $this->website->follow_redirects = false;

        Http::fake([
            'https://example.com' => Http::response('', 301, ['Location' => 'https://example.com/new']),
        ]);

        $result = $this->checker->checkWebsite($this->website);

        expect($result->status)->toBe('down')
            ->and($result->httpStatusCode)->toBe(301);
    });

    test('validates expected status code correctly', function () {
        $this->website->expected_status_code = 201;

        Http::fake([
            'https://example.com' => Http::response('Welcome to our site', 201),
        ]);

        $result = $this->checker->checkWebsite($this->website);

        expect($result->status)->toBe('up')
            ->and($result->httpStatusCode)->toBe(201);
    });

    test('detects wrong status code', function () {
        $this->website->expected_status_code = 201;

        Http::fake([
            'https://example.com' => Http::response('Welcome to our site', 200),
        ]);

        $result = $this->checker->checkWebsite($this->website);

        expect($result->status)->toBe('down')
            ->and($result->httpStatusCode)->toBe(200)
            ->and($result->errorMessage)->toContain('Expected status 201, got 200');
    });
});
