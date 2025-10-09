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
        // Note: Using Firefox instead of Chrome to avoid Chrome 128+ crashpad issues

        // Try to find Firefox in shared Playwright directory (production)
        $playwrightShared = '/var/www/monitor.intermedien.at/web/shared/.playwright';
        if (is_dir($playwrightShared)) {
            $firefoxDirs = glob($playwrightShared . '/firefox-*/firefox/firefox');
            if (!empty($firefoxDirs)) {
                return $firefoxDirs[0];
            }
        }

        // Development environment
        if (env('APP_ENV') === 'local' && file_exists('/home/sail')) {
            // Try Playwright in node_modules first
            $playwrightPath = base_path('node_modules/playwright-core/.local-browsers');
            if (is_dir($playwrightPath)) {
                $firefoxDirs = glob($playwrightPath . '/firefox-*/firefox/firefox');
                if (!empty($firefoxDirs)) {
                    return $firefoxDirs[0];
                }
            }
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
        'disable-gpu-sandbox',
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
