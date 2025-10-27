# Historical Monitoring Data - Quick Start Guide

**Quick reference for implementing historical data tracking in SSL Monitor v4**

📚 **Full Documentation**: See `HISTORICAL_DATA_MASTER_PLAN.md` (2,800+ lines)

**🚧 Implementation Status**: 94% Complete
- ✅ Phase 1: 100% (Database tables and models)
- ✅ Phase 2: 100% (Event system)
- ✅ Phase 3: 100% (Dashboard integration)
- ✅ Phase 4: 100% COMPLETE (All listeners, jobs, and tests implemented)
- ⚠️ Phase 5: 40% (Basic caching/queue config done)

**📋 Completion Guides**:
- **Phase 4**: ✅ COMPLETE - See `PHASE4_COMPLETION_PROMPT.md` for implementation details
- **Phase 5**: See `PHASE5_IMPLEMENTATION_PROMPT.md` (2-3 hours remaining)

---

## 🎯 What This Adds

**Current**: Only current monitor state stored (previous check data lost)
**After Implementation**: Complete history of all checks with trends and analytics

---

## 📊 Quick Facts

- **4 new database tables** (monitoring_results, monitoring_check_summaries, monitoring_alerts, monitoring_events)
- **Event-driven architecture** (fire-and-forget, no performance impact)
- **6-week timeline** (5 phases)
- **Storage**: ~23 MB/day for 50 websites (90-day retention = ~2.6 GB)
- **Dashboard improvement**: 15-30s → < 2s load time

---

## 🚀 Phase 1 Quick Start (Week 1)

### 1. Create Migrations

```bash
./vendor/bin/sail artisan make:migration create_monitoring_results_table
./vendor/bin/sail artisan make:migration create_monitoring_check_summaries_table
./vendor/bin/sail artisan make:migration create_monitoring_alerts_table
./vendor/bin/sail artisan make:migration create_monitoring_events_table
```

### 2. Copy Schema from Master Plan

Open `HISTORICAL_DATA_MASTER_PLAN.md` and copy the CREATE TABLE statements into your migrations.

**Critical**: Use the EXACT schema provided (includes optimized indexes).

### 3. Create Models

```bash
./vendor/bin/sail artisan make:model MonitoringResult
./vendor/bin/sail artisan make:model MonitoringCheckSummary
./vendor/bin/sail artisan make:model MonitoringAlert
./vendor/bin/sail artisan make:model MonitoringEvent
```

Copy model code from master plan (includes relationships and casts).

### 4. Run Migrations

```bash
./vendor/bin/sail artisan migrate
```

### 5. Validate

```bash
# Check tables exist
./vendor/bin/sail artisan tinker
>>> Schema::hasTable('monitoring_results')  // Should be true

# Run tests
./vendor/bin/sail artisan test
```

---

## ⚡ Phase 2 Quick Start (Week 2)

### 1. Create Events

```bash
./vendor/bin/sail artisan make:event MonitoringCheckStarted
./vendor/bin/sail artisan make:event MonitoringCheckCompleted
./vendor/bin/sail artisan make:event MonitoringCheckFailed
./vendor/bin/sail artisan make:event MonitoringBatchCompleted
```

### 2. Create Listeners

```bash
./vendor/bin/sail artisan make:listener RecordMonitoringResult --event=MonitoringCheckCompleted
./vendor/bin/sail artisan make:listener RecordMonitoringFailure --event=MonitoringCheckFailed
./vendor/bin/sail artisan make:listener UpdateMonitoringSummaries --event=MonitoringCheckCompleted
./vendor/bin/sail artisan make:listener CheckAlertConditions --event=MonitoringCheckCompleted
```

**IMPORTANT**: Add `implements ShouldQueue` to all listeners.

### 3. Modify CheckMonitorJob

```php
// In app/Jobs/CheckMonitorJob.php

use App\Events\MonitoringCheckStarted;
use App\Events\MonitoringCheckCompleted;
use App\Events\MonitoringCheckFailed;

public string $triggerType = 'scheduled';
public ?int $triggeredByUserId = null;

public function handle(): void
{
    $startedAt = now();

    event(new MonitoringCheckStarted(
        $this->monitor,
        $this->triggerType,
        $this->triggeredByUserId
    ));

    try {
        // Existing check logic...
        $results = $this->performChecks();

        event(new MonitoringCheckCompleted(
            $this->monitor,
            $this->triggerType,
            $this->triggeredByUserId,
            $startedAt,
            now(),
            $results
        ));
    } catch (\Throwable $e) {
        event(new MonitoringCheckFailed(
            $this->monitor,
            $this->triggerType,
            $this->triggeredByUserId,
            $startedAt,
            $e
        ));
        throw $e;
    }
}
```

### 4. Configure Horizon Queues

