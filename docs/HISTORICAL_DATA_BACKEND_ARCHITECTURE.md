# Historical Monitoring Data - Event-Driven Backend Architecture

**Document Version**: 1.0
**Created**: 2025-10-17
**Status**: Design Phase
**Target Implementation**: Phase 2 of MONITORING_DATA_TRACKING_PLAN.md

---

## Executive Summary

This document provides the Laravel backend implementation architecture for historical monitoring data tracking in SSL Monitor v4. The design leverages Laravel's event system to capture every monitoring check (SSL, uptime, manual, scheduled) without impacting existing monitoring performance.

**Key Design Principles**:
1. **Event-Driven**: Zero coupling to existing check logic
2. **Performance-First**: Queue-based async processing, no blocking operations
3. **Comprehensive**: Capture all check types and contexts
4. **Test-Optimized**: Maintain < 20s test suite with proper mocking
5. **Future-Proof**: Extensible for analytics and reporting

---

## Current System Analysis

### Existing Architecture

**Monitoring Flow**:
```
routes/console.php (Schedule)
    ↓ (every minute)
monitors:dispatch-scheduled-checks (Artisan Command)
    ↓
CheckMonitorJob (Queue)
    ├─→ checkUptime() → Spatie MonitorCollection
    └─→ checkSsl() → Monitor->checkCertificate()
         ↓
    AutomationLogger (Logs results)
    ↓
Monitor Model (Current state only)
```

**Manual Check Flow**:
```
User Action (Frontend)
    ↓
ImmediateWebsiteCheckJob (Queue)
    ↓
CheckMonitorJob->handle() (Direct call)
    ↓
Same flow as scheduled checks
```

### Key Components Identified

**Jobs** (Data capture points):
- `/app/Jobs/CheckMonitorJob.php` - Main check execution
- `/app/Jobs/ImmediateWebsiteCheckJob.php` - Manual check wrapper

**Models**:
- `/app/Models/Monitor.php` - Custom extended Spatie monitor
- `/app/Models/Website.php` - Website management
- `/app/Models/AlertConfiguration.php` - Alert rules

**Services**:
- `/app/Services/AlertService.php` - Alert evaluation
- `/app/Services/MonitorIntegrationService.php` - Monitor sync

**Observers**:
- `/app/Observers/WebsiteObserver.php` - Website lifecycle events

**Events** (Existing, empty templates):
- `/app/Events/WebsiteStatusChanged.php` - Empty template

### Current Limitations

1. **No Historical Storage**: Only current state in `monitors` table
2. **Lost Context**: Check trigger source (manual vs scheduled) not tracked
3. **No Trends**: Cannot analyze performance over time
4. **Limited Debugging**: No audit trail of check executions
5. **Alert Gaps**: Alert history not retained

---

## Proposed Event-Driven Architecture

### Architecture Diagram (Text-Based)

