<script setup lang="ts">
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import {
  CheckCircle,
  XCircle,
  AlertTriangle,
  Clock,
  RefreshCw,
  User,
  Calendar,
  Activity,
  Wifi,
  Shield,
  Loader2
} from 'lucide-vue-next';

// Props interface
interface Props {
  monitorId: number;
  limit?: number;
  autoRefresh?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  limit: 50,
  autoRefresh: true
});

// TypeScript interfaces
interface RecentCheckResponse {
  data: RecentCheck[];
  meta: {
    total: number;
    per_page: number;
    current_page: number;
    has_more: boolean;
  };
}

interface RecentCheck {
  id: string;
  uuid: string;
  check_type: string;
  status: string;
  started_at: string;
  response_time_ms?: number;
  uptime_status?: string;
  http_status_code?: number;
  ssl_status?: string;
  days_until_expiration?: number;
  error_message?: string;
  trigger_type?: string;
  triggered_by_user?: {
    id: number;
    name: string;
  };
}

// Component state
const loading = ref(true);
const error = ref<string | null>(null);
const checks = ref<RecentCheck[]>([]);
const hasMore = ref(false);
const loadingMore = ref(false);
const retryCount = ref(0);
const maxRetries = 3;
const refreshInterval = ref<NodeJS.Timeout | null>(null);
const timelineRef = ref<HTMLElement | null>(null);

// Computed property to ensure checks is always an array
const safeChecks = computed(() => {
  // Ensure checks.value is always an array, even if it's undefined or null
  if (!Array.isArray(checks.value)) {
    return [];
  }
  return checks.value;
});

// Status type interface
interface CheckStatus {
  type: 'success' | 'error' | 'warning';
  color: string;
  icon: any;
  bgColor: string;
  borderColor: string;
}

// Get status configuration
const getStatusConfig = (status: string | undefined | null): CheckStatus => {
  // Handle null, undefined, or empty status
  if (!status || typeof status !== 'string') {
    return {
      type: 'warning',
      color: 'text-muted-foreground',
      icon: Activity,
      bgColor: 'bg-muted',
      borderColor: 'border-border'
    };
  }

  switch (status.toLowerCase()) {
    case 'up':
    case 'valid':
    case 'success':
      return {
        type: 'success',
        color: 'status-badge-success',
        icon: CheckCircle,
        bgColor: 'bg-emerald-50',
        borderColor: 'border-emerald-200'
      };
    case 'down':
    case 'expired':
    case 'invalid':
    case 'error':
      return {
        type: 'error',
        color: 'status-badge-error',
        icon: XCircle,
        bgColor: 'bg-red-50',
        borderColor: 'border-red-200'
      };
    case 'warning':
    case 'slow':
    case 'content_mismatch':
      return {
        type: 'warning',
        color: 'status-badge-warning',
        icon: AlertTriangle,
        bgColor: 'bg-amber-50',
        borderColor: 'border-amber-200'
      };
    default:
      return {
        type: 'warning',
        color: 'text-muted-foreground',
        icon: Activity,
        bgColor: 'bg-muted',
        borderColor: 'border-border'
      };
  }
};

// Get check type icon
const getCheckTypeIcon = (checkType: string | undefined | null) => {
  if (!checkType || typeof checkType !== 'string') {
    return Activity;
  }

  switch (checkType.toLowerCase()) {
    case 'ssl':
    case 'certificate':
      return Shield;
    case 'uptime':
    case 'http':
      return Wifi;
    default:
      return Activity;
  }
};

// Format timestamp to relative time
const formatTimeAgo = (timestamp: string | undefined | null): string => {
  if (!timestamp) {
    return 'Unknown time';
  }

  const date = new Date(timestamp);

  // Check if date is invalid
  if (isNaN(date.getTime())) {
    return 'Invalid time';
  }

  const now = new Date();
  const diffInMs = now.getTime() - date.getTime();
  const diffInSeconds = Math.floor(diffInMs / 1000);

  if (diffInSeconds < 60) return 'Just now';
  if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
  if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
  return `${Math.floor(diffInSeconds / 86400)}d ago`;
};

// Format full timestamp
const formatFullTime = (timestamp: string | undefined | null): string => {
  if (!timestamp) {
    return 'Unknown time';
  }

  const date = new Date(timestamp);
  if (isNaN(date.getTime())) {
    return 'Invalid time';
  }

  return date.toLocaleString();
};

// Format response time
const formatResponseTime = (ms: number | null): string => {
  if (!ms) return 'N/A';
  if (ms < 1000) return `${ms}ms`;
  return `${(ms / 1000).toFixed(2)}s`;
};

