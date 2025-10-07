# SSL Monitor v4 - Queue and Scheduler Architecture

## 📋 Overview

SSL Monitor v4 uses a **hybrid architecture** that **extends Spatie Uptime Monitor** with custom enhanced functionality. The system combines Laravel's **Scheduler** for automated monitoring and **Queues** for manual user actions, while using our **extended Monitor model** (`App\Models\Monitor`) that adds JavaScript content validation, response time tracking, and advanced content checking capabilities.

## 🏗️ Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────────────┐
│                           CRON SCHEDULER                               │
│                    * * * * * php artisan schedule:run                  │
└─────────────────────────────┬───────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                     LARAVEL SCHEDULER                                  │
│                      (routes/console.php)                              │
│                                                                         │
│  ⏱️  Every 5 minutes:   monitor:check-uptime                           │
│  🔒 Twice daily (6&18): monitor:check-certificate                      │
│  🔍 Every 5 minutes:    Queue health monitoring                        │
│  🧹 Daily at 2 AM:      Cleanup old jobs and logs                     │
│  📊 Every 30 minutes:   monitors:sync-websites                         │
│  📈 Weekly Sunday 3 AM:  System health report                         │
└─────────────────────────────┬───────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                   EXTENDED MONITOR SYSTEM                              │
│                (App\Models\Monitor extends SpatieMonitor)              │
│                                                                         │
│  • Batch processes ALL websites simultaneously                         │
│  • Updates `monitors` table with enhanced results                      │
│  • Fires Laravel events (success/failure/recovery)                     │
│  • NO individual queued jobs per website                               │
│  • Handles SSL certificate and uptime checking                         │
│  • Tracks response times and content validation                        │
│  • JavaScript content rendering support                                │
│  • Advanced content validation (expected/forbidden strings, regex)     │
└─────────────────────────────┬───────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                        QUEUE SYSTEM                                    │
│                    (Manual Actions Only)                               │
│                                                                         │
│  🚀 immediate queue:     ImmediateWebsiteCheckJob (manual checks)      │
│  📧 notifications queue: TeamInvitationMail + other emails             │
│  📦 default queue:       Fallback for unspecified Laravel jobs         │
│                                                                         │
│  ❌ uptime queue:        CONFIGURED BUT UNUSED                         │
│  ❌ ssl queue:           CONFIGURED BUT UNUSED                         │
│  ❌ cleanup queue:       CONFIGURED BUT UNUSED                         │
└─────────────────────────────────────────────────────────────────────────┘
```

## 🔄 Two Monitoring Systems

### 1. 🤖 Automated Monitoring (Scheduler-Based)

**Purpose**: Continuous background monitoring of all websites
**Technology**: Laravel Scheduler + Extended Monitor Model (App\Models\Monitor)
**Frequency**:
- Uptime checks: Every 5 minutes
- SSL certificate checks: Twice daily (6 AM and 6 PM)

**How it works**:
```bash
# Cron triggers Laravel scheduler
* * * * * php artisan schedule:run

# Scheduler runs these commands
php artisan monitor:check-uptime      # Every 5 minutes
php artisan monitor:check-certificate # Twice daily
```

**Key Characteristics**:
- ✅ Processes ALL websites in batch operations
- ✅ No individual jobs queued per website
- ✅ Updates `monitors` table with enhanced data
- ✅ Fires Laravel events for notifications
- ✅ Efficient for large numbers of websites
- ✅ Enhanced with response time tracking
- ✅ JavaScript content validation support
- ✅ Advanced content checking (expected/forbidden strings, regex)

### 2. 👤 Manual Monitoring (Queue-Based)

**Purpose**: Immediate checks when users click "Check Now"
**Technology**: Laravel Queues + Custom Jobs
**Frequency**: On-demand user actions

**How it works**:
```php
// User clicks "Check Now" button
ImmediateWebsiteCheckJob::dispatch($website)
    ->onQueue('immediate');

