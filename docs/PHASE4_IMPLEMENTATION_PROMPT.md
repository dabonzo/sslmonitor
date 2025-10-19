# Phase 4 Implementation Prompt - Advanced Features & Data Management

**Copy this entire prompt to start Phase 4 implementation**

---

## ⚠️ **CRITICAL ARCHITECTURAL CONSTRAINT**

**IMPORTANT**: The `Monitor` model (extended from Spatie) **DOES NOT have a `website_id` column**.

### Data Relationship Architecture

```
Monitor (Spatie\UptimeMonitor\Models\Monitor)
  ├── id
  ├── url
  ├── uptime/ssl config
  └── NO website_id ❌

MonitoringResult
  ├── id
  ├── monitor_id     ✓
  ├── website_id     ✓ (THIS is where website_id lives)
  └── check data

Website
  ├── id
  ├── monitor_id (optional)
  └── website data
```

**Key Insight**: The relationship is `Monitor ←(1:N)→ MonitoringResult ←(N:1)→ Website`

### ✅ Correct Pattern: Get website_id from MonitoringResult

```php
// ❌ WRONG - Monitor doesn't have website_id
MonitoringCheckSummary::create([
    'monitor_id' => $monitor->id,
    'website_id' => $monitor->website_id,  // FAILS!
]);

// ✅ CORRECT - Get website_id from aggregated results
$stats = MonitoringResult::where('monitor_id', $monitor->id)
    ->selectRaw('website_id, COUNT(*) as total_checks, ...')
    ->groupBy('website_id')
    ->first();

MonitoringCheckSummary::create([
    'monitor_id' => $monitor->id,
    'website_id' => $stats->website_id,  // ✓ Works!
]);
```

### 🎯 Recommended Solution: Add Helper to Monitor Model

**Best Practice**: Add a convenience accessor to `App\Models\Monitor` while keeping queries explicit.

```php
// In app/Models/Monitor.php
public function getWebsiteIdAttribute(): ?int
{
    // Get website_id from the most recent monitoring result
    return $this->monitoringResults()->latest()->value('website_id');
}

// Or add a relationship
public function monitoringResults(): HasMany
{
    return $this->hasMany(MonitoringResult::class, 'monitor_id');
}
```

**Usage**:
```php
// Still get from results in aggregations (explicit & efficient)
$stats = MonitoringResult::where('monitor_id', $monitor->id)
    ->selectRaw('website_id, ...')
    ->first();

// Use accessor for simple cases (convenient)
$websiteId = $monitor->website_id;  // Uses the accessor
```

---

## 🎯 Mission: Implement Phase 4 - Advanced Features (Week 4)

You are implementing **Phase 4: Advanced Features & Data Management** of the Historical Data Tracking system for SSL Monitor v4. This phase creates intelligent data aggregation, alert correlation, data retention policies, and reporting capabilities.

## 📚 Essential Context

**Project**: SSL Monitor v4 - Laravel 12 + Vue 3 + Inertia.js + MariaDB
**Current State**: Phase 3 complete - Dashboard displaying historical data with charts
**Branch**: `feature/historical-data-tracking` (continue from Phase 3)
**Test Performance Requirement**: Maintain < 20s parallel test execution

**Documentation**:
- **Master Plan**: `docs/HISTORICAL_DATA_MASTER_PLAN.md` (complete implementation guide)
- **Phase 1-3 Completion**: Database, events, and dashboard ready
- **Testing Guide**: `docs/TESTING_INSIGHTS.md`
- **Development Primer**: `docs/DEVELOPMENT_PRIMER.md`
- **Queue Architecture**: `docs/QUEUE_AND_SCHEDULER_ARCHITECTURE.md`

## 🤖 Optimal Implementation Using Specialized Agents

**RECOMMENDED**: Use specialized agents for faster, more accurate implementation:

### **Approach 1: Use Multiple Agents in Parallel** 🚀 (Recommended - Fastest)

Launch agents simultaneously for maximum speed:

**Agent 1: laravel-backend-specialist** - Create aggregation jobs and commands
**Agent 2: laravel-backend-specialist** - Implement alert correlation system
**Agent 3: testing-specialist** - Create comprehensive tests
**Agent 4: documentation-writer** - Create data retention documentation

**Example**:
```
Use these agents in parallel:
1. laravel-backend-specialist: Create AggregateMonitoringSummariesJob and retention command
2. laravel-backend-specialist: Implement alert correlation and lifecycle tracking
3. testing-specialist: Write tests for aggregation, correlation, and retention
4. documentation-writer: Document data retention policies and reporting
```

### **Approach 2: Sequential Agent Workflow** 🐢 (Slower but controlled)

