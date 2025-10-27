# Debug Logging Enhancement Plan

## Overview
Add comprehensive debug logging system with .env control and optional debug UI menu for monitoring scheduled tasks, queue operations, and general application debugging.

## Current State

### Existing Infrastructure
- **AutomationLogger** (`app/Support/AutomationLogger.php`) - Already provides structured logging
- **Log Channels** configured in `config/logging.php`:
  - `queue` → `storage/logs/queue.log`
  - `scheduler` → `storage/logs/scheduler.log`
  - `ssl-monitoring` → `storage/logs/ssl-monitoring.log`
  - `uptime-monitoring` → `storage/logs/uptime-monitoring.log`
  - `immediate-checks` → `storage/logs/immediate-checks.log`
  - `errors` → `storage/logs/errors.log`

### Current Usage
- Scheduler tasks use `AutomationLogger::scheduler()` in `routes/console.php`
- Queue jobs use `AutomationLogger::jobStart()`, `jobComplete()`, `jobFailed()`
- Already has `AutomationLogger::debug()` method that checks `config('app.debug')`

## Problem Identified
During debugging of scheduled tasks:
- Scheduler was running but monitors had 60-minute intervals instead of 5 minutes
- No visibility into why `monitor:check-uptime` was reporting "0 monitors to check"
- Difficult to debug without detailed logging of:
  - Which monitors are being selected
  - Why monitors are/aren't being checked
  - Actual execution flow of scheduled commands

## Proposed Enhancements

### Phase 1: Environment-Controlled Debug Logging

#### 1.1 New .env Variables
```env
# Debug Logging Controls
DEBUG_SCHEDULER=true              # Enable detailed scheduler logging
DEBUG_MONITORING=true             # Enable monitor selection/execution logging
DEBUG_QUEUE=true                  # Enable detailed queue job logging
DEBUG_LOG_CHANNEL=debug           # Dedicated debug log channel
DEBUG_LOG_DAYS=7                  # How long to keep debug logs
```

#### 1.2 Enhanced AutomationLogger Methods
Add detailed debug methods:
- `AutomationLogger::schedulerDebug()` - Log scheduler decisions
- `AutomationLogger::monitorSelection()` - Log which monitors are selected and why
- `AutomationLogger::monitorSkipped()` - Log why a monitor was skipped
- `AutomationLogger::queryDebug()` - Log database queries for debugging

#### 1.3 Add Logging to Critical Points

**In Scheduler (`routes/console.php`):**
```php
Schedule::command('monitor:check-uptime')
    ->everyFiveMinutes()
    ->before(function () {
        if (config('app.debug_scheduler')) {
            AutomationLogger::schedulerDebug('Starting monitor:check-uptime', [
                'scheduled_time' => now()->toDateTimeString(),
                'total_monitors' => Monitor::count(),
                'enabled_monitors' => Monitor::where('uptime_check_enabled', true)->count(),
            ]);
        }
    })
    ->after(function () {
        if (config('app.debug_scheduler')) {
            AutomationLogger::schedulerDebug('Completed monitor:check-uptime', [
                'completed_at' => now()->toDateTimeString(),
            ]);
        }
    });
```

**In MonitorIntegrationService:**
```php
public function createOrUpdateMonitorForWebsite(Website $website): Monitor
{
    if (config('app.debug_monitoring')) {
        AutomationLogger::monitorSelection('Creating/updating monitor', [
            'website_id' => $website->id,
            'url' => $website->url,
            'uptime_enabled' => $website->uptime_monitoring_enabled,
            'ssl_enabled' => $website->ssl_monitoring_enabled,
            'check_interval' => $this->getCheckIntervalInMinutes($website->monitoring_config ?? []),
        ]);
    }

    // ... existing code
}
```

**In Spatie's Monitor Model (via custom Monitor model):**
```php
public function shouldCheckUptime(): bool
{
    $shouldCheck = parent::shouldCheckUptime();

    if (config('app.debug_monitoring')) {
        AutomationLogger::monitorSelection(
            $shouldCheck ? 'Monitor selected for check' : 'Monitor skipped',
            [
                'url' => $this->url,
                'reason' => $this->getSkipReason(),
                'last_check' => $this->uptime_last_check_date,
                'interval_minutes' => $this->uptime_check_interval_in_minutes,
                'next_check_due' => $this->getNextCheckTime(),
            ]
        );
    }

    return $shouldCheck;
}
```

### Phase 2: Debug UI Menu (Optional)

#### 2.1 .env Control
```env
# Debug UI Menu
DEBUG_MENU_ENABLED=true           # Show debug menu in navigation
DEBUG_MENU_ROLE=admin             # Which role can see debug menu
```

#### 2.2 Debug Dashboard Features

**Route:** `/debug`

**Features:**
1. **Real-time Log Viewer**
   - Tail logs in browser
   - Filter by channel (scheduler, queue, monitoring, etc.)
   - Search and highlight
   - Auto-refresh

