<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import {
  Shield,
  AlertTriangle,
  Clock,
  Calendar,
  TrendingUp,
  RefreshCw,
  CheckCircle
} from 'lucide-vue-next';

// Props interface
interface Props {
  monitorId: number;
}

// SSL Certificate API response interface
interface SslCertificateData {
  certificate_status: string;
  certificate_issuer: string | null;
  certificate_subject: string | null;
  certificate_expiration_date: string | null;
  days_until_expiration: number | null;
  is_valid: boolean;
  last_checked: string | null;
}

const props = defineProps<Props>();

// Component state
const loading = ref(true);
const error = ref<string | null>(null);
const sslData = ref<SslCertificateData | null>(null);
const trendData = ref<any[]>([]);
const currentTime = ref(new Date());
const timeInterval = ref<NodeJS.Timeout | null>(null);
const retryCount = ref(0);
const maxRetries = 3;

// Computed days until expiration - use API value directly, but also calculate as fallback
const daysUntilExpiration = computed(() => {
  // First try to use the API value
  if (sslData.value && typeof sslData.value.days_until_expiration === 'number' && sslData.value.days_until_expiration !== null) {
    return sslData.value.days_until_expiration;
  }

  // Fallback: calculate from certificate_expiration_date
  if (!sslData.value?.certificate_expiration_date) {
    return null;
  }

  try {
    const expirationDate = new Date(sslData.value.certificate_expiration_date);
    const diffInMs = expirationDate.getTime() - currentTime.value.getTime();
    const diffInDays = Math.ceil(diffInMs / (1000 * 60 * 60 * 24));
    return diffInDays;
  } catch (error) {
    console.error('Error calculating days until expiration:', error);
    return null;
  }
});

// Status color based on days until expiration
const statusColor = computed(() => {
  const days = daysUntilExpiration.value;
  if (days === null) return 'unknown';
  if (days > 30) return 'success';
  if (days > 7) return 'warning';
  return 'error';
});

// Status badge classes
const statusBadgeClasses = computed(() => {
  const base = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border';

  switch (statusColor.value) {
    case 'success':
      return `${base} status-badge-success`;
    case 'warning':
      return `${base} status-badge-warning`;
    case 'error':
      return `${base} status-badge-error`;
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
      return AlertTriangle;
    default:
      return Shield;
  }
});

// Status icon color
const statusIconColor = computed(() => {
  switch (statusColor.value) {
    case 'success':
      return 'text-green-500';
    case 'warning':
      return 'text-yellow-500';
    case 'error':
      return 'text-destructive';
    default:
      return 'text-muted-foreground';
  }
});

// Status text
const statusText = computed(() => {
  const days = daysUntilExpiration.value;
  if (days === null) return 'Unknown Status';
  if (days < 0) return 'Expired';
  if (days === 0) return 'Expires Today';
  if (days === 1) return 'Expires Tomorrow';
  if (days <= 7) return `Expires in ${days} days`;
  if (days <= 30) return `Expires in ${days} days`;
  return 'Valid';
});

// Countdown display
const countdownDisplay = computed(() => {
  const days = daysUntilExpiration.value;
  if (days === null) return 'N/A';
  if (days < 0) return `${Math.abs(days)} days ago`;
  if (days === 0) return 'Today';
  if (days === 1) return 'Tomorrow';
  return `${days} days`;
});

// Progress percentage (for visual progress bar)
const progressPercentage = computed(() => {
  if (!sslData.value?.certificate_expiration_date) return 0;

  // Since we don't have issued_at from the API, estimate based on typical SSL cert lifespan
  const estimatedLifespanDays = 90; // Typical SSL certificate lifespan
  const daysUntilExp = daysUntilExpiration.value || 0;

  // Estimate progress based on days remaining
  const progress = ((estimatedLifespanDays - daysUntilExp) / estimatedLifespanDays) * 100;

  return Math.min(Math.max(progress, 0), 100);
});

