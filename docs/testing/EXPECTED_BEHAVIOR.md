# Expected Monitoring Behavior

This document describes the expected workflow, logs, and system behavior for SSL Monitor v4's monitoring system. Use this as a reference when analyzing logs and verifying test results.

## Table of Contents
- [Monitor Creation Flow](#monitor-creation-flow)
- [SSL Certificate Analysis Flow](#ssl-certificate-analysis-flow)
- [Scheduled Monitoring Flow](#scheduled-monitoring-flow)
- [Alert System Flow](#alert-system-flow)
- [Historical Data Recording Flow](#historical-data-recording-flow)
- [Expected Logs by Operation](#expected-logs-by-operation)
- [Queue Assignment](#queue-assignment)

## Monitor Creation Flow

### Trigger
User creates a Website via UI or API

### Expected Workflow
1. **Website Created** (`Website::create()`)
   - `WebsiteObserver::created()` fires
   - Checks if monitoring enabled (`ssl_monitoring_enabled` or `uptime_monitoring_enabled`)

2. **Monitor Integration** (`MonitorIntegrationService::createOrUpdateMonitorForWebsite()`)
   - Creates/updates Monitor record via Spatie's system
   - Syncs monitoring settings (SSL, uptime, intervals, content validation)
   - Sets up JavaScript rendering if configured

3. **Monitor Observer Verification** (`MonitorObserver::creating()` and `::created()`)
   - Logs warning if Monitor created without matching Website
   - Verifies relationship after creation
   - Expected source: `WebsiteObserver (expected)`

4. **SSL Analysis Job Dispatch** (if `ssl_monitoring_enabled`)
   - `AnalyzeSslCertificateJob` dispatched to `monitoring-analysis` queue
   - 5-second delay to ensure Monitor is created
   - Job analyzes certificate and saves full certificate data

### Expected Logs
```
[INFO] Monitor synchronized for website
  - website_id: 123
  - monitor_id: 456
  - url: https://example.com
  - uptime_enabled: true
  - ssl_enabled: true
  - content_validation_enabled: false
  - javascript_enabled: false

[INFO] Starting SSL certificate analysis for: https://example.com
  - website_id: 123

[INFO] Completed SSL certificate analysis for: https://example.com
  - website_id: 123
```

### Expected Database State
- `websites` table: New record with monitoring flags
- `monitors` table: New record via Spatie's system
- `website.latest_ssl_certificate`: Populated with full certificate data (if SSL enabled)
- `website.ssl_certificate_analyzed_at`: Timestamp of analysis

### Queue Activity
- **Queue**: `monitoring-analysis`
- **Job**: `App\Jobs\AnalyzeSslCertificateJob`
- **Expected Duration**: 2-10 seconds (depending on network latency)

---

## SSL Certificate Analysis Flow

### Trigger
- Website created with `ssl_monitoring_enabled: true`
- Manual "Analyze Now" button click
- Certificate renewal detected during scheduled check

### Expected Workflow
1. **Job Dispatched** (`AnalyzeSslCertificateJob`)
   - Queue: `monitoring-analysis`
   - Payload: Website model

2. **Analysis Service** (`SslCertificateAnalysisService::analyzeAndSave()`)
   - Connects to remote server via SSL
   - Extracts full certificate chain
   - Parses certificate details (issuer, subject, validity dates, SANs)
   - Detects Let's Encrypt vs commercial certificates
   - Saves to `website.latest_ssl_certificate` JSON field

3. **Monitor Update**
   - Spatie monitor updated with basic SSL status
   - Certificate expiration date stored
   - Issuer information saved

### Expected Logs
```
[INFO] Starting SSL certificate analysis for: https://example.com
  - website_id: 123

[INFO] Completed SSL certificate analysis for: https://example.com
  - website_id: 123
```

### Expected Database State
```json
// website.latest_ssl_certificate
{
  "issuer": "Let's Encrypt Authority X3",
  "subject": "example.com",
  "valid_from": "2025-10-27T00:00:00.000000Z",
  "valid_until": "2025-12-26T23:59:59.000000Z",
  "domains": ["example.com", "www.example.com"],
  "serial_number": "04:A1:B2:C3...",
  "is_lets_encrypt": true
}
```

---

## Scheduled Monitoring Flow

### Trigger
Laravel Scheduler runs `monitor:check-uptime` and `monitor:check-certificate` commands (via Spatie)

### Expected Workflow
1. **Spatie Commands Execute**
   - `monitor:check-uptime` runs every 5 minutes (configurable)
   - `monitor:check-certificate` runs every 12 hours (configurable)

2. **CheckMonitorJob Dispatched** (custom enhancement)
   - Queue: `default`
   - Triggered for each monitor
   - Check type: `uptime`, `ssl`, or `both`

3. **Check Execution**
   - **Start Event**: `MonitoringCheckStarted` fired
   - Uptime check performed (if enabled)
   - SSL check performed (if enabled)
   - Certificate renewal detection
   - **Complete Event**: `MonitoringCheckCompleted` fired

4. **Event Listeners Process Results** (async, queued)
   - `RecordMonitoringResult` → Queue: `monitoring-history`
   - `CheckAlertConditions` → Queue: `monitoring-history`
   - `UpdateMonitoringSummaries` → Queue: `monitoring-aggregation`

### Expected Logs
```
[SCHEDULER] Starting scheduled check for monitor: https://example.com
  - monitor_id: 456

[WEBSITE_CHECK] Uptime check for https://example.com
  - status: up
  - response_time: 145
  - status_code: 200

[WEBSITE_CHECK] SSL check for https://example.com
  - status: valid
  - expires_at: 2025-12-26T23:59:59.000000Z
  - issuer: Let's Encrypt Authority X3
  - certificate_status: valid

[SCHEDULER] Completed scheduled check for monitor: https://example.com
  - monitor_id: 456
  - uptime_status: up
  - ssl_status: valid

[JOB_COMPLETE] App\Jobs\CheckMonitorJob completed in 2345.67ms
  - monitor_id: 456
  - results: {...}
```

### Expected Database State
- `monitors` table: Updated with latest check results
- `monitoring_results` table: New record created by `RecordMonitoringResult` listener
- `monitoring_summaries` table: Updated by `UpdateMonitoringSummaries` listener (hourly aggregation)

### Queue Activity
1. **Primary Queue**: `default`
   - Job: `App\Jobs\CheckMonitorJob`

2. **History Queue**: `monitoring-history` (processes in parallel)
   - Listener: `RecordMonitoringResult`
   - Listener: `CheckAlertConditions`

3. **Aggregation Queue**: `monitoring-aggregation` (processes after history)
   - Listener: `UpdateMonitoringSummaries`

---

## Alert System Flow

### Trigger
Monitoring check detects alert condition (SSL expiring, uptime down, slow response)

### Expected Workflow
1. **Alert Evaluation** (`AlertService::checkAndTriggerAlerts()`)
   - Loads `AlertConfiguration` for website
   - Prepares check data (status, days remaining, response time)
   - Evaluates threshold conditions
   - Checks cooldown period (prevents alert spam)

2. **Alert Triggered** (`AlertService::triggerAlert()`)
   - Logs alert trigger
   - Sends notifications based on configured channels:
     - Email: Via Mailable classes
     - Dashboard: Creates notification record
     - Slack: (future implementation)
   - Marks alert as triggered (`alert_configurations.last_triggered_at`)

3. **Email Dispatch** (`AlertService::sendEmailAlert()`)
   - Selects appropriate Mailable class:
     - `SslCertificateExpiryAlert`
     - `SslCertificateInvalidAlert`
     - `UptimeDownAlert`
     - `SlowResponseTimeAlert`
   - Sends to website owner's email
   - Logs email sent confirmation

### Expected Logs
```
[INFO] Triggering alert
  - alert_type: ssl_expiry
  - website_id: 123
  - alert_level: warning

[INFO] Email alert sent
  - alert_type: ssl_expiry
  - recipient: user@example.com
  - website: Example Website
```

### Expected Database State
- `alert_configurations` table: `last_triggered_at` updated
- `monitoring_events` table: Alert event recorded
- Mail queue: Email job dispatched (if using queue)

### Alert Cooldown
- Default: 24 hours between repeat alerts
- Prevents alert fatigue
- Can be bypassed with `bypassCooldown: true` parameter

---

## Historical Data Recording Flow

### Trigger
`MonitoringCheckCompleted` event fired after successful monitoring check

### Expected Workflow
1. **Event Fired** (`CheckMonitorJob::handle()`)
   - Contains: monitor, trigger type, timestamps, check results

2. **RecordMonitoringResult Listener** (Queue: `monitoring-history`)
   - Creates `MonitoringResult` record with full check data
   - Includes: uptime status, SSL status, response time, certificate info
   - Calls `AlertCorrelationService` to check/create alerts
   - Auto-resolves alerts if conditions improved

3. **UpdateMonitoringSummaries Listener** (Queue: `monitoring-aggregation`)
   - Aggregates results into hourly/daily/weekly/monthly summaries
   - Calculates averages, uptime percentages, incident counts
   - Updates `monitoring_summaries` table

4. **Data Retention** (automated cleanup)
   - Raw `monitoring_results`: 90 days
   - Aggregated `monitoring_summaries`: 1+ years

### Expected Logs
```
[INFO] Recording monitoring result
  - monitor_id: 456
  - check_type: both
  - status: success
  - uptime_status: up
  - ssl_status: valid
  - duration_ms: 2345

[INFO] Aggregating monitoring summaries
  - period: hourly
  - summaries_updated: 1
```

### Expected Database State
```sql
-- monitoring_results (detailed records)
INSERT INTO monitoring_results (
  uuid, monitor_id, website_id, check_type, trigger_type,
  started_at, completed_at, duration_ms,
  status, uptime_status, http_status_code, response_time_ms,
  ssl_status, certificate_issuer, certificate_expiration_date,
  days_until_expiration, ...
) VALUES (...);

-- monitoring_summaries (aggregated data)
INSERT INTO monitoring_summaries (
  website_id, period_type, period_start, period_end,
  total_checks, successful_checks, failed_checks,
  uptime_percentage, average_response_time_ms,
  ssl_checks, ssl_valid, ssl_issues_detected,
  incidents_count, ...
) VALUES (...);
```

---

## Expected Logs by Operation

### Website Creation
```
[INFO] Monitor synchronized for website
[WARNING] Monitor being created without matching Website (if race condition)
[INFO] Starting SSL certificate analysis for: https://example.com
[INFO] Completed SSL certificate analysis for: https://example.com
```

### Scheduled Monitoring Check
```
[SCHEDULER] Starting scheduled check for monitor: https://example.com
[WEBSITE_CHECK] Uptime check for https://example.com
[WEBSITE_CHECK] SSL check for https://example.com
[SCHEDULER] Completed scheduled check for monitor: https://example.com
[JOB_COMPLETE] App\Jobs\CheckMonitorJob completed in Xms
```

### SSL Certificate Renewal Detected
```
[INFO] Certificate renewal detected for: https://example.com
  - old_serial: 04:A1:B2:C3...
  - new_serial: 05:D4:E5:F6...
[INFO] Starting SSL certificate analysis for: https://example.com
[INFO] Completed SSL certificate analysis for: https://example.com
```

### Alert Triggered
```
[INFO] Triggering alert
  - alert_type: ssl_expiry
  - website_id: 123
  - alert_level: warning
[INFO] Email alert sent
  - alert_type: ssl_expiry
  - recipient: user@example.com
  - website: Example Website
```

### Job Failure
```
[ERROR] Uptime check failed for monitor: https://example.com
  - monitor_id: 456
  - error: Connection timeout after 30s
[JOB_FAILED] App\Jobs\CheckMonitorJob failed after 3 attempts
  - monitor_id: 456
  - final_error: Connection timeout after 30s
```

---

## Queue Assignment

### Production Environment (`config/horizon.php`)

#### Default Queue (`supervisor-1`)
- Max processes: 10
- Jobs: General application jobs, `CheckMonitorJob`

#### Monitoring History Queue (`monitoring-history`)
- Processes: 3
- Tries: 3
- Jobs:
  - `RecordMonitoringResult` (listener)
  - `CheckAlertConditions` (listener)

#### Monitoring Aggregation Queue (`monitoring-aggregation`)
- Processes: 2
- Tries: 2
- Jobs:
  - `UpdateMonitoringSummaries` (listener)
  - `AggregateMonitoringSummariesJob`

#### Monitoring Analysis Queue (implicit)
- Jobs:
  - `AnalyzeSslCertificateJob`

### Queue Processing Order
1. **Synchronous**: Website created, Monitor created (immediate)
2. **Async - Default**: `CheckMonitorJob` dispatched
3. **Async - Monitoring History**: Event listeners record results and check alerts (parallel)
4. **Async - Monitoring Aggregation**: Summaries aggregated (after history)

---

## Error Scenarios & Expected Behavior

### SSL Connection Failure
```
[ERROR] SSL check failed for monitor: https://example.com
  - monitor_id: 456
  - error: SSL handshake failed: certificate verify failed
```
- Monitor status: `invalid`
- Alert triggered: `SslCertificateInvalidAlert`
- User notified via email

### Uptime Check Timeout
```
[ERROR] Uptime check failed for monitor: https://example.com
  - monitor_id: 456
  - error: Connection timeout after 30s
```
- Monitor status: `down`
- Alert triggered: `UptimeDownAlert`
- Consecutive failure counter incremented

### Website Without Monitor (Race Condition)
```
[WARNING] Monitor being created without matching Website
  - monitor_url: https://example.com
  - certificate_check_enabled: true
  - uptime_check_enabled: true
  - created_via: Unknown source

[ERROR] Orphaned Monitor created - no matching Website found
  - monitor_id: 456
  - monitor_url: https://example.com
  - action_required: Create Website model or delete orphaned Monitor
```
- This indicates a bug in the creation flow
- Should be investigated immediately

### Queue Worker Failure
```
[ERROR] Failed to process job
  - job: App\Jobs\CheckMonitorJob
  - attempts: 3/3
  - error: Maximum execution time of 60s exceeded

[JOB_FAILED] App\Jobs\CheckMonitorJob failed after 3 attempts
  - monitor_id: 456
  - final_error: Maximum execution time exceeded
```
- Job moved to `failed_jobs` table
- Can be retried via Horizon UI
- Investigate long-running checks

---

## Verification Checklist

Use this checklist when verifying monitoring system behavior:

### Website Creation
- [ ] Website record created in database
- [ ] Monitor record created via Spatie system
- [ ] `WebsiteObserver::created()` fired
- [ ] `MonitorIntegrationService::createOrUpdateMonitorForWebsite()` called
- [ ] `AnalyzeSslCertificateJob` dispatched (if SSL enabled)
- [ ] No orphaned Monitor warnings in logs
- [ ] SSL certificate analysis completed within 10 seconds
- [ ] `website.latest_ssl_certificate` populated

### Scheduled Monitoring
- [ ] `CheckMonitorJob` dispatched to default queue
- [ ] `MonitoringCheckStarted` event fired
- [ ] Uptime check completed (if enabled)
- [ ] SSL check completed (if enabled)
- [ ] `MonitoringCheckCompleted` event fired
- [ ] `RecordMonitoringResult` listener executed
- [ ] `monitoring_results` record created
- [ ] `CheckAlertConditions` listener executed
- [ ] `UpdateMonitoringSummaries` listener executed
- [ ] `monitoring_summaries` updated

### Alert System
- [ ] Alert condition detected
- [ ] Cooldown period checked
- [ ] Alert not triggered if within cooldown
- [ ] Email notification sent (if triggered)
- [ ] `alert_configurations.last_triggered_at` updated
- [ ] Alert logged in `monitoring_events` table

### Queue Health
- [ ] Horizon running and accessible
- [ ] All queues processing jobs (default, monitoring-history, monitoring-aggregation)
- [ ] No jobs in `failed_jobs` table (or failures expected and documented)
- [ ] Queue wait times < 60 seconds
- [ ] No memory leaks or process crashes

### Data Integrity
- [ ] `monitors` table synchronized with `websites` table
- [ ] No orphaned monitors (monitors without matching websites)
- [ ] SSL certificate data matches remote server
- [ ] Response times logged accurately
- [ ] Historical data retention policies enforced

---

## Common Issues & Debugging

### Issue: Orphaned Monitor Warnings
**Symptom**: `[WARNING] Monitor being created without matching Website`

**Cause**:
- Race condition in observer execution
- Direct Monitor creation (should never happen)
- Test factory creating Monitor instead of Website

**Solution**:
- Always create Website, not Monitor directly
- Use `Website::factory()`, not `Monitor::factory()`
- WebsiteObserver automatically creates Monitor

### Issue: SSL Analysis Job Fails
**Symptom**: `[ERROR] Failed to analyze SSL certificate`

**Cause**:
- Network connectivity issue
- SSL handshake failure
- Timeout reaching remote server

**Solution**:
- Verify network connectivity from server
- Check firewall rules (port 443 outbound)
- Increase timeout in `SslCertificateAnalysisService`
- Verify remote server is accessible

### Issue: Alerts Not Triggering
**Symptom**: No alert emails despite failed checks

**Cause**:
- Alert cooldown period active
- No `AlertConfiguration` records
- Email configuration issue
- Queue not processing

**Solution**:
- Check `alert_configurations.last_triggered_at` (must be > 24h ago)
- Verify `AlertConfiguration` exists for website
- Test email configuration via `php artisan tinker`
- Check Horizon queue status

### Issue: Slow Queue Processing
**Symptom**: Jobs taking > 60 seconds to process

**Cause**:
- Too few queue workers
- Network latency to monitored sites
- Database query performance
- Memory leaks

**Solution**:
- Increase `maxProcesses` in `config/horizon.php`
- Add database indexes
- Profile slow queries with Laravel Debugbar
- Monitor memory usage, restart workers if leaking

---

## Performance Benchmarks

### Expected Timings
- Website creation: < 1 second (synchronous)
- SSL analysis job: 2-10 seconds (async)
- Uptime check: 0.5-2 seconds
- SSL certificate check: 1-5 seconds
- Alert evaluation: < 100ms
- Historical data recording: < 500ms
- Summary aggregation: < 2 seconds

### Queue Throughput
- **Default queue**: 10 jobs/second (10 workers)
- **Monitoring history**: 3 jobs/second (3 workers)
- **Monitoring aggregation**: 2 jobs/second (2 workers)

### Database Performance
- Monitoring result insert: < 50ms
- Summary aggregation query: < 200ms
- Alert condition check: < 100ms

---

## Next Steps

After understanding expected behavior:
1. Review [MONITORING_GUIDE.md](./MONITORING_GUIDE.md) for tools to monitor logs
2. Use [PHASE6_LOG_ANALYSIS.md](./PHASE6_LOG_ANALYSIS.md) template to document findings
3. Run tests and compare actual logs to expected logs
4. Report discrepancies immediately