Execute agents one after another:

**Step 1**: `laravel-backend-specialist` - Build aggregation job
**Step 2**: `laravel-backend-specialist` - Implement alert correlation
**Step 3**: `laravel-backend-specialist` - Create retention policies
**Step 4**: `testing-specialist` - Write comprehensive tests
**Step 5**: `documentation-writer` - Create operational documentation

### **Approach 3: Manual Step-by-Step** 🛠️ (Most control, slowest)

Follow steps 1-20 manually in the implementation prompt below.

---

## 🎯 Phase 4 Goals

Create advanced features for intelligent data management:

1. ✅ Aggregation job for daily/weekly/monthly summaries
2. ✅ Alert correlation linking alerts to specific checks
3. ✅ Data retention policies (90-day raw, 1-year summaries)
4. ✅ Reporting capabilities (CSV export, date range queries)
5. ✅ Scheduled jobs running via Laravel Scheduler
6. ✅ All tests passing (maintain < 20s)

## 📋 Detailed Implementation Steps

### Part 0: Add Monitor Helper (PREREQUISITE)

**IMPORTANT**: Do this first to make the rest of the implementation cleaner.

#### Update Monitor Model

**Path**: `app/Models/Monitor.php`

Add these methods after the existing class methods:

```php
    /**
     * Get website_id from the most recent monitoring result
     *
     * NOTE: Monitor model doesn't have website_id column.
     * This accessor retrieves it from the monitoring_results table.
     */
    public function getWebsiteIdAttribute(): ?int
    {
        return $this->monitoringResults()->latest()->value('website_id');
    }

    /**
     * Monitoring results relationship
     */
    public function monitoringResults(): HasMany
    {
        return $this->hasMany(MonitoringResult::class, 'monitor_id');
    }
```

**Add import at top**:
```php
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\MonitoringResult;
```

This allows `$monitor->website_id` to work via accessor while keeping the architecture explicit.

---

### Part 1: Summary Aggregation System

#### Step 1: Create AggregateMonitoringSummariesJob

**Path**: `app/Jobs/AggregateMonitoringSummariesJob.php`

**Purpose**: Calculate hourly/daily/monthly statistics from raw monitoring_results

```php
<?php

namespace App\Jobs;

use App\Models\Monitor;
use App\Models\MonitoringCheckSummary;
use App\Models\MonitoringResult;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class AggregateMonitoringSummariesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $period,  // 'hourly', 'daily', 'weekly', 'monthly'
        public readonly ?Carbon $date = null,
    ) {}

    public function handle(): void
    {
        $targetDate = $this->date ?? now();

        Monitor::chunk(100, function ($monitors) use ($targetDate) {
            foreach ($monitors as $monitor) {
                $this->aggregateForMonitor($monitor, $targetDate);
            }
        });
    }

    protected function aggregateForMonitor(Monitor $monitor, Carbon $date): void
    {
        $dateRange = $this->getDateRange($date);

        // ✅ CORRECT: Get website_id from results, not from monitor
        $stats = MonitoringResult::where('monitor_id', $monitor->id)
            ->whereBetween('started_at', [$dateRange['start'], $dateRange['end']])
            ->select([
                DB::raw('website_id'),  // ← Get website_id here
                DB::raw('COUNT(*) as total_checks'),
                DB::raw('SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as successful_checks'),
                DB::raw('SUM(CASE WHEN status != "success" THEN 1 ELSE 0 END) as failed_checks'),
                DB::raw('AVG(response_time_ms) as avg_response_time'),
                DB::raw('MIN(response_time_ms) as min_response_time'),
                DB::raw('MAX(response_time_ms) as max_response_time'),
                DB::raw('SUM(CASE WHEN uptime_status = "up" THEN 1 ELSE 0 END) as uptime_count'),
                DB::raw('SUM(CASE WHEN uptime_status = "down" THEN 1 ELSE 0 END) as downtime_count'),
                DB::raw('SUM(CASE WHEN ssl_status = "valid" THEN 1 ELSE 0 END) as ssl_valid_count'),
                DB::raw('SUM(CASE WHEN ssl_status IN ("invalid", "expired") THEN 1 ELSE 0 END) as ssl_invalid_count'),
            ])
            ->groupBy('website_id')  // ← Group by website_id
            ->first();

        if ($stats && $stats->total_checks > 0) {
            MonitoringCheckSummary::updateOrCreate(
                [
                    'monitor_id' => $monitor->id,
                    'website_id' => $stats->website_id,  // ← Use from results
                    'summary_period' => $this->period,
                    'period_start' => $dateRange['start'],
                ],
                [
                    'period_end' => $dateRange['end'],
                    'total_checks' => $stats->total_checks,
                    'successful_checks' => $stats->successful_checks,
                    'failed_checks' => $stats->failed_checks,
                    'avg_response_time_ms' => round($stats->avg_response_time ?? 0, 2),
                    'min_response_time_ms' => $stats->min_response_time,
                    'max_response_time_ms' => $stats->max_response_time,
                    'uptime_count' => $stats->uptime_count,
                    'downtime_count' => $stats->downtime_count,
                    'uptime_percentage' => $stats->total_checks > 0
                        ? round(($stats->uptime_count / $stats->total_checks) * 100, 2)
                        : 0,
                    'ssl_valid_count' => $stats->ssl_valid_count,
                    'ssl_invalid_count' => $stats->ssl_invalid_count,
                    'last_aggregated_at' => now(),
                ]
            );
        }
    }

    protected function getDateRange(Carbon $date): array
    {
        return match ($this->period) {
            'hourly' => [
                'start' => $date->copy()->startOfHour(),
                'end' => $date->copy()->endOfHour(),
            ],
            'daily' => [
                'start' => $date->copy()->startOfDay(),
                'end' => $date->copy()->endOfDay(),
            ],
            'weekly' => [
                'start' => $date->copy()->startOfWeek(),
                'end' => $date->copy()->endOfWeek(),
            ],
            'monthly' => [
                'start' => $date->copy()->startOfMonth(),
                'end' => $date->copy()->endOfMonth(),
            ],
            default => [
                'start' => $date->copy()->startOfDay(),
                'end' => $date->copy()->endOfDay(),
            ],
        };
    }
}
```