```
┌─────────────────────────────────────────────────────────────┐
│                    MONITORING TRIGGERS                       │
│  ┌────────────────┐              ┌────────────────┐         │
│  │   Scheduled    │              │  Manual Check  │         │
│  │  (Every Min)   │              │  (User Action) │         │
│  └────────┬───────┘              └────────┬───────┘         │
└───────────┼──────────────────────────────┼──────────────────┘
            │                              │
            ▼                              ▼
┌─────────────────────────────────────────────────────────────┐
│                    CHECK EXECUTION JOBS                      │
│  ┌────────────────────────────────────────────────────┐     │
│  │           CheckMonitorJob (Modified)               │     │
│  │  ┌──────────────────────────────────────────────┐  │     │
│  │  │  1. Fire: MonitoringCheckStarted             │  │     │
│  │  │  2. Perform: checkUptime() + checkSsl()      │  │     │
│  │  │  3. Fire: MonitoringCheckCompleted (success) │  │     │
│  │  │     OR: MonitoringCheckFailed (error)        │  │     │
│  │  └──────────────────────────────────────────────┘  │     │
│  └────────────────────────────────────────────────────┘     │
└───────────┬──────────────────────────────┬──────────────────┘
            │                              │
            ▼                              ▼
┌─────────────────────────┐  ┌─────────────────────────────┐
│   EVENT: CheckStarted   │  │  EVENT: CheckCompleted      │
│                         │  │         CheckFailed         │
│  • Monitor context      │  │  • Full check results       │
│  • Trigger type         │  │  • Response times           │
│  • User (if manual)     │  │  • SSL certificate data     │
│  • Start timestamp      │  │  • Error details            │
└────────┬────────────────┘  └──────────┬──────────────────┘
         │                              │
         └──────────────┬───────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│                    EVENT LISTENERS (Queued)                  │
│  ┌────────────────────────────────────────────────────┐     │
│  │  RecordMonitoringResult                            │     │
│  │  → Create monitoring_results record                │     │
│  │  → Store complete check data                       │     │
│  │  → Non-blocking queue processing                   │     │
│  └────────────────────────────────────────────────────┘     │
│  ┌────────────────────────────────────────────────────┐     │
│  │  UpdateMonitoringSummaries                         │     │
│  │  → Update hourly/daily aggregates                  │     │
│  │  → Calculate performance metrics                   │     │
│  │  → Deferred batch processing                       │     │
│  └────────────────────────────────────────────────────┘     │
│  ┌────────────────────────────────────────────────────┐     │
│  │  CheckAlertConditions (Existing AlertService)      │     │
│  │  → Evaluate alert thresholds                       │     │
│  │  → Record alert in monitoring_alerts               │     │
│  │  → Trigger notifications                           │     │
│  └────────────────────────────────────────────────────┘     │
└───────────┬──────────────────────────────┬──────────────────┘
            │                              │
            ▼                              ▼
┌─────────────────────┐        ┌─────────────────────────────┐
│  monitoring_results │        │ monitoring_check_summaries  │
│  (Every check)      │        │ (Aggregated data)           │
│                     │        │                             │
│  • Full results     │        │ • Hourly stats              │
│  • Timestamps       │        │ • Daily trends              │
│  • Response times   │        │ • Performance metrics       │
│  • SSL details      │        │                             │
│  • Trigger context  │        │                             │
└─────────────────────┘        └─────────────────────────────┘
```

### Data Flow Sequence

**Scheduled Check**:
```
1. Scheduler → DispatchScheduledChecks command
2. Command → CheckMonitorJob (queued)
3. CheckMonitorJob:
   a. event(MonitoringCheckStarted) [non-blocking]
   b. Perform uptime/SSL checks
   c. event(MonitoringCheckCompleted) [non-blocking]
4. RecordMonitoringResult listener (queued):
   a. Extract check results
   b. Insert into monitoring_results
5. UpdateMonitoringSummaries listener (queued):
   a. Update hourly aggregates
   b. Calculate trends
6. CheckAlertConditions listener (queued):
   a. Evaluate alert rules
   b. Send notifications if triggered
```

**Manual Check**:
```
1. User clicks "Check Now"
2. ImmediateWebsiteCheckJob (queued)
3. CheckMonitorJob->handle() [same flow as scheduled]
4. Additional context: triggered_by_user_id
5. Same event flow with manual trigger_type
```

---

## Laravel Components to Create

### Phase 1: Events (app/Events/)

#### 1. MonitoringCheckStarted
**Purpose**: Fired when any monitoring check begins
**File**: `/app/Events/MonitoringCheckStarted.php`

**Properties**:
```php
public Monitor $monitor;
public string $checkType;      // 'uptime', 'ssl_certificate', 'both'
public string $triggerType;    // 'scheduled', 'manual_immediate', 'manual_bulk'
public ?int $triggeredByUserId;
public Carbon $startedAt;
public array $monitorConfig;   // Snapshot of monitor settings
```

**Usage**:
```php
event(new MonitoringCheckStarted(
    monitor: $this->monitor,
    checkType: 'both',
    triggerType: 'scheduled',
    triggeredByUserId: null,
    startedAt: now(),
    monitorConfig: $this->monitor->toArray()
));
```

#### 2. MonitoringCheckCompleted
**Purpose**: Fired when check completes successfully
**File**: `/app/Events/MonitoringCheckCompleted.php`

**Properties**:
```php
public Monitor $monitor;
public string $checkType;
public string $triggerType;
public ?int $triggeredByUserId;
public Carbon $startedAt;
public Carbon $completedAt;
public array $uptimeResults;
public array $sslResults;
public int $durationMs;
```

**Computed Properties** (methods):
```php
public function getDaysUntilExpiration(): ?int
public function shouldTriggerAlert(): bool
public function getAlertLevel(): ?string
```

