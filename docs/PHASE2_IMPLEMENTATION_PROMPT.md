# Phase 2 Implementation Prompt - Historical Data Tracking

**Copy this entire prompt to start Phase 2 implementation**

---

## 🎯 Mission: Implement Phase 2 - Data Capture Integration (Week 2)

You are implementing **Phase 2: Data Capture Integration** of the Historical Data Tracking system for SSL Monitor v4. This phase creates the event system and listeners that automatically capture monitoring check results into the historical database tables.

## 📚 Essential Context

**Project**: SSL Monitor v4 - Laravel 12 + Vue 3 + Inertia.js + MariaDB
**Current State**: Phase 1 complete - 4 tables and models created, 549 tests passing in 6.51s
**Branch**: `feature/historical-data-tracking` (continue from Phase 1)
**Test Performance Requirement**: Maintain < 20s parallel test execution

**Documentation**:
- **Master Plan**: `docs/HISTORICAL_DATA_MASTER_PLAN.md` (complete implementation guide)
- **Phase 1 Completion**: All database tables and models ready
- **Testing Guide**: `docs/TESTING_INSIGHTS.md`
- **Development Primer**: `docs/DEVELOPMENT_PRIMER.md`


## 🤖 Optimal Implementation Using Specialized Agents

**RECOMMENDED**: Use specialized agents for faster, more accurate implementation:

### **Approach 1: Use laravel-backend-specialist Agent** ⚡ (Recommended)

Launch the laravel-backend-specialist agent with this prompt:

```
Implement Phase 2 of Historical Data Tracking - Event System and Data Capture.

Read docs/PHASE2_IMPLEMENTATION_PROMPT.md and implement all steps:
- Create 4 Laravel events (MonitoringCheckStarted, MonitoringCheckCompleted, MonitoringCheckFailed, MonitoringBatchCompleted)
- Create 4 queued listeners (RecordMonitoringResult, RecordMonitoringFailure, UpdateMonitoringSummaries, CheckAlertConditions)
- CRITICAL: All listeners MUST implement ShouldQueue interface
- Modify CheckMonitorJob to fire events
- Modify ImmediateWebsiteCheckJob for manual trigger tracking
- Configure Horizon queues
- Register event listeners in AppServiceProvider

Follow exact code from the implementation prompt.
```

### **Approach 2: Use Multiple Agents in Parallel** 🚀 (Fastest)

For maximum speed, use agents in parallel:

**Agent 1: laravel-backend-specialist** - Create events, listeners, job modifications
**Agent 2: testing-specialist** - Create integration tests while Agent 1 works

Launch both agents simultaneously for parallel execution.

### **Approach 3: Manual Step-by-Step** 🐢 (Slowest)

Follow steps 1-12 manually in the implementation prompt below.

---

## 🎯 Phase 2 Goals

Create the event-driven architecture for automatic data capture:
1. ✅ 4 Laravel events for monitoring lifecycle
2. ✅ 4 queued listeners to capture and process data
3. ✅ Modify CheckMonitorJob to fire events
4. ✅ Configure Horizon queues for processing
5. ✅ Integration tests for event system
6. ✅ All tests passing (maintain < 20s)

## 📋 Detailed Implementation Steps

### Step 1: Create Laravel Events (4 files)

```bash
./vendor/bin/sail artisan make:event MonitoringCheckStarted
./vendor/bin/sail artisan make:event MonitoringCheckCompleted
./vendor/bin/sail artisan make:event MonitoringCheckFailed
./vendor/bin/sail artisan make:event MonitoringBatchCompleted
```

### Step 2: Implement Event Classes

**CRITICAL**: Copy exact code from `docs/HISTORICAL_DATA_MASTER_PLAN.md` sections for events (search for "Event Classes")

#### **For `app/Events/MonitoringCheckStarted.php`**:
```php
<?php

namespace App\Events;

use App\Models\Monitor;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MonitoringCheckStarted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Monitor $monitor,
        public readonly string $triggerType,
        public readonly ?int $triggeredByUserId = null,
    ) {}
}
```

#### **For `app/Events/MonitoringCheckCompleted.php`**:
```php
<?php

namespace App\Events;

use App\Models\Monitor;
use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MonitoringCheckCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Monitor $monitor,
        public readonly string $triggerType,
        public readonly ?int $triggeredByUserId,
        public readonly Carbon $startedAt,
        public readonly Carbon $completedAt,
        public readonly array $checkResults,
    ) {}
}
```