**Key Changes from Original**:
- Line 47: Added `DB::raw('website_id')` to SELECT
- Line 60: Added `->groupBy('website_id')`
- Line 62: Changed condition to `if ($stats && $stats->total_checks > 0)`
- Line 66: Changed to `'website_id' => $stats->website_id`

#### Step 2: Schedule Aggregation Job

Add to `routes/console.php` (Laravel 12 uses routes/console.php instead of app/Console/Kernel.php):

```php
use App\Jobs\AggregateMonitoringSummariesJob;
use Illuminate\Support\Facades\Schedule;

// Aggregate monitoring data at different intervals
Schedule::job(new AggregateMonitoringSummariesJob('hourly'))
    ->hourly()
    ->at('05')
    ->withoutOverlapping()
    ->name('aggregate-hourly-monitoring-summaries');

Schedule::job(new AggregateMonitoringSummariesJob('daily'))
    ->dailyAt('01:00')
    ->withoutOverlapping()
    ->name('aggregate-daily-monitoring-summaries');

Schedule::job(new AggregateMonitoringSummariesJob('weekly'))
    ->weeklyOn(1, '02:00')  // Monday at 2 AM
    ->withoutOverlapping()
    ->name('aggregate-weekly-monitoring-summaries');

Schedule::job(new AggregateMonitoringSummariesJob('monthly'))
    ->monthlyOn(1, '03:00')  // 1st day at 3 AM
    ->withoutOverlapping()
    ->name('aggregate-monthly-monitoring-summaries');
```

---

### Part 2: Alert Correlation System

#### Step 3: Create AlertCorrelationService

**Path**: `app/Services/AlertCorrelationService.php`

**Purpose**: Link alerts to monitoring results and manage alert lifecycle

