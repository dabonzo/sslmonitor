# Historical Monitoring Data - Implementation Summary

**Quick Reference Guide for Event-Driven Data Tracking**

---

## Event-Driven Architecture Overview

```
┌──────────────────────────────────────────────────────────────┐
│                    MONITORING CHECK FLOW                      │
└──────────────────────────────────────────────────────────────┘

Scheduler/User Action
        ↓
CheckMonitorJob / ImmediateWebsiteCheckJob
        ↓
    ┌───────────────────────────────────┐
    │  event(MonitoringCheckStarted)    │ ← Non-blocking
    └───────────────────────────────────┘
        ↓
    Perform Check (SSL + Uptime)
        ↓
    ┌───────────────────────────────────┐
    │  event(MonitoringCheckCompleted)  │ ← Non-blocking
    │        OR MonitoringCheckFailed   │
    └───────────────────────────────────┘
        ↓
    Job Returns (No Waiting)


        ┌────────────────────────────────┐
        │  QUEUED EVENT LISTENERS        │
        │  (Process Asynchronously)      │
        └────────────────────────────────┘
                ↓
    ┌────────────────────────────────────┐
    │  RecordMonitoringResult           │ → monitoring_results
    │  RecordMonitoringFailure          │ → monitoring_results
    │  UpdateMonitoringSummaries        │ → monitoring_check_summaries
    │  CheckAlertConditions             │ → monitoring_alerts
    └────────────────────────────────────┘
```

---

## Components to Create

### 1. Events (app/Events/)

| Event | Purpose | Fired When |
|-------|---------|------------|
| `MonitoringCheckStarted` | Track check initiation | Check begins |
| `MonitoringCheckCompleted` | Capture successful results | Check succeeds |
| `MonitoringCheckFailed` | Record failures | Check errors |
| `MonitoringBatchCompleted` | Trigger aggregations | Batch completes |

### 2. Listeners (app/Listeners/)

| Listener | Queue | Purpose |
|----------|-------|---------|
| `RecordMonitoringResult` | monitoring-history | Save check results |
| `RecordMonitoringFailure` | monitoring-history | Save failures |
| `UpdateMonitoringSummaries` | monitoring-aggregation | Calculate stats |
| `CheckAlertConditions` | alerts | Evaluate alert rules |

### 3. Service (app/Services/)

| Service | Methods | Purpose |
|---------|---------|---------|
| `MonitoringHistoryService` | getTrendData()<br>getRecentHistory()<br>getSummaryStats()<br>pruneOldData() | Historical data operations |

---

## Code Modifications Required

### CheckMonitorJob.php

**Add event firing at 3 points:**

```php
public function handle(): array
{
    $startedAt = now();

    // 1. Fire start event
    event(new MonitoringCheckStarted(
        monitor: $this->monitor,
        checkType: 'both',
        triggerType: $this->triggerType,
        triggeredByUserId: $this->triggeredByUserId,
        startedAt: $startedAt,
        monitorConfig: $this->monitor->toArray()
    ));

    try {
        // Existing check logic
        $results = [
            'uptime' => $this->checkUptime(),
            'ssl' => $this->shouldCheckSsl() ? $this->checkSsl() : $this->getLastSslResult(),
        ];

        // 2. Fire success event
        event(new MonitoringCheckCompleted(
            monitor: $this->monitor,
            checkType: 'both',
            triggerType: $this->triggerType,
            triggeredByUserId: $this->triggeredByUserId,
            startedAt: $startedAt,
            completedAt: now(),
            uptimeResults: $results['uptime'],
            sslResults: $results['ssl'],
            durationMs: $this->getDurationMs($startedAt)
        ));

        return $results;

    } catch (\Throwable $exception) {
        // 3. Fire failure event
        event(new MonitoringCheckFailed(
            monitor: $this->monitor,
            checkType: 'both',
            triggerType: $this->triggerType,
            triggeredByUserId: $this->triggeredByUserId,
            failedAt: now(),
            exception: $exception,
            partialResults: $results ?? [],
            failureCategory: $this->categorizeFailure($exception)
        ));

        throw $exception;
    }
}
```

**Add properties:**
```php
public string $triggerType = 'scheduled';
public ?int $triggeredByUserId = null;
```

### ImmediateWebsiteCheckJob.php

**Set manual check context:**

```php
public function handle(MonitorIntegrationService $monitorService): array
{
    $monitor = $monitorService->getMonitorForWebsite($this->website);

    $checkJob = new CheckMonitorJob($monitor);

    // NEW: Set manual check context
    $checkJob->triggerType = 'manual_immediate';
    $checkJob->triggeredByUserId = $this->website->user_id;

    return $checkJob->handle();
}
```

