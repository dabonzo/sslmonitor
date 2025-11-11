# Monitoring Guide for Phase 6 Testing

This guide provides practical instructions for monitoring logs, queues, and system health during Phase 6 testing. Use this alongside [EXPECTED_BEHAVIOR.md](./EXPECTED_BEHAVIOR.md) to verify actual behavior matches expectations.

## Table of Contents
- [Prerequisites](#prerequisites)
- [Laravel Boost MCP Tools](#laravel-boost-mcp-tools)
- [Monitoring Laravel Logs](#monitoring-laravel-logs)
- [Monitoring Browser Console](#monitoring-browser-console)
- [Monitoring Queue Health](#monitoring-queue-health)
- [Database Inspection](#database-inspection)
- [Network Request Monitoring](#network-request-monitoring)
- [Real-Time Monitoring Setup](#real-time-monitoring-setup)
- [Issue Detection Patterns](#issue-detection-patterns)

---

## Prerequisites

### Start Development Environment
```bash
./vendor/bin/sail up -d
```

### Start Horizon (Queue Worker)
```bash
./vendor/bin/sail artisan horizon
```

### Start Vite (Frontend Dev Server)
```bash
./vendor/bin/sail npm run dev
```

### Access Horizon Dashboard
```
http://localhost/horizon
```
- View queue status, job throughput, failed jobs
- Monitor memory usage and worker health

---

## Laravel Boost MCP Tools

Laravel Boost provides powerful MCP (Model Context Protocol) tools for inspecting the application. These are accessible via Claude Code.

### Read Application Logs

**Tool**: `mcp__laravel-boost__read-log-entries`

**Usage**:
```javascript
// Read last 50 log entries
mcp__laravel-boost__read-log-entries({ entries: 50 })

// Read last 100 log entries (for detailed debugging)
mcp__laravel-boost__read-log-entries({ entries: 100 })
```

**What to Look For**:
- `[INFO]` entries confirming expected operations
- `[ERROR]` or `[CRITICAL]` entries indicating failures
- Job execution logs (`[JOB_START]`, `[JOB_COMPLETE]`, `[JOB_FAILED]`)
- Scheduler logs (`[SCHEDULER]`)
- Website check logs (`[WEBSITE_CHECK]`)
- Alert trigger logs (`[INFO] Triggering alert`)

**Example Output**:
```
[2025-11-10 14:23:45] local.INFO: Monitor synchronized for website
  {"website_id":123,"monitor_id":456,"url":"https://example.com",...}

[2025-11-10 14:23:50] local.INFO: Starting SSL certificate analysis for: https://example.com
  {"website_id":123}

[2025-11-10 14:23:55] local.INFO: Completed SSL certificate analysis for: https://example.com
  {"website_id":123}
```

### Read Browser Console Logs

**Tool**: `mcp__laravel-boost__browser-logs`

**Usage**:
```javascript
// Read last 20 browser log entries
mcp__laravel-boost__browser-logs({ entries: 20 })

// Read only errors
mcp__laravel-boost__browser-logs({ entries: 50, onlyErrors: true })
```

**What to Look For**:
- JavaScript errors (syntax errors, undefined variables)
- Network request failures (404, 500 errors)
- Vue component errors
- Inertia.js routing issues
- Console warnings about deprecated APIs

### Get Last Error Details

**Tool**: `mcp__laravel-boost__last-error`

**Usage**:
```javascript
// Get details of the most recent error/exception
mcp__laravel-boost__last-error()
```

**What You Get**:
- Exception class and message
- Stack trace
- File and line number
- Request context (URL, method, user)

### Check Database Schema

**Tool**: `mcp__laravel-boost__database-schema`

**Usage**:
```javascript
// Get full database schema
mcp__laravel-boost__database-schema()

// Filter to specific tables
mcp__laravel-boost__database-schema({ filter: "monitoring" })
```

**Use Cases**:
- Verify table structure matches expectations
- Check indexes for performance
- Validate foreign key relationships
- Inspect column types and constraints

### Query Database

**Tool**: `mcp__laravel-boost__database-query`

**Usage**:
```javascript
// Check monitoring results count
mcp__laravel-boost__database-query({
  query: "SELECT COUNT(*) as total FROM monitoring_results WHERE created_at > NOW() - INTERVAL 1 HOUR"
})

// Check failed jobs
mcp__laravel-boost__database-query({
  query: "SELECT * FROM failed_jobs ORDER BY failed_at DESC LIMIT 10"
})

// Check alert configurations
mcp__laravel-boost__database-query({
  query: "SELECT website_id, alert_type, enabled, last_triggered_at FROM alert_configurations WHERE enabled = 1"
})
```

**IMPORTANT**: Only read-only queries allowed (SELECT, SHOW, EXPLAIN, DESCRIBE)

### List Artisan Commands

**Tool**: `mcp__laravel-boost__list-artisan-commands`

**Usage**:
```javascript
// List all available commands
mcp__laravel-boost__list-artisan-commands()
```

**Useful Commands to Know**:
- `monitor:check-uptime` - Run uptime checks manually
- `monitor:check-certificate` - Run SSL checks manually
- `horizon:status` - Check Horizon status
- `queue:work` - Start queue worker
- `queue:failed` - List failed jobs
- `queue:retry` - Retry failed jobs

---

## Monitoring Laravel Logs

### Real-Time Log Monitoring (Terminal)

**Method 1: Via Sail**
```bash
./vendor/bin/sail artisan tail
```
- Follows Laravel logs in real-time
- Press Ctrl+C to stop

**Method 2: Via Docker Logs**
```bash
docker logs -f ssl-monitor-v4-laravel.test-1
```
- Includes all container output (PHP-FPM, scheduler, etc.)
- More verbose than `artisan tail`

**Method 3: Via Log File**
```bash
tail -f storage/logs/laravel.log
```
- Direct file access
- Fastest method for large log files

### Filtering Logs by Type

**Show Only Errors**:
```bash
./vendor/bin/sail artisan tail | grep -i "ERROR\|CRITICAL"
```

**Show Only Job Logs**:
```bash
./vendor/bin/sail artisan tail | grep -i "JOB_START\|JOB_COMPLETE\|JOB_FAILED"
```

**Show Only Alert Logs**:
```bash
./vendor/bin/sail artisan tail | grep -i "Triggering alert\|Email alert sent"
```

**Show Only Scheduler Logs**:
```bash
./vendor/bin/sail artisan tail | grep -i "SCHEDULER"
```

### Log Analysis Workflow

1. **Start Fresh**:
```bash
# Clear old logs
./vendor/bin/sail artisan log:clear

# Or rotate logs
mv storage/logs/laravel.log storage/logs/laravel-$(date +%Y%m%d).log
touch storage/logs/laravel.log
```

2. **Run Test Action** (e.g., create website, trigger check)

3. **Review Logs**:
```bash
# Via MCP tool
mcp__laravel-boost__read-log-entries({ entries: 50 })

# Via terminal
tail -100 storage/logs/laravel.log
```

4. **Compare to Expected Behavior** (see EXPECTED_BEHAVIOR.md)

---

## Monitoring Browser Console

### Chrome DevTools
1. Open Chrome DevTools (F12 or Cmd+Option+I)
2. Navigate to **Console** tab
3. Filter by:
   - **Errors** (red X icon)
   - **Warnings** (yellow triangle icon)
   - **Info** (blue i icon)

### Console Log Types

**Network Errors**:
```
Failed to load resource: the server responded with a status of 404 (Not Found)
```

**JavaScript Errors**:
```
Uncaught TypeError: Cannot read property 'name' of undefined
```

**Vue Component Errors**:
```
[Vue warn]: Property "website" was accessed during render but is not defined on instance
```

**Inertia.js Errors**:
```
Inertia request failed: 500 Internal Server Error
```

### Network Tab Monitoring
1. Navigate to **Network** tab
2. Filter by:
   - **XHR** - AJAX requests
   - **JS** - JavaScript files
   - **CSS** - Stylesheets
   - **Img** - Images

3. Check for:
   - Failed requests (red status codes)
   - Slow requests (> 1000ms)
   - Large payloads (> 1MB)

---

## Monitoring Queue Health

### Horizon Dashboard

**Access**: `http://localhost/horizon`

**Key Metrics**:
- **Jobs Per Minute**: Should be > 0 if queues active
- **Failed Jobs**: Should be 0 (or documented failures)
- **Wait Time**: Should be < 60 seconds
- **Memory Usage**: Should be stable (not growing)

**Queues to Monitor**:
1. **default**: General jobs, `CheckMonitorJob`
2. **monitoring-history**: `RecordMonitoringResult`, `CheckAlertConditions`
3. **monitoring-aggregation**: `UpdateMonitoringSummaries`
4. **monitoring-analysis**: `AnalyzeSslCertificateJob` (implicit)

### Horizon Status via Artisan

```bash
# Check Horizon status
./vendor/bin/sail artisan horizon:status

# Expected output:
# Horizon is running.
```

### Queue Failed Jobs

**View Failed Jobs**:
```bash
./vendor/bin/sail artisan queue:failed
```

**Expected Output**:
```
+------+------------+-------+------------------+---------------------+
| ID   | Connection | Queue | Class            | Failed At           |
+------+------------+-------+------------------+---------------------+
| (empty if no failures)                                             |
+------+------------+-------+------------------+---------------------+
```

**Retry Failed Job**:
```bash
./vendor/bin/sail artisan queue:retry {job-id}

# Retry all failed jobs
./vendor/bin/sail artisan queue:retry all
```

**Flush Failed Jobs** (clear table):
```bash
./vendor/bin/sail artisan queue:flush
```

### Queue Listening (Development)

**Start Synchronous Queue Worker** (for debugging):
```bash
./vendor/bin/sail artisan queue:work --once
```
- Processes one job then exits
- Useful for debugging job logic

**Start Continuous Queue Worker**:
```bash
./vendor/bin/sail artisan queue:work
```
- Processes jobs continuously
- Use `queue:listen` for auto-reload on code changes

---

## Database Inspection

### Via Laravel Boost MCP

**Check Monitoring Results**:
```javascript
mcp__laravel-boost__database-query({
  query: `
    SELECT
      id, website_id, check_type, status,
      uptime_status, ssl_status, response_time_ms,
      created_at
    FROM monitoring_results
    ORDER BY created_at DESC
    LIMIT 10
  `
})
```

**Check Monitoring Summaries**:
```javascript
mcp__laravel-boost__database-query({
  query: `
    SELECT
      website_id, period_type, period_start, period_end,
      total_checks, successful_checks, failed_checks,
      uptime_percentage, average_response_time_ms
    FROM monitoring_summaries
    WHERE period_type = 'hourly'
    ORDER BY period_start DESC
    LIMIT 10
  `
})
```

**Check Alert Configurations**:
```javascript
mcp__laravel-boost__database-query({
  query: `
    SELECT
      website_id, alert_type, alert_level, enabled,
      threshold_days, cooldown_seconds,
      last_triggered_at
    FROM alert_configurations
    WHERE enabled = 1
  `
})
```

**Check Monitors**:
```javascript
mcp__laravel-boost__database-query({
  query: `
    SELECT
      id, url, uptime_check_enabled, certificate_check_enabled,
      uptime_status, certificate_status,
      uptime_last_check_date, certificate_expiration_date
    FROM monitors
  `
})
```

### Via Tinker

**Start Tinker**:
```bash
./vendor/bin/sail artisan tinker
```

**Example Queries**:
```php
// Get all monitors
App\Models\Monitor::all();

// Get latest monitoring result
App\Models\MonitoringResult::latest()->first();

// Get website with latest SSL certificate data
$website = App\Models\Website::find(1);
$website->latest_ssl_certificate;

// Check alert configurations
App\Models\AlertConfiguration::where('enabled', true)->get();
```

### Via MySQL Client

**Connect to Database**:
```bash
./vendor/bin/sail mysql
```

**Useful Queries**:
```sql
-- Show all tables
SHOW TABLES;

-- Describe monitoring_results structure
DESCRIBE monitoring_results;

-- Count monitoring results by status
SELECT status, COUNT(*) as count
FROM monitoring_results
GROUP BY status;

-- Show recent failed checks
SELECT website_id, check_type, error_message, created_at
FROM monitoring_results
WHERE status = 'failed'
ORDER BY created_at DESC
LIMIT 10;
```

---

## Network Request Monitoring

### Via Browser DevTools

**Monitor Inertia Requests**:
1. Open Network tab
2. Filter by **XHR**
3. Look for requests to:
   - `/dashboard`
   - `/websites`
   - `/websites/{id}/check`
   - `/monitoring/results`

**Check Request Headers**:
```
X-Inertia: true
X-Inertia-Version: {hash}
Content-Type: application/json
```

**Check Response**:
```json
{
  "component": "Dashboard/Index",
  "props": {
    "websites": [...],
    "flash": {...}
  },
  "url": "/dashboard",
  "version": "{hash}"
}
```

### Via Laravel Boost MCP

**List Routes**:
```javascript
// Get all routes
mcp__laravel-boost__list-routes()

// Filter monitoring routes
mcp__laravel-boost__list-routes({ path: "monitoring" })

// Filter API routes
mcp__laravel-boost__list-routes({ path: "api" })
```

---

## Real-Time Monitoring Setup

### Terminal Multiplexer Setup (tmux/screen)

**Terminal 1: Application Logs**
```bash
./vendor/bin/sail artisan tail
```

**Terminal 2: Horizon Queue Worker**
```bash
./vendor/bin/sail artisan horizon
```

**Terminal 3: Vite Dev Server**
```bash
./vendor/bin/sail npm run dev
```

**Terminal 4: Test Execution**
```bash
./vendor/bin/sail artisan test --parallel
```

### VS Code Setup

**Install Extensions**:
- Laravel Extra Intellisense
- PHP Intelephense
- Vue - Official
- Tailwind CSS IntelliSense

**Open Multiple Terminals**:
1. Terminal 1: Logs (`artisan tail`)
2. Terminal 2: Horizon (`artisan horizon`)
3. Terminal 3: Tests (`artisan test`)

### Monitoring Dashboard (Custom)

Create a custom monitoring dashboard in `resources/js/Pages/Admin/Monitoring.vue` with:
- Real-time queue status (via Horizon API)
- Recent monitoring results
- Active alerts
- System health metrics

---

## Issue Detection Patterns

### Pattern 1: Orphaned Monitor Warning

**Symptom**:
```
[WARNING] Monitor being created without matching Website
  - monitor_url: https://example.com
  - created_via: Unknown source
```

**Cause**: Race condition or direct Monitor creation

**Action**:
1. Check recent code changes to `WebsiteObserver`
2. Verify tests use `Website::factory()`, not `Monitor::factory()`
3. Check for manual Monitor creation in seeders/commands

### Pattern 2: SSL Analysis Timeout

**Symptom**:
```
[ERROR] Failed to analyze SSL certificate for: https://example.com
  - website_id: 123
  - error: Connection timeout after 30s
```

**Cause**: Network connectivity issue or slow remote server

**Action**:
1. Verify network connectivity: `ping example.com`
2. Test SSL connection: `openssl s_client -connect example.com:443`
3. Check firewall rules
4. Increase timeout in `SslCertificateAnalysisService`

### Pattern 3: Queue Not Processing

**Symptom**:
- Jobs stuck in `pending` status
- Horizon shows 0 jobs per minute
- No job completion logs

**Cause**: Horizon not running or crashed

**Action**:
1. Check Horizon status: `artisan horizon:status`
2. Restart Horizon: `artisan horizon:terminate` then restart
3. Check Redis connectivity
4. Review Horizon logs for crashes

### Pattern 4: Failed Jobs Accumulating

**Symptom**:
```
+------+------------+-------+------------------+---------------------+
| ID   | Connection | Queue | Class            | Failed At           |
+------+------------+-------+------------------+---------------------+
| 1234 | redis      | default | CheckMonitorJob | 2025-11-10 14:23:45 |
| 1235 | redis      | default | CheckMonitorJob | 2025-11-10 14:24:15 |
+------+------------+-------+------------------+---------------------+
```

**Cause**: Repeated job failures due to code bug or external service issue

**Action**:
1. Review last error: `mcp__laravel-boost__last-error()`
2. Check job payload: `SELECT * FROM failed_jobs WHERE id = 1234`
3. Fix underlying issue
4. Retry job: `artisan queue:retry 1234`

### Pattern 5: Memory Leak in Queue Worker

**Symptom**:
- Horizon memory usage growing continuously
- Worker processes killed by OS
- Logs show: `Horizon worker exited: 137` (killed by signal)

**Cause**: Memory leak in job processing or large payloads

**Action**:
1. Monitor memory: Watch Horizon dashboard
2. Profile job: Add memory logging in `CheckMonitorJob`
3. Reduce `maxProcesses` in `config/horizon.php`
4. Add memory limit: `memory_limit` in horizon config
5. Restart workers more frequently

### Pattern 6: Alert Spam

**Symptom**:
```
[INFO] Email alert sent - 10 times in 1 hour
```

**Cause**: Cooldown period not working or disabled

**Action**:
1. Check cooldown config: `alert_configurations.cooldown_seconds`
2. Verify `last_triggered_at` is being updated
3. Review `AlertService::triggerAlert()` logic
4. Increase cooldown period: Default 24h (86400 seconds)

### Pattern 7: Database Query Slow

**Symptom**:
```
[WARNING] Slow query detected: 2345ms
  - query: SELECT * FROM monitoring_results WHERE ...
```

**Cause**: Missing database index or large dataset

**Action**:
1. Review query: Enable query logging
2. Analyze with EXPLAIN: `EXPLAIN SELECT * FROM ...`
3. Add index if needed: Create migration
4. Paginate results: Use `simplePaginate()` or `cursorPaginate()`

---

## Monitoring Checklist

Use this checklist during testing sessions:

### Pre-Test Setup
- [ ] Development environment running (`sail up -d`)
- [ ] Horizon running (`artisan horizon`)
- [ ] Vite dev server running (`npm run dev`)
- [ ] Logs cleared or rotated
- [ ] Browser console cleared
- [ ] Horizon failed jobs cleared (if any)

### During Test
- [ ] Monitor Laravel logs in real-time
- [ ] Monitor browser console for JS errors
- [ ] Check Horizon dashboard for queue health
- [ ] Watch network requests in DevTools
- [ ] Note any ERROR or WARNING logs

### Post-Test Analysis
- [ ] Review full log output (last 100 entries)
- [ ] Check for failed jobs in Horizon
- [ ] Verify database state matches expectations
- [ ] Compare actual logs to expected logs (EXPECTED_BEHAVIOR.md)
- [ ] Document any discrepancies in PHASE6_LOG_ANALYSIS.md

### Issue Investigation
- [ ] Get last error details via MCP
- [ ] Query database for relevant records
- [ ] Check queue retry history
- [ ] Review stack traces
- [ ] Reproduce issue in isolation
- [ ] Document root cause and fix

---

## Quick Reference Commands

### Logs
```bash
# Real-time logs
./vendor/bin/sail artisan tail

# Last 50 entries via MCP
mcp__laravel-boost__read-log-entries({ entries: 50 })

# Last error via MCP
mcp__laravel-boost__last-error()
```

### Queues
```bash
# Check Horizon status
./vendor/bin/sail artisan horizon:status

# View failed jobs
./vendor/bin/sail artisan queue:failed

# Retry failed job
./vendor/bin/sail artisan queue:retry {id}

# Retry all
./vendor/bin/sail artisan queue:retry all
```

### Database
```bash
# Connect to MySQL
./vendor/bin/sail mysql

# Query via MCP
mcp__laravel-boost__database-query({ query: "SELECT ..." })

# Get schema via MCP
mcp__laravel-boost__database-schema({ filter: "monitoring" })
```

### Testing
```bash
# Run all tests (parallel)
./vendor/bin/sail artisan test --parallel

# Run specific test
./vendor/bin/sail artisan test --filter=TestName

# Profile slow tests
./vendor/bin/sail artisan test --profile
```

---

## Next Steps

After setting up monitoring:
1. Review [EXPECTED_BEHAVIOR.md](./EXPECTED_BEHAVIOR.md) to understand what logs to expect
2. Use [PHASE6_LOG_ANALYSIS.md](./PHASE6_LOG_ANALYSIS.md) template to document findings
3. Run tests while monitoring logs and queues
4. Compare actual behavior to expected behavior
5. Report discrepancies immediately
