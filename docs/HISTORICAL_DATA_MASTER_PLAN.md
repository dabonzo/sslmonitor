# SSL Monitor v4 - Historical Monitoring Data Tracking Master Plan

**Version**: 1.0
**Created**: October 17, 2025
**Status**: 🔴 NOT IMPLEMENTED (Consolidation of two previous plans)
**Target Completion**: 6 weeks from start date

---

## 📋 Executive Summary

### Purpose
Implement comprehensive historical data tracking for all SSL certificate and uptime monitoring checks in SSL Monitor v4, enabling trend analysis, performance reporting, debugging, and advanced analytics.

### Current Status
**⚠️ CRITICAL CLARIFICATION**: Despite one planning document claiming "Phase 1 Complete", **NO IMPLEMENTATION EXISTS**:
- ❌ No database tables created (`monitoring_results`, `monitoring_check_summaries`, etc.)
- ❌ No Laravel models implemented
- ❌ No event classes or listeners
- ❌ No historical data being captured

This document consolidates two previous plans into a single, definitive implementation roadmap.

### Expected Benefits
- **Complete Audit Trail**: Every check recorded with full context
- **Trend Analysis**: 7-day, 30-day, 90-day performance trends
- **Advanced Alerting**: Pattern-based alerts from historical data
- **Performance Debugging**: Detailed timing breakdowns for all checks
- **Compliance Support**: Full event audit trail for regulatory requirements
- **Business Intelligence**: Custom reports and analytics dashboards

### Key Metrics
- **Current State**: Only current status stored, no historical data
- **Target State**: All 530+ checks/day recorded with full details
- **Storage Impact**: ~23 MB/day for 50 websites (90-day retention = ~2.6 GB)
- **Performance Impact**: < 5% overhead on monitoring checks
- **Dashboard Improvement**: 15-30s load time → < 2s

---

## 🔍 Current System Analysis

### Existing Architecture
```
┌─────────────────────────────────────────────────────────┐
│  Current Monitoring Flow (NO Historical Data Capture)  │
└─────────────────────────────────────────────────────────┘

Laravel Scheduler (every minute)
        ↓
DispatchScheduledChecks Command
        ↓
CheckMonitorJob (queued)
        ↓
Monitor::checkCertificate() + Monitor::checkUptime()
        ↓
Updates monitors table (OVERWRITES previous data)
        ↓
AlertService evaluates conditions
        ↓
END (Previous check data is LOST)
```

### Current Data Flow
1. **Scheduled Checks**: Run every minute via `php artisan schedule:work`
2. **Manual Checks**: Triggered via "Check Now" button → `ImmediateWebsiteCheckJob`
3. **Data Storage**: Only current state in `monitors` table
4. **Alert System**: Uses current state from `alert_configurations` table

### What Data is Currently Lost
- ❌ Historical check results (SSL + uptime)
- ❌ Response time trends over time
- ❌ Certificate change history (renewals, issuer changes)
- ❌ Uptime/downtime patterns and durations
- ❌ Manual vs automated check attribution
- ❌ Detailed failure reasons and debugging context
- ❌ Performance degradation patterns
- ❌ Alert trigger history and acknowledgments

---

## 🗄️ Consolidated Database Schema (FINAL DECISION)

### Schema Overview: 4 Optimized Tables

After extensive analysis, we've consolidated from 6 tables (Plan 1) to 4 tables with optimized performance:

```
monitoring_results          ← Primary historical data (all checks)
monitoring_check_summaries  ← Pre-calculated aggregates (hourly/daily/weekly)
monitoring_alerts           ← Alert lifecycle tracking
monitoring_events           ← Audit trail (optional for compliance)
```

### Table 1: `monitoring_results` (Primary Historical Data)

**Purpose**: Single source of truth for all monitoring check results

```sql
CREATE TABLE monitoring_results (
    -- Primary Key & UUID
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    uuid CHAR(36) UNIQUE NOT NULL COMMENT 'UUID for external API references',

    -- Relationships (CRITICAL: Both monitor_id AND website_id for flexible queries)
    monitor_id BIGINT UNSIGNED NOT NULL COMMENT 'FK to monitors table',
    website_id BIGINT UNSIGNED NOT NULL COMMENT 'FK to websites table (enables direct queries)',

    -- Check Classification
    check_type ENUM('uptime', 'ssl_certificate', 'both') NOT NULL DEFAULT 'both',
    trigger_type ENUM('scheduled', 'manual_immediate', 'manual_bulk', 'system') NOT NULL,
    triggered_by_user_id BIGINT UNSIGNED NULL COMMENT 'User who triggered manual check',

    -- Check Timing (MILLISECOND PRECISION for accurate performance analysis)
    started_at TIMESTAMP(3) NOT NULL COMMENT 'High-precision start time',
    completed_at TIMESTAMP(3) NULL COMMENT 'High-precision completion time',
    duration_ms INT UNSIGNED NULL COMMENT 'Total check duration in milliseconds',

    -- Overall Check Status
    status ENUM('success', 'failed', 'timeout', 'error') NOT NULL,
    error_message TEXT NULL COMMENT 'Detailed error if failed',

    -- ==================== UPTIME-SPECIFIC DATA ====================
    uptime_status ENUM('up', 'down') NULL,
    http_status_code SMALLINT UNSIGNED NULL COMMENT 'HTTP status code (200, 404, 500, etc.)',
    response_time_ms INT UNSIGNED NULL COMMENT 'Response time in milliseconds',
    response_body_size_bytes INT UNSIGNED NULL COMMENT 'Response body size',
    redirect_count TINYINT UNSIGNED NULL DEFAULT 0 COMMENT 'Number of redirects followed',
    final_url VARCHAR(2048) NULL COMMENT 'Final URL after redirects',

    -- ==================== SSL CERTIFICATE-SPECIFIC DATA ====================
    ssl_status ENUM('valid', 'invalid', 'expired', 'expires_soon', 'self_signed') NULL,
    certificate_issuer VARCHAR(255) NULL COMMENT 'Certificate issuer (e.g., Let\'s Encrypt)',
    certificate_subject VARCHAR(255) NULL COMMENT 'Certificate subject',
    certificate_expiration_date TIMESTAMP NULL COMMENT 'Certificate expiration',
    certificate_valid_from_date TIMESTAMP NULL COMMENT 'Certificate valid from',
    days_until_expiration INT NULL COMMENT 'Days until certificate expires',
    certificate_chain JSON NULL COMMENT 'Full certificate chain data',

    -- ==================== CONTENT VALIDATION DATA ====================
    content_validation_enabled BOOLEAN DEFAULT FALSE,
    content_validation_status ENUM('passed', 'failed', 'not_checked') NULL,
    expected_strings_found JSON NULL COMMENT 'Array of expected strings that were found',
    forbidden_strings_found JSON NULL COMMENT 'Array of forbidden strings that were found',
    regex_matches JSON NULL COMMENT 'Regex pattern match results',
    javascript_rendered BOOLEAN DEFAULT FALSE COMMENT 'Was JavaScript rendering used?',
    javascript_wait_seconds TINYINT UNSIGNED NULL COMMENT 'Seconds waited for JS rendering',
    content_hash VARCHAR(64) NULL COMMENT 'SHA-256 hash of content for change detection',

    -- ==================== TECHNICAL DETAILS ====================
    check_method VARCHAR(20) DEFAULT 'GET' COMMENT 'HTTP method used',
    user_agent VARCHAR(255) NULL COMMENT 'User agent string used',
    request_headers JSON NULL COMMENT 'Request headers sent',
    response_headers JSON NULL COMMENT 'Response headers received',
    ip_address VARCHAR(45) NULL COMMENT 'Server IP address (IPv4/IPv6)',
    server_software VARCHAR(255) NULL COMMENT 'Server software identification',

    -- ==================== MONITORING CONTEXT ====================
    monitor_config JSON NULL COMMENT 'Monitor configuration at time of check (for debugging)',
    check_interval_minutes SMALLINT UNSIGNED NULL COMMENT 'Configured check interval',

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- ==================== INDEXES FOR PERFORMANCE ====================
    -- CRITICAL: Composite index for dashboard queries (90%+ improvement)
    INDEX idx_monitor_website_time (monitor_id, website_id, started_at DESC),

    -- Individual relationship indexes
    INDEX idx_monitor_results_monitor_id (monitor_id),
    INDEX idx_monitor_results_website_id (website_id),

    -- Query filters
    INDEX idx_check_type_status (check_type, status, started_at DESC),
    INDEX idx_trigger_type (trigger_type, started_at DESC),
    INDEX idx_status_time (status, started_at DESC),

    -- SSL-specific queries
    INDEX idx_ssl_expiration (certificate_expiration_date, ssl_status),

    -- Time-based queries (for data retention)
    INDEX idx_started_at (started_at),

    -- ==================== FOREIGN KEY CONSTRAINTS ====================
    CONSTRAINT fk_monitoring_results_monitor
        FOREIGN KEY (monitor_id) REFERENCES monitors(id) ON DELETE CASCADE,
    CONSTRAINT fk_monitoring_results_website
        FOREIGN KEY (website_id) REFERENCES websites(id) ON DELETE CASCADE,
    CONSTRAINT fk_monitoring_results_user
        FOREIGN KEY (triggered_by_user_id) REFERENCES users(id) ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Complete historical record of all monitoring checks (SSL + Uptime)';
```