// Queue worker processes job (uses Redis for optimal performance)
php artisan queue:work redis --queue=immediate
```

**Key Characteristics**:
- ✅ Individual jobs per website check
- ✅ Immediate user feedback
- ✅ Uses `ImmediateWebsiteCheckJob` with enhanced monitoring
- ✅ Queued on `immediate` queue
- ✅ Perfect for responsive UI interactions
- ✅ Uses extended `App\Models\Monitor` for enhanced data collection
- ✅ Supports JavaScript content validation and response time tracking

## 🎯 Queue Configuration Analysis

### ✅ Active Queues (Actually Used)

| Queue Name | Purpose | Usage | Jobs |
|------------|---------|-------|------|
| `immediate` | Manual website checks | ✅ **ACTIVE** | `ImmediateWebsiteCheckJob` |
| `notifications` | Email sending | ✅ **ACTIVE** | `TeamInvitationMail` |
| `default` | Laravel fallback | ✅ **FALLBACK** | Any unspecified jobs |

### ❌ Configured But Unused Queues

| Queue Name | Configured | Actually Used | Reason |
|------------|------------|---------------|---------|
| `uptime` | ✅ Yes | ❌ No | Extended Monitor handles via scheduler |
| `ssl` | ✅ Yes | ❌ No | Extended Monitor handles via scheduler |
| `cleanup` | ✅ Yes | ❌ No | Cleanup runs via scheduler |

**Why they exist**:
1. **Legacy from v3**: Original design had separate queue workers
2. **Production planning**: Supervisor config shows intended scale architecture
3. **Future-proofing**: Could be used if architecture changes to separate queue workers
4. **Documentation**: Shows intended production queue separation
5. **Hybrid architecture**: Current system uses scheduler for batch processing, queues for manual actions

## 🛠️ Development Setup

### Start Both Systems

```bash
# Terminal 1: Start scheduler worker (automated monitoring)
./vendor/bin/sail artisan schedule:work

# Terminal 2: Start queue workers (manual actions - uses Redis for optimal performance)
./vendor/bin/sail artisan queue:work redis --queue=immediate,notifications --timeout=90 --tries=3
```

### Alternative: All-in-One Development

```bash
# Start all development services
./vendor/bin/sail composer run dev
```

### Check System Status

```bash
# Check scheduler status
./vendor/bin/sail artisan schedule:list

# Check queue status
./vendor/bin/sail artisan queue:monitor

# Check website monitoring status
./vendor/bin/sail artisan monitor:list
```

## 🚀 Production Setup

### Cron Configuration

```bash
# Add to crontab
* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
```

### Queue Workers (Supervisor)

```bash
# Start all configured queue workers
sudo supervisorctl start ssl-monitor-queues:*

# Individual workers available:
# - ssl-monitor-immediate-queue (2 workers)
# - ssl-monitor-notifications-queue (3 workers)
# - ssl-monitor-uptime-queue (2 workers) - UNUSED
# - ssl-monitor-ssl-queue (1 worker) - UNUSED
# - ssl-monitor-cleanup-queue (1 worker) - UNUSED
```

### Recommended Production Command

```bash
# Efficient: Only run active queues (Redis for optimal performance)
php artisan queue:work redis --queue=immediate,notifications,default --timeout=90 --tries=3

# Alternative: Database queues (for development environments with limited Redis)
php artisan queue:work database --queue=immediate,notifications,default --timeout=90 --tries=3
```

## 📊 Data Flow

### Website Creation Flow

1. **User creates website** → Website model saved
2. **ImmediateWebsiteCheckJob dispatched** → `immediate` queue
3. **Queue worker processes job** → Uses extended `App\Models\Monitor` with enhanced features
4. **Results saved** → `monitors` table updated with response times and content validation
5. **UI updated** → Real-time status display with enhanced data

### Automated Monitoring Flow

1. **Cron triggers scheduler** → Every minute
2. **Scheduler runs monitor commands** → Batch processes all websites
3. **Extended Monitor checks all sites** → HTTP requests + SSL validation + content validation
4. **Results saved** → `monitors` table updated with enhanced data (response times, content validation)
5. **Events fired** → Notifications sent if failures

## 🔧 Extended Monitor Model Features

### JavaScript Content Validation
The extended `App\Models\Monitor` adds powerful content validation capabilities:

```php
// Enhanced database fields
'content_expected_strings' => 'array',      // Strings that must be present
'content_forbidden_strings' => 'array',     // Strings that must not be present
'content_regex_patterns' => 'array',        // Regex patterns to match
'javascript_enabled' => 'boolean',          // Enable JavaScript rendering
'javascript_wait_seconds' => 'integer',     // Wait time for JS rendering
'content_validation_failure_reason' => 'text', // Failure details
```

### Enhanced Methods
```php
// Content validation management
$monitor->addExpectedString('Welcome to our site');
$monitor->addForbiddenString('Error 404');
$monitor->addRegexPattern('/copyright \d{4}/i');

