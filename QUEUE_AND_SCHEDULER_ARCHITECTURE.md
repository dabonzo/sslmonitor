# SSL Monitor v4 - Queue and Scheduler Architecture

## 📋 Overview

SSL Monitor v4 uses a **hybrid architecture** combining Laravel's **Scheduler** for automated monitoring and **Queues** for manual user actions. This document explains the complete monitoring system architecture.

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
│                   SPATIE UPTIME MONITOR                                │
│                                                                         │
│  • Batch processes ALL websites simultaneously                         │
│  • Updates `monitors` table with latest results                        │
│  • Fires Laravel events (success/failure/recovery)                     │
│  • NO individual queued jobs per website                               │
│  • Handles both SSL certificate and uptime checking                    │
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
**Technology**: Laravel Scheduler + Spatie Uptime Monitor package
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
- ✅ Updates `monitors` table directly
- ✅ Fires Laravel events for notifications
- ✅ Efficient for large numbers of websites

### 2. 👤 Manual Monitoring (Queue-Based)

**Purpose**: Immediate checks when users click "Check Now"
**Technology**: Laravel Queues + Custom Jobs
**Frequency**: On-demand user actions

**How it works**:
```php
// User clicks "Check Now" button
ImmediateWebsiteCheckJob::dispatch($website)
    ->onQueue('immediate');

// Queue worker processes job
php artisan queue:work redis --queue=immediate
```

**Key Characteristics**:
- ✅ Individual jobs per website check
- ✅ Immediate user feedback
- ✅ Uses `ImmediateWebsiteCheckJob`
- ✅ Queued on `immediate` queue
- ✅ Perfect for responsive UI interactions

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
| `uptime` | ✅ Yes | ❌ No | Spatie handles via scheduler |
| `ssl` | ✅ Yes | ❌ No | Spatie handles via scheduler |
| `cleanup` | ✅ Yes | ❌ No | Cleanup runs via scheduler |

**Why they exist**:
1. **Legacy from v3**: Original design had separate queue workers
2. **Production planning**: Supervisor config shows intended scale architecture
3. **Future-proofing**: Could be used if architecture changes
4. **Documentation**: Shows intended production queue separation

## 🛠️ Development Setup

### Start Both Systems

```bash
# Terminal 1: Start scheduler worker (automated monitoring)
./vendor/bin/sail artisan schedule:work

# Terminal 2: Start queue workers (manual actions)
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
# Efficient: Only run active queues
php artisan queue:work redis --queue=immediate,notifications,default --timeout=90 --tries=3
```

## 📊 Data Flow

### Website Creation Flow

1. **User creates website** → Website model saved
2. **ImmediateWebsiteCheckJob dispatched** → `immediate` queue
3. **Queue worker processes job** → Calls Spatie monitor methods
4. **Results saved** → `monitors` table updated
5. **UI updated** → Real-time status display

### Automated Monitoring Flow

1. **Cron triggers scheduler** → Every minute
2. **Scheduler runs monitor commands** → Batch processes all websites
3. **Spatie package checks all sites** → HTTP requests + SSL validation
4. **Results saved** → `monitors` table updated
5. **Events fired** → Notifications sent if failures

## 🔍 Key Files

### Scheduler Configuration
- `routes/console.php` - All scheduled tasks
- `app/Console/Kernel.php` - Legacy (Laravel 12 uses routes/console.php)

### Queue Configuration
- `.env` - Queue connection and queue names
- `config/queue.php` - Queue driver configuration
- `supervisor/ssl-monitor-queues.conf` - Production worker config

### Monitoring Jobs
- `app/Jobs/ImmediateWebsiteCheckJob.php` - Manual check job
- `app/Mail/TeamInvitationMail.php` - Email notifications

### Spatie Integration
- `config/uptime-monitor.php` - Spatie package configuration
- Artisan commands: `monitor:check-uptime`, `monitor:check-certificate`

## 🚨 Common Issues & Solutions

### Issue: SSL/Uptime checks not running automatically
**Solution**: Ensure scheduler is running
```bash
./vendor/bin/sail artisan schedule:work
```

### Issue: Manual "Check Now" buttons don't work
**Solution**: Ensure queue worker is running
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

**SSL Monitor v4 Architecture**:
- **Scheduler** handles automated periodic monitoring (efficient batch processing)
- **Queues** handle manual user actions (responsive UI interactions)
- **Spatie Uptime Monitor** provides the core monitoring functionality
- **Hybrid approach** combines the best of both worlds

**For Development**: Run both `schedule:work` and `queue:work`
**For Production**: Setup cron + supervisor for robust 24/7 operation

This architecture provides reliable automated monitoring while maintaining responsive manual interactions for users.