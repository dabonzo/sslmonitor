<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { Plus, Edit, Trash2, Eye, Search, Filter, RotateCcw, Zap, Shield, Clock, AlertTriangle, CheckSquare, Square, Trash, Play, ArrowRightLeft, Loader2, Bell } from 'lucide-vue-next';
import ssl from '@/routes/ssl';
import BulkTransferModal from '@/components/team/BulkTransferModal.vue';
import WebsiteSkeleton from '@/components/ui/WebsiteSkeleton.vue';
import CertificateDetailsModal from '@/components/ssl/CertificateDetailsModal.vue';
import BulkCertificateActions from '@/components/ssl/BulkCertificateActions.vue';
import ImmediateCheckButton from '@/components/ssl/ImmediateCheckButton.vue';
import WebsiteAlertsModal from '@/components/ssl/WebsiteAlertsModal.vue';

interface SslCertificate {
  status: string;
  expires_at: string;
  days_remaining: number;
  issuer: string;
  subject: string;
  serial_number: string;
  signature_algorithm: string;
  is_valid: boolean;
  last_checked: string;
  response_time: number;
}

interface Website {
  id: number;
  name: string;
  url: string;
  ssl_monitoring_enabled: boolean;
  uptime_monitoring_enabled: boolean;
  ssl_status: string;
  ssl_days_remaining: number | null;
  latest_ssl_certificate: SslCertificate | null;
  team_badge: {
    type: 'team' | 'personal';
    name: string | null;
    color: string;
  };
  created_at: string;
}

interface AlertConfiguration {
  id: number;
  alert_type: string;
  alert_type_label: string;
  enabled: boolean;
  alert_level: string;
  alert_level_color: string;
  threshold_days: number | null;
  threshold_response_time: number | null;
  notification_channels: string[];
  custom_message: string | null;
  last_triggered_at: string | null;
}

interface PaginatedWebsites {
  data: Website[];
  links: any[];
  meta: any;
}

interface Team {
  id: number;
  name: string;
  description?: string;
  member_count?: number;
  user_role?: string;
}

interface Props {
  websites: PaginatedWebsites;
  filters: {
    search?: string;
    filter?: string;
    team?: string;
  };
  filterStats: {
    all: number;
    ssl_issues: number;
    uptime_issues: number;
    expiring_soon: number;
    critical: number;
  };
  availableTeams?: Team[];
}

interface WebsiteDetails {
  id: number;
  name: string;
  url: string;
  ssl_monitoring_enabled: boolean;
  uptime_monitoring_enabled: boolean;
  created_at: string;
  updated_at: string;
  ssl: {
    status: string;
    days_remaining: number | null;
    certificate: any;
    recent_checks: any[];
  };
  monitoring: {
    config: any;
    ssl_enabled: boolean;
    uptime_enabled: boolean;
  };
  stats: {
    total_ssl_checks: number;
    total_certificates: number;
    avg_response_time: number;
    success_rate: number;
  };
}

const props = defineProps<Props>();

// Filter state
const searchQuery = ref(props.filters.search || '');
const activeFilter = ref(props.filters.filter || 'all');
const activeTeam = ref(props.filters.team || 'all');
const showingFilters = ref(false);

// Modal state
const showModal = ref(false);
const showCertificateAnalysis = ref(false);
const certificateAnalysis = ref(null);
const analysisLoading = ref(false);
const showCertificateDetails = ref(false);
const selectedWebsiteForCertificate = ref<Website | null>(null);

// Alerts modal state
const showWebsiteAlertsModal = ref(false);
const selectedWebsiteForAlerts = ref<Website | null>(null);
const websiteAlertConfigurations = ref<AlertConfiguration[]>([]);
const alertsModalLoading = ref(false);

// Bulk operations state
const selectedWebsites = ref<number[]>([]);
const showBulkActions = ref(false);
const bulkActionLoading = ref(false);
const selectedWebsite = ref<WebsiteDetails | null>(null);
const loading = ref(false);
const deleting = ref<number | null>(null);

// Page loading states
const isFilterLoading = ref(false);
const isPageLoading = ref(false);

// Search input ref for focus management
const searchInputRef = ref<HTMLInputElement | null>(null);

// Real-time status monitoring with individual website polling
const websiteStatuses = ref<Map<number, any>>(new Map());
const filterStatsLocal = ref(props.filterStats);
const websitesLocal = ref([...props.websites.data]);

// Function to calculate filter stats locally
const calculateFilterStats = (websites: any[]) => {
  const stats = {
    all: websites.length,
    ssl_issues: 0,
    uptime_issues: 0,
    expiring_soon: 0,
    critical: 0,
  };

  for (const website of websites) {
    // SSL Issues
    if (website.ssl_status && ['invalid', 'expired'].includes(website.ssl_status)) {
      stats.ssl_issues++;
    }

    // Uptime Issues
    if (website.uptime_status && ['down', 'slow', 'content_mismatch'].includes(website.uptime_status)) {
      stats.uptime_issues++;
    }

    // Expiring Soon (within 30 days)
    if (website.ssl_days_remaining !== null && website.ssl_days_remaining <= 30 && website.ssl_days_remaining >= 0) {
      stats.expiring_soon++;
    }

    // Critical (3 days or less, expired certs, or down sites)
    if (
      (website.ssl_days_remaining !== null && website.ssl_days_remaining <= 3) ||
      website.ssl_status === 'expired' ||
      ['down', 'content_mismatch'].includes(website.uptime_status)
    ) {
      stats.critical++;
    }
  }

  return stats;
};

// Function to check individual website status
const checkWebsiteStatus = async (websiteId: number) => {
  try {
    const response = await axios.get(`/ssl/websites/${websiteId}/check-status`);
    const newStatus = response.data;

    const currentStatus = websiteStatuses.value.get(websiteId);

    // Check if status has changed
    if (!currentStatus ||
        currentStatus.ssl_status !== newStatus.ssl_status ||
        currentStatus.uptime_status !== newStatus.uptime_status) {

      websiteStatuses.value.set(websiteId, newStatus);

      // Update the website in the local list
      const websiteIndex = websitesLocal.value.findIndex(w => w.id === websiteId);
      if (websiteIndex !== -1) {
        websitesLocal.value[websiteIndex].ssl_status = newStatus.ssl_status;
        websitesLocal.value[websiteIndex].uptime_status = newStatus.uptime_status;

        // Recalculate filter stats locally
        filterStatsLocal.value = calculateFilterStats(websitesLocal.value);

        console.log(`Status updated for website ${websiteId}: SSL=${newStatus.ssl_status}, Uptime=${newStatus.uptime_status}`);
      }
    }
  } catch (error) {
    console.error(`Failed to check status for website ${websiteId}:`, error);
  }
};

// Function to poll all website statuses
const pollAllWebsiteStatuses = async () => {
  const promises = websitesLocal.value.map(website => checkWebsiteStatus(website.id));
  await Promise.allSettled(promises);
};