#### 3. MonitoringCheckFailed
**Purpose**: Fired when check encounters errors
**File**: `/app/Events/MonitoringCheckFailed.php`

**Properties**:
```php
public Monitor $monitor;
public string $checkType;
public string $triggerType;
public ?int $triggeredByUserId;
public Carbon $failedAt;
public Throwable $exception;
public array $partialResults;  // Any data captured before failure
public string $failureCategory; // 'network', 'dns', 'ssl', 'timeout'
```

#### 4. MonitoringBatchCompleted
**Purpose**: Fired after batch of checks completes
**File**: `/app/Events/MonitoringBatchCompleted.php`

**Properties**:
```php
public Carbon $batchStartedAt;
public Carbon $batchCompletedAt;
public int $totalChecks;
public int $successfulChecks;
public int $failedChecks;
public array $monitorIds;
public string $summaryPeriod; // 'hourly', 'daily'
```

---

### Phase 2: Event Listeners (app/Listeners/)

#### 1. RecordMonitoringResult
**Purpose**: Primary data capture listener
**File**: `/app/Listeners/RecordMonitoringResult.php`

**Queue**: `monitoring-history` (dedicated queue)
**Implements**: `ShouldQueue`

**Key Methods**:
```php
public function handle(MonitoringCheckCompleted $event): void
{
    // Create monitoring_results record
    MonitoringResult::create([
        'uuid' => Str::uuid(),
        'monitor_id' => $event->monitor->id,
        'website_id' => $this->getWebsiteId($event->monitor),
        'check_type' => $event->checkType,
        'trigger_type' => $event->triggerType,
        'triggered_by_user_id' => $event->triggeredByUserId,
        'started_at' => $event->startedAt,
        'completed_at' => $event->completedAt,
        'duration_ms' => $event->durationMs,
        'status' => 'success',
        // ... map uptime results
        // ... map SSL results
    ]);
}

private function getWebsiteId(Monitor $monitor): ?int
{
    // Resolve website_id from monitor URL
    return Website::where('url', $monitor->url)->value('id');
}

public function failed(MonitoringCheckCompleted $event, Throwable $exception): void
{
    // Log failure, don't break monitoring
    Log::error('Failed to record monitoring result', [...]);
}
```

#### 2. RecordMonitoringFailure
**Purpose**: Record failed check attempts
**File**: `/app/Listeners/RecordMonitoringFailure.php`

**Queue**: `monitoring-history`
**Listens To**: `MonitoringCheckFailed`

**Key Methods**:
```php
public function handle(MonitoringCheckFailed $event): void
{
    MonitoringResult::create([
        'status' => 'error',
        'error_message' => $event->exception->getMessage(),
        'check_data' => $event->partialResults,
        // ... other fields
    ]);
}
```

#### 3. UpdateMonitoringSummaries
**Purpose**: Calculate and update hourly/daily aggregates
**File**: `/app/Listeners/UpdateMonitoringSummaries.php`

**Queue**: `monitoring-aggregation` (low priority)
**Listens To**: `MonitoringBatchCompleted`

**Key Methods**:
```php
public function handle(MonitoringBatchCompleted $event): void
{
    foreach ($event->monitorIds as $monitorId) {
        $this->updateHourlySummary($monitorId);
        $this->updateDailySummary($monitorId);
    }
}

private function updateHourlySummary(int $monitorId): void
{
    $periodStart = now()->startOfHour();
    $periodEnd = now()->endOfHour();

    $summary = MonitoringCheckSummary::updateOrCreate(
        [
            'monitor_id' => $monitorId,
            'summary_period' => 'hourly',
            'period_start' => $periodStart,
        ],
        $this->calculateSummaryStats($monitorId, $periodStart, $periodEnd)
    );
}

private function calculateSummaryStats(int $monitorId, Carbon $start, Carbon $end): array
{
    return MonitoringResult::where('monitor_id', $monitorId)
        ->whereBetween('started_at', [$start, $end])
        ->selectRaw('
            COUNT(*) as total_checks,
            SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as successful_checks,
            AVG(response_time_ms) as average_response_time_ms,
            MIN(response_time_ms) as min_response_time_ms,
            MAX(response_time_ms) as max_response_time_ms
        ')
        ->first()
        ->toArray();
}
```

#### 4. CheckAlertConditions
**Purpose**: Evaluate alert rules and trigger notifications
**File**: `/app/Listeners/CheckAlertConditions.php`

