#!/usr/bin/env node
/**
 * Fetch content from URL with JavaScript rendering using Playwright
 * Usage: node fetch-js-content.mjs <url> [waitSeconds] [browserPath]
 */

import { firefox } from 'playwright-core';

const url = process.argv[2];
const waitSeconds = parseInt(process.argv[3] || '5');
const browserPath = process.argv[4];

if (!url) {
    console.error('Usage: node fetch-js-content.mjs <url> [waitSeconds] [browserPath]');
    process.exit(1);
}

(async () => {
    let browser;
    try {
        const launchOptions = {
            headless: true,
            args: []
        };

        // Set executable path if provided
        if (browserPath) {
            launchOptions.executablePath = browserPath;
        }

        browser = await firefox.launch(launchOptions);
        const context = await browser.newContext();
        const page = await context.newPage();

        // Navigate and wait
        await page.goto(url, { waitUntil: 'networkidle' });
        await page.waitForTimeout(waitSeconds * 1000);

        // Get HTML content
        const content = await page.content();

        // Output content to stdout
        console.log(content);

        await browser.close();
        process.exit(0);
    } catch (error) {
        if (browser) {
            await browser.close();
        }
        console.error('Error fetching content:', error.message);
        process.exit(1);
    }
})();