// Real-time status monitoring
onMounted(() => {
  const urlParams = new URLSearchParams(window.location.search);
  const refreshParam = urlParams.get('refresh');
  const page = usePage();
  const isAuthenticated = page.props.auth?.user !== null;

  // Initialize website statuses
  websitesLocal.value.forEach(website => {
    websiteStatuses.value.set(website.id, {
      ssl_status: website.ssl_status,
      uptime_status: website.uptime_status,
    });
  });

  // Only enable polling if user is authenticated
  if (!isAuthenticated) {
    console.log('User not authenticated, polling disabled');
    return;
  }

  // Immediate check refresh (short-term, frequent updates)
  if (refreshParam === 'check') {
    // Start checking every 3 seconds for updates after an immediate check
    let refreshCount = 0;
    const maxRefreshes = 10; // Check for 30 seconds (3s * 10)

    const intervalId = setInterval(async () => {
      refreshCount++;
      console.log('Fast polling: checking all website statuses...');
      await pollAllWebsiteStatuses();

      // Stop after 30 seconds and switch to normal polling
      if (refreshCount >= maxRefreshes) {
        clearInterval(intervalId);
        console.log('Fast polling stopped, switching to normal polling');

        // Start normal polling
        const normalPollingId = setInterval(async () => {
          console.log('Normal polling: checking all website statuses...');
          await pollAllWebsiteStatuses();
        }, 15000); // 15 seconds

        // Store interval ID globally to prevent multiple intervals
        (window as any).sslMonitorStatusInterval = normalPollingId;
      }
    }, 3000);

    // Clean up the URL parameter
    const newUrl = new URL(window.location.href);
    newUrl.searchParams.delete('refresh');
    window.history.replaceState({}, '', newUrl);
  } else {
    // Clear any existing interval to prevent duplicates
    if ((window as any).sslMonitorStatusInterval) {
      clearInterval((window as any).sslMonitorStatusInterval);
    }

    // Start normal polling immediately if not coming from immediate check
    const normalPollingId = setInterval(async () => {
      console.log('Normal polling: checking all website statuses...');
      await pollAllWebsiteStatuses();
    }, 15000); // 15 seconds

    // Store interval ID globally to prevent multiple intervals
    (window as any).sslMonitorStatusInterval = normalPollingId;
  }

  // Cleanup on unmount
  onUnmounted(() => {
    if ((window as any).sslMonitorStatusInterval) {
      clearInterval((window as any).sslMonitorStatusInterval);
      delete (window as any).sslMonitorStatusInterval;
    }
  });
});

// Bulk transfer modal state
const showBulkTransferModal = ref(false);
const selectedWebsitesForTransfer = computed(() =>
  props.websites.data.filter(website => selectedWebsites.value.includes(website.id))
);

const openWebsiteModal = async (website: Website) => {
  loading.value = true;
  try {
    const response = await axios.get(`/ssl/websites/${website.id}/details`);
    selectedWebsite.value = response.data;
    showModal.value = true;
  } catch (error) {
    console.error('Failed to load website details:', error);
  } finally {
    loading.value = false;
  }
};

const closeModal = () => {
  showModal.value = false;
  selectedWebsite.value = null;
};

const closeCertificateAnalysis = () => {
  showCertificateAnalysis.value = false;
  certificateAnalysis.value = null;
};

const openCertificateDetails = (website: Website) => {
  selectedWebsiteForCertificate.value = website;
  showCertificateDetails.value = true;
};

const closeCertificateDetails = () => {
  showCertificateDetails.value = false;
  selectedWebsiteForCertificate.value = null;
};

const openCertificateAnalysis = async (website: Website) => {
  analysisLoading.value = true;
  try {
    const response = await axios.get(`/ssl/websites/${website.id}/certificate-analysis`);
    certificateAnalysis.value = response.data;
    showCertificateAnalysis.value = true;
  } catch (error) {
    console.error('Failed to load certificate analysis:', error);
    alert('Failed to load certificate analysis. Please try again.');
  } finally {
    analysisLoading.value = false;
  }
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString();
};

const getDaysRemainingColor = (days: number | null) => {
  if (!days) return 'text-gray-500';
  if (days <= 7) return 'text-red-600';
  if (days <= 30) return 'text-yellow-600';
  return 'text-green-600';
};

const viewWebsite = (website: Website) => {
  router.visit(ssl.websites.show(website.id).url);
};

const editWebsite = (website: Website) => {
  router.visit(ssl.websites.edit(website.id).url);
};

const openAlertsModal = async (website: Website) => {
  selectedWebsiteForAlerts.value = website;
  alertsModalLoading.value = true;
  showWebsiteAlertsModal.value = true;

  try {
    const response = await axios.get(`/ssl/websites/${website.id}/alerts`);
    websiteAlertConfigurations.value = response.data.alertConfigurations || [];
  } catch (error) {
    console.error('Failed to load alert configurations:', error);
    // Use empty array if there's an error
    websiteAlertConfigurations.value = [];
  } finally {
    alertsModalLoading.value = false;
  }
};

const closeAlertsModal = () => {
  showWebsiteAlertsModal.value = false;
  selectedWebsiteForAlerts.value = null;
  websiteAlertConfigurations.value = [];
};

const handleUpdateAlert = async (alertId: number, updates: Partial<AlertConfiguration>) => {
  try {
    const response = await axios.patch(`/settings/alerts/${alertId}`, updates);

    // Update the local state
    const index = websiteAlertConfigurations.value.findIndex(alert => alert.id === alertId);
    if (index !== -1) {
      websiteAlertConfigurations.value[index] = { ...websiteAlertConfigurations.value[index], ...response.data };
    }

    // Show success message
    router.reload({
      only: ['flash'],
      data: {
        success: 'Alert configuration updated successfully.'
      }
    });
  } catch (error) {
    console.error('Failed to update alert:', error);
    alert('Failed to update alert configuration. Please try again.');
  }
};

const handleTestAlert = async (alertId: number) => {
  try {
    await axios.post(`/settings/alerts/${alertId}/test`);

    // Show success message
    router.reload({
      only: ['flash'],
      data: {
        success: 'Test alert sent successfully.'
      }
    });
  } catch (error) {
    console.error('Failed to send test alert:', error);
    alert('Failed to send test alert. Please try again.');
  }
};

const deleteWebsite = async (website: Website) => {
  if (!confirm(`Are you sure you want to delete "${website.name}"? This action cannot be undone.`)) {
    return;
  }

  deleting.value = website.id;
  try {
    await router.delete(ssl.websites.destroy(website.id).url, {
      onSuccess: () => {
        // The page will automatically refresh with updated data
      },
      onError: (errors) => {
        console.error('Failed to delete website:', errors);
        alert('Failed to delete website. Please try again.');
      },
      onFinish: () => {
        deleting.value = null;
      }
    });
  } catch (error) {
    console.error('Error deleting website:', error);
    deleting.value = null;
  }
};

// Filter functions
const applyFilters = () => {
  const params: any = {};

  if (searchQuery.value) params.search = searchQuery.value;
  if (activeFilter.value !== 'all') params.filter = activeFilter.value;
  if (activeTeam.value !== 'all') params.team = activeTeam.value;

  router.get(ssl.websites.index().url, params, {
    preserveState: true,
    replace: true
  });
};

const clearFilters = () => {
  searchQuery.value = '';
  activeFilter.value = 'all';
  activeTeam.value = 'all';
  router.get(ssl.websites.index().url, {}, {
    preserveState: true,
    replace: true
  });
};

const runManualCheck = async (website: Website) => {
  try {
    await axios.post(`/ssl/websites/${website.id}/check`);
    // Refresh the page data
    router.reload({ only: ['websites', 'filterStats'] });
  } catch (error) {
    console.error('Failed to run manual check:', error);
    alert('Failed to run manual check. Please try again.');
  }
};

// Computed properties
const hasActiveFilters = computed(() => {
  return searchQuery.value || activeFilter.value !== 'all' || activeTeam.value !== 'all';
});

const allSelected = computed(() => {
  return props.websites.data.length > 0 && selectedWebsites.value.length === props.websites.data.length;
});

const someSelected = computed(() => {
  return selectedWebsites.value.length > 0;
});

