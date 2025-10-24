<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import {
  TrendingUp,
  TrendingDown,
  CheckCircle,
  XCircle,
  Clock,
  Activity,
  Zap
} from 'lucide-vue-next';

// Props interface
interface Props {
  monitorId: number;
  period?: '1h' | '24h' | '7d' | '30d' | '90d';
}

const props = withDefaults(defineProps<Props>(), {
  period: '24h'
});

// Component state
const loading = ref(true);
const error = ref<string | null>(null);
const uptimeData = ref<any>(null);
const retryCount = ref(0);
const maxRetries = 3;

// Computed uptime percentage with status color
const uptimePercentage = computed(() => {
  return uptimeData.value?.uptime_percentage || 0;
});

// Status color based on uptime percentage
const statusColor = computed(() => {
  const uptime = uptimePercentage.value;
  if (uptime >= 99) return 'success';
  if (uptime >= 95) return 'warning';
  return 'error';
});

// Status badge classes
const statusBadgeClasses = computed(() => {
  const base = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border';

  switch (statusColor.value) {
    case 'success':
      return `${base} bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800`;
    case 'warning':
      return `${base} bg-yellow-50 text-yellow-700 border-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-400 dark:border-yellow-800`;
    case 'error':
      return `${base} bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800`;
    default:
      return `${base} bg-muted text-muted-foreground border-border`;
  }
});

// Status icon
const statusIcon = computed(() => {
  switch (statusColor.value) {
    case 'success':
      return CheckCircle;
    case 'warning':
      return AlertTriangle;
    case 'error':
      return XCircle;
    default:
      return Activity;
  }
});

// Status icon color
const statusIconColor = computed(() => {
  switch (statusColor.value) {
    case 'success':
      return 'text-green-600 dark:text-green-400';
    case 'warning':
      return 'text-yellow-600 dark:text-yellow-400';
    case 'error':
      return 'text-destructive dark:text-red-400';
    default:
      return 'text-muted-foreground';
  }
});

// Uptime trend direction
const uptimeTrend = computed(() => {
  if (!uptimeData.value?.previous_uptime_percentage) return 'neutral';

  const current = uptimePercentage.value;
  const previous = uptimeData.value.previous_uptime_percentage;

  if (current > previous + 0.5) return 'up';
  if (current < previous - 0.5) return 'down';
  return 'stable';
});

// Trend icon
const trendIcon = computed(() => {
  switch (uptimeTrend.value) {
    case 'up':
      return TrendingUp;
    case 'down':
      return TrendingDown;
    default:
      return Activity;
  }
});

// Trend color
const trendColor = computed(() => {
  switch (uptimeTrend.value) {
    case 'up':
      return 'text-green-600 dark:text-green-400';
    case 'down':
      return 'text-destructive dark:text-red-400';
    default:
      return 'text-muted-foreground';
  }
});

// Fetch uptime data from API
const fetchUptimeData = async () => {
  loading.value = true;
  error.value = null;

  try {
    const response = await fetch(`/api/monitors/${props.monitorId}/uptime-stats?period=${props.period}`);

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();
    uptimeData.value = data;
    retryCount.value = 0;
  } catch (err) {
    console.error('Failed to fetch uptime statistics:', err);

    if (retryCount.value < maxRetries) {
      retryCount.value++;
      setTimeout(() => {
        fetchUptimeData();
      }, 1000 * retryCount.value);
    } else {
      error.value = err instanceof Error ? err.message : 'Failed to load uptime data';
    }
  } finally {
    loading.value = false;
  }
};

// Retry loading data
const retryLoading = () => {
  retryCount.value = 0;
  fetchUptimeData();
};

// Format response time
const formatResponseTime = (ms: number | null): string => {
  if (!ms) return 'N/A';
  if (ms < 1000) return `${Math.round(ms)}ms`;
  return `${(ms / 1000).toFixed(2)}s`;
};

// Watch for prop changes
watch([() => props.monitorId, () => props.period], () => {
  fetchUptimeData();
}, { immediate: false });

// Load data on mount
onMounted(() => {
  fetchUptimeData();
});

// Import missing icon
import { AlertTriangle } from 'lucide-vue-next';
</script>