**Storage Projection**:
- 50 websites × 1,440 checks/day = 72,000 rows/day
- ~350 bytes/row average = ~23 MB/day
- 90-day retention = ~2.6 GB steady state

### Table 2: `monitoring_check_summaries` (Pre-calculated Analytics)

**Purpose**: Pre-calculated aggregates for fast dashboard loading

```sql
CREATE TABLE monitoring_check_summaries (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    monitor_id BIGINT UNSIGNED NOT NULL,
    website_id BIGINT UNSIGNED NOT NULL,

    -- Summary Period
    summary_period ENUM('hourly', 'daily', 'weekly', 'monthly') NOT NULL,
    period_start TIMESTAMP NOT NULL,
    period_end TIMESTAMP NOT NULL,

    -- ==================== UPTIME SUMMARY STATISTICS ====================
    total_uptime_checks INT UNSIGNED DEFAULT 0,
    successful_uptime_checks INT UNSIGNED DEFAULT 0,
    failed_uptime_checks INT UNSIGNED DEFAULT 0,
    uptime_percentage DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Uptime % (0.00 to 100.00)',

    -- Response Time Metrics
    average_response_time_ms INT UNSIGNED DEFAULT 0,
    min_response_time_ms INT UNSIGNED DEFAULT 0,
    max_response_time_ms INT UNSIGNED DEFAULT 0,
    p95_response_time_ms INT UNSIGNED DEFAULT 0 COMMENT '95th percentile',
    p99_response_time_ms INT UNSIGNED DEFAULT 0 COMMENT '99th percentile',

    -- ==================== SSL SUMMARY STATISTICS ====================
    total_ssl_checks INT UNSIGNED DEFAULT 0,
    successful_ssl_checks INT UNSIGNED DEFAULT 0,
    failed_ssl_checks INT UNSIGNED DEFAULT 0,
    certificates_expiring INT UNSIGNED DEFAULT 0 COMMENT 'Certificates expiring in < 30 days',
    certificates_expired INT UNSIGNED DEFAULT 0,

    -- ==================== PERFORMANCE METRICS ====================
    total_checks INT UNSIGNED DEFAULT 0 COMMENT 'Total checks (SSL + uptime)',
    total_check_duration_ms BIGINT UNSIGNED DEFAULT 0,
    average_check_duration_ms INT UNSIGNED DEFAULT 0,

    -- ==================== CONTENT VALIDATION METRICS ====================
    total_content_validations INT UNSIGNED DEFAULT 0,
    successful_content_validations INT UNSIGNED DEFAULT 0,
    failed_content_validations INT UNSIGNED DEFAULT 0,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- ==================== INDEXES ====================
    -- Prevent duplicate summaries for same period
    UNIQUE KEY unique_summary (monitor_id, website_id, summary_period, period_start),

    -- Time-based queries
    INDEX idx_summary_period (summary_period, period_start DESC),
    INDEX idx_monitor_period (monitor_id, summary_period, period_start DESC),
    INDEX idx_website_period (website_id, summary_period, period_start DESC),

    -- ==================== FOREIGN KEYS ====================
    CONSTRAINT fk_summaries_monitor
        FOREIGN KEY (monitor_id) REFERENCES monitors(id) ON DELETE CASCADE,
    CONSTRAINT fk_summaries_website
        FOREIGN KEY (website_id) REFERENCES websites(id) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Pre-calculated summary statistics for fast dashboard queries';
```

**Performance Impact**: Dashboard load 15-30s → < 2s

### Table 3: `monitoring_alerts` (Alert Lifecycle Tracking)

**Purpose**: Track alert conditions, notifications, and resolution lifecycle

```sql
CREATE TABLE monitoring_alerts (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    monitor_id BIGINT UNSIGNED NOT NULL,
    website_id BIGINT UNSIGNED NOT NULL,

    -- Alert Classification
    alert_type ENUM(
        'uptime_down',
        'uptime_recovery',
        'ssl_expiring',
        'ssl_expired',
        'ssl_invalid',
        'performance_degradation',
        'content_validation_failed'
    ) NOT NULL,
    alert_severity ENUM('info', 'warning', 'urgent', 'critical') NOT NULL,
    alert_title VARCHAR(255) NOT NULL,
    alert_message TEXT NULL,

    -- Alert Lifecycle Timestamps
    first_detected_at TIMESTAMP NOT NULL COMMENT 'When alert condition first detected',
    last_occurred_at TIMESTAMP NULL COMMENT 'Most recent occurrence',
    acknowledged_at TIMESTAMP NULL COMMENT 'When user acknowledged',
    resolved_at TIMESTAMP NULL COMMENT 'When alert condition resolved',

    -- User Actions
    acknowledged_by_user_id BIGINT UNSIGNED NULL,
    acknowledgment_note TEXT NULL COMMENT 'User note when acknowledging',

    -- Alert Context (CRITICAL for debugging)
    trigger_value JSON NULL COMMENT 'Value that triggered alert (e.g., {"response_time": 5000})',
    threshold_value JSON NULL COMMENT 'Threshold that was exceeded (e.g., {"max_response_time": 2000})',
    affected_check_result_id BIGINT UNSIGNED NULL COMMENT 'FK to monitoring_results',

    -- Notification Tracking
    notifications_sent JSON NULL COMMENT 'Array of notifications sent with timestamps',
    notification_channels VARCHAR(255) NULL COMMENT 'Comma-separated: email,slack,webhook',
    notification_status ENUM('pending', 'sent', 'failed', 'acknowledged') DEFAULT 'pending',

    -- Alert Suppression
    suppressed BOOLEAN DEFAULT FALSE COMMENT 'Is alert suppressed during maintenance?',
    suppressed_until TIMESTAMP NULL COMMENT 'Suppression end time',

    -- Metadata
    occurrence_count INT UNSIGNED DEFAULT 1 COMMENT 'Number of times this alert occurred',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- ==================== INDEXES ====================
    INDEX idx_alerts_monitor (monitor_id, alert_type, first_detected_at DESC),
    INDEX idx_alerts_website (website_id, alert_severity, first_detected_at DESC),
    INDEX idx_alerts_type_severity (alert_type, alert_severity, resolved_at),
    INDEX idx_alerts_status (notification_status, first_detected_at DESC),
    INDEX idx_alerts_unresolved (resolved_at, first_detected_at DESC),

    -- ==================== FOREIGN KEYS ====================
    CONSTRAINT fk_alerts_monitor
        FOREIGN KEY (monitor_id) REFERENCES monitors(id) ON DELETE CASCADE,
    CONSTRAINT fk_alerts_website
        FOREIGN KEY (website_id) REFERENCES websites(id) ON DELETE CASCADE,
    CONSTRAINT fk_alerts_user
        FOREIGN KEY (acknowledged_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_alerts_check_result
        FOREIGN KEY (affected_check_result_id) REFERENCES monitoring_results(id) ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Alert lifecycle tracking with notifications and acknowledgments';
```

### Table 4: `monitoring_events` (Audit Trail - Optional)

**Purpose**: System and user event audit trail for compliance

