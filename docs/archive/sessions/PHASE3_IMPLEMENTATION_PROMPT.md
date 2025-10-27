# Phase 3 Implementation Prompt - Historical Data Dashboard Integration

**Copy this entire prompt to start Phase 3 implementation**

---

## 🎯 Mission: Implement Phase 3 - Dashboard Integration (Week 3)

You are implementing **Phase 3: Dashboard Integration** of the Historical Data Tracking system for SSL Monitor v4. This phase creates the service layer, API endpoints, and Vue components to display historical monitoring data with trends and charts.

## 📚 Essential Context

**Project**: SSL Monitor v4 - Laravel 12 + Vue 3 + Inertia.js + MariaDB
**Current State**: Phase 2 complete - Event system capturing historical data to database
**Branch**: `feature/historical-data-tracking` (continue from Phase 2)
**Test Performance Requirement**: Maintain < 20s parallel test execution

**Documentation**:
- **Master Plan**: `docs/HISTORICAL_DATA_MASTER_PLAN.md` (complete implementation guide)
- **Phase 1 & 2 Completion**: Database tables, models, and event system ready
- **Testing Guide**: `docs/TESTING_INSIGHTS.md`
- **Development Primer**: `docs/DEVELOPMENT_PRIMER.md`
- **Agent Usage**: `docs/AGENT_USAGE_GUIDE.md`
- **Styling Guide**: `docs/TAILWIND_V4_STYLING_GUIDE.md`

## 🤖 Optimal Implementation Using Specialized Agents

**RECOMMENDED**: Use specialized agents for faster, more accurate implementation:

### **Approach 1: Use Multiple Agents in Parallel** 🚀 (Recommended - Fastest)

Launch agents simultaneously for maximum speed:

**Agent 1: laravel-backend-specialist** - Create service layer and API endpoints
**Agent 2: vue-component-builder** - Create Vue components and charts
**Agent 3: testing-specialist** - Create tests for service and API

**Example**:
```
Use these agents in parallel:
1. laravel-backend-specialist: Create MonitoringHistoryService and API endpoints
2. vue-component-builder: Create dashboard components and charts
3. testing-specialist: Write tests for service methods and API endpoints
```

### **Approach 2: Sequential Agent Workflow** 🐢 (Slower but controlled)

Execute agents one after another:

**Step 1**: `laravel-backend-specialist` - Build service layer and API
**Step 2**: `vue-component-builder` - Create frontend components
**Step 3**: `testing-specialist` - Write comprehensive tests
**Step 4**: `styling-expert` - Ensure design system compliance

### **Approach 3: Manual Step-by-Step** 🛠️ (Most control, slowest)

Follow steps 1-15 manually in the implementation prompt below.

---

## 🎯 Phase 3 Goals

Create the dashboard integration for historical data visualization:
1. ✅ MonitoringHistoryService with 6 core methods
2. ✅ 3 API endpoints for historical data
3. ✅ 4 Vue components with charts
4. ✅ Dashboard integration with < 2s load time
5. ✅ All tests passing (maintain < 20s)
6. ✅ Design system compliance

## 📋 Detailed Implementation Steps

### Step 1: Create MonitoringHistoryService

**Path**: `app/Services/MonitoringHistoryService.php`