```php
<?php

namespace App\Services;

use App\Models\MonitoringAlert;
use App\Models\MonitoringResult;

class AlertCorrelationService
{
    /**
     * Check if alert conditions are met and create alerts
     */
    public function checkAndCreateAlerts(MonitoringResult $result): void
    {
        // Check SSL expiration alert
        if ($result->ssl_status && $result->days_until_expiration !== null) {
            $this->checkSslExpirationAlert($result);
        }

        // Check uptime alert
        if ($result->uptime_status === 'down') {
            $this->checkUptimeAlert($result);
        }

        // Check response time alert
        if ($result->response_time_ms && $result->response_time_ms > 5000) {
            $this->checkResponseTimeAlert($result);
        }
    }

    protected function checkSslExpirationAlert(MonitoringResult $result): void
    {
        if ($result->days_until_expiration <= 7) {
            // Check if alert already exists for this monitor
            $existingAlert = MonitoringAlert::where('monitor_id', $result->monitor_id)
                ->where('alert_type', 'ssl_expiring')
                ->whereNull('resolved_at')
                ->first();

            if (! $existingAlert) {
                MonitoringAlert::create([
                    'monitor_id' => $result->monitor_id,
                    'website_id' => $result->website_id,  // ✅ From result
                    'affected_check_result_id' => $result->id,
                    'alert_type' => 'ssl_expiring',
                    'alert_severity' => $result->days_until_expiration <= 3 ? 'critical' : 'warning',
                    'alert_title' => 'SSL Certificate Expiring Soon',
                    'alert_message' => "SSL certificate expires in {$result->days_until_expiration} days",
                    'trigger_value' => [
                        'days_until_expiration' => $result->days_until_expiration,
                        'certificate_expiration_date' => $result->certificate_expiration_date?->toIso8601String(),
                        'certificate_issuer' => $result->certificate_issuer,
                    ],
                    'threshold_value' => [
                        'warning_days' => 7,
                        'critical_days' => 3,
                    ],
                    'first_detected_at' => now(),
                    'last_occurred_at' => now(),
                ]);
            }
        }
    }

    protected function checkUptimeAlert(MonitoringResult $result): void
    {
        // Count consecutive failures within the last hour
        $consecutiveFailures = MonitoringResult::where('monitor_id', $result->monitor_id)
            ->where('started_at', '>=', now()->subHour())
            ->where('uptime_status', 'down')
            ->orderByDesc('started_at')
            ->count();

        if ($consecutiveFailures >= 3) {
            // Check if we already have an active uptime alert
            $existingAlert = MonitoringAlert::where('monitor_id', $result->monitor_id)
                ->where('alert_type', 'uptime_down')
                ->whereNull('resolved_at')
                ->first();

            if (! $existingAlert) {
                MonitoringAlert::create([
                    'monitor_id' => $result->monitor_id,
                    'website_id' => $result->website_id,  // ✅ From result
                    'affected_check_result_id' => $result->id,
                    'alert_type' => 'uptime_down',
                    'alert_severity' => 'critical',
                    'alert_title' => 'Website Down',
                    'alert_message' => "Website has been down for {$consecutiveFailures} consecutive checks",
                    'trigger_value' => [
                        'consecutive_failures' => $consecutiveFailures,
                        'error_message' => $result->error_message,
                        'http_status_code' => $result->http_status_code,
                    ],
                    'threshold_value' => [
                        'max_consecutive_failures' => 3,
                    ],
                    'first_detected_at' => now(),
                    'last_occurred_at' => now(),
                ]);
            } else {
                // Update existing alert with latest occurrence
                $existingAlert->update([
                    'last_occurred_at' => now(),
                    'occurrence_count' => $existingAlert->occurrence_count + 1,
                    'affected_check_result_id' => $result->id,
                    'trigger_value' => [
                        'consecutive_failures' => $consecutiveFailures,
                        'error_message' => $result->error_message,
                        'http_status_code' => $result->http_status_code,
                    ],
                ]);
            }
        }
    }

    protected function checkResponseTimeAlert(MonitoringResult $result): void
    {
        MonitoringAlert::create([
            'monitor_id' => $result->monitor_id,
            'website_id' => $result->website_id,  // ✅ From result
            'affected_check_result_id' => $result->id,
            'alert_type' => 'performance_degradation',
            'alert_severity' => 'warning',
            'alert_title' => 'Slow Response Time',
            'alert_message' => "Response time of {$result->response_time_ms}ms exceeds threshold",
            'trigger_value' => [
                'response_time_ms' => $result->response_time_ms,
            ],
            'threshold_value' => [
                'max_response_time_ms' => 5000,
            ],
            'first_detected_at' => now(),
            'last_occurred_at' => now(),
        ]);
    }

    /**
     * Mark alert as acknowledged
     */
    public function acknowledgeAlert(MonitoringAlert $alert, int $userId, ?string $note = null): void
    {
        $alert->update([
            'acknowledged_at' => now(),
            'acknowledged_by_user_id' => $userId,
            'acknowledgment_note' => $note,
        ]);
    }

    /**
     * Mark alert as resolved
     */
    public function resolveAlert(MonitoringAlert $alert, ?string $resolution = null): void
    {
        $alert->update([
            'resolved_at' => now(),
            'acknowledgment_note' => $resolution ? ($alert->acknowledgment_note ? $alert->acknowledgment_note."\n\nResolution: ".$resolution : "Resolution: {$resolution}") : $alert->acknowledgment_note,
        ]);
    }

    /**
     * Auto-resolve alerts when conditions improve
     */
    public function autoResolveAlerts(MonitoringResult $result): void
    {
        // Auto-resolve SSL alerts if certificate is renewed
        if ($result->ssl_status === 'valid' && $result->days_until_expiration > 30) {
            MonitoringAlert::where('monitor_id', $result->monitor_id)
                ->where('alert_type', 'ssl_expiring')
                ->whereNull('resolved_at')
                ->update([
                    'resolved_at' => now(),
                    'acknowledgment_note' => 'SSL certificate renewed - auto-resolved',
                ]);
        }

        // Auto-resolve uptime alerts if site is back up
        if ($result->uptime_status === 'up') {
            MonitoringAlert::where('monitor_id', $result->monitor_id)
                ->where('alert_type', 'uptime_down')
                ->whereNull('resolved_at')
                ->update([
                    'resolved_at' => now(),
                    'acknowledgment_note' => 'Website back online - auto-resolved',
                ]);
        }
    }
}
```

