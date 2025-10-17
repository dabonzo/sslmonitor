# Queue Architecture Analysis & Optimization Plan

## Current Architecture Analysis

### Existing Queues (Horizon Configuration)

```php
'queue' => ['default', 'immediate', 'uptime', 'ssl', 'notifications', 'cleanup'],
```

**Configured Queues:**
1. `default` - General purpose queue
2. `immediate` - Used by ImmediateWebsiteCheckJob ✅ **ACTIVELY USED**
3. `uptime` - Reserved but **NOT USED** ❌
4. `ssl` - Reserved but **NOT USED** ❌
5. `notifications` - Reserved but **NOT USED** ❌
6. `cleanup` - Reserved but **NOT USED** ❌

### Current Job Classes

**Only 1 job class exists:**
- `App\Jobs\ImmediateWebsiteCheckJob` → Uses `immediate` queue
  - Dispatched when user clicks "Check Now" button
  - Performs both uptime AND SSL checks for a single website
  - Uses Spatie's MonitorCollection directly
  - ✅ Works perfectly - reliable and fast

### Current Scheduler Configuration

```php
// Runs Spatie's command directly (synchronous, blocks ~20s)
Schedule::command('monitor:check-uptime')
    ->everyMinute()  // Temporarily set to 1 min for testing
    ->appendOutputTo(storage_path('logs/scheduler.log'));

// SSL checks
Schedule::command('monitor:check-certificate')
    ->twiceDaily(6, 18);
```

**Problems with Current Approach:**
1. ❌ **Blocking**: Scheduler service blocks for ~20 seconds while checking all monitors
2. ❌ **Sequential**: All monitors checked one-by-one, no parallelization
3. ❌ **No retry logic**: If check fails, it's gone until next run
4. ❌ **No visibility**: Can't see individual check progress in Horizon
5. ❌ **Inconsistent**: Immediate checks use reliable queue system, scheduled checks don't
6. ❌ **`runInBackground()` broken**: Background shell processes don't update database reliably

## Spatie Package Research Findings

### Official Documentation Review

**Sources Checked:**
- Official docs: https://spatie.be/docs/laravel-uptime-monitor/v3/introduction
- GitHub: https://github.com/spatie/laravel-uptime-monitor

**Key Findings:**
1. ❌ **No built-in queue/job support** - Spatie does not provide native queue integration
2. ✅ **Concurrent checks feature** - `concurrent_checks` config (set to 10 in our app)
   - Checks up to 10 monitors in parallel
   - BUT runs synchronously within the artisan command
   - Still blocks the scheduler while running
3. 📝 **Expected usage pattern**: Run via Laravel scheduler calling artisan commands
4. 🔍 **No mention of**: Job dispatching, async execution, or queue integration

### Spatie's Built-in Concurrency

**Current Configuration:**
```php
// config/uptime-monitor.php
'concurrent_checks' => 10,
```