```sql
CREATE TABLE monitoring_events (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    monitor_id BIGINT UNSIGNED NULL COMMENT 'Related monitor (if applicable)',
    website_id BIGINT UNSIGNED NULL COMMENT 'Related website (if applicable)',
    user_id BIGINT UNSIGNED NULL COMMENT 'User who triggered event (if applicable)',

    -- Event Classification
    event_type ENUM(
        'monitor_created',
        'monitor_updated',
        'monitor_deleted',
        'monitor_enabled',
        'monitor_disabled',
        'ssl_status_changed',
        'ssl_certificate_renewed',
        'ssl_issuer_changed',
        'uptime_status_changed',
        'performance_degraded',
        'performance_improved',
        'content_validation_failed',
        'content_validation_passed',
        'check_interval_changed',
        'alert_triggered',
        'alert_acknowledged',
        'alert_resolved',
        'user_action',
        'system_action'
    ) NOT NULL,
    event_name VARCHAR(255) NOT NULL COMMENT 'Human-readable event name',
    description TEXT NULL COMMENT 'Detailed event description',

    -- Change Tracking
    old_values JSON NULL COMMENT 'Previous values (for updates)',
    new_values JSON NULL COMMENT 'New values (for updates)',
    event_data JSON NULL COMMENT 'Additional event context',

    -- Request Context
    ip_address VARCHAR(45) NULL COMMENT 'User/system IP address',
    user_agent TEXT NULL COMMENT 'User agent string',
    source ENUM('system', 'user', 'api', 'webhook', 'cli') NOT NULL DEFAULT 'system',

    -- Timestamp
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- ==================== INDEXES ====================
    INDEX idx_events_type (event_type, created_at DESC),
    INDEX idx_events_monitor (monitor_id, event_type, created_at DESC),
    INDEX idx_events_website (website_id, event_type, created_at DESC),
    INDEX idx_events_user (user_id, event_type, created_at DESC),
    INDEX idx_events_created (created_at DESC),

    -- ==================== FOREIGN KEYS ====================
    CONSTRAINT fk_events_monitor
        FOREIGN KEY (monitor_id) REFERENCES monitors(id) ON DELETE CASCADE,
    CONSTRAINT fk_events_website
        FOREIGN KEY (website_id) REFERENCES websites(id) ON DELETE CASCADE,
    CONSTRAINT fk_events_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='System and user event audit trail for compliance and debugging';
```

**Note**: This table is optional. Include it if regulatory compliance or detailed audit trails are required.

---

## ⚡ Event-Driven Architecture

### Architecture Diagram

```
┌──────────────────────────────────────────────────────────────┐
│          MONITORING FLOW WITH HISTORICAL DATA CAPTURE         │
└──────────────────────────────────────────────────────────────┘

Scheduled Check (every minute)
        ↓
DispatchScheduledChecks Command
        ↓
CheckMonitorJob (horizon queue: "monitoring")
        ↓
    ┌─────────────────────────────────┐
    │  EVENT: MonitoringCheckStarted  │  ← NON-BLOCKING
    │  Payload: monitor_id, timestamp │
    └─────────────────────────────────┘
        ↓
Perform SSL + Uptime Checks (current logic unchanged)
        ↓
    ┌────────────────────────────────────┐
    │  EVENT: MonitoringCheckCompleted   │  ← NON-BLOCKING
    │  Payload: full check results       │
    └────────────────────────────────────┘
        ↓
CheckMonitorJob COMPLETES (no waiting for listeners)
        ↓
Monitoring continues normally

    ┌───────────────────────────────────────────────┐
    │   QUEUED EVENT LISTENERS (Async Processing)   │
    │   Queue: "monitoring-history" (priority: high)│
    └───────────────────────────────────────────────┘
            ↓
    [RecordMonitoringResult Listener]
            ↓
    INSERT INTO monitoring_results
            ↓
    [UpdateMonitoringSummaries Listener]
            ↓
    UPDATE monitoring_check_summaries
            ↓
    [CheckAlertConditions Listener]
            ↓
    INSERT INTO monitoring_alerts (if triggered)
            ↓
    [RecordMonitoringEvent Listener]
            ↓
    INSERT INTO monitoring_events
```

### Key Design Principles

1. **Zero Performance Impact**: Events are fire-and-forget, jobs continue immediately
2. **Queued Processing**: All listeners are queued (async) with proper failure handling
3. **Graceful Degradation**: Monitoring continues even if history recording fails
4. **Comprehensive Capture**: Every check recorded with full context
5. **User Attribution**: Manual checks tracked with user_id

---

## 🚀 Implementation Phases (6-Week Timeline)

### Phase 1: Foundation (Week 1)

**Goal**: Create database schema and core models

**Tasks**:
1. Create 4 database migrations
   - `create_monitoring_results_table.php`
   - `create_monitoring_check_summaries_table.php`
   - `create_monitoring_alerts_table.php`
   - `create_monitoring_events_table.php` (optional)

2. Create 4 Eloquent models
   - `app/Models/MonitoringResult.php`
   - `app/Models/MonitoringCheckSummary.php`
   - `app/Models/MonitoringAlert.php`
   - `app/Models/MonitoringEvent.php` (optional)

3. Define model relationships:
   - `MonitoringResult` → `belongsTo(Monitor, Website, User)`
   - `MonitoringCheckSummary` → `belongsTo(Monitor, Website)`
   - `MonitoringAlert` → `belongsTo(Monitor, Website, User, MonitoringResult)`
   - `MonitoringEvent` → `belongsTo(Monitor, Website, User)`

4. Write migration tests:
   - Test table creation
   - Test foreign key constraints
   - Test indexes exist

**Validation**:
```bash
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan test --filter=MigrationTest
```

**Deliverables**:
- ✅ 4 migrations created and tested
- ✅ 4 models with relationships
- ✅ Database schema validated

---

### Phase 2: Data Capture Integration (Week 2)

**Goal**: Integrate event system and start capturing historical data

**Tasks**:
1. Create 4 Laravel events:
   - `app/Events/MonitoringCheckStarted.php`
   - `app/Events/MonitoringCheckCompleted.php`
   - `app/Events/MonitoringCheckFailed.php`
   - `app/Events/MonitoringBatchCompleted.php`

2. Create 4 event listeners:
   - `app/Listeners/RecordMonitoringResult.php`
   - `app/Listeners/RecordMonitoringFailure.php`
   - `app/Listeners/UpdateMonitoringSummaries.php`
   - `app/Listeners/CheckAlertConditions.php`

3. Modify `app/Jobs/CheckMonitorJob.php`:
   - Add properties: `$triggerType`, `$triggeredByUserId`
   - Fire `MonitoringCheckStarted` at beginning
   - Fire `MonitoringCheckCompleted` on success
   - Fire `MonitoringCheckFailed` on error

4. Modify `app/Jobs/ImmediateWebsiteCheckJob.php`:
   - Set `$checkJob->triggerType = 'manual_immediate'`
   - Set `$checkJob->triggeredByUserId = auth()->id()`

5. Register listeners in `app/Providers/AppServiceProvider.php`:
```php
use Illuminate\Support\Facades\Event;
use App\Events\MonitoringCheckCompleted;
use App\Listeners\RecordMonitoringResult;

public function boot(): void
{
    Event::listen(
        MonitoringCheckCompleted::class,
        [RecordMonitoringResult::class, 'handle']
    );

    // Register other 3 listeners...
}
```

6. Configure Horizon queues in `config/horizon.php`:
```php
'environments' => [
    'production' => [
        'monitoring-history' => [
            'connection' => 'redis',
            'queue' => ['monitoring-history'],
            'balance' => 'auto',
            'processes' => 3,
            'tries' => 3,
        ],
        'monitoring-aggregation' => [
            'connection' => 'redis',
            'queue' => ['monitoring-aggregation'],
            'balance' => 'auto',
            'processes' => 2,
            'tries' => 2,
        ],
    ],
],
```

7. Write integration tests:
   - Test event firing
   - Test listener queuing
   - Test database record creation
   - Test manual vs scheduled distinction

**Validation**:
```bash
# Run monitoring check and verify history is captured
./vendor/bin/sail artisan tinker
>>> $monitor = Monitor::first();
>>> event(new App\Events\MonitoringCheckCompleted($monitor, [...data...]));
>>> App\Models\MonitoringResult::count(); // Should be > 0

# Verify tests pass
./vendor/bin/sail artisan test --filter=MonitoringHistory
```