```php
<?php

namespace App\Services;

use App\Models\Monitor;
use App\Models\MonitoringResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MonitoringHistoryService
{
    /**
     * Get trend data for a monitor over a specified period
     *
     * @param Monitor $monitor
     * @param string $period '7d', '30d', '90d'
     * @return array{labels: array, datasets: array}
     */
    public function getTrendData(Monitor $monitor, string $period = '7d'): array
    {
        $days = match($period) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            default => 7,
        };

        $results = MonitoringResult::where('monitor_id', $monitor->id)
            ->where('started_at', '>=', now()->subDays($days))
            ->orderBy('started_at')
            ->select([
                DB::raw('DATE(started_at) as date'),
                DB::raw('AVG(response_time_ms) as avg_response_time'),
                DB::raw('COUNT(*) as check_count'),
                DB::raw('SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as success_count'),
            ])
            ->groupBy('date')
            ->get();

        return [
            'labels' => $results->pluck('date')->toArray(),
            'datasets' => [
                [
                    'label' => 'Response Time (ms)',
                    'data' => $results->pluck('avg_response_time')->toArray(),
                ],
                [
                    'label' => 'Success Rate (%)',
                    'data' => $results->map(fn($r) =>
                        $r->check_count > 0 ? ($r->success_count / $r->check_count) * 100 : 0
                    )->toArray(),
                ],
            ],
        ];
    }

    /**
     * Get recent check history for a monitor
     *
     * @param Monitor $monitor
     * @param int $limit
     * @return Collection<int, MonitoringResult>
     */
    public function getRecentHistory(Monitor $monitor, int $limit = 50): Collection
    {
        return MonitoringResult::where('monitor_id', $monitor->id)
            ->with(['triggeredByUser:id,name'])
            ->orderByDesc('started_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get summary statistics for a monitor over a period
     *
     * @param Monitor $monitor
     * @param string $period
     * @return array{total_checks: int, success_count: int, failure_count: int, avg_response_time: float, uptime_percentage: float}
     */
    public function getSummaryStats(Monitor $monitor, string $period = '30d'): array
    {
        $days = match($period) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            default => 30,
        };

        $stats = MonitoringResult::where('monitor_id', $monitor->id)
            ->where('started_at', '>=', now()->subDays($days))
            ->select([
                DB::raw('COUNT(*) as total_checks'),
                DB::raw('SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as success_count'),
                DB::raw('SUM(CASE WHEN status != "success" THEN 1 ELSE 0 END) as failure_count'),
                DB::raw('AVG(response_time_ms) as avg_response_time'),
            ])
            ->first();

        $totalChecks = $stats->total_checks ?? 0;
        $successCount = $stats->success_count ?? 0;

        return [
            'total_checks' => $totalChecks,
            'success_count' => $successCount,
            'failure_count' => $stats->failure_count ?? 0,
            'avg_response_time' => round($stats->avg_response_time ?? 0, 2),
            'uptime_percentage' => $totalChecks > 0
                ? round(($successCount / $totalChecks) * 100, 2)
                : 0,
        ];
    }

    /**
     * Get response time trend data for charting
     *
     * @param Monitor $monitor
     * @param string $period
     * @return array{timestamps: array, response_times: array}
     */
    public function getResponseTimeTrend(Monitor $monitor, string $period = '7d'): array
    {
        $days = match($period) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            default => 7,
        };

        $results = MonitoringResult::where('monitor_id', $monitor->id)
            ->where('started_at', '>=', now()->subDays($days))
            ->whereNotNull('response_time_ms')
            ->orderBy('started_at')
            ->select(['started_at', 'response_time_ms'])
            ->get();

        return [
            'timestamps' => $results->pluck('started_at')->map(fn($date) =>
                $date->toIso8601String()
            )->toArray(),
            'response_times' => $results->pluck('response_time_ms')->toArray(),
        ];
    }

    /**
     * Calculate uptime percentage for a monitor over a period
     *
     * @param Monitor $monitor
     * @param string $period
     * @return float
     */
    public function getUptimePercentage(Monitor $monitor, string $period = '30d'): float
    {
        $days = match($period) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            default => 30,
        };

        $result = MonitoringResult::where('monitor_id', $monitor->id)
            ->where('started_at', '>=', now()->subDays($days))
            ->select([
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN uptime_status = "up" THEN 1 ELSE 0 END) as up_count'),
            ])
            ->first();

        $total = $result->total ?? 0;
        $upCount = $result->up_count ?? 0;

        return $total > 0 ? round(($upCount / $total) * 100, 2) : 0;
    }

    /**
     * Get SSL certificate expiration trend data
     *
     * @param Monitor $monitor
     * @return array{dates: array, days_until_expiry: array}
     */
    public function getSslExpirationTrend(Monitor $monitor): array
    {
        $results = MonitoringResult::where('monitor_id', $monitor->id)
            ->whereNotNull('days_until_expiration')
            ->where('started_at', '>=', now()->subDays(90))
            ->orderBy('started_at')
            ->select(['started_at', 'days_until_expiration'])
            ->get();

        return [
            'dates' => $results->pluck('started_at')->map(fn($date) =>
                $date->format('Y-m-d')
            )->toArray(),
            'days_until_expiry' => $results->pluck('days_until_expiration')->toArray(),
        ];
    }
}
```

### Step 2: Create API Controller Methods

