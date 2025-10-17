<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';
import axios from 'axios';
import {
  Calendar,
  AlertTriangle,
  CheckCircle,
  Clock,
  Shield,
  Settings,
  Zap,
  RefreshCw,
  Trash2,
  Plus,
  Save,
  X,
  Play,
  BarChart3,
  Users,
  Eye
} from 'lucide-vue-next';

interface DebugOverride {
  id: number;
  override_data: {
    expiry_date: string;
    original_expiry: string;
    reason: string;
  };
  is_active: boolean;
  created_at: string;
}

interface Website {
  id: number;
  name: string;
  url: string;
  real_expiry_date: string | null;
  override: DebugOverride | null;
  effective_expiry: string | null;
  days_remaining: number;
  monitor_status: string;
  can_override: boolean;
}

interface Stats {
  total_websites: number;
  active_overrides: number;
  urgent_alerts: number;
}

const page = usePage();
const { success, error, warning, info } = useToast();

// Props from backend
const props = defineProps<{
  websites: Website[];
  stats: Stats;
}>();

// Reactive data
const loading = ref(false);
const customDates = ref<Record<number, string>>({});
const selectedWebsites = ref<number[]>([]);
const showBulkActions = ref(false);
const processing = ref<number[]>([]);

// Computed properties
const hasActiveOverrides = computed(() => props.stats.active_overrides > 0);
const hasUrgentAlerts = computed(() => props.stats.urgent_alerts > 0);

const getDaysClass = (days: number) => {
  if (days < 0) return 'text-destructive bg-red-50 border-red-200';
  if (days <= 7) return 'text-orange-600 bg-orange-50 border-orange-200';
  if (days <= 30) return 'text-yellow-600 bg-yellow-50 border-yellow-200';
  return 'text-green-600 bg-green-50 border-green-200';
};

const getDaysBadgeText = (days: number) => {
  if (days < 0) return `${Math.abs(days)} days ago`;
  if (days === 0) return 'Expires today';
  if (days === 1) return 'Tomorrow';
  return `${days} days`;
};