// Fetch recent checks from API
const fetchChecks = async (append = false) => {
  if (append) {
    loadingMore.value = true;
  } else {
    loading.value = true;
    error.value = null;
    // Initialize checks as empty array to prevent undefined errors
    if (!checks.value) {
      checks.value = [];
    }
  }

  try {
    // Ensure checks is always an array before calculating offset
    const currentLength = Array.isArray(checks.value) ? checks.value.length : 0;
    const offset = append ? currentLength : 0;
    const limit = props.limit;
    const response = await fetch(
      `/api/monitors/${props.monitorId}/recent-checks?limit=${limit}&offset=${offset}`
    );

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data: RecentCheckResponse = await response.json();

    // Debug logging to help identify issues
    console.debug('Recent checks API response:', {
      monitorId: props.monitorId,
      responseData: data,
      hasData: !!data?.data,
      dataLength: data?.data?.length,
      hasMore: data?.meta?.has_more,
      isArray: Array.isArray(data?.data)
    });

    // Ensure data.data is an array before using it
    const newData = Array.isArray(data?.data) ? data.data : [];

    if (append) {
      // Ensure current checks is an array before spreading
      const currentChecks = Array.isArray(checks.value) ? checks.value : [];
      checks.value = [...currentChecks, ...newData];
    } else {
      checks.value = newData;
    }

    hasMore.value = data?.meta?.has_more || false;
    retryCount.value = 0;

    // Scroll to bottom if appending
    if (append && timelineRef.value) {
      await nextTick();
      timelineRef.value.scrollTop = timelineRef.value.scrollHeight;
    }
  } catch (err) {
    console.error('Failed to fetch recent checks:', err);

    if (retryCount.value < maxRetries) {
      retryCount.value++;
      setTimeout(() => {
        fetchChecks(append);
      }, 1000 * retryCount.value);
    } else {
      error.value = err instanceof Error ? err.message : 'Failed to load recent checks';
      // Ensure checks is always an array to prevent length errors
      checks.value = [];
    }
  } finally {
    if (append) {
      loadingMore.value = false;
    } else {
      loading.value = false;
    }
  }
};

// Load more checks
const loadMore = () => {
  if (hasMore.value && !loadingMore.value) {
    fetchChecks(true);
  }
};

// Retry loading data
const retryLoading = () => {
  retryCount.value = 0;
  fetchChecks();
};

// Start auto refresh
const startAutoRefresh = () => {
  if (props.autoRefresh && !refreshInterval.value) {
    refreshInterval.value = setInterval(() => {
      // Only refresh if not currently loading or loading more
      if (!loading.value && !loadingMore.value) {
        fetchChecks();
      }
    }, 30000); // Refresh every 30 seconds
  }
};

// Stop auto refresh
const stopAutoRefresh = () => {
  if (refreshInterval.value) {
    clearInterval(refreshInterval.value);
    refreshInterval.value = null;
  }
};

// Watch for prop changes
watch(() => props.monitorId, () => {
  // Reset and ensure checks is always an array
  checks.value = [];
  error.value = null;
  loading.value = true;
  fetchChecks();
}, { immediate: false });

// Watch for auto refresh changes
watch(() => props.autoRefresh, (newValue) => {
  if (newValue) {
    startAutoRefresh();
  } else {
    stopAutoRefresh();
  }
});

// Load data on mount
onMounted(() => {
  fetchChecks();
  if (props.autoRefresh) {
    startAutoRefresh();
  }
});

// Cleanup on unmount
import { onUnmounted } from 'vue';
onUnmounted(() => {
  stopAutoRefresh();
});
</script>