Add to existing `app/Http/Controllers/WebsiteController.php` or create new `MonitorHistoryController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use App\Services\MonitoringHistoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonitorHistoryController extends Controller
{
    public function __construct(
        private readonly MonitoringHistoryService $historyService
    ) {}

    /**
     * Get recent check history for a monitor
     */
    public function history(Monitor $monitor, Request $request): JsonResponse
    {
        $limit = $request->integer('limit', 50);

        $history = $this->historyService->getRecentHistory($monitor, $limit);

        return response()->json([
            'data' => $history,
        ]);
    }

    /**
     * Get trend data for charts
     */
    public function trends(Monitor $monitor, Request $request): JsonResponse
    {
        $period = $request->string('period', '7d')->toString();

        $trendData = $this->historyService->getTrendData($monitor, $period);
        $responseTimeTrend = $this->historyService->getResponseTimeTrend($monitor, $period);
        $sslTrend = $this->historyService->getSslExpirationTrend($monitor);

        return response()->json([
            'trend_data' => $trendData,
            'response_time_trend' => $responseTimeTrend,
            'ssl_expiration_trend' => $sslTrend,
        ]);
    }

    /**
     * Get summary statistics
     */
    public function summary(Monitor $monitor, Request $request): JsonResponse
    {
        $period = $request->string('period', '30d')->toString();

        $stats = $this->historyService->getSummaryStats($monitor, $period);
        $uptimePercentage = $this->historyService->getUptimePercentage($monitor, $period);

        return response()->json([
            'period' => $period,
            'total_checks' => $stats['total_checks'],
            'success_count' => $stats['success_count'],
            'failure_count' => $stats['failure_count'],
            'avg_response_time' => $stats['avg_response_time'],
            'uptime_percentage' => $uptimePercentage,
        ]);
    }
}
```

### Step 3: Add API Routes

Add to `routes/web.php`:

```php
use App\Http\Controllers\MonitorHistoryController;

// Monitor history API endpoints
Route::middleware(['auth'])->prefix('api/monitors/{monitor}')->group(function () {
    Route::get('/history', [MonitorHistoryController::class, 'history'])->name('api.monitors.history');
    Route::get('/trends', [MonitorHistoryController::class, 'trends'])->name('api.monitors.trends');
    Route::get('/summary', [MonitorHistoryController::class, 'summary'])->name('api.monitors.summary');
});
```

### Step 4: Create Vue Component - MonitoringHistoryChart.vue

**Path**: `resources/js/Components/Monitoring/MonitoringHistoryChart.vue`

```vue
<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { Line } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  type ChartData,
  type ChartOptions
} from 'chart.js'

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend
)

interface Props {
  monitorId: number
  period?: '7d' | '30d' | '90d'
  height?: number
}

const props = withDefaults(defineProps<Props>(), {
  period: '7d',
  height: 300
})

const loading = ref(true)
const chartData = ref<ChartData<'line'> | null>(null)

const chartOptions = computed<ChartOptions<'line'>>(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top',
      labels: {
        color: 'hsl(var(--foreground))',
      }
    },
    tooltip: {
      mode: 'index',
      intersect: false,
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        color: 'hsl(var(--muted-foreground))',
      },
      grid: {
        color: 'hsl(var(--border))',
      }
    },
    x: {
      ticks: {
        color: 'hsl(var(--muted-foreground))',
      },
      grid: {
        color: 'hsl(var(--border))',
      }
    }
  }
}))

async function loadChartData() {
  loading.value = true

  try {
    const response = await fetch(
      `/api/monitors/${props.monitorId}/trends?period=${props.period}`
    )
    const data = await response.json()

    chartData.value = {
      labels: data.response_time_trend.timestamps.map((ts: string) =>
        new Date(ts).toLocaleDateString()
      ),
      datasets: [
        {
          label: 'Response Time (ms)',
          data: data.response_time_trend.response_times,
          borderColor: 'hsl(var(--primary))',
          backgroundColor: 'hsl(var(--primary) / 0.1)',
          tension: 0.3,
        }
      ]
    }
  } catch (error) {
    console.error('Failed to load chart data:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadChartData()
})
</script>

<template>
  <div class="relative" :style="{ height: `${height}px` }">
    <div v-if="loading" class="absolute inset-0 flex items-center justify-center">
      <div class="text-muted-foreground">Loading chart data...</div>
    </div>

    <Line
      v-if="chartData && !loading"
      :data="chartData"
      :options="chartOptions"
    />
  </div>
</template>
```

### Step 5: Create Vue Component - UptimeTrendCard.vue

**Path**: `resources/js/Components/Monitoring/UptimeTrendCard.vue`

