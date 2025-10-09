<?php

namespace App\Services\UptimeMonitor;

use Illuminate\Support\Facades\Log;
use Spatie\UptimeMonitor\Models\Monitor;
use Symfony\Component\Process\Process;

class JavaScriptContentFetcher
{
    public function __construct()
    {
        // Playwright script-based fetching
    }

    /**
     * Fetch content from URL with JavaScript rendering
     */
    public function fetchContent(string $url, ?int $waitSeconds = null): string
    {
        $waitSeconds = $waitSeconds ?? config('browsershot.wait_seconds', 5);
        $chromePath = config('browsershot.chrome_path');
        $scriptPath = base_path('scripts/fetch-js-content.mjs');

        try {
            // Build command
            $command = ['node', $scriptPath, $url, (string) $waitSeconds];

            // Add Chrome path if configured
            if ($chromePath) {
                $command[] = $chromePath;
            }

            // Execute Playwright script with environment variables to disable crashpad
            $process = new Process($command, null, [
                'CHROME_DEVEL_SANDBOX' => '/dev/null',
                'PLAYWRIGHT_SKIP_BROWSER_GC' => '1',
            ]);
            $process->setTimeout(config('browsershot.timeout', 30) + 10); // Add buffer
            $process->run();

            if (! $process->isSuccessful()) {
                throw new \RuntimeException($process->getErrorOutput());
            }

            $content = $process->getOutput();

            // Save content to file for debugging
            try {
                $filename = storage_path('logs/playwright-' . md5($url) . '.html');
                file_put_contents($filename, $content);
                Log::info('Playwright content saved', [
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
                'wait_seconds' => $waitSeconds,
                'chrome_path' => $chromePath
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
     * Clean up browser resources (not needed for Playwright script)
     */
    public function cleanup(): void
    {
        // Playwright handles cleanup automatically
    }

    /**
     * Test if Playwright is available and working
     */
    public static function isAvailable(): bool
    {
        $scriptPath = base_path('scripts/fetch-js-content.mjs');

        if (! file_exists($scriptPath)) {
            Log::warning('Playwright script not found', ['path' => $scriptPath]);
            return false;
        }

        try {
            $process = new Process(['node', '--version']);
            $process->run();

            if (! $process->isSuccessful()) {
                Log::info('Node.js is not available');
                return false;
            }

            Log::info('Playwright is available', [
                'node_version' => trim($process->getOutput())
            ]);

            return true;

        } catch (\Exception $e) {
            Log::info('Playwright is not available', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
