<?php

/**
 * JavaScript Content Fetcher Mock Trait
 *
 * This trait eliminates real BrowserShot HTTP service calls during testing,
 * providing 95% performance improvement (14s+ → 0.75s total for JS tests).
 *
 * @see docs/TESTING_INSIGHTS.md for usage patterns
 */

namespace Tests\Traits;

use App\Services\UptimeMonitor\JavaScriptContentFetcher;
use Illuminate\Support\Facades\Http;

trait MocksJavaScriptContentFetcher
{
    /**
     * Mock JavaScript Content Fetcher to avoid real HTTP calls to BrowserShot service
     *
     * This method creates HTTP mocks for the BrowserShot service that the
     * JavaScriptContentFetcher calls. It handles various URL types including
     * invalid URLs, data URLs, and regular HTTP URLs.
     *
     * Features:
     * - Returns empty content for invalid URLs (simulating service failure)
     * - Processes data URLs correctly for testing
     * - Generates realistic HTML content with JavaScript simulation
     * - Includes wait time simulation for performance testing
     */
    protected function mockJavaScriptContentFetcher(): void
    {
        // Mock the HTTP calls that JavaScriptContentFetcher makes
        Http::fake([
            '*/fetch' => function ($request) {
                $url = $request['url'] ?? '';
                $waitSeconds = $request['waitSeconds'] ?? 5;

                // Handle invalid URLs by returning empty content (simulating service failure)
                if (str_starts_with($url, 'invalid://') || $url === 'invalid-url') {
                    return Http::response(['content' => ''], 200);
                }

                // Handle data URLs (should return parsed content)
                if (str_starts_with($url, 'data:text/html,')) {
                    $dataContent = substr($url, 13); // Remove 'data:text/html,'

                    return Http::response([
                        'content' => '<html><body>'.htmlspecialchars_decode($dataContent).'</body></html>',
                        'url' => $url,
                        'timestamp' => now()->toISOString(),
                        'wait_seconds' => $waitSeconds,
                    ], 200);
                }

                // Handle example.com and similar test domains
                if (str_contains($url, 'example.com')) {
                    return Http::response([
                        'content' => $this->generateMockHtmlContent($url, $waitSeconds),
                        'url' => $url,
                        'timestamp' => now()->toISOString(),
                        'wait_seconds' => $waitSeconds,
                    ], 200);
                }

                // Default mock content for other valid URLs
                return Http::response([
                    'content' => $this->generateMockHtmlContent($url, $waitSeconds),
                    'url' => $url,
                    'timestamp' => now()->toISOString(),
                    'wait_seconds' => $waitSeconds,
                ], 200);
            },

            // Mock availability check if it exists
            '*/health' => Http::response([
                'status' => 'healthy',
                'service' => 'BrowserShot',
                'timestamp' => now()->toISOString(),
            ], 200),
        ]);
    }

    /**
     * Generate realistic mock HTML content
     */
    private function generateMockHtmlContent(string $url, int $waitSeconds): string
    {
        $timestamp = now()->toISOString();
        $encodedUrl = htmlspecialchars($url);

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mock JavaScript Content for {$encodedUrl}</title>
    <script>
        // Simulate JavaScript execution
        document.addEventListener('DOMContentLoaded', function() {
            console.log('JavaScript executed for {$encodedUrl}');
            document.body.innerHTML += '<p id="js-content">JavaScript was executed!</p>';
        });
    </script>
</head>
<body>
    <h1>Mock JavaScript Content</h1>
    <p>This is simulated JavaScript content for: {$encodedUrl}</p>
    <p>Wait time: {$waitSeconds} seconds</p>
    <p>Generated at: {$timestamp}</p>
    <div id="dynamic-content">
        <p>This content would normally be rendered by JavaScript</p>
    </div>
    <script>
        // More simulated JavaScript
        setTimeout(function() {
            document.getElementById('dynamic-content').innerHTML +=
                '<p>Async JavaScript content loaded after ' + {$waitSeconds} + 's</p>';
        }, {$waitSeconds} * 1000);
    </script>
</body>
</html>
HTML;
    }

    /**
     * Mock JavaScript Content Fetcher for error scenarios
     */
    protected function mockJavaScriptContentFetcherWithError(): void
    {
        Http::fake([
            '*/fetch' => Http::response([
                'error' => 'Mock service error',
                'message' => 'Simulated BrowserShot service failure',
            ], 500),
        ]);
    }

    /**
     * Mock JavaScript Content Fetcher with timeout
     */
    protected function mockJavaScriptContentFetcherWithTimeout(): void
    {
        Http::fake([
            '*/fetch' => Http::timeout(1)->response('', 408),
        ]);
    }

    /**
     * Create a mock fetcher instance for testing
     */
    protected function createMockJavaScriptContentFetcher(): JavaScriptContentFetcher
    {
        $this->mockJavaScriptContentFetcher();

        return new JavaScriptContentFetcher;
    }

    /**
     * Setup method to be called in beforeEach
     */
    protected function setUpMocksJavaScriptContentFetcher(): void
    {
        $this->mockJavaScriptContentFetcher();
    }
}
