#!/usr/bin/env node
/**
 * Fetch content from URL with JavaScript rendering using Playwright
 * Usage: node fetch-js-content.mjs <url> [waitSeconds] [chromePath]
 */

import { chromium } from 'playwright-core';

const url = process.argv[2];
const waitSeconds = parseInt(process.argv[3] || '5');
const chromePath = process.argv[4];

if (!url) {
    console.error('Usage: node fetch-js-content.mjs <url> [waitSeconds] [chromePath]');
    process.exit(1);
}

(async () => {
    let browser;
    try {
        const launchOptions = {
            headless: true,
            args: [
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-accelerated-2d-canvas',
                '--no-first-run',
                '--no-zygote',
                '--disable-gpu',
                '--disable-gpu-sandbox',
                '--no-sandbox'
            ]
        };

        // Set executable path if provided
        if (chromePath) {
            launchOptions.executablePath = chromePath;
        }

        browser = await chromium.launch(launchOptions);
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
