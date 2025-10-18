<?php

namespace App\Services\UptimeMonitor;

use App\Models\Monitor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JavaScriptContentFetcher
{
    private ?string $serviceUrl = null;

    private function getServiceUrl(): string
    {
        if ($this->serviceUrl === null) {
            $this->serviceUrl = config('browsershot.service_url', 'http://127.0.0.1:3000');
        }

        return $this->serviceUrl;
    }

    /**
     * Fetch content from URL with JavaScript rendering
     */
    public function fetchContent(string $url, ?int $waitSeconds = null): string
    {
        $waitSeconds = $waitSeconds ?? config('browsershot.wait_seconds', 5);

        try {
            // Call the HTTP service
            $serviceUrl = $this->getServiceUrl();
            $response = Http::timeout(config('browsershot.timeout', 30) + 10)
                ->post("{$serviceUrl}/fetch", [
                    'url' => $url,
                    'waitSeconds' => $waitSeconds,
                ]);

            if (! $response->successful()) {
                throw new \RuntimeException(
                    $response->json('error') ?? 'HTTP service returned error: '.$response->status()
                );
            }

            $content = $response->json('content', '');

            // Save content to file for debugging
            try {
                $filename = storage_path('logs/playwright-'.md5($url).'.html');
                file_put_contents($filename, $content);
                try {
                    Log::info('JavaScript content fetched via HTTP service', [
                        'url' => $url,
                        'filename' => $filename,
                        'content_length' => strlen($content),
                        'wait_seconds' => $waitSeconds,
                    ]);
                } catch (\Exception $logException) {
                    // Log facade not available, ignore
                }
            } catch (\Exception $e) {
                // Ignore file save errors - don't break the content fetching
            }

            return $content;

        } catch (\Exception $e) {
            try {
                Log::error('JavaScript content fetching failed', [
                    'url' => $url,
                    'error' => $e->getMessage(),
                    'wait_seconds' => $waitSeconds,
                    'service_url' => $this->getServiceUrl(),
                ]);
            } catch (\Exception $logException) {
                // Log facade not available, ignore
            }

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
        try {
            // Try to create an instance to check if config is available
            $fetcher = new self;
            $serviceUrl = $fetcher->getServiceUrl();

            $response = Http::timeout(5)->get("{$serviceUrl}/health");

            if ($response->successful() && $response->json('status') === 'ok') {
                try {
                    Log::info('JavaScript content fetching service is available', [
                        'service_url' => $serviceUrl,
                        'browser' => $response->json('browser'),
                    ]);
                } catch (\Exception $logException) {
                    // Log facade not available, ignore
                }

                return true;
            }

            try {
                Log::warning('JavaScript content fetching service returned unhealthy status', [
                    'service_url' => $serviceUrl,
                    'response' => $response->json(),
                ]);
            } catch (\Exception $logException) {
                // Log facade not available, ignore
            }

            return false;

        } catch (\Exception $e) {
            try {
                Log::warning('JavaScript content fetching service is not available', [
                    'error' => $e->getMessage(),
                ]);
            } catch (\Exception $logException) {
                // Log facade not available, ignore
            }

            return false;
        }
    }
}