**Key Points**:
- All `website_id` references use `$result->website_id` ✅
- Alert types: `ssl_expiring`, `uptime_down`, `performance_degradation`
- Auto-resolution based on improved conditions

#### Step 4: Integrate Alert Service with Event Listener

**Path**: `app/Listeners/RecordMonitoringResult.php`

Add alert correlation after creating the monitoring result:

```php
use App\Services\AlertCorrelationService;

class RecordMonitoringResult
{
    public function __construct(
        protected AlertCorrelationService $alertService
    ) {}

    public function handle(UptimeCheckSucceeded|UptimeCheckFailed|CertificateCheckSucceeded|CertificateCheckFailed $event): void
    {
        // ... existing MonitoringResult::create() code ...

        $result = MonitoringResult::create([...]);

        // ✅ NEW: Check and create alerts
        $this->alertService->checkAndCreateAlerts($result);
        $this->alertService->autoResolveAlerts($result);
    }
}
```

---

### Part 3: Data Retention Policies

#### Step 5: Create Data Retention Command

**Path**: `app/Console/Commands/PruneMonitoringDataCommand.php`

**Purpose**: Delete raw monitoring_results older than 90 days

```php
<?php

namespace App\Console\Commands;

use App\Models\MonitoringResult;
use Illuminate\Console\Command;

class PruneMonitoringDataCommand extends Command
{
    protected $signature = 'monitoring:prune-old-data
                            {--days=90 : Number of days to retain raw monitoring data}
                            {--dry-run : Show what would be deleted without actually deleting}';

    protected $description = 'Prune old monitoring result data (keeps summaries, removes raw results)';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');

        $cutoffDate = now()->subDays($days);

        $this->info("Pruning monitoring results older than {$days} days (before {$cutoffDate->toDateString()})");

        $query = MonitoringResult::where('started_at', '<', $cutoffDate);
        $count = $query->count();

        if ($count === 0) {
            $this->info('No records to prune.');
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn("DRY RUN: Would delete {$count} monitoring result records");
            return self::SUCCESS;
        }

        if (! $this->confirm("Delete {$count} monitoring result records?")) {
            $this->info('Cancelled.');
            return self::SUCCESS;
        }

        $deleted = 0;
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $query->chunkById(1000, function ($results) use (&$deleted, $bar) {
            foreach ($results as $result) {
                $result->delete();
                $deleted++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();

        $this->info("Successfully deleted {$deleted} monitoring result records.");

        return self::SUCCESS;
    }
}
```

#### Step 6: Schedule Data Retention

Add to `routes/console.php`:

```php
use App\Console\Commands\PruneMonitoringDataCommand;

// Prune old monitoring data daily
Schedule::command('monitoring:prune-old-data', ['--days' => 90])
    ->dailyAt('04:00')
    ->withoutOverlapping()
    ->name('prune-monitoring-data');
```

---

### Part 4: Reporting Capabilities

#### Step 7: Create Reporting Service

**Path**: `app/Services/MonitoringReportService.php`