// Progress bar color
const progressColor = computed(() => {
  const percentage = progressPercentage.value;
  if (percentage < 70) return 'bg-green-500';
  if (percentage < 90) return 'bg-yellow-500';
  return 'bg-destructive';
});

// Format date
const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

// Mini trend data for visualization using real API data
const miniTrendData = computed(() => {
  // If we have real trend data from the API, use it
  if (trendData.value && trendData.value.length > 0) {
    return trendData.value.slice(-30); // Last 30 days
  }

  // Fallback: generate some mock trend data based on current expiration
  const days = 30;
  const data = [];
  const now = new Date();
  const currentDaysUntil = daysUntilExpiration.value || 0;

  for (let i = days; i >= 0; i--) {
    const date = new Date(now.getTime() - i * 24 * 60 * 60 * 1000);
    data.push({
      date: date.toISOString().split('T')[0],
      days_until_expiration: Math.max(0, currentDaysUntil + i)
    });
  }

  return data;
});

// Fetch SSL trend data from API
const fetchSslTrendData = async () => {
  try {
    const response = await fetch(`/api/monitors/${props.monitorId}/ssl-expiration-trends?days=30`);

    if (!response.ok) {
      console.warn('Failed to fetch SSL trend data:', response.status);
      return;
    }

    const data = await response.json();

    if (data && data.data && Array.isArray(data.data)) {
      trendData.value = data.data;
    }
  } catch (err) {
    console.warn('Failed to fetch SSL trend data:', err);
    // Don't fail the entire component if trend data fails
  }
};

// Fetch SSL data from API
const fetchSslData = async () => {
  loading.value = true;
  error.value = null;

  try {
    // Fetch both SSL info and trend data in parallel
    const [sslInfoResponse] = await Promise.all([
      fetch(`/api/monitors/${props.monitorId}/ssl-info`),
      fetchSslTrendData() // Fetch trend data but don't wait for it
    ]);

    if (!sslInfoResponse.ok) {
      throw new Error(`HTTP error! status: ${sslInfoResponse.status}`);
    }

    const data = await sslInfoResponse.json();

    // Validate the response structure
    if (data && typeof data === 'object') {
      sslData.value = {
        certificate_status: data.certificate_status || 'unknown',
        certificate_issuer: data.certificate_issuer || null,
        certificate_subject: data.certificate_subject || null,
        certificate_expiration_date: data.certificate_expiration_date || null,
        days_until_expiration: data.days_until_expiration || null,
        is_valid: Boolean(data.is_valid),
        last_checked: data.last_checked || null,
      };
      retryCount.value = 0;
    } else {
      throw new Error('Invalid response format from API');
    }
  } catch (err) {
    console.error('Failed to fetch SSL certificate info:', err);

    if (retryCount.value < maxRetries) {
      retryCount.value++;
      setTimeout(() => {
        fetchSslData();
      }, 1000 * retryCount.value);
    } else {
      error.value = err instanceof Error ? err.message : 'Failed to load SSL certificate data';
      sslData.value = null;
    }
  } finally {
    loading.value = false;
  }
};

// Retry loading data
const retryLoading = () => {
  retryCount.value = 0;
  fetchSslData();
};

// Update current time
const updateCurrentTime = () => {
  currentTime.value = new Date();
};

// Watch for prop changes
watch(() => props.monitorId, () => {
  fetchSslData();
}, { immediate: false });

// Load data on mount
onMounted(() => {
  fetchSslData();
  // Update time every minute for accurate countdown
  timeInterval.value = setInterval(updateCurrentTime, 60000);
});

// Cleanup on unmount
onUnmounted(() => {
  if (timeInterval.value) {
    clearInterval(timeInterval.value);
  }
});
</script>