const hasSelection = computed(() => {
  return selectedWebsites.value.length > 0;
});

const filterOptions = computed(() => [
  { key: 'all', label: 'All Websites', icon: Shield, count: filterStatsLocal.value.all },
  { key: 'ssl_issues', label: 'SSL Issues', icon: AlertTriangle, count: filterStatsLocal.value.ssl_issues },
  { key: 'uptime_issues', label: 'Uptime Issues', icon: Zap, count: filterStatsLocal.value.uptime_issues },
  { key: 'expiring_soon', label: 'Expiring Soon', icon: Clock, count: filterStatsLocal.value.expiring_soon },
  { key: 'critical', label: 'Critical', icon: AlertTriangle, count: filterStatsLocal.value.critical }
]);

const teamOptions = [
  { key: 'all', label: 'All Websites' },
  { key: 'personal', label: 'Personal Sites' },
  { key: 'team', label: 'Team Sites' }
];

// Bulk operation functions
const toggleAllSelection = () => {
  if (allSelected.value) {
    selectedWebsites.value = [];
  } else {
    selectedWebsites.value = props.websites.data.map(website => website.id);
  }
  showBulkActions.value = selectedWebsites.value.length > 0;
};

const toggleWebsiteSelection = (websiteId: number) => {
  const index = selectedWebsites.value.indexOf(websiteId);
  if (index > -1) {
    selectedWebsites.value.splice(index, 1);
  } else {
    selectedWebsites.value.push(websiteId);
  }
  showBulkActions.value = selectedWebsites.value.length > 0;
};

const clearSelection = () => {
  selectedWebsites.value = [];
  showBulkActions.value = false;
};

const bulkDelete = async () => {
  if (!confirm(`Are you sure you want to delete ${selectedWebsites.value.length} websites? This action cannot be undone.`)) {
    return;
  }

  bulkActionLoading.value = true;
  try {
    await router.post('/ssl/websites/bulk-destroy', {
      website_ids: selectedWebsites.value
    }, {
      onSuccess: () => {
        clearSelection();
      },
      onError: (errors) => {
        console.error('Bulk delete failed:', errors);
        alert('Bulk delete failed. Please try again.');
      },
      onFinish: () => {
        bulkActionLoading.value = false;
      }
    });
  } catch (error) {
    console.error('Error in bulk delete:', error);
    bulkActionLoading.value = false;
  }
};

const bulkCheck = async () => {
  bulkActionLoading.value = true;
  try {
    await router.post('/ssl/websites/bulk-check', {
      website_ids: selectedWebsites.value
    }, {
      onSuccess: () => {
        clearSelection();
      },
      onError: (errors) => {
        console.error('Bulk check failed:', errors);
        alert('Bulk check failed. Please try again.');
      },
      onFinish: () => {
        bulkActionLoading.value = false;
      }
    });
  } catch (error) {
    console.error('Error in bulk check:', error);
    bulkActionLoading.value = false;
  }
};

// Bulk transfer functions
const openBulkTransferModal = () => {
  if (selectedWebsites.value.length === 0) return;
  showBulkTransferModal.value = true;
};

const closeBulkTransferModal = () => {
  showBulkTransferModal.value = false;
};

const handleTransferCompleted = () => {
  clearSelection();
  // Refresh the page data
  router.reload({ only: ['websites'] });
};

// Quick inline transfer function
const quickTransferToFirstTeam = (website: Website) => {
  if (!props.availableTeams || props.availableTeams.length === 0) return;

  const firstTeam = props.availableTeams[0];

  if (confirm(`Transfer "${website.name}" to ${firstTeam.name}?`)) {
    router.post(`/ssl/websites/${website.id}/transfer-to-team`, {
      team_id: firstTeam.id
    }, {
      onSuccess: () => {
        // Refresh the page data
        router.reload({ only: ['websites', 'filterStats'] });
      },
      onError: (errors) => {
        console.error('Quick transfer failed:', errors);
        alert('Transfer failed. Please try again.');
      }
    });
  }
};

// Reactive filtering with debounced search
let searchTimeout: NodeJS.Timeout | null = null;

const performFilterUpdate = () => {
  isFilterLoading.value = true;

  // Check if search input is currently focused
  const wasSearchFocused = searchInputRef.value === document.activeElement;

  const params: any = {};

  if (searchQuery.value) params.search = searchQuery.value;
  if (activeFilter.value !== 'all') params.filter = activeFilter.value;
  if (activeTeam.value !== 'all') params.team = activeTeam.value;

  router.get(ssl.websites.index().url, params, {
    preserveState: true,
    replace: true,
    onFinish: () => {
      isFilterLoading.value = false;

      // Restore focus to search input if it was focused before the update
      if (wasSearchFocused && searchInputRef.value) {
        nextTick(() => {
          searchInputRef.value?.focus();
        });
      }
    }
  });
};

// Watch for search query changes with debouncing
watch(searchQuery, () => {
  if (searchTimeout) {
    clearTimeout(searchTimeout);
  }

  searchTimeout = setTimeout(() => {
    performFilterUpdate();
  }, 500); // 500ms debounce
});

// Watch for immediate filter changes
watch(activeFilter, () => {
  performFilterUpdate();
});

// Watch for immediate team changes
watch(activeTeam, () => {
  performFilterUpdate();
});
</script>