```php
<?php

namespace App\Services;

use App\Models\Monitor;
use App\Models\MonitoringResult;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class MonitoringReportService
{
    /**
     * Generate CSV export for monitoring data
     */
    public function generateCsvExport(Monitor $monitor, Carbon $startDate, Carbon $endDate): string
    {
        $results = MonitoringResult::where('monitor_id', $monitor->id)
            ->whereBetween('started_at', [$startDate, $endDate])
            ->orderBy('started_at', 'desc')
            ->get();

        $csv = "Timestamp,Status,Uptime Status,Response Time (ms),SSL Status,Days Until Expiration,Error\n";

        foreach ($results as $result) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s\n",
                $result->started_at->toIso8601String(),
                $result->status,
                $result->uptime_status ?? 'N/A',
                $result->response_time_ms ?? 'N/A',
                $result->ssl_status ?? 'N/A',
                $result->days_until_expiration ?? 'N/A',
                str_replace(["\n", "\r", ','], ' ', $result->error_message ?? '')
            );
        }

        return $csv;
    }

    /**
     * Get summary report for period
     */
    public function getSummaryReport(Monitor $monitor, string $period = '30d'): array
    {
        $days = match ($period) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            default => 30,
        };

        $results = MonitoringResult::where('monitor_id', $monitor->id)
            ->where('started_at', '>=', now()->subDays($days))
            ->get();

        return [
            'period' => $period,
            'total_checks' => $results->count(),
            'success_count' => $results->where('status', 'success')->count(),
            'failure_count' => $results->where('status', '!=', 'success')->count(),
            'avg_response_time' => round($results->avg('response_time_ms') ?? 0, 2),
            'uptime_percentage' => $this->calculateUptimePercentage($results),
            'ssl_checks' => $results->whereNotNull('ssl_status')->count(),
            'ssl_valid' => $results->where('ssl_status', 'valid')->count(),
            'ssl_issues' => $results->whereIn('ssl_status', ['invalid', 'expired'])->count(),
        ];
    }

    protected function calculateUptimePercentage(Collection $results): float
    {
        $total = $results->whereNotNull('uptime_status')->count();
        if ($total === 0) {
            return 0;
        }

        $up = $results->where('uptime_status', 'up')->count();

        return round(($up / $total) * 100, 2);
    }

    /**
     * Get daily breakdown for period
     */
    public function getDailyBreakdown(Monitor $monitor, Carbon $startDate, Carbon $endDate): array
    {
        $results = MonitoringResult::where('monitor_id', $monitor->id)
            ->whereBetween('started_at', [$startDate, $endDate])
            ->selectRaw('DATE(started_at) as date, COUNT(*) as checks, AVG(response_time_ms) as avg_response')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $results->map(function ($day) {
            return [
                'date' => $day->date,
                'checks' => $day->checks,
                'avg_response_time' => round($day->avg_response ?? 0, 2),
            ];
        })->toArray();
    }
}
```

#### Step 8: Create Report API Endpoints

**Path**: `app/Http/Controllers/MonitoringReportController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use App\Services\MonitoringReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MonitoringReportController extends Controller
{
    public function __construct(
        protected MonitoringReportService $reportService
    ) {}

    /**
     * Export monitoring data as CSV
     */
    public function exportCsv(Monitor $monitor, Request $request): StreamedResponse
    {
        $startDate = Carbon::parse($request->input('start_date', now()->subDays(30)));
        $endDate = Carbon::parse($request->input('end_date', now()));

        $csv = $this->reportService->generateCsvExport($monitor, $startDate, $endDate);

        $filename = "monitor-{$monitor->id}-{$startDate->format('Y-m-d')}-to-{$endDate->format('Y-m-d')}.csv";

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Get summary report
     */
    public function summary(Monitor $monitor, Request $request)
    {
        $period = $request->input('period', '30d');

        return response()->json([
            'report' => $this->reportService->getSummaryReport($monitor, $period),
        ]);
    }

    /**
     * Get daily breakdown
     */
    public function dailyBreakdown(Monitor $monitor, Request $request)
    {
        $startDate = Carbon::parse($request->input('start_date', now()->subDays(30)));
        $endDate = Carbon::parse($request->input('end_date', now()));

        return response()->json([
            'breakdown' => $this->reportService->getDailyBreakdown($monitor, $startDate, $endDate),
        ]);
    }
}
```

#### Step 9: Add Report Routes

**Path**: `routes/web.php`

```php
use App\Http\Controllers\MonitoringReportController;

Route::middleware(['auth'])
    ->prefix('api/monitors/{monitor}/reports')
    ->name('api.monitors.reports.')
    ->group(function () {
        Route::get('/export-csv', [MonitoringReportController::class, 'exportCsv'])
            ->name('export-csv');
        Route::get('/summary', [MonitoringReportController::class, 'summary'])
            ->name('summary');
        Route::get('/daily-breakdown', [MonitoringReportController::class, 'dailyBreakdown'])
            ->name('daily-breakdown');
    });
```

---

### Part 5: Testing

#### Step 10: Create Aggregation Tests

**Path**: `tests/Feature/Jobs/AggregateMonitoringSummariesJobTest.php`