#### **For `app/Events/MonitoringCheckFailed.php`**:
```php
<?php

namespace App\Events;

use App\Models\Monitor;
use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Throwable;

class MonitoringCheckFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Monitor $monitor,
        public readonly string $triggerType,
        public readonly ?int $triggeredByUserId,
        public readonly Carbon $startedAt,
        public readonly Throwable $exception,
    ) {}
}
```

#### **For `app/Events/MonitoringBatchCompleted.php`**:
```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MonitoringBatchCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $totalChecks,
        public readonly int $successfulChecks,
        public readonly int $failedChecks,
        public readonly array $monitorIds,
    ) {}
}
```

### Step 3: Create Event Listeners (4 files)

```bash
./vendor/bin/sail artisan make:listener RecordMonitoringResult --event=MonitoringCheckCompleted
./vendor/bin/sail artisan make:listener RecordMonitoringFailure --event=MonitoringCheckFailed
./vendor/bin/sail artisan make:listener UpdateMonitoringSummaries --event=MonitoringCheckCompleted
./vendor/bin/sail artisan make:listener CheckAlertConditions --event=MonitoringCheckCompleted
```

### Step 4: Implement Listener Classes

**CRITICAL**: ALL listeners MUST implement `ShouldQueue` interface for async processing

#### **For `app/Listeners/RecordMonitoringResult.php`**:
```php
<?php

namespace App\Listeners;

use App\Events\MonitoringCheckCompleted;
use App\Models\MonitoringResult;
use Illuminate\Contracts\Queue\ShouldQueue;

class RecordMonitoringResult implements ShouldQueue
{
    public string $queue = 'monitoring-history';

    public function handle(MonitoringCheckCompleted $event): void
    {
        $monitor = $event->monitor;
        $results = $event->checkResults;

        MonitoringResult::create([
            'monitor_id' => $monitor->id,
            'website_id' => $monitor->website_id ?? $this->getWebsiteIdFromMonitor($monitor),
            'check_type' => $results['check_type'] ?? 'both',
            'trigger_type' => $event->triggerType,
            'triggered_by_user_id' => $event->triggeredByUserId,
            
            // Timing
            'started_at' => $event->startedAt,
            'completed_at' => $event->completedAt,
            'duration_ms' => $event->startedAt->diffInMilliseconds($event->completedAt),
            
            // Overall status
            'status' => $results['status'] ?? 'success',
            'error_message' => $results['error_message'] ?? null,
            
            // Uptime data
            'uptime_status' => $results['uptime_status'] ?? null,
            'http_status_code' => $results['http_status_code'] ?? null,
            'response_time_ms' => $monitor->uptime_check_response_time_in_ms,
            'response_body_size_bytes' => $results['response_body_size_bytes'] ?? null,
            'redirect_count' => $results['redirect_count'] ?? 0,
            'final_url' => $results['final_url'] ?? null,
            
            // SSL data
            'ssl_status' => $results['ssl_status'] ?? null,
            'certificate_issuer' => $monitor->certificate_issuer,
            'certificate_subject' => $results['certificate_subject'] ?? null,
            'certificate_expiration_date' => $monitor->certificate_expiration_date,
            'certificate_valid_from_date' => $results['certificate_valid_from_date'] ?? null,
            'days_until_expiration' => $results['days_until_expiration'] ?? null,
            'certificate_chain' => $results['certificate_chain'] ?? null,
            
            // Content validation
            'content_validation_enabled' => $results['content_validation_enabled'] ?? false,
            'content_validation_status' => $results['content_validation_status'] ?? null,
            'expected_strings_found' => $results['expected_strings_found'] ?? null,
            'forbidden_strings_found' => $results['forbidden_strings_found'] ?? null,
            'regex_matches' => $results['regex_matches'] ?? null,
            'javascript_rendered' => $results['javascript_rendered'] ?? false,
            'javascript_wait_seconds' => $results['javascript_wait_seconds'] ?? null,
            'content_hash' => $results['content_hash'] ?? null,
            
            // Technical details
            'check_method' => $results['check_method'] ?? 'GET',
            'user_agent' => $results['user_agent'] ?? null,
            'request_headers' => $results['request_headers'] ?? null,
            'response_headers' => $results['response_headers'] ?? null,
            'ip_address' => $results['ip_address'] ?? null,
            'server_software' => $results['server_software'] ?? null,
            
            // Monitoring context
            'monitor_config' => [
                'uptime_check_interval' => $monitor->uptime_check_interval_in_minutes,
                'look_for_string' => $monitor->look_for_string,
            ],
            'check_interval_minutes' => $monitor->uptime_check_interval_in_minutes,
        ]);
    }

    private function getWebsiteIdFromMonitor($monitor): ?int
    {
        // Get website_id from monitor's URL
        $website = \App\Models\Website::where('url', (string) $monitor->url)->first();
        return $website?->id;
    }
}
```