### AppServiceProvider.php

**Register event listeners:**

```php
use Illuminate\Support\Facades\Event;

public function boot(): void
{
    Event::listen(
        MonitoringCheckCompleted::class,
        [RecordMonitoringResult::class, 'handle']
    );

    Event::listen(
        MonitoringCheckFailed::class,
        [RecordMonitoringFailure::class, 'handle']
    );

    Event::listen(
        MonitoringBatchCompleted::class,
        [UpdateMonitoringSummaries::class, 'handle']
    );

    Event::listen(
        MonitoringCheckCompleted::class,
        [CheckAlertConditions::class, 'handle']
    );
}
```

---

## Queue Configuration

### config/queue.php

```php
'connections' => [
    'monitoring-history' => [
        'driver' => 'redis',
        'queue' => 'monitoring-history',
        'retry_after' => 120,
    ],
    'monitoring-aggregation' => [
        'driver' => 'redis',
        'queue' => 'monitoring-aggregation',
        'retry_after' => 300,
    ],
],
```

### config/horizon.php

```php
'environments' => [
    'production' => [
        'supervisor-monitoring' => [
            'connection' => 'redis',
            'queue' => ['default', 'monitoring-history'],
            'processes' => 10,
            'tries' => 3,
        ],
        'supervisor-aggregation' => [
            'connection' => 'redis',
            'queue' => ['monitoring-aggregation'],
            'processes' => 2,
            'tries' => 2,
        ],
        'supervisor-alerts' => [
            'connection' => 'redis',
            'queue' => ['alerts'],
            'processes' => 5,
            'tries' => 3,
        ],
    ],
],
```

---

## Testing Strategy

### Test Structure

```php
use Tests\Traits\MocksSslCertificateAnalysis;
use Tests\Traits\MocksMonitorHttpRequests;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class, MocksSslCertificateAnalysis::class);

it('fires monitoring check completed event', function () {
    Event::fake([MonitoringCheckCompleted::class]);

    $monitor = Monitor::factory()->create();
    $job = new CheckMonitorJob($monitor);
    $job->handle();

    Event::assertDispatched(MonitoringCheckCompleted::class);
});

it('records monitoring result in database', function () {
    $monitor = Monitor::factory()->create();
    $event = new MonitoringCheckCompleted(/* ... */);

    $listener = new RecordMonitoringResult();
    $listener->handle($event);

    expect(MonitoringResult::count())->toBe(1);
    expect(MonitoringResult::first()->monitor_id)->toBe($monitor->id);
});

it('distinguishes manual from scheduled checks', function () {
    $event = new MonitoringCheckCompleted(
        triggerType: 'manual_immediate',
        triggeredByUserId: $user->id,
        /* ... */
    );

    $listener = new RecordMonitoringResult();
    $listener->handle($event);

    $result = MonitoringResult::first();
    expect($result->trigger_type)->toBe('manual_immediate');
    expect($result->triggered_by_user_id)->toBe($user->id);
});
```

### Performance Requirements

- **Individual tests**: < 1 second
- **Full monitoring history suite**: < 5 seconds
- **Total test suite**: Maintain < 20 seconds
- **Use mocks**: Always use `MocksSslCertificateAnalysis` and `MocksMonitorHttpRequests`

---

## Performance Safeguards

### 1. Non-Blocking Events

Events don't wait for listeners:
```php
event(new MonitoringCheckCompleted(...)); // Returns immediately
```

### 2. Queued Listeners

All listeners process asynchronously:
```php
class RecordMonitoringResult implements ShouldQueue
{
    public $queue = 'monitoring-history';
    public $tries = 3;
}
```

### 3. Graceful Failures

Listener failures don't break monitoring:
```php
public function failed($event, $exception): void
{
    Log::error('Failed to record result', [/* ... */]);
    // Don't throw - monitoring continues
}
```

### 4. Database Optimization

Efficient indexes:
```sql
INDEX idx_monitor_started (monitor_id, started_at)
INDEX idx_check_type_status (check_type, status)
INDEX idx_trigger_type (trigger_type)
```

### 5. Data Retention

Automated cleanup:
```php
Schedule::call(function () {
    app(MonitoringHistoryService::class)->pruneOldData(90);
})->dailyAt('03:00');
```

---

## Implementation Checklist

### Phase 2: Data Capture Integration (Week 2)

