# JavaScript Content Fetcher Service

HTTP service for fetching JavaScript-rendered web content using Playwright with Firefox.

## Overview

This service runs a persistent Firefox browser instance and provides an HTTP API for rendering JavaScript-heavy websites. It's designed to work with the SSL Monitor's uptime monitoring to check websites that require JavaScript execution.

**Key Features:**
- Persistent browser instance (~200MB memory footprint)
- Tab-based concurrency (up to 5 concurrent requests)
- Request queue management
- Graceful shutdown handling
- Health check endpoint

## Architecture

```
Laravel Application (JavaScriptContentFetcher.php)
    ↓ HTTP POST
JS Content Fetcher Service (port 3000)
    ↓ Browser automation
Firefox Browser (persistent instance)
    ↓ Render
Target Website
```

## Installation

### 1. Install to Production Server

```bash
# SSH to production server
ssh root@monitor.intermedien.at

# Create service directory
mkdir -p /opt/js-content-fetcher
cd /opt/js-content-fetcher

# Copy files from repository
# - server.js
# - package.json

# Install dependencies
npm install

# Install Playwright Firefox browser
PLAYWRIGHT_BROWSERS_PATH=/var/www/monitor.intermedien.at/web/shared/.playwright \
  npx playwright install firefox
```

### 2. Configure Systemd Service

```bash
# Copy systemd service file
cp js-content-fetcher.service /etc/systemd/system/

# Reload systemd
systemctl daemon-reload

# Enable and start service
systemctl enable js-content-fetcher
systemctl start js-content-fetcher

# Check status
systemctl status js-content-fetcher
```

### 3. Configure Laravel Application

In `.env`:
```env
BROWSERSHOT_SERVICE_URL=http://127.0.0.1:3000
BROWSERSHOT_TIMEOUT=30
BROWSERSHOT_WAIT_SECONDS=5
```

## API Endpoints

### Health Check
```bash
GET http://127.0.0.1:3000/health

Response:
{
  "status": "ok",
  "browser": "connected",
  "activeTabs": 2,
  "queueLength": 0
}
```

### Fetch Content
```bash
POST http://127.0.0.1:3000/fetch
Content-Type: application/json

{
  "url": "https://example.com",
  "waitSeconds": 5
}

Response:
{
  "content": "<html>...</html>"
}
```

## Performance

- **Memory**: ~200MB fixed (persistent browser)
- **Throughput**: 10-20 requests/minute
- **Response Time**: 3-7 seconds per request (network + JS rendering + wait time)
- **Concurrency**: Up to 5 concurrent tabs

## Logs

```bash
# Service logs
tail -f /var/log/js-content-fetcher.log
tail -f /var/log/js-content-fetcher-error.log

# Systemd journal
journalctl -u js-content-fetcher -f
```

## Troubleshooting

### Service won't start
```bash
# Check service status
systemctl status js-content-fetcher

# Check logs
journalctl -u js-content-fetcher -n 50

# Check if Firefox is installed
ls -la /var/www/monitor.intermedien.at/web/shared/.playwright/firefox-*/firefox/firefox
```

### Browser fails to launch
```bash
# Check Firefox executable path in server.js
# Should match: /var/www/monitor.intermedien.at/web/shared/.playwright/firefox-XXXX/firefox/firefox

# Reinstall Firefox
cd /opt/js-content-fetcher
PLAYWRIGHT_BROWSERS_PATH=/var/www/monitor.intermedien.at/web/shared/.playwright \
  npx playwright install firefox
```

### Connection refused from Laravel
```bash
# Check if service is running
systemctl status js-content-fetcher

# Test health endpoint
curl http://127.0.0.1:3000/health

# Check firewall (should allow localhost)
```

## Maintenance

### Restart service
```bash
systemctl restart js-content-fetcher
```

### Update service
```bash
cd /opt/js-content-fetcher

# Pull latest code
git pull  # or manually copy updated files

# Install dependencies
npm install

# Restart service
systemctl restart js-content-fetcher
```

### Monitor memory usage
```bash
systemctl status js-content-fetcher | grep Memory
```

## Security

- Service runs as `root` to avoid permission issues with Firefox
- Listens only on `127.0.0.1` (localhost) - not exposed externally
- No authentication required (internal service only)
- Browser runs in headless mode with sandbox disabled (necessary for systemd environment)

## Why This Approach?

**Problem**: Running Firefox/Chrome from Laravel Horizon (as web6 user) failed with:
- Sandbox permission errors (EPERM on CLONE_NEWPID)
- Fontconfig cache directory issues
- Missing graphics libraries

**Solution**: Separate HTTP service running as root with:
- Proper permissions for browser execution
- Persistent browser pool (memory efficient)
- Clean separation of concerns
- Easy to monitor and restart independently