#### **For `app/Listeners/RecordMonitoringFailure.php`**:
```php
<?php

namespace App\Listeners;

use App\Events\MonitoringCheckFailed;
use App\Models\MonitoringResult;
use Illuminate\Contracts\Queue\ShouldQueue;

class RecordMonitoringFailure implements ShouldQueue
{
    public string $queue = 'monitoring-history';

    public function handle(MonitoringCheckFailed $event): void
    {
        $monitor = $event->monitor;

        MonitoringResult::create([
            'monitor_id' => $monitor->id,
            'website_id' => $this->getWebsiteIdFromMonitor($monitor),
            'check_type' => 'both',
            'trigger_type' => $event->triggerType,
            'triggered_by_user_id' => $event->triggeredByUserId,
            
            'started_at' => $event->startedAt,
            'completed_at' => now(),
            'duration_ms' => $event->startedAt->diffInMilliseconds(now()),
            
            'status' => 'error',
            'error_message' => $event->exception->getMessage(),
        ]);
    }

    private function getWebsiteIdFromMonitor($monitor): ?int
    {
        $website = \App\Models\Website::where('url', (string) $monitor->url)->first();
        return $website?->id;
    }
}
```

#### **For `app/Listeners/UpdateMonitoringSummaries.php`**:
```php
<?php

namespace App\Listeners;

use App\Events\MonitoringCheckCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateMonitoringSummaries implements ShouldQueue
{
    public string $queue = 'monitoring-aggregation';

    public function handle(MonitoringCheckCompleted $event): void
    {
        // TODO: Implement in Phase 4
        // This will calculate and update hourly/daily summaries
    }
}
```

#### **For `app/Listeners/CheckAlertConditions.php`**:
```php
<?php

namespace App\Listeners;

use App\Events\MonitoringCheckCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;

class CheckAlertConditions implements ShouldQueue
{
    public string $queue = 'monitoring-history';

    public function handle(MonitoringCheckCompleted $event): void
    {
        // TODO: Implement in Phase 4
        // This will check if alerts should be triggered based on check results
    }
}
```

### Step 5: Modify CheckMonitorJob

Find `app/Jobs/CheckMonitorJob.php` and add event firing:

```php
<?php

namespace App\Jobs;

use App\Events\MonitoringCheckCompleted;
use App\Events\MonitoringCheckFailed;
use App\Events\MonitoringCheckStarted;
use App\Models\Monitor;
// ... existing imports

class CheckMonitorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Add these properties
    public string $triggerType = 'scheduled';
    public ?int $triggeredByUserId = null;

    public function __construct(
        public Monitor $monitor,
    ) {}

    public function handle(): void
    {
        $startedAt = now();

        // Fire started event
        event(new MonitoringCheckStarted(
            monitor: $this->monitor,
            triggerType: $this->triggerType,
            triggeredByUserId: $this->triggeredByUserId,
        ));

        try {
            // Existing check logic...
            $this->performUptimeCheck();
            $this->performSslCheck();
            
            // Gather check results
            $checkResults = [
                'check_type' => $this->determineCheckType(),
                'status' => $this->determineOverallStatus(),
                'uptime_status' => $this->monitor->uptime_status,
                'http_status_code' => $this->getHttpStatusCode(),
                'ssl_status' => $this->monitor->certificate_status,
                'days_until_expiration' => $this->calculateDaysUntilExpiration(),
                // Add more result data as needed
            ];

            // Fire completed event
            event(new MonitoringCheckCompleted(
                monitor: $this->monitor,
                triggerType: $this->triggerType,
                triggeredByUserId: $this->triggeredByUserId,
                startedAt: $startedAt,
                completedAt: now(),
                checkResults: $checkResults,
            ));

        } catch (\Throwable $e) {
            // Fire failed event
            event(new MonitoringCheckFailed(
                monitor: $this->monitor,
                triggerType: $this->triggerType,
                triggeredByUserId: $this->triggeredByUserId,
                startedAt: $startedAt,
                exception: $e,
            ));

            throw $e;
        }
    }

    private function determineCheckType(): string
    {
        if ($this->monitor->uptime_check_enabled && $this->monitor->certificate_check_enabled) {
            return 'both';
        } elseif ($this->monitor->uptime_check_enabled) {
            return 'uptime';
        } elseif ($this->monitor->certificate_check_enabled) {
            return 'ssl_certificate';
        }
        return 'both';
    }

    private function determineOverallStatus(): string
    {
        // Determine if check was successful overall
        $uptimeOk = !$this->monitor->uptime_check_enabled || $this->monitor->uptime_status === 'up';
        $sslOk = !$this->monitor->certificate_check_enabled || $this->monitor->certificate_status === 'valid';
        
        return ($uptimeOk && $sslOk) ? 'success' : 'failed';
    }

    private function getHttpStatusCode(): ?int
    {
        // Extract from existing check logic
        return null; // Implement based on existing code
    }

    private function calculateDaysUntilExpiration(): ?int
    {
        if (!$this->monitor->certificate_expiration_date) {
            return null;
        }
        
        return now()->diffInDays($this->monitor->certificate_expiration_date, false);
    }

    // ... rest of existing CheckMonitorJob code
}
```