**Queue**: `alerts` (high priority)
**Listens To**: `MonitoringCheckCompleted`

**Key Methods**:
```php
public function handle(MonitoringCheckCompleted $event): void
{
    // Leverage existing AlertService
    $website = $this->getWebsite($event->monitor);

    if (!$website) {
        return;
    }

    // Call existing alert service
    $triggeredAlerts = $this->alertService->checkAndTriggerAlerts($website);

    // Record alerts in monitoring_alerts table
    foreach ($triggeredAlerts as $alert) {
        $this->recordAlert($event, $alert);
    }
}

private function recordAlert(MonitoringCheckCompleted $event, array $alertData): void
{
    MonitoringAlert::create([
        'monitor_id' => $event->monitor->id,
        'website_id' => $this->getWebsiteId($event->monitor),
        'alert_type' => $alertData['type'],
        'alert_severity' => $alertData['level'],
        'first_detected_at' => now(),
        'notification_status' => 'sent',
        // ... other fields
    ]);
}
```

---

### Phase 3: Services (app/Services/)

#### MonitoringHistoryService
**Purpose**: Centralized service for historical data operations
**File**: `/app/Services/MonitoringHistoryService.php`

**Key Methods**:
```php
class MonitoringHistoryService
{
    /**
     * Get trend data for dashboard (7-day, 30-day, etc.)
     */
    public function getTrendData(Monitor $monitor, int $days = 7): array
    {
        return MonitoringResult::where('monitor_id', $monitor->id)
            ->whereBetween('started_at', [now()->subDays($days), now()])
            ->orderBy('started_at')
            ->get()
            ->groupBy(fn($result) => $result->started_at->format('Y-m-d'))
            ->map(function ($dayResults) {
                return [
                    'date' => $dayResults->first()->started_at->format('Y-m-d'),
                    'total_checks' => $dayResults->count(),
                    'successful_checks' => $dayResults->where('status', 'success')->count(),
                    'average_response_time' => $dayResults->avg('response_time_ms'),
                    'uptime_percentage' => $this->calculateUptimePercentage($dayResults),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Get recent check history
     */
    public function getRecentHistory(Monitor $monitor, int $limit = 50): Collection
    {
        return MonitoringResult::where('monitor_id', $monitor->id)
            ->latest('started_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get summary statistics for a period
     */
    public function getSummaryStats(Monitor $monitor, string $period = 'daily'): array
    {
        return MonitoringCheckSummary::where('monitor_id', $monitor->id)
            ->where('summary_period', $period)
            ->latest('period_start')
            ->first()
            ?->toArray() ?? [];
    }

    /**
     * Calculate uptime percentage
     */
    private function calculateUptimePercentage(Collection $results): float
    {
        $total = $results->count();
        $successful = $results->where('uptime_status', 'up')->count();

        return $total > 0 ? round(($successful / $total) * 100, 2) : 0;
    }

    /**
     * Cleanup old historical data
     */
    public function pruneOldData(int $daysToKeep = 90): int
    {
        return MonitoringResult::where('created_at', '<', now()->subDays($daysToKeep))
            ->delete();
    }
}
```

---

## Code Integration Points

### Modify: CheckMonitorJob

**File**: `/app/Jobs/CheckMonitorJob.php`
**Lines**: 44-99 (handle method)