```php
<?php

use App\Jobs\AggregateMonitoringSummariesJob;
use App\Models\Monitor;
use App\Models\MonitoringCheckSummary;
use App\Models\MonitoringResult;
use App\Models\Website;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
    $this->monitor = Monitor::first();
    $this->website = Website::first();
});

test('aggregates daily statistics correctly', function () {
    // Create test data for today
    MonitoringResult::factory()->count(5)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'started_at' => now(),
        'status' => 'success',
        'uptime_status' => 'up',
    ]);

    // Run aggregation
    $job = new AggregateMonitoringSummariesJob('daily');
    $job->handle();

    // Verify summary was created
    $summary = MonitoringCheckSummary::where('monitor_id', $this->monitor->id)
        ->where('summary_period', 'daily')
        ->first();

    expect($summary)->not->toBeNull();
    expect($summary->total_checks)->toBe(5);
    expect($summary->successful_checks)->toBe(5);
    expect($summary->website_id)->toBe($this->website->id);
});

test('handles multiple periods correctly', function () {
    MonitoringResult::factory()->count(10)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'started_at' => now(),
    ]);

    foreach (['hourly', 'daily', 'weekly', 'monthly'] as $period) {
        $job = new AggregateMonitoringSummariesJob($period);
        $job->handle();

        $summary = MonitoringCheckSummary::where('monitor_id', $this->monitor->id)
            ->where('summary_period', $period)
            ->first();

        expect($summary)->not->toBeNull();
        expect($summary->total_checks)->toBe(10);
    }
});
```

#### Step 11: Create Alert Correlation Tests

**Path**: `tests/Feature/Services/AlertCorrelationServiceTest.php`

```php
<?php

use App\Models\Monitor;
use App\Models\MonitoringAlert;
use App\Models\MonitoringResult;
use App\Models\User;
use App\Models\Website;
use App\Services\AlertCorrelationService;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
    $this->service = app(AlertCorrelationService::class);
    $this->monitor = Monitor::first();
    $this->testUser = User::first();
    $this->website = Website::first();
});

test('creates SSL expiration alert when certificate expires soon', function () {
    $result = MonitoringResult::factory()->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'ssl_status' => 'expires_soon',
        'days_until_expiration' => 5,
    ]);

    $this->service->checkAndCreateAlerts($result);

    $alert = MonitoringAlert::where('monitor_id', $this->monitor->id)
        ->where('alert_type', 'ssl_expiring')
        ->first();

    expect($alert)->not->toBeNull();
    expect($alert->alert_severity)->toBe('warning');
    expect($alert->affected_check_result_id)->toBe($result->id);
    expect($alert->website_id)->toBe($this->website->id);
});

test('creates uptime alert after 3 consecutive failures', function () {
    // Create 3 consecutive down results
    for ($i = 0; $i < 3; $i++) {
        MonitoringResult::factory()->create([
            'monitor_id' => $this->monitor->id,
            'website_id' => $this->website->id,
            'uptime_status' => 'down',
            'started_at' => now()->subMinutes(30 - ($i * 5)),
        ]);
    }

    // Latest result that should trigger alert
    $latestResult = MonitoringResult::factory()->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'uptime_status' => 'down',
        'started_at' => now(),
    ]);

    $this->service->checkAndCreateAlerts($latestResult);

    $alert = MonitoringAlert::where('monitor_id', $this->monitor->id)
        ->where('alert_type', 'uptime_down')
        ->first();

    expect($alert)->not->toBeNull();
    expect($alert->alert_severity)->toBe('critical');
});

test('creates performance alert for slow response time', function () {
    $result = MonitoringResult::factory()->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'response_time_ms' => 5500,
    ]);

    $this->service->checkAndCreateAlerts($result);

    $alert = MonitoringAlert::where('monitor_id', $this->monitor->id)
        ->where('alert_type', 'performance_degradation')
        ->first();

    expect($alert)->not->toBeNull();
    expect($alert->alert_severity)->toBe('warning');
});

test('auto-resolves SSL alerts when certificate renewed', function () {
    // Create active SSL alert
    $alert = MonitoringAlert::create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'alert_type' => 'ssl_expiring',
        'alert_severity' => 'warning',
        'alert_title' => 'SSL Expiring',
        'alert_message' => 'Certificate expires in 7 days',
        'first_detected_at' => now()->subDays(1),
    ]);

    // New result shows certificate renewed
    $result = MonitoringResult::factory()->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'ssl_status' => 'valid',
        'days_until_expiration' => 60,
    ]);

    $this->service->autoResolveAlerts($result);

    $alert->refresh();
    expect($alert->resolved_at)->not->toBeNull();
});
```

#### Step 12: Create Data Retention Tests

**Path**: `tests/Feature/Console/PruneMonitoringDataCommandTest.php`