const formatDate = (dateString: string | null) => {
  if (!dateString) return 'N/A';
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

// Methods
const setOverride = async (websiteId: number, days: number) => {
  if (processing.value.includes(websiteId)) return;

  const expiryDate = new Date();
  expiryDate.setDate(expiryDate.getDate() + days);

  await createOverride(websiteId, expiryDate.toISOString(), `${days} day override`);
};

const setCustomOverride = async (websiteId: number) => {
  const customDate = customDates.value[websiteId];
  if (!customDate) return;

  await createOverride(websiteId, customDate, 'Custom override');
  customDates.value[websiteId] = '';
};

const createOverride = async (websiteId: number, expiryDate: string, reason: string) => {
  processing.value = [...processing.value, websiteId];

  try {
    const response = await axios.post('/debug/ssl-overrides', {
      website_id: websiteId,
      expiry_date: expiryDate,
      reason: reason
    });

    success(response.data.message || 'SSL override created successfully');
    router.reload({ only: ['websites', 'stats'] });
  } catch (err: any) {
    error(err.response?.data?.message || 'Failed to create SSL override');
  } finally {
    processing.value = processing.value.filter(id => id !== websiteId);
  }
};

const clearOverride = async (websiteId: number) => {
  if (processing.value.includes(websiteId)) return;

  processing.value = [...processing.value, websiteId];

  try {
    const website = props.websites.find(w => w.id === websiteId);
    if (!website?.override) return;

    await axios.delete(`/debug/ssl-overrides/${website.override.id}`);
    success('SSL override removed successfully');
    router.reload({ only: ['websites', 'stats'] });
  } catch (err: any) {
    error(err.response?.data?.message || 'Failed to remove SSL override');
  } finally {
    processing.value = processing.value.filter(id => id !== websiteId);
  }
};

const testAlerts = async (websiteId: number) => {
  if (processing.value.includes(websiteId)) return;

  processing.value = [...processing.value, websiteId];

  try {
    const response = await axios.post('/debug/ssl-overrides/test', {
      website_id: websiteId
    });

    if (response.data.success) {
      success(response.data.message);
      info(`Triggered ${response.data.triggered_alerts?.length || 0} alerts`);
    } else {
      warning(response.data.message);
    }
  } catch (err: any) {
    error(err.response?.data?.message || 'Failed to test alerts');
  } finally {
    processing.value = processing.value.filter(id => id !== websiteId);
  }
};

const setBulkOverrides = async (days: number) => {
  if (selectedWebsites.value.length === 0) {
    warning('Please select websites first');
    return;
  }

  loading.value = true;

  try {
    const response = await axios.post('/debug/ssl-overrides/bulk', {
      website_ids: selectedWebsites.value,
      days_ahead: days,
      reason: `Bulk override: ${days} days`
    });

    success(response.data.message);
    selectedWebsites.value = [];
    router.reload({ only: ['websites', 'stats'] });
  } catch (err: any) {
    error(err.response?.data?.message || 'Failed to create bulk overrides');
  } finally {
    loading.value = false;
  }
};

const clearBulkOverrides = async () => {
  if (selectedWebsites.value.length === 0) {
    warning('Please select websites first');
    return;
  }

  loading.value = true;

  try {
    const response = await axios.delete('/debug/ssl-overrides/bulk', {
      data: { website_ids: selectedWebsites.value }
    });

    success(response.data.message);
    selectedWebsites.value = [];
    router.reload({ only: ['websites', 'stats'] });
  } catch (err: any) {
    error(err.response?.data?.message || 'Failed to remove bulk overrides');
  } finally {
    loading.value = false;
  }
};

const selectAll = () => {
  selectedWebsites.value = props.websites.map(w => w.id);
};

const clearSelection = () => {
  selectedWebsites.value = [];
};

const refreshData = () => {
  router.reload({ only: ['websites', 'stats'] });
  info('Data refreshed');
};

// Lifecycle
onMounted(() => {
  // Initialize custom dates for any existing overrides
  props.websites.forEach(website => {
    if (website.override?.override_data?.expiry_date) {
      customDates.value[website.id] = website.override.override_data.expiry_date;
    }
  });
});
</script>

<template>
  <Head title="SSL Debug Overrides" />

  <DashboardLayout title="SSL Certificate Overrides">
    <div class="debug-ssl-overrides">
      <!-- Page Header -->
      <div class="mb-6">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h1 class="text-2xl font-semibold text-foreground">SSL Certificate Overrides</h1>
            <p class="text-muted-foreground">DEBUG • Override SSL certificate expiry dates for testing purposes</p>
          </div>
          <button
            @click="refreshData"
            :disabled="loading"
            class="flex items-center space-x-2 px-4 py-2 text-sm text-muted-foreground hover:text-foreground transition-colors"
          >
            <RefreshCw :class="['w-4 h-4', loading && 'animate-spin']" />
            <span>Refresh</span>
          </button>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
          <div class="bg-card text-card-foreground rounded-lg shadow p-4 border border-border">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-muted-foreground">Total Websites</p>
                <p class="text-2xl font-bold text-foreground">{{ stats.total_websites }}</p>
              </div>
              <BarChart3 class="w-8 h-8 text-blue-500" />
            </div>
          </div>

          <div class="bg-card text-card-foreground rounded-lg shadow p-4 border border-border">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-muted-foreground">Active Overrides</p>
                <p class="text-2xl font-bold text-primary">{{ stats.active_overrides }}</p>
              </div>
              <Settings class="w-8 h-8 text-blue-500" />
            </div>
          </div>

          <div class="bg-card text-card-foreground rounded-lg shadow p-4 border border-border">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-muted-foreground">Urgent Alerts</p>
                <p class="text-2xl font-bold text-orange-600">{{ stats.urgent_alerts }}</p>
              </div>
              <AlertTriangle class="w-8 h-8 text-orange-500" />
            </div>
          </div>

          <div class="bg-card text-card-foreground rounded-lg shadow p-4 border border-border">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-muted-foreground">Selected</p>
                <p class="text-2xl font-bold text-green-600">{{ selectedWebsites.length }}</p>
              </div>
              <CheckSquare class="w-8 h-8 text-green-500" />
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-card text-card-foreground rounded-lg shadow p-4 border border-border mb-6">
          <h3 class="text-lg font-semibold text-foreground mb-4 flex items-center">
            <Zap class="w-5 h-5 mr-2 text-yellow-500" />
            Quick Actions
          </h3>

          <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
            <!-- Quick Override Buttons -->
            <button
              @click="setBulkOverrides(7)"
              :disabled="selectedWebsites.length === 0 || loading"
              class="flex items-center justify-center space-x-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <Calendar class="w-4 h-4" />
              <span>Set 7 Days</span>
            </button>

            <button
              @click="setBulkOverrides(3)"
              :disabled="selectedWebsites.length === 0 || loading"
              class="flex items-center justify-center space-x-2 px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <Clock class="w-4 h-4" />
              <span>Set 3 Days</span>
            </button>

            <button
              @click="setBulkOverrides(1)"
              :disabled="selectedWebsites.length === 0 || loading"
              class="flex items-center justify-center space-x-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <AlertTriangle class="w-4 h-4" />
              <span>Set 1 Day</span>
            </button>

            <button
              @click="setBulkOverrides(0)"
              :disabled="selectedWebsites.length === 0 || loading"
              class="flex items-center justify-center space-x-2 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <Shield class="w-4 h-4" />
              <span>Expires Today</span>
            </button>

            <button
              @click="clearBulkOverrides"
              :disabled="selectedWebsites.length === 0 || loading"
              class="flex items-center justify-center space-x-2 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <Trash2 class="w-4 h-4" />
              <span>Clear All</span>
            </button>

            <button
              @click="selectAll"
              :disabled="loading"
              class="flex items-center justify-center space-x-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <CheckSquare class="w-4 h-4" />
              <span>Select All</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Websites List -->
      <div class="bg-card text-card-foreground rounded-lg shadow border border-border">
        <div class="px-4 py-3 border-b border-border">
          <h3 class="text-lg font-semibold text-foreground flex items-center">
            <Eye class="w-5 h-5 mr-2 text-blue-500" />
            Websites with SSL Monitoring
            <span class="ml-2 text-sm text-muted-foreground">({{ websites.length }} total)</span>
          </h3>
        </div>

        <div class="divide-y divide-border">
          <div
            v-for="website in websites"
            :key="website.id"
            class="p-4 hover:bg-accent transition-colors"
          >
            <div class="flex items-start justify-between">
              <!-- Website Info -->
              <div class="flex-1 min-w-0 mr-4">
                <div class="flex items-center space-x-3 mb-2">
                  <input
                    type="checkbox"
                    :checked="selectedWebsites.includes(website.id)"
                    @change="e => {
                      if (e.target.checked) {
                        selectedWebsites.push(website.id);
                      } else {
                        selectedWebsites = selectedWebsites.filter(id => id !== website.id);
                      }
                    }"
                    class="w-4 h-4 text-primary border-border rounded focus:ring-blue-500"
                  />

                  <div>
                    <h4 class="text-lg font-medium text-foreground truncate">{{ website.name }}</h4>
                    <p class="text-sm text-muted-foreground truncate">{{ website.url }}</p>
                  </div>
                </div>

                <!-- SSL Status -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                  <!-- Real Expiry -->
                  <div class="flex items-center space-x-2">
                    <span class="text-muted-foreground">Real:</span>
                    <span class="font-medium text-foreground">
                      {{ formatDate(website.real_expiry_date) }}
                    </span>
                  </div>

                  <!-- Override Info -->
                  <div v-if="website.override" class="flex items-center space-x-2">
                    <span class="text-primary">Override:</span>
                    <span class="font-medium text-blue-900">
                      {{ formatDate(website.override.override_data.expiry_date) }}
                    </span>
                    <span class="status-badge-info">
                      {{ website.override.override_data.reason }}
                    </span>
                  </div>

                  <!-- Effective Status -->
                  <div class="flex items-center space-x-2">
                    <span class="text-muted-foreground">Effective:</span>
                    <span
                      :class="[
                        'font-medium px-2 py-1 rounded border text-xs',
                        getDaysClass(website.days_remaining)
                      ]"
                    >
                      {{ getDaysBadgeText(website.days_remaining) }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Actions -->
              <div class="flex items-center space-x-2 ml-4">
                <!-- Quick Override Buttons -->
                <div class="flex items-center space-x-1">
                  <button
                    @click="setOverride(website.id, 7)"
                    :disabled="!website.can_override || processing.includes(website.id)"
                    class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded hover:bg-blue-200 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    title="Set to 7 days"
                  >
                    7d
                  </button>

                  <button
                    @click="setOverride(website.id, 3)"
                    :disabled="!website.can_override || processing.includes(website.id)"
                    class="px-2 py-1 text-xs bg-orange-100 text-orange-800 rounded hover:bg-orange-200 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    title="Set to 3 days"
                  >
                    3d
                  </button>

                  <button
                    @click="setOverride(website.id, 1)"
                    :disabled="!website.can_override || processing.includes(website.id)"
                    class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded hover:bg-red-200 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    title="Set to 1 day"
                  >
                    1d
                  </button>

                  <button
                    @click="setOverride(website.id, 0)"
                    :disabled="!website.can_override || processing.includes(website.id)"
                    class="px-2 py-1 text-xs bg-purple-100 text-purple-800 rounded hover:bg-purple-200 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    title="Set to expires today"
                  >
                    0d
                  </button>
                </div>

                <!-- Custom Date -->
                <div class="flex items-center space-x-1">
                  <input
                    v-model="customDates[website.id]"
                    type="datetime-local"
                    :disabled="!website.can_override || processing.includes(website.id)"
                    class="w-32 px-2 py-1 text-xs input-styled"
                  />
                  <button
                    @click="setCustomOverride(website.id)"
                    :disabled="!customDates[website.id] || !website.can_override || processing.includes(website.id)"
                    class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded hover:bg-green-200 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                  >
                    Set
                  </button>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center space-x-1 border-l border-border pl-2">
                  <button
                    v-if="website.override"
                    @click="clearOverride(website.id)"
                    :disabled="processing.includes(website.id)"
                    class="p-1 text-muted-foreground hover:text-destructive disabled:opacity-50 transition-colors"
                    title="Remove override"
                  >
                    <X class="w-4 h-4" />
                  </button>

                  <button
                    @click="testAlerts(website.id)"
                    :disabled="!website.can_override || processing.includes(website.id)"
                    class="p-1 text-muted-foreground hover:text-green-600 disabled:opacity-50 transition-colors"
                    title="Test alerts"
                  >
                    <Play class="w-4 h-4" />
                  </button>
                </div>

                <!-- Loading Indicator -->
                <div v-if="processing.includes(website.id)" class="flex items-center">
                  <RefreshCw class="w-4 h-4 text-primary animate-spin" />
                </div>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="websites.length === 0" class="p-8 text-center">
            <Bug class="w-12 h-12 text-muted-foreground mx-auto mb-4" />
            <h3 class="text-lg font-medium text-foreground mb-2">No websites found</h3>
            <p class="text-muted-foreground">You don't have any websites with SSL monitoring enabled.</p>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>