<template>
  <div class="glass-card-strong p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center space-x-3">
        <div class="rounded-lg bg-primary/10 p-2">
          <Activity class="h-5 w-5 text-primary" />
        </div>
        <div>
          <h3 class="text-lg font-semibold text-foreground">Uptime Statistics</h3>
          <p class="text-sm text-muted-foreground">
            Monitor #{{ monitorId }} •
            <span class="capitalize">{{ period }}</span>
          </p>
        </div>
      </div>

      <!-- Status Badge -->
      <div :class="statusBadgeClasses">
        <component :is="statusIcon" class="h-3 w-3 mr-1" :class="statusIconColor" />
        {{ statusColor === 'success' ? 'Healthy' : statusColor === 'warning' ? 'Warning' : 'Critical' }}
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="space-y-4">
      <div class="animate-pulse">
        <div class="h-8 bg-muted rounded mb-4"></div>
        <div class="grid grid-cols-2 gap-4">
          <div class="h-12 bg-muted rounded"></div>
          <div class="h-12 bg-muted rounded"></div>
        </div>
        <div class="grid grid-cols-2 gap-4 mt-4">
          <div class="h-12 bg-muted rounded"></div>
          <div class="h-12 bg-muted rounded"></div>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="text-center py-8">
      <div class="rounded-lg bg-destructive/10 p-3 mb-3 inline-block">
        <XCircle class="h-6 w-6 text-destructive" />
      </div>
      <p class="text-sm text-muted-foreground mb-3">{{ error }}</p>
      <button
        @click="retryLoading"
        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-primary bg-primary/10 hover:bg-primary/20 rounded-md transition-colors"
      >
        <RefreshCw class="h-3 w-3 mr-1.5" />
        Retry
      </button>
    </div>

    <!-- Uptime Data -->
    <div v-else-if="uptimeData" class="space-y-6">
      <!-- Main Uptime Percentage -->
      <div class="text-center">
        <div class="flex items-center justify-center space-x-2 mb-2">
          <component
            :is="trendIcon"
            class="h-4 w-4"
            :class="trendColor"
          />
          <span class="text-sm font-medium" :class="trendColor">
            {{ uptimeTrend === 'up' ? 'Improved' : uptimeTrend === 'down' ? 'Declined' : 'Stable' }}
          </span>
        </div>
        <div class="text-4xl font-bold" :class="{
          'text-green-600 dark:text-green-400': statusColor === 'success',
          'text-yellow-600 dark:text-yellow-400': statusColor === 'warning',
          'text-destructive dark:text-red-400': statusColor === 'error'
        }">
          {{ uptimePercentage.toFixed(2) }}%
        </div>
        <p class="text-sm text-muted-foreground mt-1">Uptime</p>
      </div>

      <!-- Statistics Grid -->
      <div class="grid grid-cols-2 gap-4">
        <!-- Total Checks -->
        <div class="bg-muted/50 rounded-lg p-4 text-center">
          <div class="flex items-center justify-center mb-2">
            <Clock class="h-4 w-4 text-muted-foreground" />
          </div>
          <div class="text-2xl font-semibold text-foreground">
            {{ uptimeData.total_checks || 0 }}
          </div>
          <p class="text-xs text-muted-foreground mt-1">Total Checks</p>
        </div>

        <!-- Successful Checks -->
        <div class="bg-muted/50 rounded-lg p-4 text-center">
          <div class="flex items-center justify-center mb-2">
            <CheckCircle class="h-4 w-4 text-green-600 dark:text-green-400" />
          </div>
          <div class="text-2xl font-semibold text-green-600 dark:text-green-400">
            {{ uptimeData.successful_checks || 0 }}
          </div>
          <p class="text-xs text-muted-foreground mt-1">Successful</p>
        </div>

        <!-- Failed Checks -->
        <div class="bg-muted/50 rounded-lg p-4 text-center">
          <div class="flex items-center justify-center mb-2">
            <XCircle class="h-4 w-4 text-destructive" />
          </div>
          <div class="text-2xl font-semibold text-destructive">
            {{ uptimeData.failed_checks || 0 }}
          </div>
          <p class="text-xs text-muted-foreground mt-1">Failed</p>
        </div>

        <!-- Average Response Time -->
        <div class="bg-muted/50 rounded-lg p-4 text-center">
          <div class="flex items-center justify-center mb-2">
            <Zap class="h-4 w-4 text-primary" />
          </div>
          <div class="text-2xl font-semibold text-foreground">
            {{ formatResponseTime(uptimeData.avg_response_time) }}
          </div>
          <p class="text-xs text-muted-foreground mt-1">Avg Response</p>
        </div>
      </div>

      <!-- Additional Stats -->
      <div class="pt-4 border-t border-border">
        <div class="grid grid-cols-3 gap-4 text-sm">
          <div class="text-center">
            <p class="text-muted-foreground">Success Rate</p>
            <p class="font-semibold text-foreground">
              {{ uptimeData.success_rate ? `${uptimeData.success_rate.toFixed(1)}%` : 'N/A' }}
            </p>
          </div>
          <div class="text-center">
            <p class="text-muted-foreground">Last Check</p>
            <p class="font-semibold text-foreground">
              {{ uptimeData.last_check_status || 'Unknown' }}
            </p>
          </div>
          <div class="text-center">
            <p class="text-muted-foreground">Period</p>
            <p class="font-semibold text-foreground capitalize">
              {{ period }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-8">
      <div class="rounded-lg bg-muted p-3 mb-3 inline-block">
        <Activity class="h-6 w-6 text-muted-foreground" />
      </div>
      <p class="text-sm text-muted-foreground">No uptime data available</p>
    </div>
  </div>
</template>