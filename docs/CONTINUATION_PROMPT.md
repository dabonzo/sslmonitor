# Queue-Based Scheduler Implementation - Continuation Prompt

## Quick Context for AI Assistant

We're implementing a queue-based architecture for scheduled monitor checks in the SSL Monitor v4 Laravel application. Currently on branch `feature/queue-based-scheduler`.

### What We're Doing

**Goal:** Replace blocking Spatie artisan commands with non-blocking queue jobs for scheduled uptime and SSL checks.

**Current Problem:**
- Scheduler runs `monitor:check-uptime` command directly
- Blocks for ~20 seconds while checking all monitors sequentially
- No retry logic, no parallelization, no Horizon visibility
- Inconsistent with immediate checks (which use reliable queue jobs)

**Target Solution:**
- Scheduler dispatches queue jobs for each monitor
- Non-blocking (~500ms to dispatch)
- Parallel execution via Horizon workers
- Automatic retries, full visibility, consistent architecture

### Architecture Decisions Made

Based on research and analysis:

1. ✅ **Queue Strategy**: Simplify from 6 queues to 1 queue (`default`)
2. ✅ **Job Granularity**: One `CheckMonitorJob` for both uptime + SSL checks
3. ✅ **Code Reuse**: Shared job used by immediate and scheduled checks
4. ✅ **Spatie Research**: No built-in queue support in spatie/laravel-uptime-monitor

### Current State

**Branch:** `feature/queue-based-scheduler` (created, safe to experiment)
**Main Branch:** Stable, uses blocking Spatie commands (works but suboptimal)

**Existing Code:**
- `app/Jobs/ImmediateWebsiteCheckJob.php` - Proven, reliable immediate check job
- `app/Models/Monitor.php` - Extended Spatie Monitor with integer cast for `uptime_check_interval_in_minutes`
- `routes/console.php` - Currently uses `monitor:check-uptime` every minute (testing config)

**Recent Fixes Applied:**
- Added integer cast for `uptime_check_interval_in_minutes` in Monitor model
- Removed `withoutOverlapping()` and `runInBackground()` (they were blocking)
- Set monitors to 1-minute intervals for testing
- Set scheduler to run every minute (temporary for testing)

### Required Reading

**MUST READ BEFORE IMPLEMENTING:**

1. **`docs/QUEUE_ARCHITECTURE_ANALYSIS.md`** - Complete architecture analysis
   - Current vs optimized architecture comparison
   - Spatie research findings
   - Queue optimization decisions
   - Implementation plan with code examples
   - Performance expectations

2. **`app/Jobs/ImmediateWebsiteCheckJob.php`** - Reference implementation
   - This is the proven pattern to follow
   - Shows how to use Spatie's MonitorCollection
   - Excellent error handling and logging
   - This job will be refactored to use the new CheckMonitorJob

3. **`app/Models/Monitor.php`** - Extended Spatie Monitor model
   - Custom casts including `uptime_check_interval_in_minutes`
   - Custom methods for content validation
   - This is what CheckMonitorJob will work with

4. **`config/horizon.php`** - Current Horizon configuration
   - Currently has 6 queues, needs simplification to 1
   - Worker configuration

### Implementation Checklist

Follow this order for best results:

#### Phase 1: Create CheckMonitorJob
- [ ] Create `app/Jobs/CheckMonitorJob.php`
- [ ] Base it on ImmediateWebsiteCheckJob's check logic
- [ ] Accept `Monitor` model instance (not Website)
- [ ] Perform both uptime AND SSL checks
- [ ] Dispatch to `default` queue
- [ ] Keep all error handling and logging
- [ ] Test manually with single monitor

#### Phase 2: Create Dispatch Command
- [ ] Create `app/Console/Commands/DispatchScheduledChecks.php`
- [ ] Query monitors where `uptime_check_enabled = true`
- [ ] Filter using `shouldCheckUptime()` method
- [ ] Dispatch `CheckMonitorJob` for each due monitor
- [ ] Log summary (how many dispatched)
- [ ] Test command manually

