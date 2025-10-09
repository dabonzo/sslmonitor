const express = require('express');
const { firefox } = require('playwright-core');

const app = express();
const PORT = 3000;
const MAX_CONCURRENT_TABS = 5;

app.use(express.json());

let browser = null;
let activeTabs = 0;
const requestQueue = [];

// Initialize browser on startup
async function initBrowser() {
    console.log('[Init] Starting Firefox browser...');
    try {
        browser = await firefox.launch({
            headless: true,
            executablePath: '/var/www/monitor.intermedien.at/web/shared/.playwright/firefox-1495/firefox/firefox',
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-accelerated-2d-canvas',
                '--disable-gpu'
            ]
        });
        console.log('[Init] ✓ Firefox browser ready');
    } catch (error) {
        console.error('[Init] ✗ Failed to start browser:', error.message);
        process.exit(1);
    }
}

// Fetch content from URL
async function fetchContent(url, waitSeconds = 5) {
    const context = await browser.newContext();
    const page = await context.newPage();

    try {
        await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });
        await page.waitForTimeout(waitSeconds * 1000);
        const content = await page.content();
        return content;
    } finally {
        await context.close();
    }
}

// Process request queue
async function processQueue() {
    if (requestQueue.length === 0 || activeTabs >= MAX_CONCURRENT_TABS) {
        return;
    }

    const { url, waitSeconds, resolve, reject } = requestQueue.shift();
    activeTabs++;

    try {
        const content = await fetchContent(url, waitSeconds);
        resolve(content);
    } catch (error) {
        reject(error);
    } finally {
        activeTabs--;
        processQueue(); // Process next in queue
    }
}

// Enqueue fetch request
function enqueueFetch(url, waitSeconds) {
    return new Promise((resolve, reject) => {
        requestQueue.push({ url, waitSeconds, resolve, reject });
        processQueue();
    });
}

// Health check endpoint
app.get('/health', (req, res) => {
    res.json({
        status: 'ok',
        browser: browser ? 'connected' : 'disconnected',
        activeTabs,
        queueLength: requestQueue.length
    });
});

// Fetch content endpoint
app.post('/fetch', async (req, res) => {
    const { url, waitSeconds = 5 } = req.body;

    if (!url) {
        return res.status(400).json({ error: 'URL is required' });
    }

    if (!browser) {
        return res.status(503).json({ error: 'Browser not initialized' });
    }

    const startTime = Date.now();
    console.log(`[Request] ${url} (wait: ${waitSeconds}s)`);

    try {
        const content = await enqueueFetch(url, waitSeconds);
        const duration = ((Date.now() - startTime) / 1000).toFixed(2);
        console.log(`[Success] ${url} (${content.length} bytes, ${duration}s)`);
        res.json({ content });
    } catch (error) {
        const duration = ((Date.now() - startTime) / 1000).toFixed(2);
        console.error(`[Error] ${url} (${duration}s):`, error.message);
        res.status(500).json({ error: error.message });
    }
});

// Graceful shutdown
async function shutdown() {
    console.log('[Shutdown] Closing browser...');
    if (browser) {
        await browser.close();
    }
    console.log('[Shutdown] ✓ Browser closed');
    process.exit(0);
}

process.on('SIGTERM', shutdown);
process.on('SIGINT', shutdown);

// Start server
async function start() {
    await initBrowser();
    app.listen(PORT, '127.0.0.1', () => {
        console.log(`[Server] Listening on http://127.0.0.1:${PORT}`);
    });
}

start().catch(error => {
    console.error('[Fatal]', error);
    process.exit(1);
});