**Events (4 files)**:
- [ ] Create `app/Events/MonitoringCheckStarted.php`
- [ ] Create `app/Events/MonitoringCheckCompleted.php`
- [ ] Create `app/Events/MonitoringCheckFailed.php`
- [ ] Create `app/Events/MonitoringBatchCompleted.php`

**Listeners (4 files)**:
- [ ] Create `app/Listeners/RecordMonitoringResult.php`
- [ ] Create `app/Listeners/RecordMonitoringFailure.php`
- [ ] Create `app/Listeners/UpdateMonitoringSummaries.php`
- [ ] Create `app/Listeners/CheckAlertConditions.php`

**Job Modifications (2 files)**:
- [ ] Modify `app/Jobs/CheckMonitorJob.php` - Add event firing
- [ ] Modify `app/Jobs/ImmediateWebsiteCheckJob.php` - Add manual context

**Configuration (3 files)**:
- [ ] Modify `app/Providers/AppServiceProvider.php` - Register listeners
- [ ] Modify `config/queue.php` - Add monitoring queues
- [ ] Modify `config/horizon.php` - Configure supervisors

**Service Layer (1 file)**:
- [ ] Create `app/Services/MonitoringHistoryService.php`

**Tests (1 file)**:
- [ ] Create `tests/Feature/MonitoringHistoryTest.php`

### Validation Steps

```bash
# 1. Run tests
./vendor/bin/sail artisan test --parallel

# 2. Verify event firing
./vendor/bin/sail artisan tinker
>>> $monitor = App\Models\Monitor::first();
>>> event(new App\Events\MonitoringCheckCompleted(...));

# 3. Check queue processing
./vendor/bin/sail artisan horizon:list

# 4. Verify database records
./vendor/bin/sail artisan tinker
>>> App\Models\MonitoringResult::count()
>>> App\Models\MonitoringResult::latest()->first()

# 5. Test manual check
# (Click "Check Now" in UI)
>>> App\Models\MonitoringResult::where('trigger_type', 'manual_immediate')->count()
```

---

## Key Metrics

### Performance Targets

| Metric | Target | Measurement |
|--------|--------|-------------|
| Check overhead | < 5% | Benchmark before/after |
| Event firing | < 1ms | Event dispatch time |
| Listener execution | < 100ms | Average queue job time |
| Database insert | < 10ms | monitoring_results insert |
| Queue depth | < 100 jobs | Horizon monitoring |

### Data Quality Targets

| Metric | Target | Measurement |
|--------|--------|-------------|
| Capture rate | 100% | All checks recorded |
| Failed jobs | < 1% | With retry attempts |
| Data integrity | 100% | Proper relationships |
| Alert correlation | 100% | Linked to check results |

---

## Common Issues & Solutions

### Issue: Queue Backlog

**Symptom**: monitoring-history queue has > 500 jobs

**Solution**:
```bash
# Increase Horizon workers
# Edit config/horizon.php
'processes' => 20, // Increase from 10

# Restart Horizon
./vendor/bin/sail artisan horizon:terminate
```

### Issue: Events Not Firing

**Symptom**: No MonitoringResult records created

**Check**:
```bash
# 1. Verify event listeners registered
./vendor/bin/sail artisan event:list

# 2. Check Horizon is running
./vendor/bin/sail artisan horizon:status

# 3. Check queue jobs
./vendor/bin/sail artisan queue:work --once --queue=monitoring-history
```

### Issue: Test Performance Degradation

**Symptom**: Tests take > 20 seconds

**Solution**:
```php
// Use Event::fake() in tests
Event::fake([MonitoringCheckCompleted::class]);

// Use existing mock traits
uses(MocksSslCertificateAnalysis::class, MocksMonitorHttpRequests::class);

// Don't make real network calls
```

---

## Next Steps

1. **Review Architecture Document**: Read `/docs/HISTORICAL_DATA_BACKEND_ARCHITECTURE.md`
2. **Create Events**: Start with MonitoringCheckCompleted event
3. **Create Listeners**: Implement RecordMonitoringResult listener
4. **Modify Jobs**: Add event firing to CheckMonitorJob
5. **Write Tests**: Test event firing and data recording
6. **Deploy & Monitor**: Watch Horizon and database growth

---

**For detailed implementation guidance, see**:
- `/docs/HISTORICAL_DATA_BACKEND_ARCHITECTURE.md` - Complete architecture
- `/docs/MONITORING_DATA_TRACKING_PLAN.md` - Phase 1 status and database schema

**Status**: Ready for Phase 2 Implementation
**Estimated Time**: 1-2 weeks