**Changes**:
```php
public function handle(): array
{
    $startTime = microtime(true);
    $startedAt = now();

    // NEW: Fire check started event
    event(new MonitoringCheckStarted(
        monitor: $this->monitor,
        checkType: 'both',
        triggerType: 'scheduled',
        triggeredByUserId: null,
        startedAt: $startedAt,
        monitorConfig: $this->monitor->toArray()
    ));

    try {
        AutomationLogger::jobStart(self::class, [
            'monitor_id' => $this->monitor->id,
            'monitor_url' => $this->monitor->url,
        ]);

        // Existing check logic
        $results = [
            'monitor_id' => $this->monitor->id,
            'url' => $this->monitor->url,
            'checked_at' => Carbon::now()->toISOString(),
            'uptime' => $this->checkUptime(),
            'ssl' => $this->shouldCheckSsl() ? $this->checkSsl() : $this->getLastSslResult(),
        ];

        // NEW: Fire check completed event
        event(new MonitoringCheckCompleted(
            monitor: $this->monitor,
            checkType: 'both',
            triggerType: 'scheduled',
            triggeredByUserId: null,
            startedAt: $startedAt,
            completedAt: now(),
            uptimeResults: $results['uptime'],
            sslResults: $results['ssl'],
            durationMs: (int) round((microtime(true) - $startTime) * 1000)
        ));

        AutomationLogger::jobComplete(self::class, $startTime, [
            'monitor_id' => $this->monitor->id,
            'results' => $results,
        ]);

        return $results;

    } catch (\Throwable $exception) {
        // NEW: Fire check failed event
        event(new MonitoringCheckFailed(
            monitor: $this->monitor,
            checkType: 'both',
            triggerType: 'scheduled',
            triggeredByUserId: null,
            failedAt: now(),
            exception: $exception,
            partialResults: $results ?? [],
            failureCategory: $this->categorizeFailure($exception)
        ));

        AutomationLogger::jobFailed(self::class, $exception, [
            'monitor_id' => $this->monitor->id,
            'monitor_url' => $this->monitor->url,
        ]);

        // Return error results
        return [
            'monitor_id' => $this->monitor->id,
            'url' => $this->monitor->url,
            'checked_at' => Carbon::now()->toISOString(),
            'uptime' => ['status' => 'error', 'error' => $exception->getMessage()],
            'ssl' => ['status' => 'error', 'error' => $exception->getMessage()],
            'error' => true,
        ];
    }
}

/**
 * NEW: Categorize failure for better error handling
 */
private function categorizeFailure(\Throwable $exception): string
{
    $message = strtolower($exception->getMessage());

    if (str_contains($message, 'dns') || str_contains($message, 'resolve')) {
        return 'dns';
    }
    if (str_contains($message, 'ssl') || str_contains($message, 'certificate')) {
        return 'ssl';
    }
    if (str_contains($message, 'timeout')) {
        return 'timeout';
    }

    return 'network';
}
```

### Modify: ImmediateWebsiteCheckJob

**File**: `/app/Jobs/ImmediateWebsiteCheckJob.php`
**Lines**: 49-98 (handle method)

**Changes**:
```php
public function handle(MonitorIntegrationService $monitorService): array
{
    $startTime = microtime(true);

    try {
        AutomationLogger::jobStart(self::class, [
            'website_id' => $this->website->id,
            'website_url' => $this->website->url,
        ]);

        // Get or create Monitor
        $monitor = $monitorService->getMonitorForWebsite($this->website);

        if (!$monitor) {
            $monitor = $monitorService->createOrUpdateMonitorForWebsite($this->website);
        }

        // NEW: Create check job with manual trigger context
        $checkJob = new CheckMonitorJob($monitor);

        // NEW: Override trigger type for manual checks
        // Note: This requires adding a property to CheckMonitorJob
        $checkJob->triggerType = 'manual_immediate';
        $checkJob->triggeredByUserId = $this->website->user_id;

        // Delegate to CheckMonitorJob
        $results = $checkJob->handle();

        // Add website context
        $results['website_id'] = $this->website->id;

        // Update website timestamp
        $this->website->updated_at = Carbon::now()->format('Y-m-d H:i:s.u');
        $this->website->save();

        AutomationLogger::jobComplete(self::class, $startTime, [
            'website_id' => $this->website->id,
            'monitor_id' => $monitor->id,
            'results' => $results,
        ]);

        return $results;

    } catch (\Throwable $exception) {
        AutomationLogger::jobFailed(self::class, $exception, [
            'website_id' => $this->website->id,
            'website_url' => $this->website->url,
        ]);

        return [
            'website_id' => $this->website->id,
            'url' => $this->website->url,
            'checked_at' => Carbon::now()->toISOString(),
            'uptime' => ['status' => 'error', 'error' => $exception->getMessage()],
            'ssl' => ['status' => 'error', 'error' => $exception->getMessage()],
            'error' => true,
        ];
    }
}
```

**Additional Change**: Add properties to CheckMonitorJob:
```php
class CheckMonitorJob implements ShouldQueue
{
    // Existing properties
    public Monitor $monitor;

    // NEW: Manual check context
    public string $triggerType = 'scheduled';
    public ?int $triggeredByUserId = null;

    // ... rest of class
}
```

### Register Event Listeners

**File**: `/app/Providers/AppServiceProvider.php` (or create EventServiceProvider)