**Deliverables**:
- ✅ 4 events created
- ✅ 4 listeners implemented and queued
- ✅ CheckMonitorJob modified to fire events
- ✅ Horizon queues configured
- ✅ Integration tests passing
- ✅ Historical data being captured

---

### Phase 3: Dashboard Integration (Week 3)

**Goal**: Display historical data in dashboard with trends

**Tasks**:
1. Create `app/Services/MonitoringHistoryService.php`:
```php
class MonitoringHistoryService
{
    public function getTrendData(Monitor $monitor, string $period = '7d'): array
    public function getRecentHistory(Monitor $monitor, int $limit = 50): Collection
    public function getSummaryStats(Monitor $monitor, string $period = '30d'): array
    public function getResponseTimeTrend(Monitor $monitor, string $period = '7d'): array
    public function getUptimePercentage(Monitor $monitor, string $period = '30d'): float
    public function getSslExpirationTrend(Monitor $monitor): array
}
```

2. Add controller methods for API endpoints:
   - `GET /api/monitors/{monitor}/history` - Recent check history
   - `GET /api/monitors/{monitor}/trends` - Trend data for charts
   - `GET /api/monitors/{monitor}/summary` - Summary statistics

3. Create Vue components:
   - `MonitoringHistoryChart.vue` - Line chart for response times
   - `UptimeTrendCard.vue` - 7-day uptime percentage
   - `RecentChecksTimeline.vue` - Recent check results
   - `SslExpirationTrendCard.vue` - SSL expiration countdown

4. Integrate into dashboard:
   - Add history section to website detail page
   - Add trend cards to dashboard overview
   - Add "View History" link to each monitor

**Validation**:
```bash
# Test API endpoints
curl http://localhost/api/monitors/1/history
curl http://localhost/api/monitors/1/trends?period=7d

# Verify dashboard loads in < 2s
time curl http://localhost/dashboard
```

**Deliverables**:
- ✅ MonitoringHistoryService implemented
- ✅ API endpoints created
- ✅ Vue components implemented
- ✅ Dashboard displays historical data
- ✅ Dashboard loads in < 2s

---

### Phase 4: Advanced Features (Week 4)

**Goal**: Implement aggregations, alert correlation, and data retention

**Tasks**:
1. Create summary aggregation job:
   - `app/Jobs/AggregateMonitoringSummariesJob.php`
   - Run hourly via scheduler
   - Calculate daily/weekly/monthly summaries

2. Implement alert correlation:
   - Link alerts to specific check results
   - Track alert lifecycle (detected → acknowledged → resolved)
   - Implement alert suppression during maintenance

3. Create data retention policies:
   - Keep 90 days of raw `monitoring_results`
   - Keep 1 year of daily summaries
   - Archive older data to yearly aggregates

4. Add reporting capabilities:
   - Generate PDF reports
   - Export CSV of historical data
   - Custom date range queries

**Validation**:
```bash
# Test aggregation job
./vendor/bin/sail artisan monitoring:aggregate-summaries

# Verify summaries created
./vendor/bin/sail artisan tinker
>>> MonitoringCheckSummary::where('summary_period', 'daily')->count()

# Test data retention
./vendor/bin/sail artisan monitoring:prune-old-data --days=90
```

**Deliverables**:
- ✅ Aggregation job running hourly
- ✅ Alert correlation implemented
- ✅ Data retention policies active
- ✅ Reporting capabilities added

---

### Phase 5: Production Optimization (Weeks 5-6)

**Goal**: Performance tuning, load testing, and production deployment

**Tasks**:
1. Performance optimization:
   - Review and optimize database queries
   - Add additional indexes if needed
   - Implement caching for summary data
   - Optimize Horizon queue processing

2. Load testing:
   - Test with 50+ websites
   - Simulate 72,000 checks/day
   - Monitor database growth
   - Verify < 5% performance overhead

3. Monitoring setup:
   - Set up Horizon dashboard monitoring
   - Add alerts for failed queue jobs
   - Track database size growth
   - Monitor query performance

4. Production deployment:
   - Deploy to staging environment
   - Run full test suite
   - Monitor for 1 week
   - Deploy to production with rollback plan

**Validation**:
```bash
# Load testing
./vendor/bin/sail artisan test:load-monitoring --websites=50 --duration=24h

# Performance benchmarks
time ./vendor/bin/sail artisan tinker
>>> MonitoringResult::where('monitor_id', 1)->whereBetween('started_at', [...])->get();

# Queue health
./vendor/bin/sail artisan horizon:list
```

**Deliverables**:
- ✅ Performance optimized (< 2s dashboard)
- ✅ Load tested with 50+ websites
- ✅ Monitoring in place
- ✅ Production deployment complete

---

## 📦 Laravel Components Specification

### Models (4 files)

#### 1. `app/Models/MonitoringResult.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MonitoringResult extends Model
{
    protected $fillable = [
        'uuid', 'monitor_id', 'website_id', 'check_type', 'trigger_type',
        'triggered_by_user_id', 'started_at', 'completed_at', 'duration_ms',
        'status', 'error_message', 'uptime_status', 'http_status_code',
        'response_time_ms', 'response_body_size_bytes', 'redirect_count',
        'final_url', 'ssl_status', 'certificate_issuer', 'certificate_subject',
        'certificate_expiration_date', 'certificate_valid_from_date',
        'days_until_expiration', 'certificate_chain', 'content_validation_enabled',
        'content_validation_status', 'expected_strings_found', 'forbidden_strings_found',
        'regex_matches', 'javascript_rendered', 'javascript_wait_seconds',
        'content_hash', 'check_method', 'user_agent', 'request_headers',
        'response_headers', 'ip_address', 'server_software', 'monitor_config',
        'check_interval_minutes',
    ];

    protected $casts = [
        'started_at' => 'datetime:Y-m-d H:i:s.v',
        'completed_at' => 'datetime:Y-m-d H:i:s.v',
        'certificate_expiration_date' => 'datetime',
        'certificate_valid_from_date' => 'datetime',
        'certificate_chain' => 'array',
        'expected_strings_found' => 'array',
        'forbidden_strings_found' => 'array',
        'regex_matches' => 'array',
        'request_headers' => 'array',
        'response_headers' => 'array',
        'monitor_config' => 'array',
        'content_validation_enabled' => 'boolean',
        'javascript_rendered' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (! $model->uuid) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by_user_id');
    }

    // Scopes
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeManual($query)
    {
        return $query->whereIn('trigger_type', ['manual_immediate', 'manual_bulk']);
    }

    public function scopeScheduled($query)
    {
        return $query->where('trigger_type', 'scheduled');
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('started_at', '>=', now()->subHours($hours));
    }
}
```

#### 2. `app/Models/MonitoringCheckSummary.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoringCheckSummary extends Model
{
    protected $fillable = [
        'monitor_id', 'website_id', 'summary_period', 'period_start', 'period_end',
        'total_uptime_checks', 'successful_uptime_checks', 'failed_uptime_checks',
        'uptime_percentage', 'average_response_time_ms', 'min_response_time_ms',
        'max_response_time_ms', 'p95_response_time_ms', 'p99_response_time_ms',
        'total_ssl_checks', 'successful_ssl_checks', 'failed_ssl_checks',
        'certificates_expiring', 'certificates_expired', 'total_checks',
        'total_check_duration_ms', 'average_check_duration_ms',
        'total_content_validations', 'successful_content_validations',
        'failed_content_validations',
    ];

    protected $casts = [
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'uptime_percentage' => 'decimal:2',
    ];

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    // Scopes
    public function scopeDaily($query)
    {
        return $query->where('summary_period', 'daily');
    }

    public function scopeWeekly($query)
    {
        return $query->where('summary_period', 'weekly');
    }

    public function scopeForPeriod($query, string $period)
    {
        return $query->where('summary_period', $period);
    }
}
```

#### 3. `app/Models/MonitoringAlert.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoringAlert extends Model
{
    protected $fillable = [
        'monitor_id', 'website_id', 'alert_type', 'alert_severity', 'alert_title',
        'alert_message', 'first_detected_at', 'last_occurred_at', 'acknowledged_at',
        'resolved_at', 'acknowledged_by_user_id', 'acknowledgment_note',
        'trigger_value', 'threshold_value', 'affected_check_result_id',
        'notifications_sent', 'notification_channels', 'notification_status',
        'suppressed', 'suppressed_until', 'occurrence_count',
    ];

    protected $casts = [
        'first_detected_at' => 'datetime',
        'last_occurred_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
        'suppressed_until' => 'datetime',
        'trigger_value' => 'array',
        'threshold_value' => 'array',
        'notifications_sent' => 'array',
        'suppressed' => 'boolean',
    ];

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by_user_id');
    }

    public function affectedCheckResult(): BelongsTo
    {
        return $this->belongsTo(MonitoringResult::class, 'affected_check_result_id');
    }

    // Scopes
    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolved_at');
    }

    public function scopeResolved($query)
    {
        return $query->whereNotNull('resolved_at');
    }

    public function scopeUnacknowledged($query)
    {
        return $query->whereNull('acknowledged_at');
    }

    public function scopeCritical($query)
    {
        return $query->where('alert_severity', 'critical');
    }

    // Methods
    public function acknowledge(User $user, ?string $note = null): void
    {
        $this->update([
            'acknowledged_at' => now(),
            'acknowledged_by_user_id' => $user->id,
            'acknowledgment_note' => $note,
        ]);
    }

    public function resolve(): void
    {
        $this->update([
            'resolved_at' => now(),
        ]);
    }
}
```

#### 4. `app/Models/MonitoringEvent.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoringEvent extends Model
{
    const UPDATED_AT = null; // No updated_at column

    protected $fillable = [
        'monitor_id', 'website_id', 'user_id', 'event_type', 'event_name',
        'description', 'old_values', 'new_values', 'event_data', 'ip_address',
        'user_agent', 'source',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'event_data' => 'array',
    ];

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeUserActions($query)
    {
        return $query->where('source', 'user');
    }

    public function scopeSystemEvents($query)
    {
        return $query->where('source', 'system');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('event_type', $type);
    }
}
```

### Events (4 files)

#### 1. `app/Events/MonitoringCheckStarted.php`
```php
<?php

