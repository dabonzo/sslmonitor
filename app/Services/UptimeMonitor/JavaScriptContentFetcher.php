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
    public function fetchContent(string $url, int $waitSeconds = 5): string
    {
        try {
            // Use BrowserShot to fetch content with JavaScript rendering
            // Use Puppeteer's installed Chrome in ~/.cache/puppeteer
            $content = Browsershot::url($url)
                ->setChromePath('/home/sail/.cache/puppeteer/chrome/linux-140.0.7339.207/chrome-linux64/chrome')
                ->waitUntilNetworkIdle()
                ->timeout(30) // 30 seconds timeout
                ->delay($waitSeconds * 1000) // Wait for JavaScript to render
                ->noSandbox() // Use BrowserShot's built-in method for Docker
                ->addChromiumArguments([
                    'disable-setuid-sandbox',
                    'disable-dev-shm-usage',
                    'disable-accelerated-2d-canvas',
                    'no-first-run',
                    'no-zygote',
                    'disable-gpu'
                ])
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
            // Use Puppeteer's installed Chrome in ~/.cache/puppeteer
            $content = Browsershot::html('<html><body>Test</body></html>')
                ->setChromePath('/home/sail/.cache/puppeteer/chrome/linux-140.0.7339.207/chrome-linux64/chrome')
                ->noSandbox() // Use BrowserShot's built-in method for Docker
                ->addChromiumArguments([
                    'disable-setuid-sandbox',
                    'disable-dev-shm-usage',
                    'disable-accelerated-2d-canvas',
                    'no-first-run',
                    'no-zygote',
                    'disable-gpu'
                ])
                ->bodyHtml();

            return ! empty($content) && str_contains($content, 'Test');

        } catch (\Exception $e) {
            Log::info('BrowserShot is not available', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}