**Add to boot() method**:
```php
use Illuminate\Support\Facades\Event;
use App\Events\MonitoringCheckStarted;
use App\Events\MonitoringCheckCompleted;
use App\Events\MonitoringCheckFailed;
use App\Events\MonitoringBatchCompleted;
use App\Listeners\RecordMonitoringResult;
use App\Listeners\RecordMonitoringFailure;
use App\Listeners\UpdateMonitoringSummaries;
use App\Listeners\CheckAlertConditions;

public function boot(): void
{
    // Monitoring check result recording
    Event::listen(
        MonitoringCheckCompleted::class,
        [RecordMonitoringResult::class, 'handle']
    );

    // Monitoring failure recording
    Event::listen(
        MonitoringCheckFailed::class,
        [RecordMonitoringFailure::class, 'handle']
    );

    // Summary updates (low priority, batched)
    Event::listen(
        MonitoringBatchCompleted::class,
        [UpdateMonitoringSummaries::class, 'handle']
    );

    // Alert condition checking
    Event::listen(
        MonitoringCheckCompleted::class,
        [CheckAlertConditions::class, 'handle']
    );
}
```

---

## Queue Strategy

### Queue Configuration

**File**: `config/queue.php`

```php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
    ],

    // NEW: Dedicated monitoring history queue
    'monitoring-history' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'monitoring-history',
        'retry_after' => 120,
        'block_for' => null,
    ],

    // NEW: Low priority aggregation queue
    'monitoring-aggregation' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'monitoring-aggregation',
        'retry_after' => 300,
        'block_for' => null,
    ],
],
```

### Horizon Configuration

**File**: `config/horizon.php`

**Add to 'production' and 'local' environments**:
```php
'environments' => [
    'production' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['default', 'monitoring-history'],
            'balance' => 'auto',
            'processes' => 10,
            'tries' => 3,
        ],
        'supervisor-2' => [
            'connection' => 'redis',
            'queue' => ['monitoring-aggregation'],
            'balance' => 'auto',
            'processes' => 2,
            'tries' => 2,
        ],
        'supervisor-3' => [
            'connection' => 'redis',
            'queue' => ['alerts'],
            'balance' => 'auto',
            'processes' => 5,
            'tries' => 3,
        ],
    ],
],
```

### Queue Priority Strategy

1. **High Priority** (`alerts`): Alert notifications - immediate processing
2. **Normal Priority** (`default`, `monitoring-history`): Check execution and result recording
3. **Low Priority** (`monitoring-aggregation`): Summary calculations - can be delayed

---

## Testing Strategy

### Maintain < 20s Test Suite

**Key Principle**: Use existing mock traits, extend as needed

#### Test Structure

**File**: `tests/Feature/MonitoringHistoryTest.php`

```php
<?php

use Tests\Traits\MocksSslCertificateAnalysis;
use Tests\Traits\MocksMonitorHttpRequests;
use Tests\Traits\UsesCleanDatabase;
use App\Jobs\CheckMonitorJob;
use App\Events\MonitoringCheckCompleted;
use App\Models\MonitoringResult;

uses(UsesCleanDatabase::class, MocksSslCertificateAnalysis::class, MocksMonitorHttpRequests::class);

it('records monitoring result when check completes', function () {
    // Arrange
    Event::fake([MonitoringCheckCompleted::class]);
    $monitor = Monitor::factory()->create();

    // Act
    $job = new CheckMonitorJob($monitor);
    $job->handle();

    // Assert
    Event::assertDispatched(MonitoringCheckCompleted::class);
});

it('creates monitoring_results record from event', function () {
    // Arrange
    $monitor = Monitor::factory()->create();
    $event = new MonitoringCheckCompleted(
        monitor: $monitor,
        checkType: 'both',
        triggerType: 'scheduled',
        triggeredByUserId: null,
        startedAt: now()->subSeconds(5),
        completedAt: now(),
        uptimeResults: ['status' => 'up', 'response_time' => 150],
        sslResults: ['status' => 'valid', 'expires_at' => now()->addDays(30)],
        durationMs: 150
    );

    // Act
    $listener = new RecordMonitoringResult();
    $listener->handle($event);

    // Assert
    expect(MonitoringResult::count())->toBe(1);
    $result = MonitoringResult::first();
    expect($result->monitor_id)->toBe($monitor->id);
    expect($result->status)->toBe('success');
    expect($result->duration_ms)->toBe(150);
});

it('distinguishes manual from scheduled checks', function () {
    // Arrange
    $monitor = Monitor::factory()->create();
    $user = User::factory()->create();

    $event = new MonitoringCheckCompleted(
        monitor: $monitor,
        checkType: 'both',
        triggerType: 'manual_immediate',
        triggeredByUserId: $user->id,
        startedAt: now()->subSeconds(2),
        completedAt: now(),
        uptimeResults: ['status' => 'up'],
        sslResults: ['status' => 'valid'],
        durationMs: 100
    );

    // Act
    $listener = new RecordMonitoringResult();
    $listener->handle($event);

    // Assert
    $result = MonitoringResult::first();
    expect($result->trigger_type)->toBe('manual_immediate');
    expect($result->triggered_by_user_id)->toBe($user->id);
});
```