**What this does:**
- Spatie checks multiple monitors in parallel (using PHP's async features)
- Reduces total check time from ~20s to ~5-7s for 5 monitors
- BUT still runs synchronously in the command
- Scheduler still blocks until all concurrent checks complete

**Why it's not enough:**
- No retry logic if entire batch fails
- No visibility into individual monitor checks
- No ability to scale beyond single command execution
- Still inconsistent with immediate check architecture

## Final Architecture Decisions

Based on research and your feedback, here are the decisions:

### 1. Queue Strategy: Single `default` Queue ✅

**Decision:** Simplify from 6 queues to just 1 queue
```php
// Before
'queue' => ['default', 'immediate', 'uptime', 'ssl', 'notifications', 'cleanup']

// After
'queue' => ['default']
```

**Rationale:**
- ✅ Simpler configuration and monitoring
- ✅ Easier to understand and maintain
- ✅ Can always add more queues later if needed
- ✅ Horizon handles job prioritization automatically
- ✅ One queue is enough for our current scale

### 2. Job Granularity: Combined Uptime + SSL Check ✅

**Decision:** One `CheckMonitorJob` that performs both uptime AND SSL checks

**Rationale:**
- ✅ Matches proven `ImmediateWebsiteCheckJob` pattern
- ✅ Simpler - fewer job classes to maintain
- ✅ Atomic - both checks happen together or fail together
- ✅ More efficient - reuses same monitor fetch/update
- ❌ Alternative (separate jobs) would add complexity without clear benefit

### 3. Code Reuse Strategy ✅

**Decision:** Create shared `CheckMonitorJob`, refactor `ImmediateWebsiteCheckJob` to use it

**Architecture:**
```
CheckMonitorJob (new)
  ├── Core logic for uptime + SSL checks
  ├── Used by scheduled checks
  └── Used by immediate checks

ImmediateWebsiteCheckJob (refactored)
  ├── Handles Website → Monitor lookup
  ├── Dispatches CheckMonitorJob
  └── Returns results to user

DispatchScheduledChecks (new command)
  ├── Finds monitors due for checking
  └── Dispatches CheckMonitorJob for each
```

**Benefits:**
- ✅ DRY principle - single source of truth for check logic
- ✅ Consistent behavior for immediate and scheduled checks
- ✅ Easier to maintain and test
- ✅ Can test check logic independently

## Queue Optimization Plan

### Simplified Queue Structure

**Consolidate to 1 queue:**

```php
'queue' => ['default']
```

**Why This Is Better:**
- `default` - All jobs (monitoring, notifications, cleanup, etc.)
- ✅ Simplest possible configuration
- ✅ Easier to monitor and maintain
- ✅ Horizon auto-balances and prioritizes
- ✅ Can add specialized queues later if needed
- ✅ One queue is sufficient for our current scale

### Unified Job Architecture

**Create one reusable job:**

```php
App\Jobs\CheckMonitorJob
```

**Features:**
- Accepts a Monitor model instance
- Performs both uptime AND SSL checks (like ImmediateWebsiteCheckJob)
- Dispatched to `default` queue
- Used by BOTH immediate checks AND scheduled checks
- ✅ DRY principle - one job for all checks
- ✅ Consistent behavior everywhere
- ✅ Proven reliable (based on ImmediateWebsiteCheckJob)

### New Scheduler Configuration

```php
// Lightweight dispatch command (completes in <500ms)
Schedule::command('monitors:dispatch-scheduled-checks')
    ->everyFiveMinutes()
    ->withoutOverlapping();
```

**What it does:**
1. Query monitors that are due for checking (based on their intervals)
2. Dispatch `CheckMonitorJob` for each due monitor
3. Return immediately - jobs process asynchronously

**Benefits:**
- ✅ Non-blocking scheduler
- ✅ Parallel execution via Horizon workers
- ✅ Built-in retry logic (Horizon)
- ✅ Full visibility in Horizon dashboard
- ✅ Same reliable infrastructure as immediate checks
- ✅ Scalable - can add more workers as needed

## Implementation Plan

### Phase 1: Create CheckMonitorJob

**Base it on ImmediateWebsiteCheckJob:**
- Copy the proven uptime/SSL check logic
- Accept Monitor model instead of Website
- Dispatch to `default queue
- Keep all the excellent error handling and logging

### Phase 2: Create Dispatch Command

**New command:** `monitors:dispatch-scheduled-checks`

```php
php artisan monitors:dispatch-scheduled-checks
```

**Logic:**
```php
// Get monitors due for checking
$monitors = Monitor::where('uptime_check_enabled', true)
    ->get()
    ->filter(fn($m) => $m->shouldCheckUptime());

// Dispatch job for each
foreach ($monitors as $monitor) {
    CheckMonitorJob::dispatch($monitor)->onQueue('monitoring');
}

// Log summary
AutomationLogger::scheduler("Dispatched {$monitors->count()} monitor checks");
```

### Phase 3: Update ImmediateWebsiteCheckJob

**Refactor to use CheckMonitorJob:**
```php
public function handle(): array
{
    // Get/create monitor
    $monitor = Monitor::where('url', $this->website->url)->first();

    // Dispatch the check job
    return CheckMonitorJob::dispatchSync($monitor);
}
```

**Benefits:**
- ✅ Reuses same check logic
- ✅ Maintains immediate execution via `dispatchSync()`
- ✅ Reduces code duplication

### Phase 4: Update Horizon Configuration

**Simplified queues:**
```php
'defaults' => [
    'supervisor-1' => [
        'connection' => 'redis',
        'queue' => ['default'],  // Simplified!
        'balance' => 'auto',
        'maxProcesses' => 3,  // Can handle 3 monitors in parallel
        // ...
    ],
],
```

### Phase 5: Update Scheduler

**Replace Spatie commands:**
```php
// Old (blocking):
Schedule::command('monitor:check-uptime')->everyFiveMinutes();
Schedule::command('monitor:check-certificate')->twiceDaily(6, 18);

// New (non-blocking):
Schedule::command('monitors:dispatch-scheduled-checks')
    ->everyFiveMinutes()
    ->withoutOverlapping();
```

## Comparison: Before vs After

### Before (Current)

```
Scheduler (every 5 min)
  ↓
  monitor:check-uptime command
  ↓
  Spatie MonitorCollection->checkUptime()
  ↓
  Checks all monitors sequentially (~20s blocking)
  ↓
  Updates database
  ↓
  Returns

Problems:
- Blocks scheduler for 20s
- No parallelization
- No retry on failure
- No visibility in Horizon
```

### After (Optimized)

```
Scheduler (every 5 min)
  ↓
  monitors:dispatch-scheduled-checks command (~500ms)
  ↓
  Finds due monitors
  ↓
  Dispatches CheckMonitorJob × N
  ↓
  Returns immediately ✅

Horizon Workers (parallel):
  ↓
  Process CheckMonitorJob #1 (monitor A)
  Process CheckMonitorJob #2 (monitor B)
  Process CheckMonitorJob #3 (monitor C)
  ↓
  Each updates database independently
  ↓
  Retries on failure
  ↓
  Visible in Horizon dashboard

Benefits:
✅ Non-blocking scheduler
✅ Parallel execution (3x faster with 3 workers)
✅ Automatic retries
✅ Full Horizon visibility
✅ Consistent with immediate checks
✅ Scalable
```

## Expected Performance Improvements

### Current (5 monitors, sequential):
- Time to dispatch: 0ms (N/A - runs synchronously)
- Time to complete all checks: ~20 seconds (sequential)
- Scheduler blocked for: 20 seconds ❌

### Optimized (5 monitors, 3 workers):
- Time to dispatch: ~500ms (query + dispatch)
- Time to complete all checks: ~7 seconds (parallel with 3 workers)
- Scheduler blocked for: 500ms ✅
- **Improvement: 40x faster scheduler, 3x faster overall**

### Scalability:
- Add more workers → faster processing
- Current approach: no improvement possible
- Queue approach: linear scaling with worker count

## Testing Plan

1. ✅ Create CheckMonitorJob
2. ✅ Test CheckMonitorJob with single monitor
3. ✅ Create dispatch command
4. ✅ Test dispatch command (verify jobs appear in Horizon)
5. ✅ Update ImmediateWebsiteCheckJob to use CheckMonitorJob
6. ✅ Test immediate checks still work
7. ✅ Update scheduler configuration
8. ✅ Test scheduled checks via queue
9. ✅ Verify monitors update correctly
10. ✅ Monitor Horizon for job completion/failures
11. ✅ Deploy to production
12. ✅ Monitor production metrics

## Rollback Plan

If queue-based approach fails:
1. Checkout `main` branch
2. Deploy previous version
3. Scheduler immediately works again (blocking, but reliable)
4. Investigate issue on `feature/queue-based-scheduler` branch
5. Fix and retry

**Safety:** We're working on a feature branch, so main branch remains stable.

## Additional Benefits

### Future Enhancements Made Easy:

1. **Rate Limiting**: Limit checks per minute easily
2. **Priority Queues**: Check critical monitors first
3. **Batching**: Group monitors by domain/server
4. **Monitoring**: Track check performance over time
5. **Alerting**: Alert if queue backs up
6. **A/B Testing**: Test new check logic safely

### Consistency:

- Same job class for immediate and scheduled checks
- Same queue infrastructure everywhere
- Same error handling and retry logic
- Same logging patterns
- Easier to maintain and debug

## Conclusion

**Current State:**
- 6 queues configured, only 1 actually used
- Scheduled checks block scheduler for 20s
- Inconsistent architecture (queues vs direct commands)

**Optimized State:**
- 1 queue (default)
- Scheduler dispatches in <500ms
- Consistent queue-based architecture
- Better performance, reliability, and visibility

**Risk Level:** Low
- Working on feature branch
- Easy rollback to main
- Based on proven ImmediateWebsiteCheckJob pattern

**Effort:** Medium
- ~1-2 hours implementation
- ~30 minutes testing
- ~15 minutes deployment

**Value:** High
- 40x faster scheduler response
- 3x faster overall checks (with parallelization)
- Better reliability (retries)
- Better visibility (Horizon)
- Cleaner codebase