namespace App\Events;

use App\Models\Monitor;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MonitoringCheckStarted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Monitor $monitor,
        public string $triggerType,
        public ?int $triggeredByUserId = null,
        public ?string $checkType = 'both'
    ) {}
}
```

#### 2. `app/Events/MonitoringCheckCompleted.php`
```php
<?php

namespace App\Events;

use App\Models\Monitor;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class MonitoringCheckCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Monitor $monitor,
        public string $triggerType,
        public ?int $triggeredByUserId,
        public Carbon $startedAt,
        public Carbon $completedAt,
        public array $checkResults
    ) {}
}
```

#### 3. `app/Events/MonitoringCheckFailed.php`
```php
<?php

namespace App\Events;

use App\Models\Monitor;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;
use Throwable;

class MonitoringCheckFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Monitor $monitor,
        public string $triggerType,
        public ?int $triggeredByUserId,
        public Carbon $startedAt,
        public Throwable $exception
    ) {}
}
```

#### 4. `app/Events/MonitoringBatchCompleted.php`
```php
<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class MonitoringBatchCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Collection $monitors,
        public int $successCount,
        public int $failureCount,
        public int $durationMs
    ) {}
}
```

### Listeners (4 files)

#### 1. `app/Listeners/RecordMonitoringResult.php`
```php
<?php

namespace App\Listeners;

use App\Events\MonitoringCheckCompleted;
use App\Models\MonitoringResult;
use Illuminate\Contracts\Queue\ShouldQueue;

class RecordMonitoringResult implements ShouldQueue
{
    public $queue = 'monitoring-history';
    public $tries = 3;
    public $timeout = 120;

    public function handle(MonitoringCheckCompleted $event): void
    {
        $results = $event->checkResults;

        MonitoringResult::create([
            'monitor_id' => $event->monitor->id,
            'website_id' => $event->monitor->website_id,
            'check_type' => $results['check_type'] ?? 'both',
            'trigger_type' => $event->triggerType,
            'triggered_by_user_id' => $event->triggeredByUserId,
            'started_at' => $event->startedAt,
            'completed_at' => $event->completedAt,
            'duration_ms' => $event->startedAt->diffInMilliseconds($event->completedAt),
            'status' => 'success',

            // Uptime data
            'uptime_status' => $results['uptime_status'] ?? null,
            'http_status_code' => $results['http_status_code'] ?? null,
            'response_time_ms' => $results['response_time_ms'] ?? null,
            'response_body_size_bytes' => $results['response_body_size'] ?? null,

            // SSL data
            'ssl_status' => $results['ssl_status'] ?? null,
            'certificate_issuer' => $results['certificate_issuer'] ?? null,
            'certificate_subject' => $results['certificate_subject'] ?? null,
            'certificate_expiration_date' => $results['certificate_expiration_date'] ?? null,
            'days_until_expiration' => $results['days_until_expiration'] ?? null,

            // Additional context
            'monitor_config' => $event->monitor->toArray(),
        ]);
    }
}
```

#### 2. `app/Listeners/RecordMonitoringFailure.php`
```php
<?php

namespace App\Listeners;

use App\Events\MonitoringCheckFailed;
use App\Models\MonitoringResult;
use Illuminate\Contracts\Queue\ShouldQueue;

class RecordMonitoringFailure implements ShouldQueue
{
    public $queue = 'monitoring-history';
    public $tries = 3;

    public function handle(MonitoringCheckFailed $event): void
    {
        MonitoringResult::create([
            'monitor_id' => $event->monitor->id,
            'website_id' => $event->monitor->website_id,
            'check_type' => 'both',
            'trigger_type' => $event->triggerType,
            'triggered_by_user_id' => $event->triggeredByUserId,
            'started_at' => $event->startedAt,
            'completed_at' => now(),
            'duration_ms' => $event->startedAt->diffInMilliseconds(now()),
            'status' => 'failed',
            'error_message' => $event->exception->getMessage(),
        ]);
    }
}
```

#### 3. `app/Listeners/UpdateMonitoringSummaries.php`
```php
<?php

namespace App\Listeners;

use App\Events\MonitoringCheckCompleted;
use App\Models\MonitoringCheckSummary;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateMonitoringSummaries implements ShouldQueue
{
    public $queue = 'monitoring-aggregation';
    public $tries = 2;

    public function handle(MonitoringCheckCompleted $event): void
    {
        // Update hourly summary (most granular)
        $this->updateSummary($event, 'hourly');
    }

    protected function updateSummary(MonitoringCheckCompleted $event, string $period): void
    {
        $periodStart = match($period) {
            'hourly' => now()->startOfHour(),
            'daily' => now()->startOfDay(),
            'weekly' => now()->startOfWeek(),
            'monthly' => now()->startOfMonth(),
        };

        $periodEnd = match($period) {
            'hourly' => now()->endOfHour(),
            'daily' => now()->endOfDay(),
            'weekly' => now()->endOfWeek(),
            'monthly' => now()->endOfMonth(),
        };

        $summary = MonitoringCheckSummary::firstOrNew([
            'monitor_id' => $event->monitor->id,
            'website_id' => $event->monitor->website_id,
            'summary_period' => $period,
            'period_start' => $periodStart,
        ]);

        // Increment counters
        $summary->total_checks++;
        $summary->total_uptime_checks++;
        $summary->successful_uptime_checks++;

        // Update averages
        $results = $event->checkResults;
        if (isset($results['response_time_ms'])) {
            $summary->average_response_time_ms = (
                ($summary->average_response_time_ms * ($summary->total_checks - 1)) +
                $results['response_time_ms']
            ) / $summary->total_checks;
        }

        $summary->period_end = $periodEnd;
        $summary->save();
    }
}
```

#### 4. `app/Listeners/CheckAlertConditions.php`
```php
<?php

namespace App\Listeners;

use App\Events\MonitoringCheckCompleted;
use App\Models\MonitoringAlert;
use Illuminate\Contracts\Queue\ShouldQueue;

class CheckAlertConditions implements ShouldQueue
{
    public $queue = 'alerts';
    public $tries = 2;

    public function handle(MonitoringCheckCompleted $event): void
    {
        $results = $event->checkResults;

        // Check for response time degradation
        if (isset($results['response_time_ms']) && $results['response_time_ms'] > 2000) {
            $this->createAlert($event, 'performance_degradation', [
                'response_time' => $results['response_time_ms'],
                'threshold' => 2000,
            ]);
        }

        // Check for SSL expiration
        if (isset($results['days_until_expiration']) && $results['days_until_expiration'] < 7) {
            $this->createAlert($event, 'ssl_expiring', [
                'days_remaining' => $results['days_until_expiration'],
            ]);
        }

        // Additional alert conditions...
    }

