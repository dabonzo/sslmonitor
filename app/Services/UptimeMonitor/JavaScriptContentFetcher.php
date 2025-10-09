<?php

namespace App\Services\UptimeMonitor;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Spatie\UptimeMonitor\Models\Monitor;

class JavaScriptContentFetcher
{
    private string $serviceUrl;

    public function __construct()
    {
        $this->serviceUrl = config('browsershot.service_url', 'http://127.0.0.1:3000');
    }

    /**
     * Fetch content from URL with JavaScript rendering
     */
    public function fetchContent(string $url, ?int $waitSeconds = null): string
    {
        $waitSeconds = $waitSeconds ?? config('browsershot.wait_seconds', 5);

        try {
            // Call the HTTP service
            $response = Http::timeout(config('browsershot.timeout', 30) + 10)
                ->post("{$this->serviceUrl}/fetch", [
                    'url' => $url,
                    'waitSeconds' => $waitSeconds,
                ]);

            if (! $response->successful()) {
                throw new \RuntimeException(
                    $response->json('error') ?? 'HTTP service returned error: ' . $response->status()
                );
            }

            $content = $response->json('content', '');

            // Save content to file for debugging
            try {
                $filename = storage_path('logs/playwright-' . md5($url) . '.html');
                file_put_contents($filename, $content);
                Log::info('JavaScript content fetched via HTTP service', [
                    'url' => $url,
                    'filename' => $filename,
                    'content_length' => strlen($content),
                    'wait_seconds' => $waitSeconds,
                ]);
            } catch (\Exception $e) {
                // Ignore file save errors - don't break the content fetching
            }

            return $content;

        } catch (\Exception $e) {
            Log::error('JavaScript content fetching failed', [
                'url' => $url,
                'error' => $e->getMessage(),
                'wait_seconds' => $waitSeconds,
                'service_url' => $this->serviceUrl,
            ]);

            // Return empty string on failure
            return '';
        }
    }

    /**
     * Fetch content for a monitor with JavaScript support
     */
    public function fetchContentForMonitor(Monitor $monitor): string
    {
        if (! $monitor->hasJavaScriptEnabled()) {
            return '';
        }

        $waitSeconds = $monitor->getJavaScriptWaitSeconds();

        return $this->fetchContent($monitor->url, $waitSeconds);
    }

    /**
     * Clean up browser resources (not needed - HTTP service handles this)
     */
    public function cleanup(): void
    {
        // HTTP service handles cleanup automatically
    }

    /**
     * Test if JavaScript content fetching service is available
     */
    public static function isAvailable(): bool
    {
        $serviceUrl = config('browsershot.service_url', 'http://127.0.0.1:3000');

        try {
            $response = Http::timeout(5)->get("{$serviceUrl}/health");

            if ($response->successful() && $response->json('status') === 'ok') {
                Log::info('JavaScript content fetching service is available', [
                    'service_url' => $serviceUrl,
                    'browser' => $response->json('browser'),
                ]);

                return true;
            }

            Log::warning('JavaScript content fetching service returned unhealthy status', [
                'service_url' => $serviceUrl,
                'response' => $response->json(),
            ]);

            return false;

        } catch (\Exception $e) {
            Log::warning('JavaScript content fetching service is not available', [
                'service_url' => $serviceUrl,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