#### Mock Traits Usage

**Use existing traits**:
```php
use Tests\Traits\MocksSslCertificateAnalysis;
use Tests\Traits\MocksMonitorHttpRequests;

uses(MocksSslCertificateAnalysis::class, MocksMonitorHttpRequests::class);
```

**Expected test performance**:
- Individual tests: < 1 second
- Full monitoring history suite: < 5 seconds
- Total test suite: Maintain < 20 seconds

---

## Performance Safeguards

### 1. Non-Blocking Event Dispatch

**All events are fired asynchronously**:
```php
// Events don't wait for listeners to complete
event(new MonitoringCheckCompleted(...));
// Check job returns immediately
```

### 2. Queue-Based Listener Processing

**All listeners implement ShouldQueue**:
```php
class RecordMonitoringResult implements ShouldQueue
{
    public $queue = 'monitoring-history';
    public $tries = 3;
    public $timeout = 30;
}
```

### 3. Graceful Failure Handling

**Listener failures don't break monitoring**:
```php
public function failed(MonitoringCheckCompleted $event, Throwable $exception): void
{
    Log::error('Failed to record monitoring result', [
        'monitor_id' => $event->monitor->id,
        'exception' => $exception->getMessage(),
    ]);

    // Don't throw - let monitoring continue
}
```

### 4. Database Optimization

**Indexes on monitoring_results**:
```sql
INDEX idx_monitor_started (monitor_id, started_at)
INDEX idx_website_started (website_id, started_at)
INDEX idx_check_type_status (check_type, status)
INDEX idx_trigger_type (trigger_type)
```

**Efficient queries**:
```php
// Use indexes effectively
MonitoringResult::where('monitor_id', $monitorId)
    ->whereBetween('started_at', [$start, $end])
    ->select(['status', 'response_time_ms', 'duration_ms'])
    ->get();
```

### 5. Memory Management

**Chunk large operations**:
```php
MonitoringResult::where('created_at', '<', now()->subDays(90))
    ->chunk(500, function ($results) {
        $results->each->delete();
    });
```

---

## Implementation Phases

### Phase 1: Foundation (Week 1) - ✅ COMPLETE

Based on MONITORING_DATA_TRACKING_PLAN.md status:
- ✅ Database migrations created
- ✅ Models created (MonitoringResult, MonitoringCheckSummary, MonitoringAlert)
- ✅ Events created (4 events)

### Phase 2: Data Capture Integration (Week 2) - CURRENT

**Tasks**:
1. ✅ Create Event Listeners (4 listeners)
2. ⏳ Modify CheckMonitorJob to fire events
3. ⏳ Modify ImmediateWebsiteCheckJob for manual checks
4. ⏳ Register event listeners in AppServiceProvider
5. ⏳ Configure queues in Horizon
6. ⏳ Write integration tests

**Acceptance Criteria**:
- All checks fire events correctly
- Monitoring results recorded in database
- Manual vs scheduled checks distinguished
- No performance impact on monitoring (< 5% overhead)
- Tests pass with < 20s total runtime

### Phase 3: Service Layer & Dashboard (Week 3)

**Tasks**:
1. Create MonitoringHistoryService
2. Add trend calculation methods
3. Create API endpoints for historical data
4. Integrate with existing dashboard
5. Add performance optimization

**Acceptance Criteria**:
- Dashboard shows 7-day trends
- Historical data accessible via API
- Queries complete in < 2 seconds
- Service methods fully tested

### Phase 4: Advanced Features (Week 4-5)

**Tasks**:
1. Implement summary aggregations
2. Add alert history tracking
3. Create data retention policies
4. Build reporting capabilities
5. Performance tuning and optimization