    protected function createAlert(MonitoringCheckCompleted $event, string $type, array $triggerValue): void
    {
        MonitoringAlert::create([
            'monitor_id' => $event->monitor->id,
            'website_id' => $event->monitor->website_id,
            'alert_type' => $type,
            'alert_severity' => $this->determineSeverity($type, $triggerValue),
            'alert_title' => $this->generateTitle($type, $event->monitor),
            'alert_message' => $this->generateMessage($type, $triggerValue),
            'first_detected_at' => now(),
            'trigger_value' => $triggerValue,
        ]);
    }
}
```

### Service

#### `app/Services/MonitoringHistoryService.php`
```php
<?php

namespace App\Services;

use App\Models\Monitor;
use App\Models\MonitoringResult;
use App\Models\MonitoringCheckSummary;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class MonitoringHistoryService
{
    public function getTrendData(Monitor $monitor, string $period = '7d'): array
    {
        $startDate = $this->parsePeriod($period);

        return MonitoringResult::where('monitor_id', $monitor->id)
            ->where('started_at', '>=', $startDate)
            ->orderBy('started_at')
            ->get(['started_at', 'response_time_ms', 'status'])
            ->map(fn($result) => [
                'timestamp' => $result->started_at->toIso8601String(),
                'response_time' => $result->response_time_ms,
                'status' => $result->status,
            ])
            ->toArray();
    }

    public function getRecentHistory(Monitor $monitor, int $limit = 50): Collection
    {
        return MonitoringResult::where('monitor_id', $monitor->id)
            ->with(['triggeredBy'])
            ->latest('started_at')
            ->limit($limit)
            ->get();
    }

    public function getSummaryStats(Monitor $monitor, string $period = '30d'): array
    {
        $startDate = $this->parsePeriod($period);

        $summary = MonitoringCheckSummary::where('monitor_id', $monitor->id)
            ->where('summary_period', 'daily')
            ->where('period_start', '>=', $startDate)
            ->get();

        return [
            'uptime_percentage' => $summary->avg('uptime_percentage'),
            'average_response_time' => $summary->avg('average_response_time_ms'),
            'total_checks' => $summary->sum('total_checks'),
            'successful_checks' => $summary->sum('successful_uptime_checks'),
            'failed_checks' => $summary->sum('failed_uptime_checks'),
        ];
    }

    public function getResponseTimeTrend(Monitor $monitor, string $period = '7d'): array
    {
        $startDate = $this->parsePeriod($period);

        return MonitoringCheckSummary::where('monitor_id', $monitor->id)
            ->where('summary_period', 'hourly')
            ->where('period_start', '>=', $startDate)
            ->orderBy('period_start')
            ->get(['period_start', 'average_response_time_ms'])
            ->map(fn($summary) => [
                'timestamp' => $summary->period_start->toIso8601String(),
                'avg_response_time' => $summary->average_response_time_ms,
            ])
            ->toArray();
    }

    public function getUptimePercentage(Monitor $monitor, string $period = '30d'): float
    {
        $startDate = $this->parsePeriod($period);

        $summary = MonitoringCheckSummary::where('monitor_id', $monitor->id)
            ->where('summary_period', 'daily')
            ->where('period_start', '>=', $startDate)
            ->get();

        return round($summary->avg('uptime_percentage'), 2);
    }

    public function getSslExpirationTrend(Monitor $monitor): array
    {
        $results = MonitoringResult::where('monitor_id', $monitor->id)
            ->whereNotNull('certificate_expiration_date')
            ->latest('started_at')
            ->limit(30)
            ->get(['started_at', 'days_until_expiration']);

        return $results->map(fn($result) => [
            'timestamp' => $result->started_at->toIso8601String(),
            'days_remaining' => $result->days_until_expiration,
        ])->toArray();
    }

    protected function parsePeriod(string $period): Carbon
    {
        return match($period) {
            '24h' => now()->subHours(24),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            default => now()->subDays(7),
        };
    }

    public function pruneOldData(int $days = 90): int
    {
        return MonitoringResult::where('started_at', '<', now()->subDays($days))->delete();
    }
}
```

---

## 🧪 Testing Strategy

### Performance Requirements
- **Individual tests**: < 1 second
- **Full monitoring history suite**: < 5 seconds
- **Total test suite**: Maintain < 20 seconds (parallel)
- **NO real network calls** (use existing mock traits)

### Mock Traits to Use
```php
use Tests\Traits\MocksSslCertificateAnalysis;
use Tests\Traits\MocksMonitorHttpRequests;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class, MocksSslCertificateAnalysis::class, MocksMonitorHttpRequests::class);
```

### Test Structure

#### 1. `tests/Feature/MonitoringHistoryTest.php`
```php
<?php

use App\Events\MonitoringCheckCompleted;
use App\Models\Monitor;
use App\Models\MonitoringResult;
use Tests\Traits\MocksSslCertificateAnalysis;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class, MocksSslCertificateAnalysis::class);

test('monitoring check completed event creates historical record', function () {
    $monitor = Monitor::factory()->create();

    event(new MonitoringCheckCompleted(
        monitor: $monitor,
        triggerType: 'scheduled',
        triggeredByUserId: null,
        startedAt: now()->subSeconds(2),
        completedAt: now(),
        checkResults: [
            'check_type' => 'both',
            'uptime_status' => 'up',
            'http_status_code' => 200,
            'response_time_ms' => 150,
            'ssl_status' => 'valid',
        ]
    ));

    expect(MonitoringResult::count())->toBe(1);

    $result = MonitoringResult::first();
    expect($result->monitor_id)->toBe($monitor->id);
    expect($result->trigger_type)->toBe('scheduled');
    expect($result->status)->toBe('success');
    expect($result->response_time_ms)->toBe(150);
});

test('manual check is distinguished from scheduled check', function () {
    $user = User::factory()->create();
    $monitor = Monitor::factory()->create();

    event(new MonitoringCheckCompleted(
        monitor: $monitor,
        triggerType: 'manual_immediate',
        triggeredByUserId: $user->id,
        startedAt: now()->subSeconds(1),
        completedAt: now(),
        checkResults: ['check_type' => 'both']
    ));

    $result = MonitoringResult::first();
    expect($result->trigger_type)->toBe('manual_immediate');
    expect($result->triggered_by_user_id)->toBe($user->id);
});
```

#### 2. `tests/Feature/MonitoringSummaryTest.php`
```php
<?php

use App\Models\MonitoringCheckSummary;
use App\Services\MonitoringHistoryService;

test('summary service calculates uptime percentage correctly', function () {
    $monitor = Monitor::factory()->create();

    // Create daily summaries with 95% uptime
    MonitoringCheckSummary::factory()->create([
        'monitor_id' => $monitor->id,
        'summary_period' => 'daily',
        'uptime_percentage' => 95.00,
        'period_start' => now()->subDays(1),
    ]);

    $service = new MonitoringHistoryService();
    $uptime = $service->getUptimePercentage($monitor, '7d');

    expect($uptime)->toBe(95.00);
});
```

### Validation Commands
```bash
# Run all monitoring history tests
./vendor/bin/sail artisan test --filter=MonitoringHistory

# Test event firing
./vendor/bin/sail artisan tinker
>>> event(new App\Events\MonitoringCheckCompleted(...));

# Verify database records
./vendor/bin/sail artisan tinker
>>> App\Models\MonitoringResult::count()
>>> App\Models\MonitoringResult::latest()->first()

# Check queue processing
./vendor/bin/sail artisan horizon:list

