<?php

namespace App\Services\UptimeMonitor;

use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;
use Spatie\UptimeMonitor\Models\Monitor;

class JavaScriptContentFetcher
{
    public function __construct()
    {
        // BrowserShot doesn't need persistent instances
    }

    /**
     * Fetch content from URL with JavaScript rendering
     */
    public function fetchContent(string $url, ?int $waitSeconds = null): string
    {
        $waitSeconds = $waitSeconds ?? config('browsershot.wait_seconds', 5);

        try {
            // Use BrowserShot to fetch content with JavaScript rendering
            $content = Browsershot::url($url)
                ->setChromePath(config('browsershot.chrome_path'))
                ->waitUntilNetworkIdle()
                ->timeout(config('browsershot.timeout', 30))
                ->delay($waitSeconds * 1000) // Wait for JavaScript to render
                ->noSandbox() // Use BrowserShot's built-in method for Docker
                ->addChromiumArguments(config('browsershot.chrome_arguments', []))
                ->bodyHtml();

            // Save content to file for debugging
            try {
                $filename = storage_path('logs/browsershot-' . md5($url) . '.html');
                file_put_contents($filename, $content);
                Log::info('BrowserShot content saved', [
                    'url' => $url,
                    'filename' => $filename,
                    'content_length' => strlen($content),
                    'contains_erdgasversorger' => str_contains($content, 'Erdgasversorger')
                ]);
            } catch (\Exception $e) {
                // Ignore file save errors - don't break the content fetching
            }

            return $content;

        } catch (\Exception $e) {
            Log::error('JavaScript content fetching failed', [
                'url' => $url,
                'error' => $e->getMessage(),
                'wait_seconds' => $waitSeconds
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
     * Clean up browser resources (not needed for BrowserShot)
     */
    public function cleanup(): void
    {
        // BrowserShot handles cleanup automatically
    }

    /**
     * Test if BrowserShot is available and working
     */
    public static function isAvailable(): bool
    {
        try {
            $content = Browsershot::html('<html><body>Test</body></html>')
                ->setChromePath(config('browsershot.chrome_path'))
                ->noSandbox()
                ->addChromiumArguments(config('browsershot.chrome_arguments', []))
                ->bodyHtml();

            return ! empty($content) && str_contains($content, 'Test');

        } catch (\Exception $e) {
            Log::info('BrowserShot is not available', [
                'error' => $e->getMessage(),
                'chrome_path' => config('browsershot.chrome_path')
            ]);
            return false;
        }
    }
}