```vue
<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'

interface Props {
  monitorId: number
  period?: '7d' | '30d' | '90d'
}

const props = withDefaults(defineProps<Props>(), {
  period: '7d'
})

interface SummaryData {
  total_checks: number
  success_count: number
  failure_count: number
  avg_response_time: number
  uptime_percentage: number
}

const loading = ref(true)
const summary = ref<SummaryData | null>(null)

const uptimeClass = computed(() => {
  if (!summary.value) return 'text-muted-foreground'

  const uptime = summary.value.uptime_percentage
  if (uptime >= 99) return 'text-green-600 dark:text-green-400'
  if (uptime >= 95) return 'text-yellow-600 dark:text-yellow-400'
  return 'text-destructive'
})

async function loadSummary() {
  loading.value = true

  try {
    const response = await fetch(
      `/api/monitors/${props.monitorId}/summary?period=${props.period}`
    )
    summary.value = await response.json()
  } catch (error) {
    console.error('Failed to load summary:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadSummary()
})
</script>

<template>
  <div class="glass-card-strong p-6">
    <h3 class="text-lg font-semibold text-foreground mb-4">
      Uptime Statistics ({{ period }})
    </h3>

    <div v-if="loading" class="text-center py-8">
      <div class="text-muted-foreground">Loading statistics...</div>
    </div>

    <div v-else-if="summary" class="space-y-4">
      <!-- Uptime Percentage -->
      <div class="text-center py-4">
        <div :class="['text-5xl font-bold', uptimeClass]">
          {{ summary.uptime_percentage }}%
        </div>
        <div class="text-sm text-muted-foreground mt-2">Uptime</div>
      </div>

      <!-- Statistics Grid -->
      <div class="grid grid-cols-2 gap-4 pt-4 border-t border-border">
        <div>
          <div class="text-2xl font-semibold text-foreground">
            {{ summary.total_checks }}
          </div>
          <div class="text-sm text-muted-foreground">Total Checks</div>
        </div>

        <div>
          <div class="text-2xl font-semibold text-green-600 dark:text-green-400">
            {{ summary.success_count }}
          </div>
          <div class="text-sm text-muted-foreground">Successful</div>
        </div>

        <div>
          <div class="text-2xl font-semibold text-destructive">
            {{ summary.failure_count }}
          </div>
          <div class="text-sm text-muted-foreground">Failures</div>
        </div>

        <div>
          <div class="text-2xl font-semibold text-foreground">
            {{ summary.avg_response_time }}ms
          </div>
          <div class="text-sm text-muted-foreground">Avg Response</div>
        </div>
      </div>
    </div>
  </div>
</template>
```

### Step 6: Create Vue Component - RecentChecksTimeline.vue

**Path**: `resources/js/Components/Monitoring/RecentChecksTimeline.vue`

```vue
<script setup lang="ts">
import { ref, onMounted } from 'vue'

interface Props {
  monitorId: number
  limit?: number
}

const props = withDefaults(defineProps<Props>(), {
  limit: 20
})

interface CheckResult {
  id: string
  started_at: string
  status: string
  trigger_type: string
  response_time_ms: number | null
  uptime_status: string | null
  error_message: string | null
  triggered_by_user: {
    id: number
    name: string
  } | null
}

const loading = ref(true)
const checks = ref<CheckResult[]>([])

function getStatusColor(status: string): string {
  switch (status) {
    case 'success': return 'bg-green-500'
    case 'error': return 'bg-destructive'
    case 'warning': return 'bg-yellow-500'
    default: return 'bg-muted'
  }
}

function formatDate(dateString: string): string {
  const date = new Date(dateString)
  return date.toLocaleString()
}

async function loadChecks() {
  loading.value = true

  try {
    const response = await fetch(
      `/api/monitors/${props.monitorId}/history?limit=${props.limit}`
    )
    const result = await response.json()
    checks.value = result.data
  } catch (error) {
    console.error('Failed to load checks:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadChecks()
})
</script>

<template>
  <div class="glass-card-strong p-6">
    <h3 class="text-lg font-semibold text-foreground mb-4">
      Recent Checks
    </h3>

    <div v-if="loading" class="text-center py-8">
      <div class="text-muted-foreground">Loading check history...</div>
    </div>

    <div v-else class="space-y-3 max-h-96 overflow-y-auto">
      <div
        v-for="check in checks"
        :key="check.id"
        class="flex items-start gap-3 p-3 rounded-lg hover:bg-muted/50 transition-colors"
      >
        <!-- Status Indicator -->
        <div class="flex-shrink-0 mt-1">
          <div :class="['w-3 h-3 rounded-full', getStatusColor(check.status)]"></div>
        </div>

        <!-- Check Details -->
        <div class="flex-1 min-w-0">
          <div class="flex items-center justify-between gap-2">
            <div class="text-sm font-medium text-foreground">
              {{ check.status === 'success' ? 'Check Passed' : 'Check Failed' }}
            </div>
            <div class="text-xs text-muted-foreground">
              {{ formatDate(check.started_at) }}
            </div>
          </div>

          <div class="flex items-center gap-4 mt-1 text-xs text-muted-foreground">
            <span v-if="check.response_time_ms">
              {{ check.response_time_ms }}ms
            </span>
            <span class="capitalize">
              {{ check.trigger_type.replace('_', ' ') }}
            </span>
            <span v-if="check.triggered_by_user">
              by {{ check.triggered_by_user.name }}
            </span>
          </div>

          <div v-if="check.error_message" class="mt-2 text-xs text-destructive">
            {{ check.error_message }}
          </div>
        </div>
      </div>

      <div v-if="checks.length === 0" class="text-center py-8 text-muted-foreground">
        No check history available
      </div>
    </div>
  </div>
</template>
```