# Test performance
time ./vendor/bin/sail artisan test --parallel
```

---

## 📊 Performance Targets & Optimization

### Target Metrics

| Metric | Current | Target | How to Measure |
|--------|---------|--------|----------------|
| **Dashboard Load** | 15-30s | < 2s | Time to render with historical data |
| **Single Website History** | N/A | < 500ms | API endpoint response time |
| **Alert Dashboard** | N/A | < 300ms | Alert list query time |
| **Check Overhead** | 0% | < 5% | Benchmark with/without events |
| **Storage Growth** | 0 MB | ~23 MB/day | Database size tracking |
| **Query Performance** | N/A | All < 100ms | Laravel Telescope |

### Index Strategy

**Critical Composite Index** (90%+ improvement):
```sql
INDEX idx_monitor_website_time (monitor_id, website_id, started_at DESC)
```

**Why it works**: Dashboard queries filter by monitor_id AND website_id AND time range. This composite index satisfies all three conditions in optimal order.

**N+1 Query Prevention**:
- Eager load relationships: `MonitoringResult::with(['monitor', 'website', 'triggeredBy'])`
- Use relationship indexes on all foreign keys
- Cache summary statistics (Redis)

### Storage Optimization

**Hot Data (90 days)**:
- 50 websites × 1,440 checks/day = 72,000 rows/day
- ~350 bytes/row = ~23 MB/day
- 90 days = ~2.6 GB

**Cold Data (Archive)**:
- Move to yearly summary tables after 90 days
- Keep daily aggregates for 1 year
- Keep weekly aggregates indefinitely

### Caching Strategy
```php
// Cache summary stats for 5 minutes
Cache::remember("monitor:{$monitor->id}:summary:30d", 300, function () use ($monitor) {
    return $this->getSummaryStats($monitor, '30d');
});
```

---

## 🗄️ Data Retention & Archival

### Retention Policy

**Tier 1: Hot Data (0-90 days)**
- Table: `monitoring_results`
- Retention: 90 days
- Access: Real-time queries
- Storage: ~2.6 GB

**Tier 2: Warm Data (91-365 days)**
- Table: `monitoring_check_summaries` (daily)
- Retention: 1 year
- Access: Dashboard trends
- Storage: ~100 MB

**Tier 3: Cold Data (366+ days)**
- Table: `monitoring_check_summaries` (weekly/monthly)
- Retention: Indefinite
- Access: Historical reports
- Storage: ~50 MB/year

### Automated Pruning

Create artisan command: `app/Console/Commands/PruneMonitoringDataCommand.php`

```php
<?php

namespace App\Console\Commands;

use App\Models\MonitoringResult;
use Illuminate\Console\Command;

class PruneMonitoringDataCommand extends Command
{
    protected $signature = 'monitoring:prune-old-data {--days=90}';
    protected $description = 'Prune monitoring data older than specified days';

    public function handle(): int
    {
        $days = $this->option('days');
        $cutoffDate = now()->subDays($days);

        $this->info("Pruning monitoring data older than {$days} days ({$cutoffDate})...");

        $deletedCount = MonitoringResult::where('started_at', '<', $cutoffDate)->delete();

        $this->info("Deleted {$deletedCount} old monitoring results.");

        return Command::SUCCESS;
    }
}
```

Schedule in `app/Console/Kernel.php`:
```php
protected function schedule(Schedule $schedule): void
{
    // Run daily at 2 AM
    $schedule->command('monitoring:prune-old-data --days=90')->dailyAt('02:00');
}
```

---

## 🔄 Migration from Current State

### Current State
- ✅ `monitors` table exists with current state data
- ✅ `websites` table exists
- ✅ `alert_configurations` table exists
- ❌ NO historical data tables
- ❌ NO event system implemented

### Migration Strategy

**Phase 1: Add New Tables (No Impact)**
- Run migrations to create 4 new tables
- NO changes to existing tables
- NO data migration needed (starting fresh)

**Phase 2: Enable Event System (Low Risk)**
- Deploy event firing code
- Monitor queue processing
- Historical data starts accumulating
- Existing monitoring continues normally

**Phase 3: Dashboard Integration (Medium Risk)**
- Add new historical data endpoints
- Integrate Vue components
- Test with accumulated data
- Keep existing dashboard as fallback

**Phase 4: Full Rollout (Monitored)**
- Enable for all users
- Monitor performance metrics
- Adjust queue processing as needed

### Rollback Plan

If issues arise:
1. **Disable event firing** - Comment out event() calls in CheckMonitorJob
2. **Stop queue workers** - Pause monitoring-history queue
3. **Monitor stabilization** - Ensure monitoring continues normally
4. **Debug offline** - Fix issues without impacting monitoring
5. **Re-enable gradually** - Start with single monitor, expand

**Critical**: Historical data capture is **additive only**. Disabling it does NOT break existing monitoring.

---

## 📈 Monitoring & Maintenance

### Queue Health Monitoring

**Horizon Dashboard**: `http://your-domain.com/horizon`

Monitor:
- Queue depth (should be < 100 jobs)
- Failed job count (should be 0)
- Processing rate (jobs/minute)
- Average wait time (< 1 second)

**Alerts**:
```php
// In app/Console/Kernel.php
$schedule->command('horizon:snapshot')->everyFiveMinutes();

// Create alert if queue depth > 500
if (Redis::llen('queues:monitoring-history') > 500) {
    // Send alert to team
}
```

### Database Growth Tracking

**Weekly Report**:
```sql
SELECT
    'monitoring_results' as table_name,
    COUNT(*) as row_count,
    ROUND(SUM(LENGTH(CONCAT_WS('', monitor_id, website_id, ...))) / 1024 / 1024, 2) as size_mb
FROM monitoring_results

UNION ALL

SELECT
    'monitoring_check_summaries',
    COUNT(*),
    ROUND(SUM(LENGTH(CONCAT_WS('', monitor_id, website_id, ...))) / 1024 / 1024, 2)
FROM monitoring_check_summaries;
```

### Performance Regression Detection

**Weekly Benchmark**:
```bash
#!/bin/bash
# benchmark-monitoring.sh

echo "Running performance benchmarks..."

# Dashboard load time
time curl -s http://localhost/dashboard > /dev/null

# API endpoint response time
time curl -s http://localhost/api/monitors/1/history > /dev/null

# Database query performance
time ./vendor/bin/sail artisan tinker --execute="MonitoringResult::where('monitor_id', 1)->count();"
```

Run weekly and compare to baseline.

### Failed Job Alerts

**In `app/Providers/AppServiceProvider.php`:**
```php
use Illuminate\Support\Facades\Queue;
use Illuminate\Queue\Events\JobFailed;

public function boot(): void
{
    Queue::failing(function (JobFailed $event) {
        // Log to monitoring system
        Log::error('Queue job failed', [
            'connection' => $event->connectionName,
            'queue' => $event->job->getQueue(),
            'exception' => $event->exception->getMessage(),
        ]);

        // Send notification to team
        // Mail::to('team@example.com')->send(new JobFailedNotification($event));
    });
}
```

### Weekly Maintenance Tasks

**Checklist**:
- [ ] Review Horizon dashboard for failed jobs
- [ ] Check database size growth (should be ~160 MB/week for 50 websites)
- [ ] Run performance benchmarks
- [ ] Review queue processing rates
- [ ] Verify data retention is working (90-day prune)
- [ ] Check for slow queries in Laravel Telescope
- [ ] Review alert correlation accuracy

---

## 🔮 Future Enhancements (Post-Phase 5)

### Phase 6: AI/ML Analysis (Months 2-3)
- **Predictive Alerting**: ML model predicts certificate expiration issues
- **Anomaly Detection**: Identify unusual response time patterns
- **Trend Forecasting**: Predict when SSL renewal needed
- **Smart Thresholds**: Auto-adjust alert thresholds based on historical patterns

### Phase 7: Advanced Reporting (Month 3)
- **Custom Report Builder**: Drag-and-drop report creation
- **Scheduled Reports**: Email daily/weekly reports
- **PDF Export**: Professional monitoring reports
- **SLA Reports**: Uptime percentage reports for clients

### Phase 8: API & Integrations (Month 4)
- **RESTful API**: Full historical data API
- **Webhooks**: Push alerts to external systems
- **Third-party Integrations**: Datadog, New Relic, PagerDuty
- **Mobile App**: iOS/Android apps with push notifications

### Phase 9: Multi-Region Monitoring (Month 5)
- **Geographic Monitoring**: Check from multiple locations
- **Latency Analysis**: Region-specific response times
- **CDN Performance**: Multi-region CDN monitoring
- **Global Dashboard**: World map with monitor status

---

## 📚 Appendices

### Appendix A: Comparison of Previous Plans

**Plan 1 (`MONITORING_HISTORY_PLAN.md`)**:
- 6 separate tables (monitoring_history, ssl_certificate_history, etc.)
- More normalized structure
- Detailed performance metrics (TTFB, DOM load time)
- Persistent event audit trail