```php
<?php

use App\Models\Monitor;
use App\Models\MonitoringResult;
use App\Models\Website;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
    $this->monitor = Monitor::first();
    $this->website = Website::first();
});

test('prunes data older than specified days', function () {
    // Create old data (100 days ago)
    MonitoringResult::factory()->count(5)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'started_at' => now()->subDays(100),
    ]);

    // Create recent data (10 days ago)
    MonitoringResult::factory()->count(5)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'started_at' => now()->subDays(10),
    ]);

    expect(MonitoringResult::count())->toBe(10);

    // Run prune command
    $this->artisan('monitoring:prune-old-data', ['--days' => 90])
        ->expectsConfirmation('Delete 5 monitoring result records?', 'yes')
        ->assertSuccessful();

    // Should have deleted old data only
    expect(MonitoringResult::count())->toBe(5);
});

test('dry run does not delete data', function () {
    MonitoringResult::factory()->count(5)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'started_at' => now()->subDays(100),
    ]);

    $this->artisan('monitoring:prune-old-data', ['--days' => 90, '--dry-run' => true])
        ->assertSuccessful();

    // All data should still exist
    expect(MonitoringResult::count())->toBe(5);
});
```

#### Step 13: Create Reporting Tests

**Path**: `tests/Feature/Services/MonitoringReportServiceTest.php`

```php
<?php

use App\Models\Monitor;
use App\Models\MonitoringResult;
use App\Models\Website;
use App\Services\MonitoringReportService;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
    $this->service = app(MonitoringReportService::class);
    $this->monitor = Monitor::first();
    $this->website = Website::first();
});

test('generates CSV export with correct format', function () {
    MonitoringResult::factory()->count(5)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'started_at' => now(),
    ]);

    $csv = $this->service->generateCsvExport($this->monitor, now()->subDay(), now());

    expect($csv)->toContain('Timestamp,Status,Uptime Status');
    expect(substr_count($csv, "\n"))->toBe(6); // Header + 5 rows
});

test('summary report calculates statistics correctly', function () {
    MonitoringResult::factory()->count(10)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'status' => 'success',
        'uptime_status' => 'up',
        'started_at' => now(),
    ]);

    $report = $this->service->getSummaryReport($this->monitor, '30d');

    expect($report['total_checks'])->toBe(10);
    expect($report['success_count'])->toBe(10);
    expect($report['failure_count'])->toBe(0);
    expect($report['uptime_percentage'])->toBe(100.0);
});
```

---

## ✅ Completion Checklist

### Implementation
- [ ] Monitor model helper methods added
- [ ] AggregateMonitoringSummariesJob created
- [ ] AlertCorrelationService created
- [ ] PruneMonitoringDataCommand created
- [ ] MonitoringReportService created
- [ ] MonitoringReportController created
- [ ] All routes added
- [ ] All jobs scheduled

### Testing
- [ ] Aggregation tests passing
- [ ] Alert correlation tests passing
- [ ] Data retention tests passing
- [ ] Reporting tests passing
- [ ] Full test suite < 20 seconds

### Verification
- [ ] Run `php artisan schedule:list` - verify jobs scheduled
- [ ] Test aggregation manually: `php artisan queue:work`
- [ ] Test pruning: `php artisan monitoring:prune-old-data --dry-run`
- [ ] Export CSV from browser
- [ ] All tests passing: `php artisan test --parallel`

---

## 📊 Expected Results

**Test Performance**:
- Total tests: ~590 (adds 36 new tests)
- Execution time: < 20 seconds (parallel)
- All using `UsesCleanDatabase` trait

**Features**:
- ✅ Automated daily/weekly/monthly aggregations
- ✅ Intelligent alert correlation (SSL, uptime, performance)
- ✅ 90-day data retention policy
- ✅ CSV export and summary reports

**Scheduled Jobs** (visible in `php artisan schedule:list`):
```
aggregate-hourly-monitoring-summaries   Hourly at :05
aggregate-daily-monitoring-summaries    Daily at 01:00
aggregate-weekly-monitoring-summaries   Weekly Monday 02:00
aggregate-monthly-monitoring-summaries  Monthly 1st at 03:00
prune-monitoring-data                   Daily at 04:00
```

---

## 🎯 Recommended Implementation Path

### Best Approach: Option A + Testing-First

**Step 1**: Add Monitor helper (Part 0)
**Step 2**: Use `laravel-backend-specialist` agent for Parts 1-4 in parallel
**Step 3**: Use `testing-specialist` agent for Part 5
**Step 4**: Verify and commit

This gives you:
- ✅ Clean architecture (explicit about website_id source)
- ✅ Convenient API (`$monitor->website_id` works via accessor)
- ✅ Fast implementation (parallel agents)
- ✅ Comprehensive testing

**Total Estimated Time**: 2-3 hours (with agents in parallel)

---

**Phase 4 Status**: Ready for implementation with architectural fixes ✅