<template>
  <div class="glass-card-strong p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center space-x-3">
        <div class="rounded-lg bg-primary/10 p-2">
          <Clock class="h-5 w-5 text-primary" />
        </div>
        <div>
          <h3 class="text-lg font-semibold text-foreground">Recent Checks</h3>
          <p class="text-sm text-muted-foreground">
            Monitor #{{ monitorId }} • Latest {{ limit }} checks
          </p>
        </div>
      </div>

      <!-- Refresh Button -->
      <button
        @click="fetchChecks"
        :disabled="loading"
        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-muted-foreground hover:text-foreground bg-muted hover:bg-muted/80 rounded-md transition-colors disabled:opacity-50"
      >
        <RefreshCw :class="{ 'animate-spin': loading }" class="h-3 w-3 mr-1.5" />
        Refresh
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading && safeChecks.length === 0" class="flex items-center justify-center py-12">
      <div class="flex items-center space-x-3">
        <Loader2 class="h-5 w-5 animate-spin text-muted-foreground" />
        <span class="text-sm text-muted-foreground">Loading recent checks...</span>
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

    <!-- Timeline -->
    <div v-else class="relative">
      <!-- Timeline Container -->
      <div
        ref="timelineRef"
        class="space-y-4 max-h-96 overflow-y-auto pr-2 custom-scrollbar"
      >
        <!-- Timeline Items -->
        <div
          v-for="(check, index) in safeChecks"
          :key="`check-${check.id || check.uuid || index}`"
          class="relative flex items-start space-x-3 p-3 rounded-lg hover:bg-muted/50 transition-colors border border-transparent hover:border-border"
        >
          <!-- Status Icon -->
          <div class="flex-shrink-0 mt-1">
            <div
              class="rounded-full p-1.5 border-2"
              :class="[
                getStatusConfig(check.status).bgColor,
                getStatusConfig(check.status).borderColor
              ]"
            >
              <component
                :is="getStatusConfig(check.status).icon"
                class="h-4 w-4"
                :class="getStatusConfig(check.status).color"
              />
            </div>
          </div>

          <!-- Timeline Line -->
          <div
            v-if="index < safeChecks.length - 1"
            class="absolute left-6 top-8 w-0.5 h-12 bg-border"
            style="left: 1.5rem;"
          ></div>

          <!-- Check Details -->
          <div class="flex-1 min-w-0">
            <!-- Header Row -->
            <div class="flex items-center justify-between mb-2">
              <div class="flex items-center space-x-2">
                <!-- Check Type Icon -->
                <component
                  :is="getCheckTypeIcon(check.check_type)"
                  class="h-4 w-4 text-muted-foreground"
                />
                <!-- Status -->
                <span class="text-sm font-medium text-foreground capitalize">
                  {{ check.status || 'Unknown' }}
                </span>
                <!-- Timestamp -->
                <span
                  class="text-xs text-muted-foreground cursor-help"
                  :title="formatFullTime(check.started_at)"
                >
                  {{ formatTimeAgo(check.started_at) }}
                </span>
              </div>

              <!-- Response Time -->
              <div
                v-if="check.response_time_ms !== null && check.response_time_ms !== undefined"
                class="text-xs font-medium"
                :class="{
                  'status-badge-success': check.response_time_ms < 1000,
                  'status-badge-warning': check.response_time_ms >= 1000 && check.response_time_ms < 3000,
                  'status-badge-error': check.response_time_ms >= 3000
                }"
              >
                {{ formatResponseTime(check.response_time_ms) }}
              </div>
            </div>

            <!-- Additional Details -->
            <div class="text-xs text-muted-foreground space-y-1">
              <!-- Trigger Type -->
              <div v-if="check.trigger_type" class="flex items-center space-x-1">
                <Calendar class="h-3 w-3" />
                <span>Trigger: {{ check.trigger_type }}</span>
              </div>

              <!-- User Info -->
              <div v-if="check.triggered_by_user" class="flex items-center space-x-1">
                <User class="h-3 w-3" />
                <span>{{ check.triggered_by_user.name }}</span>
              </div>

              <!-- Error Message -->
              <div v-if="check.error_message" class="status-badge-error">
                Error: {{ check.error_message }}
              </div>
            </div>
          </div>
        </div>

        <!-- Load More Button -->
        <div v-if="hasMore" class="text-center py-4">
          <button
            @click="loadMore"
            :disabled="loadingMore"
            class="inline-flex items-center px-4 py-2 text-sm font-medium text-primary bg-primary/10 hover:bg-primary/20 rounded-md transition-colors disabled:opacity-50"
          >
            <Loader2 v-if="loadingMore" class="h-4 w-4 mr-2 animate-spin" />
            {{ loadingMore ? 'Loading...' : 'Load More' }}
          </button>
        </div>

        <!-- No More Results -->
        <div v-else-if="safeChecks.length > 0" class="text-center py-4">
          <p class="text-xs text-muted-foreground">
            Showing {{ safeChecks.length }} most recent checks
          </p>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="safeChecks.length === 0 && !loading" class="text-center py-8">
        <div class="rounded-lg bg-muted p-3 mb-3 inline-block">
          <Activity class="h-6 w-6 text-muted-foreground" />
        </div>
        <p class="text-sm text-muted-foreground">No recent checks found</p>
      </div>
    </div>

    <!-- Footer -->
    <div v-if="safeChecks.length > 0" class="mt-4 pt-4 border-t border-border">
      <div class="flex items-center justify-between text-xs text-muted-foreground">
        <div>
          {{ autoRefresh ? 'Auto-refreshing every 30s' : 'Manual refresh only' }}
        </div>
        <div>
          Total: {{ safeChecks.length }} checks
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Custom scrollbar styles */
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: hsl(var(--muted));
  border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: hsl(var(--border));
  border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: hsl(var(--muted-foreground));
}
</style>