2. **Scheduler Status**
   - List all scheduled tasks
   - Show next run times
   - View recent executions
   - Manual trigger buttons (for admins)

3. **Monitor Inspector**
   - View all monitors and their states
   - See why each monitor is/isn't being checked
   - Show check intervals and last check times
   - Preview what `shouldCheckUptime()` returns

4. **Queue Monitor**
   - View pending jobs
   - Failed jobs with retry options
   - Job performance metrics
   - Horizon integration (already installed)

5. **Configuration Viewer**
   - Show resolved config values
   - Display .env variables (sanitized)
   - Show log channel configurations

6. **System Health**
   - Database connection status
   - Redis connection status
   - Disk space
   - Memory usage
   - Queue worker status

#### 2.3 Security
- Only accessible when `DEBUG_MENU_ENABLED=true`
- Require authentication
- Check user role (admin only by default)
- Log all debug panel access

## Implementation Plan

### Step 1: Core Debug Logging (Immediate)
1. Add new config variables to `.env.example`
2. Add `debug_scheduler`, `debug_monitoring`, `debug_queue` to `config/app.php`
3. Add new debug log channel to `config/logging.php`
4. Enhance `AutomationLogger` with new debug methods
5. Add logging to:
   - Scheduler tasks (`routes/console.php`)
   - `MonitorIntegrationService`
   - Custom `Monitor` model
   - Queue jobs

### Step 2: Test Debug Logging
1. Enable debug flags in development
2. Run scheduler manually
3. Verify logs show detailed selection logic
4. Test with monitors at different intervals

### Step 3: Debug UI (Future Enhancement)
1. Create `/debug` route and controller
2. Build Vue components for log viewer
3. Add real-time log tailing (WebSockets or polling)
4. Integrate with Horizon
5. Add manual job triggers

### Step 4: Documentation
1. Update `CLAUDE.md` with debug logging info
2. Add debugging guide to `docs/`
3. Document .env variables

## Benefits

### For Development
- Quick identification of issues like the 60-minute interval bug
- Understand why monitors are/aren't being selected
- Debug scheduler timing issues
- Monitor query performance

### For Production
- Can be enabled temporarily to debug issues
- Disabled by default (no performance impact)
- Separate log files for easy analysis
- Automatic cleanup (via log rotation)

### For Future Features
- Foundation for admin debugging tools
- Real-time monitoring capabilities
- Performance profiling

## Example Debug Output

**Before (Current):**
```
[2025-10-10 12:55:00] Starting monitor:check-uptime
Start checking the uptime of 0 monitors...
All done!
```

**After (With Debug Logging):**
```
[2025-10-10 12:55:00] [SCHEDULER] Starting monitor:check-uptime
  - Total monitors in database: 5
  - Monitors with uptime_check_enabled: 5
  - Applying shouldCheckUptime filter...

[2025-10-10 12:55:00] [MONITORING] Evaluating monitor: https://www.redgas.at
  - Last checked: 2025-10-10 12:44:46
  - Interval: 60 minutes
  - Next check due: 2025-10-10 13:44:46
  - Decision: SKIP (not due yet)

[2025-10-10 12:55:00] [MONITORING] Evaluating monitor: https://www.fairnando.at
  - Last checked: 2025-10-10 12:42:54
  - Interval: 60 minutes
  - Next check due: 2025-10-10 13:42:54
  - Decision: SKIP (not due yet)

[2025-10-10 12:55:00] [SCHEDULER] monitor:check-uptime completed
  - Monitors evaluated: 5
  - Monitors checked: 0
  - Monitors skipped: 5 (all due to interval timing)
```

## Files to Modify

### Configuration
- `.env.example` - Add new debug variables
- `config/app.php` - Add debug flags
- `config/logging.php` - Add debug channel

### Core Classes
- `app/Support/AutomationLogger.php` - Add debug methods
- `routes/console.php` - Add before/after hooks with logging
- `app/Services/MonitorIntegrationService.php` - Add debug logging
- `app/Models/Monitor.php` - Override `shouldCheckUptime()` with logging

### Future (Debug UI)
- `routes/web.php` - Add `/debug` route
- `app/Http/Controllers/DebugController.php` - New controller
- `resources/js/pages/Debug/` - Vue components
- `app/Http/Middleware/DebugMenuAccess.php` - Authorization

## Notes

- Start with Phase 1 (environment-controlled logging)
- Phase 2 (Debug UI) can be implemented later based on need
- All debug logging should check config flags before logging
- Consider performance impact of debug logging in production
- Use structured logging (arrays) for easy parsing
- Leverage existing `AutomationLogger` infrastructure

## Related Issues

- Fixed: Monitor intervals defaulting to 60 minutes instead of 5 minutes
- Fixed: Scheduler running but not executing checks due to interval mismatch
- Enhancement: Need better visibility into scheduler/monitor selection logic