<template>
  <div class="glass-card-strong p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center space-x-3">
        <div class="rounded-lg bg-primary/10 p-2">
          <Shield class="h-5 w-5 text-primary" />
        </div>
        <div>
          <h3 class="text-lg font-semibold text-foreground">SSL Certificate</h3>
          <p class="text-sm text-muted-foreground">
            Monitor #{{ monitorId }} • Expiration Status
          </p>
        </div>
      </div>

      <!-- Status Badge -->
      <div :class="statusBadgeClasses">
        <component :is="statusIcon" class="h-3 w-3 mr-1" :class="statusIconColor" />
        {{ statusText }}
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="space-y-4">
      <div class="animate-pulse">
        <div class="h-16 bg-muted rounded mb-4"></div>
        <div class="grid grid-cols-2 gap-4">
          <div class="h-12 bg-muted rounded"></div>
          <div class="h-12 bg-muted rounded"></div>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="text-center py-8">
      <div class="rounded-lg bg-destructive/10 p-3 mb-3 inline-block">
        <AlertTriangle class="h-6 w-6 text-destructive" />
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

    <!-- SSL Certificate Data -->
    <div v-else-if="sslData" class="space-y-6">
      <!-- Countdown Timer -->
      <div class="text-center">
        <div class="text-3xl font-bold mb-2" :class="statusIconColor">
          {{ countdownDisplay }}
        </div>
        <div class="text-sm text-muted-foreground">until expiration</div>
      </div>

      <!-- Progress Bar -->
      <div class="space-y-2">
        <div class="flex justify-between text-xs text-muted-foreground">
          <span>Certificate Lifecycle</span>
          <span>{{ Math.round(progressPercentage) }}%</span>
        </div>
        <div class="w-full bg-muted rounded-full h-2">
          <div
            class="h-2 rounded-full transition-all duration-300"
            :class="progressColor"
            :style="{ width: `${progressPercentage}%` }"
          ></div>
        </div>
      </div>

      <!-- Certificate Details Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Last Checked -->
        <div class="bg-muted/50 rounded-lg p-4">
          <div class="flex items-center space-x-2 mb-2">
            <Calendar class="h-4 w-4 text-muted-foreground" />
            <span class="text-sm font-medium text-muted-foreground">Last Checked</span>
          </div>
          <p class="text-lg font-semibold text-foreground">
            {{ sslData.last_checked ? formatDate(sslData.last_checked) : 'Never' }}
          </p>
        </div>

        <!-- Expiration Date -->
        <div class="bg-muted/50 rounded-lg p-4">
          <div class="flex items-center space-x-2 mb-2">
            <Clock class="h-4 w-4 text-muted-foreground" />
            <span class="text-sm font-medium text-muted-foreground">Expires On</span>
          </div>
          <p class="text-lg font-semibold text-foreground">
            {{ sslData.certificate_expiration_date ? formatDate(sslData.certificate_expiration_date) : 'N/A' }}
          </p>
        </div>
      </div>

      <!-- Additional Certificate Info -->
      <div v-if="sslData.certificate_issuer || sslData.certificate_subject" class="space-y-3">
        <!-- Issuer -->
        <div v-if="sslData.certificate_issuer" class="flex items-center justify-between">
          <span class="text-sm text-muted-foreground">Issuer</span>
          <span class="text-sm font-medium text-foreground truncate ml-2">
            {{ sslData.certificate_issuer }}
          </span>
        </div>

        <!-- Subject -->
        <div v-if="sslData.certificate_subject" class="flex items-center justify-between">
          <span class="text-sm text-muted-foreground">Subject</span>
          <span class="text-sm font-medium text-foreground truncate ml-2">
            {{ sslData.certificate_subject }}
          </span>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-center pt-4 border-t border-border">
        <button
          @click="fetchSslData"
          class="inline-flex items-center px-4 py-2 text-sm font-medium text-primary bg-primary/10 hover:bg-primary/20 rounded-md transition-colors"
        >
          <RefreshCw class="h-4 w-4 mr-2" />
          Refresh Certificate Info
        </button>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-8">
      <div class="rounded-lg bg-muted p-3 mb-3 inline-block">
        <Shield class="h-6 w-6 text-muted-foreground" />
      </div>
      <p class="text-sm text-muted-foreground">No SSL certificate information available</p>
    </div>
  </div>
</template>