<template>
  <Head title="SSL Websites" />

  <DashboardLayout title="Unified Monitoring Hub">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-foreground">Unified Monitoring Hub</h1>
          <p class="text-muted-foreground">Monitor SSL certificates and uptime across all your websites</p>
        </div>
        <Link
          :href="ssl.websites.create().url"
          class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors"
        >
          <Plus class="h-4 w-4 mr-2" />
          Add Website
        </Link>
      </div>

      <!-- Smart Filter Bar -->
      <div class="rounded-lg bg-card text-card-foreground p-6 shadow-sm space-y-4">
        <!-- Search and Team Toggle -->
        <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
          <div class="flex-1 max-w-md">
            <div class="relative">
              <Search v-if="!isFilterLoading" class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Loader2 v-if="isFilterLoading" class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground animate-spin" />
              <input
                ref="searchInputRef"
                v-model="searchQuery"
                type="text"
                placeholder="Search websites..."
                :disabled="isFilterLoading"
                class="w-full pl-10 pr-4 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent disabled:opacity-50 disabled:cursor-not-allowed"
              />
            </div>
          </div>

          <div class="flex items-center gap-3">
            <!-- Team Toggle -->
            <select
              v-model="activeTeam"
              class="px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
            >
              <option
                v-for="team in teamOptions"
                :key="team.key"
                :value="team.key"
              >
                {{ team.label }}
              </option>
            </select>

            <!-- Clear Filters -->
            <button
              v-if="hasActiveFilters"
              @click="clearFilters"
              class="inline-flex items-center px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground border border-border rounded-md hover:bg-muted/50 transition-colors"
            >
              <RotateCcw class="h-4 w-4 mr-2" />
              Clear
            </button>
          </div>
        </div>

        <!-- Filter Status Tabs -->
        <div class="flex flex-wrap gap-2">
          <button
            v-for="filter in filterOptions"
            :key="filter.key"
            @click="activeFilter = filter.key"
            class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium transition-colors"
            :class="{
              'bg-primary text-primary-foreground': activeFilter === filter.key,
              'bg-muted text-muted-foreground hover:bg-muted/80 hover:text-foreground': activeFilter !== filter.key
            }"
          >
            <component :is="filter.icon" class="h-4 w-4 mr-2" />
            {{ filter.label }}
            <span class="ml-2 px-2 py-0.5 rounded-full text-xs" :class="{
              'bg-primary-foreground/20': activeFilter === filter.key,
              'bg-foreground/10': activeFilter !== filter.key
            }">
              {{ filter.count }}
            </span>
          </button>
        </div>

        <!-- Bulk Actions and Status -->
        <div class="flex justify-between items-center">
          <!-- Enhanced Bulk Actions Bar -->
          <div v-if="hasSelection" class="flex items-center gap-3">
            <span class="text-sm text-muted-foreground">
              {{ selectedWebsites.length }} selected
            </span>

            <!-- Team Transfer Button -->
            <button
              @click="openBulkTransferModal"
              :disabled="bulkActionLoading"
              class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-purple-700 bg-purple-50 border border-purple-200 rounded-md hover:bg-purple-100 transition-colors disabled:opacity-50"
            >
              <ArrowRightLeft class="h-4 w-4 mr-1" />
              Transfer
            </button>

            <!-- Run Checks Button -->
            <button
              @click="bulkCheck"
              :disabled="bulkActionLoading"
              class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100 transition-colors disabled:opacity-50"
            >
              <Play class="h-4 w-4 mr-1" />
              Run Checks
            </button>

            <!-- Delete Button -->
            <button
              @click="bulkDelete"
              :disabled="bulkActionLoading"
              class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-red-700 bg-red-50 border border-red-200 rounded-md hover:bg-red-100 transition-colors disabled:opacity-50"
            >
              <Trash class="h-4 w-4 mr-1" />
              Delete
            </button>

            <!-- Clear Selection -->
            <button
              @click="clearSelection"
              class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-muted-foreground hover:text-foreground transition-colors"
            >
              Clear
            </button>
          </div>

          <div v-else></div>

          <!-- Live Filter Status -->
          <div class="flex items-center gap-2 text-sm text-muted-foreground">
            <div class="h-2 w-2 bg-green-500 rounded-full animate-pulse"></div>
            Live filtering enabled
          </div>
        </div>
      </div>

      <!-- Enhanced Monitoring Table/Cards -->
      <div class="rounded-lg bg-card text-card-foreground p-6 shadow-sm">
        <!-- Loading State -->
        <WebsiteSkeleton v-if="isFilterLoading" :count="3" />

        <!-- Content -->
        <div v-else>
          <!-- Desktop Table -->
          <div class="hidden lg:block">
            <div class="overflow-x-auto">
              <table class="w-full">
            <thead>
              <tr class="border-b border-border">
                <th class="text-left p-4 w-12">
                  <button
                    @click="toggleAllSelection"
                    class="flex items-center justify-center w-5 h-5 rounded border-2 transition-colors"
                    :class="{
                      'bg-primary border-primary text-primary-foreground': allSelected,
                      'border-border hover:border-primary': !allSelected
                    }"
                  >
                    <CheckSquare v-if="allSelected" class="h-3 w-3" />
                    <Square v-else class="h-3 w-3" />
                  </button>
                </th>
                <th class="text-left p-4">Website</th>
                <th class="text-left p-4">SSL Status</th>
                <th class="text-left p-4">Uptime Status</th>
                <th class="text-left p-4">Days Remaining</th>
                <th class="text-left p-4">Team</th>
                <th class="text-left p-4">Manual Checks</th>
                <th class="text-left p-4">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="website in websites.data"
                :key="website.id"
                class="border-b border-border hover:bg-muted/50"
                :class="{
                  'bg-primary/5': selectedWebsites.includes(website.id)
                }"
              >
                <td class="p-4" @click.stop>
                  <button
                    @click="toggleWebsiteSelection(website.id)"
                    class="flex items-center justify-center w-5 h-5 rounded border-2 transition-colors"
                    :class="{
                      'bg-primary border-primary text-primary-foreground': selectedWebsites.includes(website.id),
                      'border-border hover:border-primary': !selectedWebsites.includes(website.id)
                    }"
                  >
                    <CheckSquare v-if="selectedWebsites.includes(website.id)" class="h-3 w-3" />
                    <Square v-else class="h-3 w-3" />
                  </button>
                </td>
                <td class="p-4 cursor-pointer" @click="openWebsiteModal(website)">
                  <div>
                    <div class="font-medium text-foreground flex items-center gap-2">
                      {{ website.name }}
                      <!-- Dynamic Team Badge -->
                      <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                            :class="{
                              'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': website.team_badge.type === 'team',
                              'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200': website.team_badge.type === 'personal'
                            }">
                        {{ website.team_badge.type === 'team' ? website.team_badge.name : 'Personal' }}
                      </span>
                    </div>
                    <div class="text-sm text-muted-foreground">{{ website.url }}</div>
                  </div>
                </td>
                <td class="p-4">
                  <span
                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                    :class="{
                      'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': website.ssl_status === 'valid',
                      'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': website.ssl_status === 'expired',
                      'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': website.ssl_status === 'expiring_soon',
                      'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200': website.ssl_status === 'unknown'
                    }"
                  >
                    {{ website.ssl_status }}
                  </span>
                </td>
                <td class="p-4">
                  <span
                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                    :class="{
                      'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': website.uptime_status === 'up',
                      'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': website.uptime_status === 'down',
                      'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': website.uptime_status === 'slow' || website.uptime_status === 'content_mismatch',
                      'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200': website.uptime_status === 'not yet checked' || !website.uptime_status
                    }"
                  >
                    {{ website.uptime_status === 'up' ? 'Online' :
                       website.uptime_status === 'down' ? 'Down' :
                       website.uptime_status === 'slow' ? 'Slow' :
                       website.uptime_status === 'content_mismatch' ? 'Content Issue' :
                       website.uptime_status === 'not yet checked' ? 'Not Checked' :
                       'Unknown' }}
                  </span>
                </td>
                <td class="p-4">
                  <span
                    v-if="website.ssl_days_remaining !== null"
                    class="font-medium"
                    :class="getDaysRemainingColor(website.ssl_days_remaining)"
                  >
                    {{ website.ssl_days_remaining }} days
                  </span>
                  <span v-else class="text-gray-500 text-sm">N/A</span>
                </td>
                <td class="p-4">
                  <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                        :class="{
                          'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': website.team_badge.type === 'team',
                          'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200': website.team_badge.type === 'personal'
                        }">
                    {{ website.team_badge.type === 'team' ? website.team_badge.name : 'Personal' }}
                  </span>
                </td>
                <td class="p-4" @click.stop>
                  <div class="flex items-center gap-2">
                    <button
                      @click="runManualCheck(website)"
                      class="inline-flex items-center px-2 py-1 text-xs font-medium text-green-700 bg-green-50 border border-green-200 rounded-md hover:bg-green-100 transition-colors"
                    >
                      <Shield class="h-3 w-3 mr-1" />
                      SSL
                    </button>
                    <button
                      @click="runManualCheck(website)"
                      class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100 transition-colors"
                    >
                      <Zap class="h-3 w-3 mr-1" />
                      Uptime
                    </button>
                    <button
                      @click="openAlertsModal(website)"
                      class="inline-flex items-center px-2 py-1 text-xs font-medium text-purple-700 bg-purple-50 border border-purple-200 rounded-md hover:bg-purple-100 transition-colors"
                    >
                      <Bell class="h-3 w-3 mr-1" />
                      Alerts
                    </button>
                  </div>
                </td>
                <td class="p-4" @click.stop>
                  <div class="flex items-center space-x-2">
                    <button
                      @click="viewWebsite(website)"
                      class="inline-flex items-center px-2 py-1 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors"
                    >
                      <Eye class="h-3 w-3 mr-1" />
                      View
                    </button>

                    <!-- Immediate Check Button -->
                    <ImmediateCheckButton
                      :website-id="website.id"
                      :website-name="website.name"
                      :ssl-enabled="website.ssl_monitoring_enabled"
                      :uptime-enabled="website.uptime_monitoring_enabled"
                      size="sm"
                      variant="compact"
                    />

                    <!-- Inline Quick Transfer -->
                    <button
                      v-if="website.team_badge.type === 'personal' && availableTeams && availableTeams.length > 0"
                      @click="quickTransferToFirstTeam(website)"
                      class="inline-flex items-center px-2 py-1 text-xs font-medium text-purple-700 bg-purple-50 border border-purple-200 rounded-md hover:bg-purple-100 transition-colors"
                      :title="`Quick transfer to ${availableTeams[0]?.name}`"
                    >
                      <ArrowRightLeft class="h-3 w-3 mr-1" />
                      Team
                    </button>

                    <button
                      @click="editWebsite(website)"
                      class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100 transition-colors"
                    >
                      <Edit class="h-3 w-3 mr-1" />
                      Edit
                    </button>
                    <button
                      @click="deleteWebsite(website)"
                      :disabled="deleting === website.id"
                      class="inline-flex items-center px-2 py-1 text-xs font-medium text-red-700 bg-red-50 border border-red-200 rounded-md hover:bg-red-100 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      <Trash2 class="h-3 w-3 mr-1" />
                      {{ deleting === website.id ? 'Deleting...' : 'Delete' }}
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
          </div>
        </div>

        <!-- Mobile Cards -->
        <div class="block lg:hidden space-y-4">
          <!-- Mobile Select All -->
          <div class="flex items-center justify-between pb-4 border-b border-border">
            <button
              @click="toggleAllSelection"
              class="flex items-center gap-3 p-2 rounded-md hover:bg-muted/50 transition-colors"
            >
              <div class="flex items-center justify-center w-5 h-5 rounded border-2 transition-colors"
                :class="{
                  'bg-primary border-primary text-primary-foreground': allSelected,
                  'border-border': !allSelected
                }">
                <CheckSquare v-if="allSelected" class="h-3 w-3" />
                <Square v-else class="h-3 w-3" />
              </div>
              <span class="text-sm font-medium">Select All</span>
            </button>
            <span v-if="selectedWebsites.length > 0" class="text-sm text-muted-foreground">
              {{ selectedWebsites.length }} selected
            </span>
          </div>

          <!-- Website Cards -->
          <div
            v-for="website in websites.data"
            :key="website.id"
            class="bg-background rounded-lg border border-border p-4 shadow-sm transition-all duration-200"
            :class="{
              'ring-2 ring-primary bg-primary/5': selectedWebsites.includes(website.id),
              'hover:shadow-md': !selectedWebsites.includes(website.id)
            }"
          >
            <!-- Card Header -->
            <div class="flex items-start justify-between mb-3">
              <div class="flex items-start gap-3 flex-1 min-w-0">
                <button
                  @click="toggleWebsiteSelection(website.id)"
                  class="flex items-center justify-center w-5 h-5 rounded border-2 transition-colors mt-1 flex-shrink-0"
                  :class="{
                    'bg-primary border-primary text-primary-foreground': selectedWebsites.includes(website.id),
                    'border-border hover:border-primary': !selectedWebsites.includes(website.id)
                  }"
                >
                  <CheckSquare v-if="selectedWebsites.includes(website.id)" class="h-3 w-3" />
                  <Square v-else class="h-3 w-3" />
                </button>

                <div class="flex-1 min-w-0" @click="openWebsiteModal(website)">
                  <div class="flex items-center gap-2 mb-1">
                    <h3 class="font-semibold text-foreground truncate">{{ website.name }}</h3>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium flex-shrink-0"
                      :class="{
                        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': website.team_badge.type === 'team',
                        'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200': website.team_badge.type === 'personal'
                      }">
                      {{ website.team_badge.type === 'team' ? website.team_badge.name : 'Personal' }}
                    </span>
                  </div>
                  <p class="text-sm text-muted-foreground truncate">{{ website.url }}</p>
                </div>
              </div>
            </div>

            <!-- Status Row -->
            <div class="grid grid-cols-2 gap-4 mb-4">
              <div>
                <div class="text-xs text-muted-foreground mb-1">SSL Status</div>
                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                  :class="{
                    'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': website.ssl_status === 'valid',
                    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': website.ssl_status === 'expired',
                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': website.ssl_status === 'expiring',
                    'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200': !website.ssl_status || website.ssl_status === 'unknown'
                  }">
                  {{ website.ssl_status }}
                </span>
              </div>
              <div>
                <div class="text-xs text-muted-foreground mb-1">Uptime Status</div>
                <span
                  class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                  :class="{
                    'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': website.uptime_status === 'up',
                    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': website.uptime_status === 'down',
                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': website.uptime_status === 'slow' || website.uptime_status === 'content_mismatch',
                    'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200': website.uptime_status === 'not yet checked' || !website.uptime_status
                  }"
                >
                  {{ website.uptime_status === 'up' ? 'Online' :
                     website.uptime_status === 'down' ? 'Down' :
                     website.uptime_status === 'slow' ? 'Slow' :
                     website.uptime_status === 'content_mismatch' ? 'Content Issue' :
                     website.uptime_status === 'not yet checked' ? 'Not Checked' :
                     'Unknown' }}
                </span>
              </div>
            </div>

            <!-- Days Remaining -->
            <div class="mb-4">
              <div class="text-xs text-muted-foreground mb-1">Days Remaining</div>
              <span v-if="website.ssl_days_remaining !== null"
                class="text-sm font-medium"
                :class="getDaysRemainingColor(website.ssl_days_remaining)">
                {{ website.ssl_days_remaining }} days
              </span>
              <span v-else class="text-sm text-muted-foreground">N/A</span>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap gap-2">
              <!-- Certificate Details -->
              <button
                @click="openCertificateDetails(website)"
                class="flex items-center px-3 py-2 text-xs font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100 transition-colors"
                :disabled="!website.ssl_monitoring_enabled"
                :title="website.ssl_monitoring_enabled ? 'View detailed certificate analysis' : 'SSL monitoring is disabled'"
              >
                <Shield class="h-4 w-4 mr-1.5" />
                Certificate
              </button>

              <!-- Manual Checks -->
              <button
                @click="runManualCheck(website)"
                class="flex items-center px-3 py-2 text-xs font-medium text-green-700 bg-green-50 border border-green-200 rounded-md hover:bg-green-100 transition-colors"
              >
                <Shield class="h-4 w-4 mr-1.5" />
                SSL Check
              </button>
              <button
                @click="runManualCheck(website)"
                class="flex items-center px-3 py-2 text-xs font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100 transition-colors"
              >
                <Zap class="h-4 w-4 mr-1.5" />
                Uptime Check
              </button>
              <button
                @click="openAlertsModal(website)"
                class="flex items-center px-3 py-2 text-xs font-medium text-purple-700 bg-purple-50 border border-purple-200 rounded-md hover:bg-purple-100 transition-colors"
              >
                <Bell class="h-4 w-4 mr-1.5" />
                Alerts
              </button>

              <!-- Actions -->
              <button
                @click="viewWebsite(website)"
                class="flex items-center px-3 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors"
              >
                <Eye class="h-4 w-4 mr-1.5" />
                View
              </button>

              <button
                @click="openCertificateDetails(website)"
                class="flex items-center px-3 py-2 text-xs font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100 transition-colors"
                :disabled="!website.ssl_monitoring_enabled"
                :title="website.ssl_monitoring_enabled ? 'View detailed certificate analysis' : 'SSL monitoring is disabled'"
              >
                <Shield class="h-4 w-4 mr-1.5" />
                Certificate
              </button>

              <!-- Immediate Check Button -->
              <ImmediateCheckButton
                :website-id="website.id"
                :website-name="website.name"
                :ssl-enabled="website.ssl_monitoring_enabled"
                :uptime-enabled="website.uptime_monitoring_enabled"
                size="md"
                variant="default"
              />

              <button
                v-if="website.team_badge.type === 'personal' && availableTeams && availableTeams.length > 0"
                @click="quickTransferToFirstTeam(website)"
                class="flex items-center px-3 py-2 text-xs font-medium text-purple-700 bg-purple-50 border border-purple-200 rounded-md hover:bg-purple-100 transition-colors"
                :title="`Quick transfer to ${availableTeams[0]?.name}`"
              >
                <ArrowRightLeft class="h-4 w-4 mr-1.5" />
                Transfer
              </button>

              <button
                @click="editWebsite(website)"
                class="flex items-center px-3 py-2 text-xs font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100 transition-colors"
              >
                <Edit class="h-4 w-4 mr-1.5" />
                Edit
              </button>

              <button
                @click="deleteWebsite(website)"
                :disabled="deleting === website.id"
                class="flex items-center px-3 py-2 text-xs font-medium text-red-700 bg-red-50 border border-red-200 rounded-md hover:bg-red-100 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <Trash2 class="h-4 w-4 mr-1.5" />
                {{ deleting === website.id ? 'Deleting...' : 'Delete' }}
              </button>
            </div>
          </div>
        </div>
        </div>

        <!-- Enhanced Footer -->
        <div v-if="websites.links" class="mt-6 flex items-center justify-between">
          <div class="text-sm text-muted-foreground">
            Showing {{ websites.data.length }} of {{ filterStatsLocal.all }} websites
            <span v-if="hasActiveFilters" class="text-primary font-medium">(filtered)</span>
          </div>
          <div class="flex items-center space-x-2">
            <!-- Pagination controls would go here -->
            <span class="text-xs text-muted-foreground">Real-time monitoring active</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Website Details Modal -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click="closeModal"
    >
      <div
        class="bg-card text-card-foreground rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto"
        @click.stop
      >
        <div class="flex items-center justify-between p-6 border-b border-border">
          <div>
            <h2 class="text-xl font-semibold text-foreground">{{ selectedWebsite?.name }}</h2>
            <p class="text-sm text-muted-foreground">{{ selectedWebsite?.url }}</p>
          </div>
          <button
            class="text-muted-foreground hover:text-foreground"
            @click="closeModal"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div v-if="selectedWebsite" class="p-6 space-y-6">
          <!-- Quick Status Overview -->
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-muted/30 rounded-lg p-4 text-center">
              <div class="text-2xl font-bold text-foreground">{{ selectedWebsite.ssl.days_remaining || 'N/A' }}</div>
              <div class="text-sm text-muted-foreground">Days Until SSL Expiry</div>
            </div>
            <div class="bg-muted/30 rounded-lg p-4 text-center">
              <div class="text-2xl font-bold text-green-600">{{ Math.round(selectedWebsite.stats.success_rate) }}%</div>
              <div class="text-sm text-muted-foreground">Success Rate</div>
            </div>
            <div class="bg-muted/30 rounded-lg p-4 text-center">
              <div class="text-2xl font-bold text-blue-600">{{ Math.round(selectedWebsite.stats.avg_response_time) }}</div>
              <div class="text-sm text-muted-foreground">Avg Response (ms)</div>
            </div>
            <div class="bg-muted/30 rounded-lg p-4 text-center">
              <div class="text-2xl font-bold text-foreground">{{ selectedWebsite.stats.total_ssl_checks }}</div>
              <div class="text-sm text-muted-foreground">Total Checks</div>
            </div>
          </div>

          <!-- Enhanced SSL and Uptime Information -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- SSL Certificate Details -->
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-foreground">SSL Certificate</h3>
                <button
                  class="inline-flex items-center px-2 py-1 text-xs font-medium text-green-700 bg-green-50 border border-green-200 rounded-md hover:bg-green-100 transition-colors"
                >
                  <Shield class="h-3 w-3 mr-1" />
                  Check Now
                </button>
              </div>

              <div class="space-y-3">
                <div class="flex justify-between items-center">
                  <span class="text-sm text-muted-foreground">Status:</span>
                  <span
                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                    :class="{
                      'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': selectedWebsite.ssl.status === 'valid',
                      'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': selectedWebsite.ssl.status === 'expired',
                      'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': selectedWebsite.ssl.status === 'expiring_soon',
                      'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200': selectedWebsite.ssl.status === 'unknown'
                    }"
                  >
                    {{ selectedWebsite.ssl.status }}
                  </span>
                </div>

                <div v-if="selectedWebsite.ssl.days_remaining" class="flex justify-between items-center">
                  <span class="text-sm text-muted-foreground">Days Remaining:</span>
                  <span
                    class="font-medium"
                    :class="getDaysRemainingColor(selectedWebsite.ssl.days_remaining)"
                  >
                    {{ selectedWebsite.ssl.days_remaining }} days
                  </span>
                </div>

                <div v-if="selectedWebsite.ssl.certificate" class="space-y-2">
                  <div class="flex justify-between items-start">
                    <span class="text-sm text-muted-foreground">Issuer:</span>
                    <span class="text-sm text-foreground text-right">{{ selectedWebsite.ssl.certificate.issuer }}</span>
                  </div>

                  <div class="flex justify-between items-start">
                    <span class="text-sm text-muted-foreground">Subject:</span>
                    <span class="text-sm text-foreground text-right">{{ selectedWebsite.ssl.certificate.subject }}</span>
                  </div>

                  <div class="flex justify-between items-start">
                    <span class="text-sm text-muted-foreground">Expires:</span>
                    <span class="text-sm text-foreground text-right">{{ formatDate(selectedWebsite.ssl.certificate.expires_at) }}</span>
                  </div>

                  <div class="flex justify-between items-start">
                    <span class="text-sm text-muted-foreground">Algorithm:</span>
                    <span class="text-sm text-foreground text-right">{{ selectedWebsite.ssl.certificate.signature_algorithm }}</span>
                  </div>

                  <div class="flex justify-between items-start">
                    <span class="text-sm text-muted-foreground">Serial Number:</span>
                    <span class="text-sm text-foreground text-right font-mono">{{ selectedWebsite.ssl.certificate.serial_number }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Uptime Monitoring Details -->
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-foreground">Uptime Monitoring</h3>
                <button
                  class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100 transition-colors"
                >
                  <Zap class="h-3 w-3 mr-1" />
                  Check Now
                </button>
              </div>

              <div class="space-y-3">
                <div class="flex justify-between items-center">
                  <span class="text-sm text-muted-foreground">Current Status:</span>
                  <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                    Online
                  </span>
                </div>

                <div class="flex justify-between items-center">
                  <span class="text-sm text-muted-foreground">Last Response Time:</span>
                  <span class="text-sm text-foreground font-medium">{{ Math.round(selectedWebsite.stats.avg_response_time) }}ms</span>
                </div>

                <div class="flex justify-between items-center">
                  <span class="text-sm text-muted-foreground">Uptime This Month:</span>
                  <span class="text-sm text-foreground font-medium text-green-600">99.9%</span>
                </div>

                <div class="flex justify-between items-center">
                  <span class="text-sm text-muted-foreground">Failed Checks:</span>
                  <span class="text-sm text-foreground font-medium">0</span>
                </div>

                <div class="flex justify-between items-center">
                  <span class="text-sm text-muted-foreground">Last Check:</span>
                  <span class="text-sm text-foreground font-medium">2 minutes ago</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Performance & Historical Data -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent SSL Checks -->
            <div>
              <h3 class="text-lg font-semibold text-foreground mb-4">Recent SSL Checks</h3>
              <div class="space-y-3 max-h-64 overflow-y-auto">
                <div
                  v-for="check in selectedWebsite.ssl.recent_checks"
                  :key="check.checked_at"
                  class="flex items-center justify-between p-3 border border-border rounded-lg"
                >
                  <div class="flex items-center space-x-3">
                    <span
                      class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                      :class="{
                        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': check.status === 'valid',
                        'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': check.status === 'failed',
                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': check.status === 'warning'
                      }"
                    >
                      {{ check.status }}
                    </span>
                    <div>
                      <div class="text-sm font-medium">
                        {{ new Date(check.checked_at).toLocaleString() }}
                      </div>
                      <div v-if="check.error_message" class="text-xs text-red-600">
                        {{ check.error_message }}
                      </div>
                    </div>
                  </div>
                  <div class="text-sm text-muted-foreground">
                    {{ check.response_time }}ms
                  </div>
                </div>
              </div>
            </div>

            <!-- Response Time Chart Placeholder -->
            <div>
              <h3 class="text-lg font-semibold text-foreground mb-4">Response Time Trends</h3>
              <div class="bg-muted/30 rounded-lg p-6 h-64 flex items-center justify-center">
                <div class="text-center">
                  <Clock class="h-12 w-12 text-muted-foreground mx-auto mb-2" />
                  <p class="text-sm text-muted-foreground">Response time chart</p>
                  <p class="text-xs text-muted-foreground">Coming soon in Phase 5</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Alert Configuration & Advanced Settings -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Monitoring Configuration -->
            <div>
              <h3 class="text-lg font-semibold text-foreground mb-4">Alert Configuration</h3>
              <div class="space-y-4">
                <div class="flex items-center justify-between p-3 border border-border rounded-lg">
                  <div>
                    <div class="font-medium text-sm">SSL Certificate Expiry</div>
                    <div class="text-xs text-muted-foreground">Alert when expiring in 7 days</div>
                  </div>
                  <span class="text-sm font-medium text-green-600">Active</span>
                </div>
                <div class="flex items-center justify-between p-3 border border-border rounded-lg">
                  <div>
                    <div class="font-medium text-sm">Website Downtime</div>
                    <div class="text-xs text-muted-foreground">Alert when site is unreachable</div>
                  </div>
                  <span class="text-sm font-medium text-green-600">Active</span>
                </div>
                <div class="flex items-center justify-between p-3 border border-border rounded-lg">
                  <div>
                    <div class="font-medium text-sm">Response Time</div>
                    <div class="text-xs text-muted-foreground">Alert when slower than 5s</div>
                  </div>
                  <span class="text-sm font-medium text-green-600">Active</span>
                </div>
              </div>
            </div>

            <!-- Monitoring Settings -->
            <div>
              <h3 class="text-lg font-semibold text-foreground mb-4">Monitoring Settings</h3>
              <div class="space-y-4">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-muted-foreground">SSL Monitoring:</span>
                  <span
                    class="text-sm font-medium"
                    :class="selectedWebsite.monitoring.ssl_enabled ? 'text-green-600' : 'text-gray-500'"
                  >
                    {{ selectedWebsite.monitoring.ssl_enabled ? 'Enabled' : 'Disabled' }}
                  </span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-sm text-muted-foreground">Uptime Monitoring:</span>
                  <span
                    class="text-sm font-medium"
                    :class="selectedWebsite.monitoring.uptime_enabled ? 'text-green-600' : 'text-gray-500'"
                  >
                    {{ selectedWebsite.monitoring.uptime_enabled ? 'Enabled' : 'Disabled' }}
                  </span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-sm text-muted-foreground">Check Frequency:</span>
                  <span class="text-sm text-foreground font-medium">Every 5 minutes</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-sm text-muted-foreground">Notification Method:</span>
                  <span class="text-sm text-foreground font-medium">Email + Dashboard</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="flex justify-between items-center p-6 border-t border-border">
          <div class="flex space-x-2">
            <button
              @click="openCertificateAnalysis(selectedWebsite)"
              :disabled="analysisLoading"
              class="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100 transition-colors disabled:opacity-50"
            >
              <Eye class="h-4 w-4 mr-2" />
              {{ analysisLoading ? 'Analyzing...' : 'Certificate Analysis' }}
            </button>
            <button class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-gray-50 border border-gray-200 rounded-md hover:bg-gray-100 transition-colors">
              <Edit class="h-4 w-4 mr-2" />
              Edit Settings
            </button>
          </div>
          <div class="flex space-x-3">
            <button
              class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors"
              @click="closeModal"
            >
              Close
            </button>
            <button class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-primary rounded-md hover:bg-primary/90 transition-colors">
              <Shield class="h-4 w-4 mr-2" />
              Run Full Check
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Certificate Analysis Modal -->
    <div
      v-if="showCertificateAnalysis"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click="closeCertificateAnalysis"
    >
      <div
        class="bg-card text-card-foreground rounded-lg shadow-xl max-w-6xl w-full mx-4 max-h-[90vh] overflow-y-auto"
        @click.stop
      >
        <div class="flex items-center justify-between p-6 border-b border-border">
          <div>
            <h2 class="text-xl font-semibold text-foreground">SSL Certificate Analysis</h2>
            <p class="text-sm text-muted-foreground">{{ certificateAnalysis?.website?.name }} - {{ certificateAnalysis?.website?.url }}</p>
          </div>
          <button
            class="text-muted-foreground hover:text-foreground"
            @click="closeCertificateAnalysis"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div v-if="certificateAnalysis" class="p-6 space-y-6">
          <!-- Risk Assessment Banner -->
          <div
            v-if="certificateAnalysis.analysis.risk_assessment"
            class="p-4 rounded-lg"
            :class="{
              'bg-red-50 border border-red-200': certificateAnalysis.analysis.risk_assessment.level === 'critical',
              'bg-yellow-50 border border-yellow-200': certificateAnalysis.analysis.risk_assessment.level === 'high',
              'bg-orange-50 border border-orange-200': certificateAnalysis.analysis.risk_assessment.level === 'medium',
              'bg-green-50 border border-green-200': certificateAnalysis.analysis.risk_assessment.level === 'low'
            }"
          >
            <div class="flex items-center justify-between">
              <div>
                <h3
                  class="font-semibold"
                  :class="{
                    'text-red-800': certificateAnalysis.analysis.risk_assessment.level === 'critical',
                    'text-yellow-800': certificateAnalysis.analysis.risk_assessment.level === 'high',
                    'text-orange-800': certificateAnalysis.analysis.risk_assessment.level === 'medium',
                    'text-green-800': certificateAnalysis.analysis.risk_assessment.level === 'low'
                  }"
                >
                  Risk Level: {{ certificateAnalysis.analysis.risk_assessment.level.toUpperCase() }}
                </h3>
                <p class="text-sm text-muted-foreground">
                  Security Score: {{ certificateAnalysis.analysis.risk_assessment.score }}/100
                </p>
              </div>
              <div v-if="certificateAnalysis.analysis.certificate_authority?.is_lets_encrypt" class="text-sm">
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                  Let's Encrypt
                </span>
              </div>
            </div>
            <div v-if="certificateAnalysis.analysis.risk_assessment.issues?.length" class="mt-3">
              <ul class="list-disc list-inside space-y-1 text-sm text-muted-foreground">
                <li v-for="issue in certificateAnalysis.analysis.risk_assessment.issues" :key="issue">
                  {{ issue }}
                </li>
              </ul>
            </div>
          </div>

          <!-- Certificate Details Grid -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Basic Certificate Information -->
            <div class="space-y-4">
              <h3 class="text-lg font-semibold text-foreground">Certificate Information</h3>
              <div class="space-y-3">
                <div class="flex justify-between">
                  <span class="text-sm text-muted-foreground">Subject:</span>
                  <span class="text-sm text-foreground font-mono">{{ certificateAnalysis.analysis.basic_info?.subject }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-sm text-muted-foreground">Issuer:</span>
                  <span class="text-sm text-foreground">{{ certificateAnalysis.analysis.basic_info?.issuer }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-sm text-muted-foreground">Serial Number:</span>
                  <span class="text-sm text-foreground font-mono break-all">{{ certificateAnalysis.analysis.basic_info?.serial_number }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-sm text-muted-foreground">Signature Algorithm:</span>
                  <span class="text-sm text-foreground">{{ certificateAnalysis.analysis.basic_info?.signature_algorithm }}</span>
                </div>
              </div>
            </div>

            <!-- Validity Information -->
            <div class="space-y-4">
              <h3 class="text-lg font-semibold text-foreground">Validity Period</h3>
              <div class="space-y-3">
                <div class="flex justify-between">
                  <span class="text-sm text-muted-foreground">Valid From:</span>
                  <span class="text-sm text-foreground">{{ new Date(certificateAnalysis.analysis.validity?.valid_from).toLocaleDateString() }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-sm text-muted-foreground">Valid Until:</span>
                  <span class="text-sm text-foreground">{{ new Date(certificateAnalysis.analysis.validity?.valid_until).toLocaleDateString() }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-sm text-muted-foreground">Days Remaining:</span>
                  <span
                    class="text-sm font-medium"
                    :class="{
                      'text-red-600': certificateAnalysis.analysis.validity?.days_remaining <= 7,
                      'text-yellow-600': certificateAnalysis.analysis.validity?.days_remaining > 7 && certificateAnalysis.analysis.validity?.days_remaining <= 30,
                      'text-green-600': certificateAnalysis.analysis.validity?.days_remaining > 30
                    }"
                  >
                    {{ certificateAnalysis.analysis.validity?.days_remaining }} days
                  </span>
                </div>
              </div>
            </div>

            <!-- Security Analysis -->
            <div class="space-y-4">
              <h3 class="text-lg font-semibold text-foreground">Security Analysis</h3>
              <div class="space-y-3">
                <div class="flex justify-between">
                  <span class="text-sm text-muted-foreground">Key Algorithm:</span>
                  <span class="text-sm text-foreground">{{ certificateAnalysis.analysis.security?.key_algorithm }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-sm text-muted-foreground">Key Size:</span>
                  <span class="text-sm text-foreground">{{ certificateAnalysis.analysis.security?.key_size }} bits</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-sm text-muted-foreground">Security Score:</span>
                  <span
                    class="text-sm font-medium"
                    :class="{
                      'text-red-600': certificateAnalysis.analysis.security?.security_score < 70,
                      'text-yellow-600': certificateAnalysis.analysis.security?.security_score >= 70 && certificateAnalysis.analysis.security?.security_score < 90,
                      'text-green-600': certificateAnalysis.analysis.security?.security_score >= 90
                    }"
                  >
                    {{ certificateAnalysis.analysis.security?.security_score }}/100
                  </span>
                </div>
              </div>
            </div>

            <!-- Domain Coverage -->
            <div class="space-y-4">
              <h3 class="text-lg font-semibold text-foreground">Domain Coverage</h3>
              <div class="space-y-3">
                <div class="flex justify-between">
                  <span class="text-sm text-muted-foreground">Primary Domain:</span>
                  <span class="text-sm text-foreground font-mono">{{ certificateAnalysis.analysis.domains?.primary_domain }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-sm text-muted-foreground">Wildcard Certificate:</span>
                  <span class="text-sm text-foreground">{{ certificateAnalysis.analysis.domains?.wildcard_cert ? 'Yes' : 'No' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-sm text-muted-foreground">Covers Requested Domain:</span>
                  <span
                    class="text-sm font-medium"
                    :class="certificateAnalysis.analysis.domains?.covers_requested_domain ? 'text-green-600' : 'text-red-600'"
                  >
                    {{ certificateAnalysis.analysis.domains?.covers_requested_domain ? 'Yes' : 'No' }}
                  </span>
                </div>
                <div v-if="certificateAnalysis.analysis.domains?.subject_alt_names?.length" class="space-y-2">
                  <span class="text-sm text-muted-foreground">Subject Alternative Names:</span>
                  <div class="flex flex-wrap gap-1">
                    <span
                      v-for="san in certificateAnalysis.analysis.domains.subject_alt_names"
                      :key="san"
                      class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-muted text-muted-foreground"
                    >
                      {{ san }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Recommendations -->
          <div v-if="certificateAnalysis.analysis.risk_assessment?.recommendations?.length" class="space-y-4">
            <h3 class="text-lg font-semibold text-foreground">Recommendations</h3>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
              <ul class="list-disc list-inside space-y-2 text-sm text-blue-800">
                <li v-for="recommendation in certificateAnalysis.analysis.risk_assessment.recommendations" :key="recommendation">
                  {{ recommendation }}
                </li>
              </ul>
            </div>
          </div>
        </div>

        <div class="flex justify-end space-x-3 p-6 border-t border-border">
          <button
            class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors"
            @click="closeCertificateAnalysis"
          >
            Close Analysis
          </button>
        </div>
      </div>
    </div>

    <!-- Advanced Certificate Management Components -->

    <!-- Certificate Details Modal -->
    <CertificateDetailsModal
      :is-open="showCertificateDetails"
      :website-id="selectedWebsiteForCertificate?.id"
      :website-name="selectedWebsiteForCertificate?.name"
      :website-url="selectedWebsiteForCertificate?.url"
      @close="closeCertificateDetails"
    />

    <!-- Bulk Certificate Actions -->
    <BulkCertificateActions
      v-if="selectedWebsites.length > 0"
      :selected-websites="websites.data.filter(w => selectedWebsites.includes(w.id))"
      :available-teams="availableTeams || []"
      @clear-selection="selectedWebsites = []"
      @refresh-websites="() => router.reload()"
      @show-toast="(message, type) => console.log(type + ': ' + message)"
    />

    <!-- Bulk Transfer Modal -->
    <BulkTransferModal
      :is-open="showBulkTransferModal"
      :selected-websites="selectedWebsitesForTransfer"
      :available-teams="availableTeams || []"
      @close="closeBulkTransferModal"
      @transfer-completed="handleTransferCompleted"
    />

    <!-- Website Alerts Modal -->
    <WebsiteAlertsModal
      :show="showWebsiteAlertsModal"
      :website="selectedWebsiteForAlerts"
      :alert-configurations="websiteAlertConfigurations"
      :loading="alertsModalLoading"
      @close="closeAlertsModal"
      @update-alert="handleUpdateAlert"
      @test-alert="handleTestAlert"
    />
  </DashboardLayout>
</template>