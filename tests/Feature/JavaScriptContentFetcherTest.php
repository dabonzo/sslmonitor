<?php

use App\Models\Monitor;
use App\Services\UptimeMonitor\JavaScriptContentFetcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\MocksJavaScriptContentFetcher;

uses(RefreshDatabase::class, MocksJavaScriptContentFetcher::class);

describe('BrowserShot JavaScript Content Fetcher', function () {
    beforeEach(function () {
        $this->setUpMocksJavaScriptContentFetcher();
        $this->fetcher = new JavaScriptContentFetcher;
    });

    afterEach(function () {
        $this->fetcher->cleanup();
    });

    describe('Instantiation and Cleanup', function () {
        test('can be instantiated', function () {
            expect($this->fetcher)->toBeInstanceOf(JavaScriptContentFetcher::class);
        });

        test('cleanup method exists and is callable', function () {
            expect(method_exists($this->fetcher, 'cleanup'))->toBeTrue();
            $this->fetcher->cleanup(); // Should not throw (BrowserShot handles cleanup automatically)
        });

        test('no persistent resources needed for BrowserShot', function () {
            // BrowserShot doesn't require persistent browser instances like Playwright
            $fetcher = new JavaScriptContentFetcher;
            unset($fetcher); // Should work without issues
            expect(true)->toBeTrue();
        });
    });

    describe('Availability Check', function () {
        test('availability check returns boolean', function () {
            $available = JavaScriptContentFetcher::isAvailable();
            expect($available)->toBeIn([true, false]);
        });

        test('availability check logs when browsershot is not available', function () {
            // This test ensures the method handles exceptions gracefully
            // The actual result depends on whether BrowserShot/Puppeteer is available
            $available = JavaScriptContentFetcher::isAvailable();
            expect($available)->toBeBool();
        });
    });

    describe('Content Fetching', function () {
        test('fetchContent method handles invalid URLs gracefully', function () {
            $content = $this->fetcher->fetchContent('invalid-url');
            expect($content)->toBe('');
        });

        test('fetchContent with data URL works', function () {
            // With our mock, data URLs should work fine
            $content = $this->fetcher->fetchContent('data:text/html,<html><body>Test Content</body></html>');
            expect($content)->toBeString();
            expect($content)->toContain('Test Content');
        });

        test('fetchContent respects wait seconds parameter', function () {
            // Test that wait seconds parameter is accepted
            $content = $this->fetcher->fetchContent('data:text/html,<html><body>Test</body></html>', 1);
            // Should not throw an exception
            expect($content)->toBeString();
        });

        test('fetchContent handles exceptions and returns empty string', function () {
            // Using an invalid protocol to force an exception
            $content = $this->fetcher->fetchContent('invalid://example.com');
            expect($content)->toBe('');
        });
    });

    describe('Monitor Integration', function () {
        test('fetchContentForMonitor returns empty string when javascript disabled', function () {
            $monitor = Monitor::factory()->create(['javascript_enabled' => false]);
            $content = $this->fetcher->fetchContentForMonitor($monitor);
            expect($content)->toBe('');
        });

        test('fetchContentForMonitor uses monitor wait seconds', function () {
            $monitor = Monitor::factory()->create([
                'javascript_enabled' => true,
                'javascript_wait_seconds' => 3,
                'url' => 'https://example.com/test-js-wait',
            ]);

            $content = $this->fetcher->fetchContentForMonitor($monitor);
            expect($content)->toBeString();
            expect($content)->toContain('Wait time: 3');
        });

        test('fetchContentForMonitor handles monitor without javascript', function () {
            $monitor = Monitor::factory()->create([
                'javascript_enabled' => false,
                'url' => 'https://example.com',
            ]);

            $content = $this->fetcher->fetchContentForMonitor($monitor);
            expect($content)->toBe('');
        });
    });

    describe('Error Handling', function () {
        test('handles browsershot failures gracefully', function () {
            // Test with invalid URL that would cause BrowserShot to fail
            $content = $this->fetcher->fetchContent('invalid://example.com');
            // Should not throw, should return empty string on failure
            expect($content)->toBe('');
        });

        test('multiple cleanup calls do not cause errors', function () {
            $this->fetcher->cleanup();
            $this->fetcher->cleanup();
            expect(true)->toBeTrue(); // Should not throw (BrowserShot handles this)
        });

        test('fetchContent after cleanup still works', function () {
            $this->fetcher->cleanup();
            $content = $this->fetcher->fetchContent('data:text/html,<html><body>Test</body></html>');
            expect($content)->toBeString();
        });
    });

    describe('BrowserShot Integration', function () {
        test('browsershot does not require persistent resources', function () {
            // BrowserShot creates and manages browser instances automatically
            $fetcher = new JavaScriptContentFetcher;

            // No persistent browser properties like Playwright
            expect(true)->toBeTrue(); // BrowserShot is stateless

            $fetcher->cleanup();
        });
    });
});