#### Phase 3: Refactor ImmediateWebsiteCheckJob
- [ ] Update to use CheckMonitorJob internally
- [ ] Get/create Monitor from Website
- [ ] Use `CheckMonitorJob::dispatchSync()` for immediate execution
- [ ] Maintain same return structure for API compatibility
- [ ] Test immediate checks still work via UI

#### Phase 4: Update Horizon Configuration
- [ ] Simplify `config/horizon.php` queue list to `['default']`
- [ ] Update wait times (remove unused queue entries)
- [ ] Keep maxProcesses = 3 for parallel execution
- [ ] Test Horizon starts without errors

#### Phase 5: Update Scheduler
- [ ] Replace `monitor:check-uptime` with `monitors:dispatch-scheduled-checks`
- [ ] Add `withoutOverlapping()` to prevent concurrent dispatches
- [ ] Change back to `->everyFiveMinutes()` (currently every minute for testing)
- [ ] Test scheduler dispatches jobs
- [ ] Verify jobs appear in Horizon
- [ ] Verify monitors get updated in database

#### Phase 6: Testing & Deployment
- [ ] Test on development environment
- [ ] Verify all checks work (immediate + scheduled)
- [ ] Check Horizon dashboard for job visibility
- [ ] Update monitors back to 5-minute intervals (from 1-minute testing)
- [ ] Deploy to production
- [ ] Monitor production logs and Horizon

### Laravel Best Practices to Follow