### Step 7: Create Vue Component - SslExpirationTrendCard.vue

**Path**: `resources/js/Components/Monitoring/SslExpirationTrendCard.vue`

```vue
<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { Line } from 'vue-chartjs'
import type { ChartData, ChartOptions } from 'chart.js'

interface Props {
  monitorId: number
}

const props = defineProps<Props>()

const loading = ref(true)
const trendData = ref<{ dates: string[], days_until_expiry: number[] } | null>(null)

const currentDaysUntilExpiry = computed(() => {
  if (!trendData.value || trendData.value.days_until_expiry.length === 0) {
    return null
  }
  return trendData.value.days_until_expiry[trendData.value.days_until_expiry.length - 1]
})

const expiryClass = computed(() => {
  const days = currentDaysUntilExpiry.value
  if (days === null) return 'text-muted-foreground'
  if (days > 30) return 'text-green-600 dark:text-green-400'
  if (days > 7) return 'text-yellow-600 dark:text-yellow-400'
  return 'text-destructive'
})

const chartData = computed<ChartData<'line'>>(() => {
  if (!trendData.value) {
    return {
      labels: [],
      datasets: []
    }
  }

  return {
    labels: trendData.value.dates,
    datasets: [
      {
        label: 'Days Until Expiration',
        data: trendData.value.days_until_expiry,
        borderColor: 'hsl(var(--primary))',
        backgroundColor: 'hsl(var(--primary) / 0.1)',
        tension: 0.3,
      }
    ]
  }
})

const chartOptions: ChartOptions<'line'> = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        color: 'hsl(var(--muted-foreground))',
      },
      grid: {
        color: 'hsl(var(--border))',
      }
    },
    x: {
      display: false
    }
  }
}

async function loadTrend() {
  loading.value = true

  try {
    const response = await fetch(`/api/monitors/${props.monitorId}/trends?period=90d`)
    const data = await response.json()
    trendData.value = data.ssl_expiration_trend
  } catch (error) {
    console.error('Failed to load SSL trend:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadTrend()
})
</script>

<template>
  <div class="glass-card-strong p-6">
    <h3 class="text-lg font-semibold text-foreground mb-4">
      SSL Certificate Expiration
    </h3>

    <div v-if="loading" class="text-center py-8">
      <div class="text-muted-foreground">Loading SSL data...</div>
    </div>

    <div v-else-if="currentDaysUntilExpiry !== null" class="space-y-4">
      <!-- Days Until Expiry -->
      <div class="text-center">
        <div :class="['text-4xl font-bold', expiryClass]">
          {{ currentDaysUntilExpiry }} days
        </div>
        <div class="text-sm text-muted-foreground mt-1">Until Expiration</div>
      </div>

      <!-- Trend Chart -->
      <div class="h-32">
        <Line :data="chartData" :options="chartOptions" />
      </div>

      <!-- Status Message -->
      <div class="text-center text-sm">
        <span v-if="currentDaysUntilExpiry > 30" class="text-green-600 dark:text-green-400">
          Certificate is valid
        </span>
        <span v-else-if="currentDaysUntilExpiry > 7" class="text-yellow-600 dark:text-yellow-400">
          Certificate expiring soon
        </span>
        <span v-else class="text-destructive">
          Certificate expires very soon!
        </span>
      </div>
    </div>
  </div>
</template>
```

### Step 8: Install Chart.js Dependencies

```bash
./vendor/bin/sail npm install chart.js vue-chartjs
```

### Step 9: Integrate Components into Dashboard

Add to website detail page (e.g., `resources/js/Pages/Websites/Show.vue`):

```vue
<script setup lang="ts">
import MonitoringHistoryChart from '@/Components/Monitoring/MonitoringHistoryChart.vue'
import UptimeTrendCard from '@/Components/Monitoring/UptimeTrendCard.vue'
import RecentChecksTimeline from '@/Components/Monitoring/RecentChecksTimeline.vue'
import SslExpirationTrendCard from '@/Components/Monitoring/SslExpirationTrendCard.vue'

// ... existing code ...
</script>

<template>
  <DashboardLayout>
    <!-- Existing website details -->

    <!-- Historical Data Section -->
    <section class="mt-8">
      <h2 class="text-2xl font-bold text-foreground mb-6">Historical Data</h2>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Uptime Trend -->
        <UptimeTrendCard
          :monitor-id="monitor.id"
          period="7d"
        />

        <!-- SSL Expiration Trend -->
        <SslExpirationTrendCard
          :monitor-id="monitor.id"
        />

        <!-- Recent Checks Timeline -->
        <RecentChecksTimeline
          :monitor-id="monitor.id"
          :limit="10"
        />
      </div>

      <!-- Response Time Chart -->
      <div class="glass-card-strong p-6">
        <h3 class="text-lg font-semibold text-foreground mb-4">
          Response Time Trend (7 Days)
        </h3>
        <MonitoringHistoryChart
          :monitor-id="monitor.id"
          period="7d"
          :height="300"
        />
      </div>
    </section>
  </DashboardLayout>
</template>
```