### Step 6: Modify ImmediateWebsiteCheckJob

Find `app/Jobs/ImmediateWebsiteCheckJob.php` and set manual trigger:

```php
public function handle(): void
{
    $monitor = $this->website->getSpatieMonitor();
    
    if (!$monitor) {
        return;
    }

    // Create check job with manual trigger type
    $checkJob = new CheckMonitorJob($monitor);
    $checkJob->triggerType = 'manual_immediate';
    $checkJob->triggeredByUserId = auth()->id();
    
    // Dispatch the job
    dispatch($checkJob);
}
```

### Step 7: Configure Horizon Queues

Edit `config/horizon.php`:

```php
'environments' => [
    'production' => [
        // ... existing queues ...
        
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
    
    'local' => [
        // ... existing queues ...
        
        'monitoring-history' => [
            'connection' => 'redis',
            'queue' => ['monitoring-history'],
            'balance' => 'auto',
            'processes' => 1,
            'tries' => 3,
        ],
        'monitoring-aggregation' => [
            'connection' => 'redis',
            'queue' => ['monitoring-aggregation'],
            'balance' => 'auto',
            'processes' => 1,
            'tries' => 2,
        ],
    ],
],
```

### Step 8: Register Event Listeners

Edit `app/Providers/AppServiceProvider.php`:

```php
<?php

namespace App\Providers;

use App\Events\MonitoringCheckCompleted;
use App\Events\MonitoringCheckFailed;
use App\Listeners\CheckAlertConditions;
use App\Listeners\RecordMonitoringFailure;
use App\Listeners\RecordMonitoringResult;
use App\Listeners\UpdateMonitoringSummaries;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Register monitoring event listeners
        Event::listen(
            MonitoringCheckCompleted::class,
            [RecordMonitoringResult::class, 'handle']
        );

        Event::listen(
            MonitoringCheckFailed::class,
            [RecordMonitoringFailure::class, 'handle']
        );

        Event::listen(
            MonitoringCheckCompleted::class,
            [UpdateMonitoringSummaries::class, 'handle']
        );

        Event::listen(
            MonitoringCheckCompleted::class,
            [CheckAlertConditions::class, 'handle']
        );
    }
}
```

### Step 9: Write Integration Tests

Create `tests/Feature/HistoricalData/EventSystemTest.php`:

```php
<?php

use App\Events\MonitoringCheckCompleted;
use App\Events\MonitoringCheckFailed;
use App\Events\MonitoringCheckStarted;
use App\Models\Monitor;
use App\Models\MonitoringResult;
use App\Models\Website;
use Illuminate\Support\Facades\Event;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
});

test('MonitoringCheckStarted event can be fired', function () {
    Event::fake([MonitoringCheckStarted::class]);
    
    $monitor = Monitor::first();
    
    event(new MonitoringCheckStarted(
        monitor: $monitor,
        triggerType: 'scheduled',
        triggeredByUserId: null,
    ));
    
    Event::assertDispatched(MonitoringCheckStarted::class);
});

test('MonitoringCheckCompleted event creates monitoring result', function () {
    $monitor = Monitor::first();
    $website = Website::first();
    
    $startedAt = now()->subSeconds(2);
    $completedAt = now();
    
    event(new MonitoringCheckCompleted(
        monitor: $monitor,
        triggerType: 'scheduled',
        triggeredByUserId: null,
        startedAt: $startedAt,
        completedAt: $completedAt,
        checkResults: [
            'check_type' => 'both',
            'status' => 'success',
            'uptime_status' => 'up',
            'http_status_code' => 200,
            'ssl_status' => 'valid',
        ],
    ));
    
    // Wait for queue processing (in tests, listeners execute synchronously by default)
    $result = MonitoringResult::where('monitor_id', $monitor->id)->first();
    
    expect($result)->not->toBeNull();
    expect($result->trigger_type)->toBe('scheduled');
    expect($result->status)->toBe('success');
    expect($result->uptime_status)->toBe('up');
});

test('MonitoringCheckFailed event creates error record', function () {
    $monitor = Monitor::first();
    
    $startedAt = now()->subSeconds(1);
    $exception = new \Exception('Test error');
    
    event(new MonitoringCheckFailed(
        monitor: $monitor,
        triggerType: 'scheduled',
        triggeredByUserId: null,
        startedAt: $startedAt,
        exception: $exception,
    ));
    
    $result = MonitoringResult::where('monitor_id', $monitor->id)->first();
    
    expect($result)->not->toBeNull();
    expect($result->status)->toBe('error');
    expect($result->error_message)->toBe('Test error');
});

test('manual check records triggered_by_user_id', function () {
    $monitor = Monitor::first();
    $user = \App\Models\User::first();
    
    event(new MonitoringCheckCompleted(
        monitor: $monitor,
        triggerType: 'manual_immediate',
        triggeredByUserId: $user->id,
        startedAt: now()->subSeconds(2),
        completedAt: now(),
        checkResults: [
            'check_type' => 'both',
            'status' => 'success',
        ],
    ));
    
    $result = MonitoringResult::where('monitor_id', $monitor->id)->first();
    
    expect($result->trigger_type)->toBe('manual_immediate');
    expect($result->triggered_by_user_id)->toBe($user->id);
});

test('check duration is calculated correctly', function () {
    $monitor = Monitor::first();
    
    $startedAt = now()->subMilliseconds(1500);
    $completedAt = now();
    
    event(new MonitoringCheckCompleted(
        monitor: $monitor,
        triggerType: 'scheduled',
        triggeredByUserId: null,
        startedAt: $startedAt,
        completedAt: $completedAt,
        checkResults: ['check_type' => 'both', 'status' => 'success'],
    ));
    
    $result = MonitoringResult::where('monitor_id', $monitor->id)->first();
    
    expect($result->duration_ms)->toBeGreaterThanOrEqual(1400);
    expect($result->duration_ms)->toBeLessThanOrEqual(1600);
});
```

### Step 10: Run Tests

```bash
# Run historical data tests
./vendor/bin/sail artisan test --filter=HistoricalData --parallel

# Run full test suite
time ./vendor/bin/sail artisan test --parallel

# MUST meet requirements:
# - All tests passing
# - Execution time < 20 seconds
# - No failures
```

### Step 11: Validation via Tinker

```bash
./vendor/bin/sail artisan tinker

# Fire a test event
>>> $monitor = \App\Models\Monitor::first();
>>> event(new \App\Events\MonitoringCheckCompleted(
...   monitor: $monitor,
...   triggerType: 'manual_immediate',
...   triggeredByUserId: 1,
...   startedAt: now()->subSeconds(2),
...   completedAt: now(),
...   checkResults: [
...     'check_type' => 'both',
...     'status' => 'success',
...     'uptime_status' => 'up',
...     'http_status_code' => 200,
...   ]
... ));

# Verify record was created
>>> \App\Models\MonitoringResult::count()
// Should be > 0

>>> \App\Models\MonitoringResult::latest()->first()->toArray()
// Should show the record with trigger_type = 'manual_immediate'

# Check Horizon
>>> exit
./vendor/bin/sail artisan horizon:list
```

### Step 12: Test in Development

```bash
# Start Horizon
./vendor/bin/sail artisan horizon

# In another terminal, trigger a manual check via UI
# Or trigger via tinker:
./vendor/bin/sail artisan tinker
>>> $monitor = \App\Models\Monitor::first();
>>> $job = new \App\Jobs\CheckMonitorJob($monitor);
>>> $job->triggerType = 'manual_immediate';
>>> $job->triggeredByUserId = 1;
>>> dispatch($job);

# Verify in Horizon dashboard: http://localhost/horizon
# Verify in database:
>>> \App\Models\MonitoringResult::latest()->first()
```

## ✅ Phase 2 Completion Checklist