**Plan 2 (`MONITORING_DATA_TRACKING_PLAN.md`)**:
- 3 consolidated tables
- Event-driven architecture
- Claims Phase 1 complete (unverified)
- Pre-calculated summaries

**This Master Plan**:
- **4 optimized tables** (best of both plans)
- Event-driven with persistent event trail (optional)
- Realistic implementation timeline
- Production-ready performance targets

### Appendix B: SQL Query Examples

**Get 7-day uptime trend**:
```sql
SELECT
    DATE(started_at) as date,
    COUNT(*) as total_checks,
    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful,
    ROUND(SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as uptime_percentage
FROM monitoring_results
WHERE monitor_id = 1
    AND started_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(started_at)
ORDER BY date;
```

**Get response time percentiles**:
```sql
SELECT
    monitor_id,
    COUNT(*) as check_count,
    AVG(response_time_ms) as avg_response,
    MIN(response_time_ms) as min_response,
    MAX(response_time_ms) as max_response,
    (SELECT response_time_ms FROM monitoring_results
     WHERE monitor_id = mr.monitor_id
     ORDER BY response_time_ms
     LIMIT 1 OFFSET FLOOR(COUNT(*) * 0.95)) as p95_response
FROM monitoring_results mr
WHERE started_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY monitor_id;
```

**Find slowest monitors in last 24 hours**:
```sql
SELECT
    m.id,
    w.url,
    AVG(mr.response_time_ms) as avg_response_time,
    COUNT(*) as check_count
FROM monitoring_results mr
JOIN monitors m ON mr.monitor_id = m.id
JOIN websites w ON mr.website_id = w.id
WHERE mr.started_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY m.id, w.url
ORDER BY avg_response_time DESC
LIMIT 10;
```

### Appendix C: Troubleshooting Guide

**Problem**: Historical data not being captured

**Solution**:
1. Check Horizon is running: `./vendor/bin/sail artisan horizon:status`
2. Verify events are firing: Add `Log::info()` in CheckMonitorJob
3. Check queue workers: `./vendor/bin/sail artisan horizon:list`
4. Review failed jobs: Horizon dashboard → Failed Jobs

---

**Problem**: Dashboard loading slowly (> 2s)

**Solution**:
1. Check composite index exists: `SHOW INDEX FROM monitoring_results WHERE Key_name = 'idx_monitor_website_time';`
2. Use summary tables: Query `monitoring_check_summaries` instead of `monitoring_results`
3. Enable caching: Cache summary statistics for 5 minutes
4. Review query with EXPLAIN: `EXPLAIN SELECT ... FROM monitoring_results WHERE ...`

---

**Problem**: Queue depth growing (> 100 jobs)

**Solution**:
1. Increase queue workers in `config/horizon.php`
2. Check for slow listeners (> 100ms)
3. Increase `tries` for listeners
4. Consider batching inserts

---

**Problem**: Database growing too fast (> 30 MB/day)

**Solution**:
1. Verify data retention is running: Check `monitoring:prune-old-data` in scheduler
2. Reduce stored data: Store only essential fields
3. Increase aggregation frequency: Hourly summaries instead of per-check
4. Archive to separate storage: Export old data to S3/external storage

### Appendix D: Performance Benchmarks

**Baseline Metrics** (50 websites, 90 days data):

| Operation | Expected Time | Query Count |
|-----------|---------------|-------------|
| Dashboard load | < 2s | 5 queries |
| Single monitor history | < 500ms | 1 query |
| Trend calculation | < 300ms | 2 queries |
| Summary aggregation | < 1s | 3 queries |
| Alert evaluation | < 100ms | 2 queries |

**Storage Benchmarks**:
- 1 monitoring result: ~350 bytes
- 72,000 checks/day: ~23 MB/day
- 90-day retention: ~2.6 GB
- 1 year summaries: ~100 MB

**Queue Benchmarks**:
- Event firing: < 1ms
- Listener execution: < 100ms
- Queue depth: < 50 jobs (normal)
- Processing rate: > 100 jobs/minute

---

## ✅ Implementation Checklist

Use this checklist to track implementation progress:

### Phase 1: Foundation
- [ ] Create `create_monitoring_results_table.php` migration
- [ ] Create `create_monitoring_check_summaries_table.php` migration
- [ ] Create `create_monitoring_alerts_table.php` migration
- [ ] Create `create_monitoring_events_table.php` migration (optional)
- [ ] Run migrations successfully
- [ ] Create `MonitoringResult` model with relationships
- [ ] Create `MonitoringCheckSummary` model
- [ ] Create `MonitoringAlert` model
- [ ] Create `MonitoringEvent` model (optional)
- [ ] Write migration tests
- [ ] All tests passing

### Phase 2: Data Capture
- [ ] Create `MonitoringCheckStarted` event
- [ ] Create `MonitoringCheckCompleted` event
- [ ] Create `MonitoringCheckFailed` event
- [ ] Create `MonitoringBatchCompleted` event
- [ ] Create `RecordMonitoringResult` listener
- [ ] Create `RecordMonitoringFailure` listener
- [ ] Create `UpdateMonitoringSummaries` listener
- [ ] Create `CheckAlertConditions` listener
- [ ] Modify `CheckMonitorJob` to fire events
- [ ] Modify `ImmediateWebsiteCheckJob` for manual tracking
- [ ] Register listeners in `AppServiceProvider`
- [ ] Configure Horizon queues
- [ ] Write integration tests
- [ ] Verify historical data being captured
- [ ] All tests passing (< 20s)

### Phase 3: Dashboard Integration
- [ ] Create `MonitoringHistoryService`
- [ ] Implement `getTrendData()` method
- [ ] Implement `getRecentHistory()` method
- [ ] Implement `getSummaryStats()` method
- [ ] Add API endpoints for historical data
- [ ] Create `MonitoringHistoryChart.vue` component
- [ ] Create `UptimeTrendCard.vue` component
- [ ] Create `RecentChecksTimeline.vue` component
- [ ] Create `SslExpirationTrendCard.vue` component
- [ ] Integrate components into dashboard
- [ ] Test dashboard loads in < 2s
- [ ] User acceptance testing

### Phase 4: Advanced Features
- [ ] Create `AggregateMonitoringSummariesJob`
- [ ] Schedule aggregation job hourly
- [ ] Implement alert correlation
- [ ] Create `PruneMonitoringDataCommand`
- [ ] Schedule data retention job daily
- [ ] Implement PDF report generation
- [ ] Add CSV export functionality
- [ ] Test with 50+ websites
- [ ] Verify storage growth (~23 MB/day)

### Phase 5: Production Optimization
- [ ] Performance audit with Laravel Telescope
- [ ] Optimize slow queries (< 100ms target)
- [ ] Implement caching for summary data
- [ ] Load testing (72,000 checks/day)
- [ ] Set up Horizon monitoring alerts
- [ ] Configure queue failure notifications
- [ ] Deploy to staging environment
- [ ] Staging validation (1 week)
- [ ] Production deployment
- [ ] Post-deployment monitoring (1 week)

---

## 📞 Support & Next Steps

### Getting Started

1. **Read this document thoroughly**
2. **Review Phase 1 checklist**
3. **Start with database migrations**
4. **Run tests after each step**

### Questions?

Refer to:
- `docs/QUEUE_AND_SCHEDULER_ARCHITECTURE.md` for queue setup
- `docs/TESTING_INSIGHTS.md` for testing patterns
- `docs/DEVELOPMENT_PRIMER.md` for development workflow

### Ready to Start?

```bash
# 1. Create feature branch
git checkout -b feature/historical-monitoring-data

# 2. Start with Phase 1: Foundation
./vendor/bin/sail artisan make:migration create_monitoring_results_table

# 3. Follow the implementation phases above

# 4. Test frequently
./vendor/bin/sail artisan test --parallel
```

---

**Document Version**: 1.0
**Last Updated**: October 17, 2025
**Status**: Ready for Implementation
**Estimated Completion**: 6 weeks from start

---

*This master plan consolidates and supersedes:*
- `MONITORING_HISTORY_PLAN.md` (archived)
- `MONITORING_DATA_TRACKING_PLAN.md` (archived)

*All future development should reference this document as the single source of truth.*