```php
// In config/horizon.php

'environments' => [
    'production' => [
        'monitoring-history' => [
            'connection' => 'redis',
            'queue' => ['monitoring-history'],
            'balance' => 'auto',
            'processes' => 3,
            'tries' => 3,
        ],
    ],
],
```

### 5. Register Listeners

```php
// In app/Providers/AppServiceProvider.php

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
        MonitoringCheckCompleted::class,
        [UpdateMonitoringSummaries::class, 'handle']
    );

    Event::listen(
        MonitoringCheckCompleted::class,
        [CheckAlertConditions::class, 'handle']
    );
}
```

### 6. Test

```bash
# Fire test event
./vendor/bin/sail artisan tinker
>>> $monitor = Monitor::first();
>>> event(new App\Events\MonitoringCheckCompleted($monitor, 'scheduled', null, now()->subSeconds(2), now(), ['check_type' => 'both']));

# Verify record created
>>> App\Models\MonitoringResult::count()  // Should be > 0

# Check Horizon
./vendor/bin/sail artisan horizon:list
```

---

## 📈 Phase 3 Quick Start (Week 3)

### 1. Create Service

```bash
./vendor/bin/sail artisan make:class Services/MonitoringHistoryService
```

Copy code from master plan.

### 2. Add API Endpoints

```php
// In routes/api.php

Route::get('/monitors/{monitor}/history', [MonitoringHistoryController::class, 'history']);
Route::get('/monitors/{monitor}/trends', [MonitoringHistoryController::class, 'trends']);
Route::get('/monitors/{monitor}/summary', [MonitoringHistoryController::class, 'summary']);
```

### 3. Create Vue Components

```bash
# In resources/js/components/
MonitoringHistoryChart.vue
UptimeTrendCard.vue
RecentChecksTimeline.vue
SslExpirationTrendCard.vue
```

### 4. Integrate into Dashboard

```vue
<template>
  <div>
    <UptimeTrendCard :monitor="monitor" period="7d" />
    <MonitoringHistoryChart :monitor="monitor" />
    <RecentChecksTimeline :monitor="monitor" :limit="50" />
  </div>
</template>
```

---

## 🔒 SSL Monitoring Enhancements

### Certificate Subject Extraction (NEW)

**Feature**: Automatic extraction and display of certificate subject (Common Name + Subject Alternative Names).

**Implementation**: `app/Jobs/CheckMonitorJob.php:424-502`

**What It Does**:
- Extracts CN (Common Name) and SANs (Subject Alternative Names) from SSL certificates
- Displays all domains the certificate is valid for
- Handles wildcard certificates (e.g., `*.example.com`)
- Stores in `monitoring_results.certificate_subject` for historical tracking

**Example Output**:
- Regular certificate: `fairnando.at, www.fairnando.at`
- Wildcard certificate: `gebrauchte.at, *.gebrauchte.at`

**Database Field**: `monitoring_results.certificate_subject` (VARCHAR, nullable)

**UI Display**: SSL Certificate card on website details page shows "Subject" field with all certificate domains.

**Key Method**:
```php
/**
 * Extract certificate subject (CN + Subject Alternative Names).
 *
 * @return array{subject: ?string, valid_from: ?Carbon, expires_at: ?Carbon}
 */
private function extractCertificateData(): array
{
    // Uses OpenSSL to parse certificate
    // Extracts CN from cert['subject']['CN']
    // Extracts SANs from cert['extensions']['subjectAltName']
    // Returns comma-separated list of domains
}
```

**Verification**:
```bash
# Trigger SSL check and verify subject extraction
./vendor/bin/sail artisan tinker
>>> $monitor = Monitor::where('url', 'like', '%example.com%')->first();
>>> $job = new App\Jobs\CheckMonitorJob($monitor, 'ssl');
>>> $result = $job->handle();
>>> echo $result['ssl']['certificate_subject'];  // Should show domains

# Check database storage
>>> MonitoringResult::latest()->first()->certificate_subject;
```

**Related Implementation Prompts**:
- **Next Feature**: `DYNAMIC_SSL_THRESHOLDS_IMPLEMENTATION.md` - Dynamic expiration thresholds based on certificate validity period

---

## 🧪 Testing Quick Reference

### Use Existing Mock Traits

```php
use Tests\Traits\MocksSslCertificateAnalysis;
use Tests\Traits\MocksMonitorHttpRequests;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class, MocksSslCertificateAnalysis::class);
```

### Test Event Firing

```php
test('monitoring check creates historical record', function () {
    $monitor = Monitor::factory()->create();

    event(new MonitoringCheckCompleted(
        monitor: $monitor,
        triggerType: 'scheduled',
        triggeredByUserId: null,
        startedAt: now()->subSeconds(2),
        completedAt: now(),
        checkResults: ['check_type' => 'both']
    ));

    expect(MonitoringResult::count())->toBe(1);
});
```

### Run Tests