Before marking Phase 2 complete, verify:

- [ ] 4 events created (Started, Completed, Failed, BatchCompleted)
- [ ] 4 listeners created with `ShouldQueue` interface
- [ ] CheckMonitorJob fires events at appropriate times
- [ ] ImmediateWebsiteCheckJob sets manual trigger type
- [ ] Horizon queues configured (monitoring-history, monitoring-aggregation)
- [ ] Event listeners registered in AppServiceProvider
- [ ] Integration tests created and passing
- [ ] Full test suite passing (all 549+ tests)
- [ ] Test execution time < 20 seconds
- [ ] Historical data is being captured
- [ ] Can fire events manually via tinker
- [ ] Horizon shows jobs processing
- [ ] MonitoringResult records created with correct data
- [ ] Manual vs scheduled checks distinguished correctly
- [ ] UUID generation still works on MonitoringResult

## 📊 Success Criteria

**Events**:
- ✅ 4 events created
- ✅ Constructor property promotion used
- ✅ All event properties are readonly

**Listeners**:
- ✅ All implement `ShouldQueue`
- ✅ Correct queue assignment
- ✅ RecordMonitoringResult captures all check data
- ✅ RecordMonitoringFailure handles errors

**Integration**:
- ✅ CheckMonitorJob fires events
- ✅ Manual checks set trigger type
- ✅ Horizon processes jobs
- ✅ Database records created

**Tests**:
- ✅ All existing tests still pass
- ✅ New integration tests pass
- ✅ Performance maintained (< 20s)

## 🚀 After Phase 2 Completion

Once Phase 2 is complete and verified:

1. **Commit your work**:
```bash
git add -A
git commit -m "feat: implement Phase 2 - event system for historical data capture

- Create 4 events for monitoring lifecycle
- Implement 4 queued listeners for data capture
- Modify CheckMonitorJob to fire events
- Configure Horizon queues for async processing
- Add integration tests for event system
- All 549+ tests passing in < 20s

Phase 2 of historical data tracking complete.
Events fire automatically, listeners capture data to monitoring_results table."
```

2. **Verify production readiness**:
   - Check Horizon is running: `./vendor/bin/sail artisan horizon:status`
   - Verify queue workers are processing
   - Test manual check in UI
   - Verify historical data is captured

3. **Proceed to Phase 3** using `docs/PHASE3_IMPLEMENTATION_PROMPT.md` (to be created after Phase 2)

## ⚠️ Common Issues & Solutions

**Issue**: Events not firing
**Solution**: Ensure Event::listen() is in AppServiceProvider boot() method

**Issue**: Listeners not executing
**Solution**: 
- Verify listeners implement `ShouldQueue`
- Check Horizon is running: `./vendor/bin/sail artisan horizon`
- Verify queue configuration in `config/horizon.php`

**Issue**: MonitoringResult not created
**Solution**: 
- Check listener is registered
- Verify foreign keys (monitor_id, website_id)
- Check Horizon failed jobs for errors

**Issue**: "website_id cannot be null" error
**Solution**: Ensure `getWebsiteIdFromMonitor()` method finds website by URL

**Issue**: Test suite slows down
**Solution**: 
- Ensure listeners are NOT executed in tests (use Event::fake())
- Check for real network calls
- Verify UsesCleanDatabase trait is used

**Issue**: CheckMonitorJob throws errors
**Solution**: 
- Wrap event firing in try-catch
- Ensure $checkResults array has all required keys
- Check monitor relationships exist

## 📚 Reference Materials

During implementation, refer to:
- `docs/HISTORICAL_DATA_MASTER_PLAN.md` - Complete event/listener code
- `docs/HISTORICAL_DATA_QUICK_START.md` - Quick commands
- `docs/TESTING_INSIGHTS.md` - Testing patterns
- `docs/DEVELOPMENT_PRIMER.md` - Development workflow
- Laravel Events Documentation: https://laravel.com/docs/12.x/events
- Laravel Queues Documentation: https://laravel.com/docs/12.x/queues
- Laravel Horizon Documentation: https://laravel.com/docs/12.x/horizon

## 🎯 Ready to Start?

Copy this entire prompt and use it to begin Phase 2 implementation. Follow each step carefully and verify at each checkpoint.

**Estimated Time**: 3-4 hours for complete Phase 2 implementation

**Next Phase**: After Phase 2 completion, Phase 3 will implement the dashboard integration and API endpoints for historical data visualization.

---

Good luck with Phase 2! 🚀