// Check validation configuration
$monitor->hasContentValidation();
$monitor->hasJavaScriptEnabled();
$monitor->getJavaScriptWaitSeconds();

// Response time tracking
$monitor->uptime_check_response_time_in_ms; // Tracked automatically
```

### Integration Benefits
- **Backward Compatible**: All existing Spatie functionality preserved
- **Enhanced Data**: Additional fields for advanced monitoring
- **Custom Logic**: Override methods for specialized behavior
- **Future-Proof**: Easy to extend further as needs evolve

## 🔍 Key Files

### Scheduler Configuration
- `routes/console.php` - All scheduled tasks
- `app/Console/Kernel.php` - Legacy (Laravel 12 uses routes/console.php)

### Queue Configuration
- `.env` - Queue connection and queue names (Redis optimized)
- `config/queue.php` - Queue driver configuration
- `supervisor/ssl-monitor-queues.conf` - Production worker config (Redis-ready)

### Redis Queue Configuration
```bash
# Environment variables for Redis queues
QUEUE_CONNECTION=redis
REDIS_QUEUE_CONNECTION=default
REDIS_QUEUE=default
REDIS_QUEUE_RETRY_AFTER=90

# Multiple queue setup for different job types
QUEUE_IMMEDIATE=immediate
QUEUE_UPTIME=uptime
QUEUE_SSL=ssl
QUEUE_NOTIFICATIONS=notifications
```

### Monitoring Jobs
- `app/Jobs/ImmediateWebsiteCheckJob.php` - Manual check job with enhanced monitoring
- `app/Mail/TeamInvitationMail.php` - Email notifications

### Extended Monitor Model
- `app/Models/Monitor.php` - Extended Monitor model with JavaScript content validation
- Database: Enhanced `monitors` table with content validation fields
- Features: Response time tracking, content validation, JavaScript rendering

### Spatie Integration
- `config/uptime-monitor.php` - Spatie package configuration
- Base functionality: Spatie Uptime Monitor package
- Artisan commands: `monitor:check-uptime`, `monitor:check-certificate`

## 🚨 Common Issues & Solutions

### Issue: SSL/Uptime checks not running automatically
**Solution**: Ensure scheduler is running
```bash
./vendor/bin/sail artisan schedule:work
```

### Issue: Manual "Check Now" buttons don't work
**Solution**: Ensure queue worker is running (Redis for optimal performance)
```bash
./vendor/bin/sail artisan queue:work redis --queue=immediate
```

### Issue: Emails not sending
**Solution**: Include notifications queue
```bash
./vendor/bin/sail artisan queue:work redis --queue=immediate,notifications
```

### Issue: "Queue worker not running" errors
**Solution**: Start appropriate queue worker for the failing queue

## 📈 Monitoring & Debugging

### View Scheduled Tasks
```bash
./vendor/bin/sail artisan schedule:list
```

### Monitor Queue Performance
```bash
./vendor/bin/sail artisan queue:monitor immediate notifications default
```

### Check Recent SSL/Uptime Results
```bash
./vendor/bin/sail artisan monitor:list
```

### View Queue Logs
```bash
# Development logs
./vendor/bin/sail logs

# Production logs
tail -f storage/logs/queue-immediate.log
tail -f storage/logs/queue-notifications.log
```

## 🎯 Summary

**SSL Monitor v4 Hybrid Architecture**:
- **Extended Monitor Model** (`App\Models\Monitor`) extends Spatie with enhanced features
- **Scheduler** handles automated periodic monitoring (efficient batch processing)
- **Queues** handle manual user actions (responsive UI interactions)
- **Enhanced Features**: JavaScript content validation, response time tracking, advanced content checking
- **Hybrid approach** combines Spatie's reliability with custom enhancements

**Key Enhancements**:
- ✅ JavaScript content rendering support
- ✅ Advanced content validation (expected/forbidden strings, regex patterns)
- ✅ Response time tracking and performance metrics
- ✅ Enhanced database schema with content validation fields
- ✅ Backward compatibility with existing Spatie functionality
- ✅ Redis queues for optimal performance and low latency
- ✅ Multiple specialized queues for different job types

**For Development**: Run both `schedule:work` and `queue:work` (Redis for optimal performance)
**For Production**: Setup cron + supervisor for robust 24/7 operation

This hybrid architecture provides reliable automated monitoring while maintaining responsive manual interactions and adding powerful content validation capabilities. Redis queues ensure immediate job processing with minimal latency for optimal user experience.