### Step 10: Write Service Tests

Create `tests/Feature/Services/MonitoringHistoryServiceTest.php`:

```php
<?php

use App\Models\Monitor;
use App\Models\MonitoringResult;
use App\Services\MonitoringHistoryService;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
    $this->service = app(MonitoringHistoryService::class);
    $this->monitor = Monitor::first();
});

test('getTrendData returns chart data for specified period', function () {
    // Create test data
    for ($i = 0; $i < 5; $i++) {
        MonitoringResult::factory()->create([
            'monitor_id' => $this->monitor->id,
            'started_at' => now()->subDays($i),
            'status' => 'success',
            'response_time_ms' => 100 + ($i * 10),
        ]);
    }

    $result = $this->service->getTrendData($this->monitor, '7d');

    expect($result)->toHaveKeys(['labels', 'datasets']);
    expect($result['datasets'])->toHaveCount(2);
    expect($result['datasets'][0]['label'])->toBe('Response Time (ms)');
});

test('getRecentHistory returns limited results', function () {
    MonitoringResult::factory()->count(100)->create([
        'monitor_id' => $this->monitor->id,
    ]);

    $history = $this->service->getRecentHistory($this->monitor, 50);

    expect($history)->toHaveCount(50);
    expect($history->first()->started_at)->toBeGreaterThan($history->last()->started_at);
});

test('getSummaryStats calculates correct statistics', function () {
    MonitoringResult::factory()->count(10)->create([
        'monitor_id' => $this->monitor->id,
        'status' => 'success',
        'response_time_ms' => 100,
    ]);

    MonitoringResult::factory()->count(2)->create([
        'monitor_id' => $this->monitor->id,
        'status' => 'error',
        'response_time_ms' => 150,
    ]);

    $stats = $this->service->getSummaryStats($this->monitor, '30d');

    expect($stats['total_checks'])->toBe(12);
    expect($stats['success_count'])->toBe(10);
    expect($stats['failure_count'])->toBe(2);
    expect($stats['uptime_percentage'])->toBeGreaterThan(80);
});

test('getUptimePercentage calculates correctly', function () {
    MonitoringResult::factory()->count(90)->create([
        'monitor_id' => $this->monitor->id,
        'uptime_status' => 'up',
    ]);

    MonitoringResult::factory()->count(10)->create([
        'monitor_id' => $this->monitor->id,
        'uptime_status' => 'down',
    ]);

    $percentage = $this->service->getUptimePercentage($this->monitor, '30d');

    expect($percentage)->toBe(90.0);
});

test('getResponseTimeTrend returns time series data', function () {
    for ($i = 0; $i < 10; $i++) {
        MonitoringResult::factory()->create([
            'monitor_id' => $this->monitor->id,
            'started_at' => now()->subHours($i),
            'response_time_ms' => 100 + $i,
        ]);
    }

    $trend = $this->service->getResponseTimeTrend($this->monitor, '7d');

    expect($trend)->toHaveKeys(['timestamps', 'response_times']);
    expect($trend['timestamps'])->toHaveCount(10);
    expect($trend['response_times'])->toHaveCount(10);
});

test('getSslExpirationTrend returns expiry data', function () {
    for ($i = 0; $i < 5; $i++) {
        MonitoringResult::factory()->create([
            'monitor_id' => $this->monitor->id,
            'started_at' => now()->subDays($i * 10),
            'days_until_expiration' => 90 - ($i * 10),
        ]);
    }

    $trend = $this->service->getSslExpirationTrend($this->monitor);

    expect($trend)->toHaveKeys(['dates', 'days_until_expiry']);
    expect($trend['dates'])->toHaveCount(5);
    expect($trend['days_until_expiry'])->toContain(90, 80, 70, 60, 50);
});
```

### Step 11: Write API Endpoint Tests

Create `tests/Feature/API/MonitorHistoryApiTest.php`:

```php
<?php

use App\Models\Monitor;
use App\Models\MonitoringResult;
use App\Models\User;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
    $this->user = User::first();
    $this->monitor = Monitor::first();
});

test('history endpoint returns recent checks', function () {
    MonitoringResult::factory()->count(25)->create([
        'monitor_id' => $this->monitor->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/monitors/{$this->monitor->id}/history?limit=10");

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            '*' => ['id', 'started_at', 'status', 'trigger_type']
        ]
    ]);
    expect($response->json('data'))->toHaveCount(10);
});

test('trends endpoint returns chart data', function () {
    MonitoringResult::factory()->count(10)->create([
        'monitor_id' => $this->monitor->id,
        'response_time_ms' => 100,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/monitors/{$this->monitor->id}/trends?period=7d");

    $response->assertOk();
    $response->assertJsonStructure([
        'trend_data' => ['labels', 'datasets'],
        'response_time_trend' => ['timestamps', 'response_times'],
        'ssl_expiration_trend' => ['dates', 'days_until_expiry'],
    ]);
});

test('summary endpoint returns statistics', function () {
    MonitoringResult::factory()->count(10)->create([
        'monitor_id' => $this->monitor->id,
        'status' => 'success',
    ]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/monitors/{$this->monitor->id}/summary?period=30d");

    $response->assertOk();
    $response->assertJsonStructure([
        'period',
        'total_checks',
        'success_count',
        'failure_count',
        'avg_response_time',
        'uptime_percentage',
    ]);
});

test('API endpoints require authentication', function () {
    $response = $this->getJson("/api/monitors/{$this->monitor->id}/history");
    $response->assertUnauthorized();

    $response = $this->getJson("/api/monitors/{$this->monitor->id}/trends");
    $response->assertUnauthorized();

    $response = $this->getJson("/api/monitors/{$this->monitor->id}/summary");
    $response->assertUnauthorized();
});
```

### Step 12: Create MonitoringResult Factory

Update `database/factories/MonitoringResultFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Monitor;
use App\Models\Website;
use Illuminate\Database\Eloquent\Factories\Factory;

class MonitoringResultFactory extends Factory
{
    public function definition(): array
    {
        return [
            'monitor_id' => Monitor::factory(),
            'website_id' => Website::factory(),
            'check_type' => $this->faker->randomElement(['uptime', 'ssl_certificate', 'both']),
            'trigger_type' => $this->faker->randomElement(['scheduled', 'manual_immediate']),
            'triggered_by_user_id' => null,

            'started_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'completed_at' => fn($attrs) => $attrs['started_at']->modify('+2 seconds'),
            'duration_ms' => $this->faker->numberBetween(500, 3000),

            'status' => $this->faker->randomElement(['success', 'error']),
            'error_message' => null,

            'uptime_status' => $this->faker->randomElement(['up', 'down', null]),
            'http_status_code' => $this->faker->randomElement([200, 301, 404, 500, null]),
            'response_time_ms' => $this->faker->numberBetween(50, 500),

            'ssl_status' => $this->faker->randomElement(['valid', 'invalid', 'expiring', null]),
            'days_until_expiration' => $this->faker->numberBetween(1, 365),

            'check_interval_minutes' => 5,
        ];
    }

    public function success(): self
    {
        return $this->state([
            'status' => 'success',
            'uptime_status' => 'up',
            'http_status_code' => 200,
            'ssl_status' => 'valid',
        ]);
    }

    public function failed(): self
    {
        return $this->state([
            'status' => 'error',
            'error_message' => $this->faker->sentence(),
        ]);
    }
}
```

### Step 13: Run Tests

```bash
# Run historical data tests
./vendor/bin/sail artisan test --filter=MonitoringHistory --parallel

# Run service tests
./vendor/bin/sail artisan test --filter=MonitoringHistoryService --parallel

# Run API tests
./vendor/bin/sail artisan test --filter=MonitorHistoryApi --parallel

# Run full test suite
time ./vendor/bin/sail artisan test --parallel

# MUST meet requirements:
# - All tests passing
# - Execution time < 20 seconds
```

### Step 14: Build Frontend Assets

```bash
./vendor/bin/sail npm run build
```

### Step 15: Verify Dashboard Performance

```bash
# Test dashboard load time (must be < 2 seconds)
time curl http://localhost/dashboard

# Test API endpoints
curl http://localhost/api/monitors/1/history
curl http://localhost/api/monitors/1/trends?period=7d
curl http://localhost/api/monitors/1/summary?period=30d
```

## ✅ Phase 3 Completion Checklist

Before marking Phase 3 complete, verify:

- [ ] MonitoringHistoryService created with 6 methods
- [ ] All service methods tested and working
- [ ] 3 API endpoints created (history, trends, summary)
- [ ] API routes registered and tested
- [ ] API endpoints require authentication
- [ ] MonitoringHistoryChart.vue component created
- [ ] UptimeTrendCard.vue component created
- [ ] RecentChecksTimeline.vue component created
- [ ] SslExpirationTrendCard.vue component created
- [ ] Chart.js dependencies installed
- [ ] Components integrated into dashboard
- [ ] Components use semantic color classes (no hardcoded colors)
- [ ] Components work in both light and dark modes
- [ ] Service tests created and passing
- [ ] API endpoint tests created and passing
- [ ] MonitoringResultFactory updated
- [ ] Full test suite passing (all 560+ tests)
- [ ] Test execution time < 20 seconds
- [ ] Dashboard loads in < 2 seconds
- [ ] All components styled with glass-card-strong
- [ ] TypeScript interfaces defined for all props
- [ ] No console errors in browser

