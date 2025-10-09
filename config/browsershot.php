<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Chrome Executable Path
    |--------------------------------------------------------------------------
    |
    | Path to the Chrome/Chromium binary. This automatically detects the
    | environment and uses the appropriate path:
    |
    | - Laravel Sail (Docker): Uses Puppeteer's installed Chrome
    | - Production: Uses BROWSERSHOT_CHROME_PATH from .env
    |
    */

    'chrome_path' => env('BROWSERSHOT_CHROME_PATH') ?? (function () {
        // Auto-detect environment if not set in .env
        if (env('APP_ENV') === 'local' && file_exists('/home/sail')) {
            // Laravel Sail environment - try Playwright in node_modules first
            $playwrightPath = base_path('node_modules/playwright-core/.local-browsers');
            if (is_dir($playwrightPath)) {
                $chromeDirs = glob($playwrightPath . '/chromium-*/chrome-linux/chrome');
                if (!empty($chromeDirs)) {
                    return $chromeDirs[0];
                }
            }

            // Fallback: Try Puppeteer cache
            $puppeteerPath = '/home/sail/.cache/puppeteer';
            if (is_dir($puppeteerPath)) {
                $chromeDirs = glob($puppeteerPath . '/chrome/linux-*/chrome-linux*/chrome');
                if (!empty($chromeDirs)) {
                    return $chromeDirs[0];
                }
            }

            // Last fallback for Sail
            return '/home/sail/.cache/puppeteer/chrome/linux-140.0.7339.207/chrome-linux64/chrome';
        }

        // Production environment - must be set in .env
        return null; // Will cause an error in JavaScriptContentFetcher if not set
    })(),

    /*
    |--------------------------------------------------------------------------
    | Chrome Arguments
    |--------------------------------------------------------------------------
    |
    | Additional arguments to pass to Chrome/Chromium
    |
    */

    'chrome_arguments' => [
        'disable-setuid-sandbox',
        'disable-dev-shm-usage',
        'disable-accelerated-2d-canvas',
        'no-first-run',
        'no-zygote',
        'disable-gpu',
        'disable-crash-reporter',
        'disable-breakpad',
        'disable-features=CrashReporter',
        'crash-dumps-dir=/tmp',
    ],

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum time (in seconds) to wait for Chrome operations
    |
    */

    'timeout' => env('BROWSERSHOT_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Default Wait Time
    |--------------------------------------------------------------------------
    |
    | Default time (in seconds) to wait for JavaScript to render
    |
    */

    'wait_seconds' => env('BROWSERSHOT_WAIT_SECONDS', 5),
];
