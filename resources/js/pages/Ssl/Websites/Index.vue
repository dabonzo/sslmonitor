<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';
import { Plus, Edit, Trash2, Eye } from 'lucide-vue-next';
import ssl from '@/routes/ssl';

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
  created_at: string;
}

interface PaginatedWebsites {
  data: Website[];
  links: any[];
  meta: any;
}

interface Props {
  websites: PaginatedWebsites;
  filters: {
    search?: string;
  };
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

const showModal = ref(false);
const selectedWebsite = ref<WebsiteDetails | null>(null);
const loading = ref(false);
const deleting = ref<number | null>(null);

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
</script>

<template>
  <Head title="SSL Websites" />

  <DashboardLayout title="SSL Websites">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-foreground">SSL Websites</h1>
          <p class="text-muted-foreground">Manage and monitor your websites</p>
        </div>
        <Link
          :href="ssl.websites.create().url"
          class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors"
        >
          <Plus class="h-4 w-4 mr-2" />
          Add Website
        </Link>
      </div>

      <!-- Websites Table -->
      <div class="rounded-lg bg-card text-card-foreground p-6 shadow-sm">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="border-b border-border">
                <th class="text-left p-4">Website</th>
                <th class="text-left p-4">SSL Status</th>
                <th class="text-left p-4">Days Remaining</th>
                <th class="text-left p-4">Monitoring</th>
                <th class="text-left p-4">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="website in websites.data"
                :key="website.id"
                class="border-b border-border hover:bg-muted/50 cursor-pointer"
                @click="openWebsiteModal(website)"
              >
                <td class="p-4">
                  <div>
                    <div class="font-medium text-foreground">{{ website.name }}</div>
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
                    v-if="website.ssl_days_remaining !== null"
                    class="font-medium"
                    :class="getDaysRemainingColor(website.ssl_days_remaining)"
                  >
                    {{ website.ssl_days_remaining }} days
                  </span>
                  <span v-else class="text-gray-500 text-sm">N/A</span>
                </td>
                <td class="p-4">
                  <div class="space-y-1">
                    <div class="flex items-center text-sm">
                      <span class="text-muted-foreground mr-2">SSL:</span>
                      <span :class="website.ssl_monitoring_enabled ? 'text-green-600' : 'text-gray-500'">
                        {{ website.ssl_monitoring_enabled ? 'Enabled' : 'Disabled' }}
                      </span>
                    </div>
                    <div class="flex items-center text-sm">
                      <span class="text-muted-foreground mr-2">Uptime:</span>
                      <span :class="website.uptime_monitoring_enabled ? 'text-green-600' : 'text-gray-500'">
                        {{ website.uptime_monitoring_enabled ? 'Enabled' : 'Disabled' }}
                      </span>
                    </div>
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

        <!-- Pagination -->
        <div v-if="websites.links" class="mt-6 flex items-center justify-between">
          <div class="text-sm text-muted-foreground">
            Showing {{ websites.data.length }} websites
          </div>
          <div class="flex items-center space-x-2">
            <!-- Pagination controls would go here -->
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
          <!-- SSL Information -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="space-y-4">
              <h3 class="text-lg font-semibold text-foreground">SSL Certificate</h3>

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

            <!-- Statistics -->
            <div class="space-y-4">
              <h3 class="text-lg font-semibold text-foreground">Statistics</h3>

              <div class="space-y-3">
                <div class="flex justify-between items-center">
                  <span class="text-sm text-muted-foreground">Total SSL Checks:</span>
                  <span class="text-sm text-foreground font-medium">{{ selectedWebsite.stats.total_ssl_checks }}</span>
                </div>

                <div class="flex justify-between items-center">
                  <span class="text-sm text-muted-foreground">Success Rate:</span>
                  <span class="text-sm text-foreground font-medium">{{ Math.round(selectedWebsite.stats.success_rate) }}%</span>
                </div>

                <div class="flex justify-between items-center">
                  <span class="text-sm text-muted-foreground">Avg Response Time:</span>
                  <span class="text-sm text-foreground font-medium">{{ Math.round(selectedWebsite.stats.avg_response_time) }}ms</span>
                </div>

                <div class="flex justify-between items-center">
                  <span class="text-sm text-muted-foreground">Total Certificates:</span>
                  <span class="text-sm text-foreground font-medium">{{ selectedWebsite.stats.total_certificates }}</span>
                </div>
              </div>
            </div>
          </div>

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

          <!-- Monitoring Configuration -->
          <div>
            <h3 class="text-lg font-semibold text-foreground mb-4">Monitoring Configuration</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
            </div>
          </div>
        </div>

        <div class="flex justify-end space-x-3 p-6 border-t border-border">
          <button class="btn btn-outline" @click="closeModal">Close</button>
          <button class="btn btn-primary">Run SSL Check</button>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>