```bash
# All tests (maintain < 20s)
./vendor/bin/sail artisan test --parallel

# Monitoring history tests only
./vendor/bin/sail artisan test --filter=MonitoringHistory
```

---

## 🎯 Essential Commands

```bash
# Check Horizon status
./vendor/bin/sail artisan horizon:status

# List queues
./vendor/bin/sail artisan horizon:list

# View monitoring results
./vendor/bin/sail artisan tinker
>>> MonitoringResult::latest()->first()

# Check summaries
>>> MonitoringCheckSummary::where('summary_period', 'daily')->count()

# Test manual check
# (Click "Check Now" in UI, then verify:)
>>> MonitoringResult::where('trigger_type', 'manual_immediate')->count()

# Prune old data (test)
./vendor/bin/sail artisan monitoring:prune-old-data --days=90

# Aggregate summaries (test)
./vendor/bin/sail artisan monitoring:aggregate-summaries
```

---

## 🔍 Debugging

### Historical Data Not Capturing?

1. **Check Horizon is running**:
   ```bash
   ./vendor/bin/sail artisan horizon:status
   ```

2. **Verify events are firing**:
   Add `Log::info('Event fired!')` in CheckMonitorJob

3. **Check failed jobs**:
   Visit `/horizon` → Failed Jobs

4. **Verify listeners are queued**:
   Check `implements ShouldQueue` on listeners

### Dashboard Loading Slowly?

1. **Check composite index exists**:
   ```sql
   SHOW INDEX FROM monitoring_results WHERE Key_name = 'idx_monitor_website_time';
   ```

2. **Use summary tables**:
   Query `monitoring_check_summaries` instead of `monitoring_results`

3. **Enable caching**:
   ```php
   Cache::remember("monitor:$id:summary", 300, fn() => ...);
   ```

### Queue Depth Growing?

1. **Increase workers** in `config/horizon.php`
2. **Check for slow listeners** (should be < 100ms)
3. **Review failed jobs** in Horizon dashboard

---

## 📊 Performance Targets

| Metric | Target | Command to Check |
|--------|--------|------------------|
| Dashboard load | < 2s | `time curl http://localhost/dashboard` |
| API endpoint | < 500ms | `time curl http://localhost/api/monitors/1/history` |
| Test suite | < 20s | `time ./vendor/bin/sail artisan test --parallel` |
| Check overhead | < 5% | Benchmark before/after |
| Storage growth | ~23 MB/day | Check database size |

---

## ⚠️ Common Mistakes

1. **❌ Forgetting `implements ShouldQueue`** on listeners → Blocks monitoring
2. **❌ Not registering listeners** in AppServiceProvider → Events ignored
3. **❌ Missing composite index** → Slow dashboard queries
4. **❌ Not using mock traits** in tests → Real network calls, slow tests
5. **❌ Querying raw results** instead of summaries → Slow dashboard
6. **❌ Not configuring Horizon queues** → Jobs not processing

---

## ✅ Validation Checklist

### Phase 1 Complete?
- [ ] 4 migrations created and run successfully
- [ ] 4 models exist with relationships
- [ ] `php artisan tinker` can query models
- [ ] All tests passing

### Phase 2 Complete?
- [ ] 4 events created
- [ ] 4 listeners created with `ShouldQueue`
- [ ] CheckMonitorJob fires events
- [ ] Listeners registered in AppServiceProvider
- [ ] Horizon queues configured
- [ ] Historical data being captured
- [ ] Tests passing (< 20s)

### Phase 3 Complete?
- [ ] MonitoringHistoryService exists
- [ ] API endpoints working
- [ ] Vue components render correctly
- [ ] Dashboard displays historical data
- [ ] Dashboard loads in < 2s

---

## 📚 Further Reading

- **Full Plan**: `HISTORICAL_DATA_MASTER_PLAN.md` (complete implementation guide)
- **Phase 4 Completion**: `PHASE4_COMPLETION_PROMPT.md` (finish remaining listeners and scheduler)
- **Phase 5 Implementation**: `PHASE5_IMPLEMENTATION_PROMPT.md` (production optimization)
- **Testing**: `TESTING_INSIGHTS.md` (testing patterns)
- **Queue System**: `QUEUE_AND_SCHEDULER_ARCHITECTURE.md` (queue setup)
- **Development**: `DEVELOPMENT_PRIMER.md` (development workflow)

---

**TL;DR**: Create 4 tables → Fire events in CheckMonitorJob → Create queued listeners → Integrate into dashboard → Test with < 20s suite

**Current Status**: 94% Complete - Phase 1, 2, 3, 4 done ✅
**Remaining Work**: Phase 5 (60% remaining) = ~2-3 hours total
**Next Step**: Use `PHASE5_IMPLEMENTATION_PROMPT.md` to complete Phase 5 optimizations
**Production Ready**: Phase 4 complete - Phase 5 optional (performance optimizations)