**Acceptance Criteria**:
- Hourly/daily summaries calculated
- Alert history tracked
- Old data pruned automatically
- System scales with data growth

---

## Success Metrics

### Performance Metrics

1. **Check Execution**: < 5% overhead from event firing
2. **Event Processing**: < 100ms average listener execution
3. **Queue Depth**: < 100 jobs in monitoring-history queue
4. **Database Writes**: < 10ms for monitoring_results insert

### Data Quality Metrics

1. **Capture Rate**: 100% of checks recorded
2. **Data Integrity**: 0 failed listener jobs (with retries)
3. **Timestamp Accuracy**: High-precision timestamps (milliseconds)
4. **Alert Correlation**: 100% of alerts linked to check results

### System Health Metrics

1. **Test Suite**: Maintain < 20s total runtime
2. **Individual Tests**: < 1s per test
3. **Queue Processing**: < 5s average job age
4. **Memory Usage**: < 10% increase from baseline

---

## Risk Mitigation

### Risk 1: Performance Impact on Monitoring

**Mitigation**:
- Events are non-blocking (fire and forget)
- Listeners are queued, not synchronous
- Monitoring jobs return immediately after firing events
- Dedicated queue prevents blocking

**Testing**:
```bash
# Benchmark before/after event integration
time ./vendor/bin/sail artisan test --filter=CheckMonitorJobTest
```

### Risk 2: Queue Backlog

**Mitigation**:
- Dedicated Horizon supervisor for monitoring-history
- Auto-scaling queue workers based on load
- Alert on queue depth > 500 jobs
- Fallback to direct database writes if queue fails

**Monitoring**:
```php
// Add to routes/console.php
Schedule::call(function () {
    $queueSize = Redis::llen('queues:monitoring-history');
    if ($queueSize > 500) {
        Log::warning('Monitoring history queue backlog', ['size' => $queueSize]);
    }
})->everyFiveMinutes();
```

### Risk 3: Database Growth

**Mitigation**:
- Implement data retention policy (90 days default)
- Automated pruning job (daily)
- Database partitioning for large datasets
- Summary tables for long-term trends

**Cleanup Job**:
```php
// Add to routes/console.php
Schedule::call(function () {
    $deleted = app(MonitoringHistoryService::class)->pruneOldData(90);
    Log::info('Pruned old monitoring data', ['records_deleted' => $deleted]);
})->dailyAt('03:00');
```

---

## Next Steps

### Immediate Actions (Week 2)

1. **Create Event Listeners** (4 files)
   - RecordMonitoringResult.php
   - RecordMonitoringFailure.php
   - UpdateMonitoringSummaries.php
   - CheckAlertConditions.php

2. **Modify Jobs** (2 files)
   - CheckMonitorJob.php - Add event firing
   - ImmediateWebsiteCheckJob.php - Add manual check context

3. **Register Listeners** (1 file)
   - AppServiceProvider.php - Wire up event listeners

4. **Configure Queues** (2 files)
   - config/queue.php - Add monitoring queues
   - config/horizon.php - Configure supervisors

5. **Write Tests** (1 file)
   - tests/Feature/MonitoringHistoryTest.php

### Validation

**After implementation, verify**:
```bash
# 1. Run tests
./vendor/bin/sail artisan test --parallel

# 2. Check event firing
./vendor/bin/sail artisan tinker
>>> event(new App\Events\MonitoringCheckCompleted(...));

# 3. Verify queue processing
./vendor/bin/sail artisan horizon:list

# 4. Check database records
./vendor/bin/sail artisan tinker
>>> App\Models\MonitoringResult::count()
```

---

## Conclusion

This event-driven architecture provides:

1. **Zero Coupling**: Events don't modify existing check logic
2. **Performance**: Async queue-based processing, < 5% overhead
3. **Reliability**: Graceful failure handling, retries, logging
4. **Scalability**: Dedicated queues, efficient database design
5. **Testability**: Mock-friendly, maintains < 20s test suite
6. **Extensibility**: Easy to add new listeners for additional features

The design leverages Laravel's event system to capture comprehensive monitoring data without impacting the existing, production-ready monitoring infrastructure.

---

**Document Status**: Ready for Implementation
**Next Review**: After Phase 2 completion
**Estimated Implementation**: 2 weeks (Phase 2-3)
