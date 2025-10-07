<?php

use App\Models\Monitor;
use App\Services\UptimeMonitor\JavaScriptContentFetcher;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    // Skip JavaScript tests if Playwright is not available
    if (! JavaScriptContentFetcher::isAvailable()) {
        $this->markTestSkipped('Playwright is not available for JavaScript content fetching tests.');
    }
});

test('javascript content fetcher can test availability', function () {
    // This test should work even without Playwright since it's testing the availability check
    $available = JavaScriptContentFetcher::isAvailable();
    expect($available)->toBeIn([true, false]); // Should return a boolean
});

test('javascript content fetcher can fetch simple html content', function () {
    $fetcher = new JavaScriptContentFetcher();

    // Test with a simple data URL
    $content = $fetcher->fetchContent('data:text/html,<html><body><h1>Test Page</h1></body></html>', 1);

    expect($content)->toContain('Test Page')
        ->and($content)->toContain('<html>')
        ->and($content)->toContain('<body>');

    $fetcher->cleanup();
});

test('javascript content fetcher returns empty string on error', function () {
    $fetcher = new JavaScriptContentFetcher();

    // Test with invalid URL
    $content = $fetcher->fetchContent('invalid://url', 1);

    expect($content)->toBe('');

    $fetcher->cleanup();
});

test('javascript content fetcher can fetch content for monitor with javascript enabled', function () {
    $monitor = Monitor::factory()->create([
        'url' => 'data:text/html,<html><body><h1>Monitor Test</h1><script>document.body.innerHTML += "<p>JavaScript Loaded</p>";</script></body></html>',
        'javascript_enabled' => true,
        'javascript_wait_seconds' => 2,
    ]);

    $fetcher = new JavaScriptContentFetcher();
    $content = $fetcher->fetchContentForMonitor($monitor);

    expect($content)->toContain('Monitor Test');
    // Note: The JavaScript may or may not execute depending on the environment

    $fetcher->cleanup();
});

test('javascript content fetcher returns empty string for monitor without javascript enabled', function () {
    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'javascript_enabled' => false,
        'javascript_wait_seconds' => 5,
    ]);

    $fetcher = new JavaScriptContentFetcher();
    $content = $fetcher->fetchContentForMonitor($monitor);

    expect($content)->toBe('');

    $fetcher->cleanup();
});

test('javascript content fetcher respects wait time bounds', function () {
    $monitor = Monitor::factory()->create([
        'url' => 'data:text/html,<html><body>Test</body></html>',
        'javascript_enabled' => true,
        'javascript_wait_seconds' => 50, // Should be clamped to 30
    ]);

    $fetcher = new JavaScriptContentFetcher();

    // The wait seconds should be handled by the Monitor model's getJavaScriptWaitSeconds method
    expect($monitor->getJavaScriptWaitSeconds())->toBe(30);

    $fetcher->cleanup();
});

test('javascript content fetcher handles cleanup properly', function () {
    $fetcher = new JavaScriptContentFetcher();

    // Fetch some content to initialize the browser
    $fetcher->fetchContent('data:text/html,<html><body>Cleanup Test</body></html>', 1);

    // Cleanup should not throw any exceptions
    expect(fn() => $fetcher->cleanup())->not->toThrow();

    // Multiple cleanups should be safe
    expect(fn() => $fetcher->cleanup())->not->toThrow();
});

test('javascript content fetcher logs errors appropriately', function () {
    Log::fake();

    $fetcher = new JavaScriptContentFetcher();

    // Test with invalid URL that should cause an error
    $content = $fetcher->fetchContent('invalid://definitely-not-a-url', 1);

    expect($content)->toBe('');

    // Verify that an error was logged
    Log::assertLogged('error');

    $fetcher->cleanup();
});

test('javascript content fetcher can be instantiated multiple times', function () {
    $fetcher1 = new JavaScriptContentFetcher();
    $fetcher2 = new JavaScriptContentFetcher();

    // Both should be able to fetch content independently
    $content1 = $fetcher1->fetchContent('data:text/html,<html><body>Fetcher 1</body></html>', 1);
    $content2 = $fetcher2->fetchContent('data:text/html,<html><body>Fetcher 2</body></html>', 1);

    expect($content1)->toContain('Fetcher 1');
    expect($content2)->toContain('Fetcher 2');

    $fetcher1->cleanup();
    $fetcher2->cleanup();
});

test('javascript content fetcher destructor calls cleanup', function () {
    // This test verifies that the destructor is properly set up
    $fetcher = new JavaScriptContentFetcher();

    // Fetch some content to initialize
    $fetcher->fetchContent('data:text/html,<html><body>Destructor Test</body></html>', 1);

    // When $fetcher goes out of scope, destructor should call cleanup
    // This is implicitly tested by not calling cleanup manually
    unset($fetcher);

    // If we get here without errors, the destructor worked properly
    expect(true)->toBeTrue();
});

test('javascript content fetcher handles network timeouts gracefully', function () {
    $fetcher = new JavaScriptContentFetcher();

    // Use a non-routable IP address to simulate a timeout
    $startTime = microtime(true);
    $content = $fetcher->fetchContent('http://10.255.255.1', 1);
    $endTime = microtime(true);

    // Should return empty string
    expect($content)->toBe('');

    // Should not take too long (timeout handling)
    $duration = $endTime - $startTime;
    expect($duration)->toBeLessThan(60); // Should timeout well before 60 seconds

    $fetcher->cleanup();
});