**DRY (Don't Repeat Yourself):**
- Extract check logic from ImmediateWebsiteCheckJob into CheckMonitorJob
- Reuse same job for immediate and scheduled checks
- Single source of truth for uptime/SSL check logic

**KISS (Keep It Simple, Stupid):**
- One queue instead of six
- One job for both uptime and SSL (not separate)
- Simple dispatch command (query → dispatch → done)

**Laravel Conventions:**
- Use `dispatchSync()` for immediate execution
- Use `dispatch()` for async queue execution
- Follow Laravel queue job structure (Queueable, ShouldQueue traits)
- Use Horizon for queue monitoring (already installed)
- Use AutomationLogger for consistent logging
- Use typed properties and return types

**Error Handling:**
- Try/catch blocks with AutomationLogger
- Return arrays with status/error information
- Use `retryUntil()` for time-based retry limits
- Implement `failed()` method for permanent failures

### Code Examples from Analysis

**CheckMonitorJob Structure:**
```php
namespace App\Jobs;

use App\Models\Monitor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckMonitorJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;
    public Monitor $monitor;

    public function __construct(Monitor $monitor)
    {
        $this->monitor = $monitor;
        $this->onQueue('default');
    }

    public function handle(): array
    {
        // Copy logic from ImmediateWebsiteCheckJob
        // checkUptime() and checkSsl() methods
        // Return results array
    }
}
```

**Dispatch Command Structure:**
```php
namespace App\Console\Commands;

use App\Models\Monitor;
use App\Jobs\CheckMonitorJob;
use App\Support\AutomationLogger;
use Illuminate\Console\Command;

class DispatchScheduledChecks extends Command
{
    protected $signature = 'monitors:dispatch-scheduled-checks';
    protected $description = 'Dispatch queue jobs for monitors due for checking';

    public function handle(): int
    {
        $monitors = Monitor::where('uptime_check_enabled', true)
            ->get()
            ->filter(fn($m) => $m->shouldCheckUptime());

        foreach ($monitors as $monitor) {
            CheckMonitorJob::dispatch($monitor);
        }

        AutomationLogger::scheduler("Dispatched {$monitors->count()} monitor checks");
        $this->info("Dispatched {$monitors->count()} monitor checks");

        return Command::SUCCESS;
    }
}
```

**Scheduler Configuration:**
```php
// routes/console.php
Schedule::command('monitors:dispatch-scheduled-checks')
    ->everyFiveMinutes()
    ->withoutOverlapping();
```

### Important Files to Modify

1. `app/Jobs/CheckMonitorJob.php` - CREATE NEW
2. `app/Console/Commands/DispatchScheduledChecks.php` - CREATE NEW
3. `app/Jobs/ImmediateWebsiteCheckJob.php` - REFACTOR
4. `config/horizon.php` - SIMPLIFY QUEUES
5. `routes/console.php` - UPDATE SCHEDULER

### Success Criteria

✅ Scheduler dispatches jobs in <500ms (non-blocking)
✅ Jobs appear in Horizon dashboard
✅ Monitors are checked and database is updated
✅ Immediate checks still work via UI
✅ Failed jobs retry automatically
✅ Parallel execution (multiple monitors checked simultaneously)
✅ Cleaner codebase (DRY, KISS principles)

### Rollback Plan

If anything goes wrong:
1. `git checkout main` - Main branch is stable
2. Redeploy main branch
3. Debug on `feature/queue-based-scheduler` branch
4. Try again

### Testing Commands

```bash
# Test dispatch command manually
php artisan monitors:dispatch-scheduled-checks

# Check Horizon for jobs
# Visit /horizon in browser

# Test immediate check via tinker
php artisan tinker
> $website = App\Models\Website::first();
> App\Jobs\ImmediateWebsiteCheckJob::dispatch($website);

# Check scheduler schedule
php artisan schedule:list

# Monitor scheduler logs
tail -f storage/logs/scheduler.log

# Check monitor last check times
php artisan tinker
> App\Models\Monitor::all()->pluck('uptime_last_check_date', 'url');
```

### Environment Context

- **Development:** Laravel Sail (./vendor/bin/sail artisan ...)
- **Production:** SSH via arm002, direct artisan commands
- **Queue:** Horizon (already running on both environments)
- **Database:** MariaDB
- **Scheduler:** systemd timer on production, artisan schedule:work on dev

### Questions to Ask if Unclear

1. Should CheckMonitorJob check both uptime AND SSL, or separate jobs?
   - **Answer:** Combined (one job, both checks)

2. How many queues?
   - **Answer:** One queue (`default`)

3. Should ImmediateWebsiteCheckJob be refactored?
   - **Answer:** Yes, use CheckMonitorJob internally

4. Testing approach?
   - **Answer:** Manual testing via artisan commands, then full deployment

---

## Prompt to Continue

"I'm working on implementing queue-based scheduled monitor checks for the SSL Monitor application. We're on the `feature/queue-based-scheduler` branch. Please read:

1. `docs/QUEUE_ARCHITECTURE_ANALYSIS.md` for complete architecture analysis
2. `docs/CONTINUATION_PROMPT.md` for implementation checklist and context
3. `app/Jobs/ImmediateWebsiteCheckJob.php` as reference implementation

**IMPORTANT:** First, create a todo list using the TodoWrite tool based on the Implementation Checklist in the continuation prompt. This will help track progress through all phases.

Follow Laravel best practices (DRY, KISS), use the implementation checklist in order, and ask clarifying questions before proceeding with each phase. Let's start with Phase 1: Creating CheckMonitorJob."

---

## Todo List Template

When starting, create this todo list:

```
Phase 1: Create CheckMonitorJob
- Create app/Jobs/CheckMonitorJob.php
- Base on ImmediateWebsiteCheckJob logic
- Accept Monitor model, perform uptime + SSL checks
- Test manually with single monitor

Phase 2: Create Dispatch Command
- Create app/Console/Commands/DispatchScheduledChecks.php
- Query due monitors and dispatch jobs
- Test command manually

Phase 3: Refactor ImmediateWebsiteCheckJob
- Update to use CheckMonitorJob internally
- Use dispatchSync() for immediate execution
- Test immediate checks still work

Phase 4: Update Horizon Configuration
- Simplify queues to just ['default']
- Update config/horizon.php
- Test Horizon starts

Phase 5: Update Scheduler
- Replace Spatie commands with dispatch command
- Add withoutOverlapping()
- Change to everyFiveMinutes()
- Test jobs dispatch and monitors update

Phase 6: Testing & Deployment
- Test on development
- Verify Horizon visibility
- Update monitors to 5-minute intervals
- Deploy to production
- Monitor production
```

---

*Document created: 2025-10-11*
*Branch: feature/queue-based-scheduler*
*Status: Ready to implement*