## 📊 Success Criteria

**Service Layer**:
- ✅ MonitoringHistoryService with 6 methods
- ✅ All methods use proper query optimization
- ✅ Period matching works correctly
- ✅ Data aggregation accurate

**API Layer**:
- ✅ 3 endpoints authenticated
- ✅ JSON responses properly formatted
- ✅ Error handling implemented
- ✅ Request validation working

**Frontend**:
- ✅ 4 Vue components created
- ✅ Charts render correctly
- ✅ Data loads asynchronously
- ✅ Loading states implemented
- ✅ Semantic classes used throughout
- ✅ Dark mode support

**Performance**:
- ✅ Dashboard loads < 2s
- ✅ API responses < 500ms
- ✅ Charts render smoothly
- ✅ No N+1 query issues

**Testing**:
- ✅ Service tests comprehensive
- ✅ API tests cover auth and responses
- ✅ All tests passing
- ✅ Performance maintained (< 20s)

## 🚀 After Phase 3 Completion

Once Phase 3 is complete and verified:

1. **Commit your work**:
```bash
git add -A
git commit -m "feat: implement Phase 3 - dashboard integration for historical data

- Create MonitoringHistoryService with 6 core methods
- Add 3 API endpoints (history, trends, summary)
- Create 4 Vue components with Chart.js integration
  - MonitoringHistoryChart: Response time line chart
  - UptimeTrendCard: 7-day uptime statistics
  - RecentChecksTimeline: Recent check history
  - SslExpirationTrendCard: SSL expiration countdown
- Integrate components into dashboard
- Add comprehensive service and API tests
- Dashboard loads in < 2s, all semantic styling
- All tests passing in < 20s

Phase 3 of historical data tracking complete.
Historical data now visualized with trends and charts."
```

2. **Verify production readiness**:
   - Check dashboard performance (< 2s)
   - Verify all charts render correctly
   - Test API endpoints return correct data
   - Verify dark mode works correctly
   - Test with real monitoring data

3. **Proceed to Phase 4** using `docs/PHASE4_IMPLEMENTATION_PROMPT.md` (to be created)

## ⚠️ Common Issues & Solutions

**Issue**: Charts not rendering
**Solution**:
- Verify Chart.js is installed: `npm list chart.js vue-chartjs`
- Check browser console for errors
- Ensure API endpoints return correct data format

**Issue**: API endpoints return 401 Unauthorized
**Solution**: Ensure routes are wrapped in `auth` middleware

**Issue**: Dashboard slow to load (> 2s)
**Solution**:
- Check for N+1 queries in MonitoringHistoryService
- Add database indexes if needed
- Verify eager loading is used

**Issue**: Charts show no data
**Solution**:
- Verify MonitoringResult records exist
- Check API endpoint responses in Network tab
- Ensure correct monitor ID is passed to components

**Issue**: Dark mode colors incorrect
**Solution**:
- Use semantic classes: `text-foreground`, `bg-card`, etc.
- Never use hardcoded colors or `dark:` prefixes
- Check `docs/TAILWIND_V4_STYLING_GUIDE.md`

**Issue**: Test suite slows down
**Solution**:
- Use factories, not real data creation
- Mock external services
- Check for unnecessary database queries in tests

## 📚 Reference Materials

During implementation, refer to:
- `docs/HISTORICAL_DATA_MASTER_PLAN.md` - Complete implementation guide
- `docs/TESTING_INSIGHTS.md` - Testing patterns
- `docs/TAILWIND_V4_STYLING_GUIDE.md` - Styling guidelines
- `docs/CODING_GUIDE.md` - Vue/TypeScript patterns
- `docs/AGENT_USAGE_GUIDE.md` - Agent usage patterns
- Laravel Service Documentation: https://laravel.com/docs/12.x/container
- Chart.js Documentation: https://www.chartjs.org/docs/latest/
- Vue 3 Composition API: https://vuejs.org/guide/extras/composition-api-faq.html

## 🎯 Ready to Start?

Copy this entire prompt and use it to begin Phase 3 implementation. Follow each step carefully and verify at each checkpoint.

**Estimated Time**: 6-8 hours for complete Phase 3 implementation

**Next Phase**: After Phase 3 completion, Phase 4 will implement advanced features including aggregations, alert correlation, and data retention policies.

---

Good luck with Phase 3! 📊
