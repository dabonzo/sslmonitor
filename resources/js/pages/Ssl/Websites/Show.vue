<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Users, ArrowRightLeft, User, Shield } from 'lucide-vue-next';
import SmartTeamPicker from '@/components/team/SmartTeamPicker.vue';
import MonitoringHistoryChart from '@/Components/Monitoring/MonitoringHistoryChart.vue';
import UptimeTrendCard from '@/Components/Monitoring/UptimeTrendCard.vue';
import RecentChecksTimeline from '@/Components/Monitoring/RecentChecksTimeline.vue';
import SslExpirationTrendCard from '@/Components/Monitoring/SslExpirationTrendCard.vue';

interface SslCertificate {
  id: number;
  status: string;
  expires_at: string;
  issuer: string;
  subject: string;
  is_valid: boolean;
  created_at: string;
}

interface SslCheck {
  id: number;
  status: string;
  checked_at: string;
  response_time: number;
  error_message: string | null;
}

interface Team {
  id: number;
  name: string;
  description: string;
}

interface CurrentOwner {
  type: 'team' | 'personal';
  id: number;
  name: string;
}

interface TransferOptions {
  teams: Team[];
  current_owner: CurrentOwner;
}

interface MonitoringResult {
  id: number;
  uuid: string;
  check_type: string;
  trigger_type: string;
  status: string;
  started_at: string;
  completed_at: string;
  duration_ms: number;
  error_message: string | null;
  uptime_status: string;
  http_status_code: number | null;
  response_time_ms: number;
  ssl_status: string;
  certificate_issuer: string | null;
  certificate_expiration_date: string | null;
  days_until_expiration: number | null;
  content_validation_enabled: boolean;
  javascript_rendered: boolean;
}

interface MonitoringStatistics {
  website_id: number;
  website_name: string;
  website_url: string;
  period_days: number;
  statistics: {
    total_checks: number;
    successful_checks: number;
    failed_checks: number;
    avg_response_time_ms: number | null;
    avg_ssl_days_until_expiration: number | null;
    ssl_checks: number;
    uptime_checks: number;
    manual_checks: number;
    scheduled_checks: number;
    success_rate: number;
  };
}

interface Website {
  id: number;
  name: string;
  url: string;
  ssl_monitoring_enabled: boolean;
  uptime_monitoring_enabled: boolean;
  ssl_status: string;
  ssl_certificates: SslCertificate[];
  recent_ssl_checks: SslCheck[];
  monitor_id?: number;
  created_at: string;
  updated_at: string;
}

interface Props {
  website: Website;
}

const props = defineProps<Props>();

const transferOptions = ref<TransferOptions | null>(null);
const selectedTeam = ref<Team | null>(null);
const selectedTeamId = ref<number | null>(null);
const isTransferring = ref(false);
const isLoadingTransferOptions = ref(false);

// Historical data state
const monitoringHistory = ref<MonitoringResult[]>([]);
const monitoringStatistics = ref<MonitoringStatistics | null>(null);
const isLoadingHistory = ref(false);
const isLoadingStatistics = ref(false);
const historyError = ref<string | null>(null);
const statisticsError = ref<string | null>(null);

// Historical data filters
const historyFilters = ref({
  per_page: 50,
  check_type: '',
  status: '',
  trigger_type: '',
  date_from: '',
  date_to: ''
});

// Load monitoring history
const loadMonitoringHistory = async () => {
  if (isLoadingHistory.value) return;

  isLoadingHistory.value = true;
  historyError.value = null;

  try {
    const params = new URLSearchParams();
    Object.entries(historyFilters.value).forEach(([key, value]) => {
      if (value) params.append(key, value);
    });

    const response = await fetch(`/ssl/websites/${props.website.id}/history?${params}`);
    if (response.ok) {
      const data = await response.json();
      monitoringHistory.value = data.data;
    } else {
      historyError.value = 'Failed to load monitoring history';
    }
  } catch (error) {
    console.error('Failed to load monitoring history:', error);
    historyError.value = 'Failed to load monitoring history';
  } finally {
    isLoadingHistory.value = false;
  }
};

// Load monitoring statistics
const loadMonitoringStatistics = async () => {
  if (isLoadingStatistics.value) return;

  isLoadingStatistics.value = true;
  statisticsError.value = null;

  try {
    const response = await fetch(`/ssl/websites/${props.website.id}/statistics`);
    if (response.ok) {
      monitoringStatistics.value = await response.json();
    } else {
      statisticsError.value = 'Failed to load monitoring statistics';
    }
  } catch (error) {
    console.error('Failed to load monitoring statistics:', error);
    statisticsError.value = 'Failed to load monitoring statistics';
  } finally {
    isLoadingStatistics.value = false;
  }
};

// Load transfer options
const loadTransferOptions = async () => {
  if (isLoadingTransferOptions.value) return;

  isLoadingTransferOptions.value = true;
  try {
    const response = await fetch(`/ssl/websites/${props.website.id}/transfer-options`);
    if (response.ok) {
      transferOptions.value = await response.json();
    }
  } catch (error) {
    console.error('Failed to load transfer options:', error);
  } finally {
    isLoadingTransferOptions.value = false;
  }
};

// Team selection handler
const handleTeamSelected = (team: Team | null) => {
  selectedTeam.value = team;
  selectedTeamId.value = team?.id || null;
};

// Transfer to team
const transferToTeam = () => {
  if (!selectedTeam.value || isTransferring.value) return;

  isTransferring.value = true;
  router.post(`/ssl/websites/${props.website.id}/transfer-to-team`, {
    team_id: selectedTeam.value.id
  }, {
    onFinish: () => {
      isTransferring.value = false;
      selectedTeam.value = null;
      selectedTeamId.value = null;
      loadTransferOptions(); // Refresh transfer options
    }
  });
};

// Transfer to personal
const transferToPersonal = () => {
  if (isTransferring.value) return;

  isTransferring.value = true;
  router.post(`/ssl/websites/${props.website.id}/transfer-to-personal`, {}, {
    onFinish: () => {
      isTransferring.value = false;
      loadTransferOptions(); // Refresh transfer options
    }
  });
};

onMounted(() => {
  loadTransferOptions();
  loadMonitoringHistory();
  loadMonitoringStatistics();
});
</script>

<template>
  <Head :title="`${website.name} - SSL Details`" />

  <DashboardLayout :title="website.name">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-foreground">{{ website.name }}</h1>
          <p class="text-muted-foreground">{{ website.url }}</p>
        </div>
        <div class="flex items-center space-x-3">
          <button class="btn btn-outline">Edit</button>
          <button class="btn btn-primary">Check SSL</button>
        </div>
      </div>

      <!-- SSL Status Overview -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="rounded-lg bg-card text-card-foreground p-6 shadow-sm">
          <h3 class="text-lg font-semibold mb-2">SSL Status</h3>
          <span
            class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium"
            :class="{
              'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': website.ssl_status === 'valid',
              'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': website.ssl_status === 'expired',
              'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': website.ssl_status === 'expiring_soon',
              'bg-muted text-gray-800 dark:bg-gray-900 dark:text-muted-foreground': website.ssl_status === 'unknown'
            }"
          >
            {{ website.ssl_status }}
          </span>
        </div>

        <div class="rounded-lg bg-card text-card-foreground p-6 shadow-sm">
          <h3 class="text-lg font-semibold mb-2">SSL Monitoring</h3>
          <span :class="website.ssl_monitoring_enabled ? 'text-green-600' : 'text-muted-foreground'">
            {{ website.ssl_monitoring_enabled ? 'Enabled' : 'Disabled' }}
          </span>
        </div>

        <div class="rounded-lg bg-card text-card-foreground p-6 shadow-sm">
          <h3 class="text-lg font-semibold mb-2">Uptime Monitoring</h3>
          <span :class="website.uptime_monitoring_enabled ? 'text-green-600' : 'text-muted-foreground'">
            {{ website.uptime_monitoring_enabled ? 'Enabled' : 'Disabled' }}
          </span>
        </div>
      </div>

      <!-- Team Management Section -->
      <Card class="bg-muted dark:bg-card border border-border dark:border-border">
        <CardHeader>
          <div class="flex items-center space-x-3">
            <div class="rounded-lg bg-muted dark:bg-card p-2">
              <Users class="h-5 w-5 text-foreground dark:text-muted-foreground" />
            </div>
            <div>
              <CardTitle class="text-xl font-bold text-foreground dark:text-foreground">Team Management</CardTitle>
              <CardDescription>Transfer website ownership between personal and team accounts</CardDescription>
            </div>
          </div>
        </CardHeader>
        <CardContent class="space-y-6">
          <!-- Current Owner Display -->
          <div v-if="transferOptions" class="flex items-center justify-between p-4 rounded-lg bg-background dark:bg-card border border-border dark:border-border">
            <div class="flex items-center space-x-3">
              <div class="rounded-lg p-2" :class="transferOptions.current_owner.type === 'team' ? 'bg-blue-100 dark:bg-blue-900/30' : 'bg-muted dark:bg-muted'">
                <Users v-if="transferOptions.current_owner.type === 'team'" class="h-4 w-4 text-primary dark:text-blue-400" />
                <User v-else class="h-4 w-4 text-foreground dark:text-muted-foreground" />
              </div>
              <div>
                <p class="text-sm font-semibold text-foreground dark:text-foreground">Current Owner</p>
                <p class="text-sm text-foreground dark:text-muted-foreground">{{ transferOptions.current_owner.name }}</p>
              </div>
            </div>
            <Badge
              :variant="transferOptions.current_owner.type === 'team' ? 'default' : 'secondary'"
              class="capitalize"
            >
              {{ transferOptions.current_owner.type }}
            </Badge>
          </div>

          <!-- Transfer Actions -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Transfer to Team -->
            <div class="space-y-4">
              <div class="flex items-center space-x-2">
                <ArrowRightLeft class="h-4 w-4 text-primary dark:text-blue-400" />
                <h3 class="text-lg font-semibold text-foreground dark:text-foreground">Transfer to Team</h3>
              </div>

              <div v-if="transferOptions && transferOptions.teams.length > 0" class="space-y-4">
                <!-- Enhanced Team Picker -->
                <div class="space-y-3">
                  <SmartTeamPicker
                    :teams="transferOptions.teams"
                    :selected-team-id="selectedTeamId"
                    @team-selected="handleTeamSelected"
                    placeholder="Select a team for this website..."
                    search-placeholder="Search your teams..."
                    :show-member-count="true"
                    :show-user-role="true"
                    :max-displayed-teams="50"
                  />

                  <!-- Transfer Preview -->
                  <div v-if="selectedTeam" class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <div class="flex items-center space-x-3">
                      <div class="rounded-lg bg-blue-100 dark:bg-blue-900/30 p-2">
                        <Users class="h-4 w-4 text-primary dark:text-blue-400" />
                      </div>
                      <div class="flex-1">
                        <p class="text-sm font-semibold text-blue-900 dark:text-blue-100">
                          Ready to transfer to {{ selectedTeam.name }}
                        </p>
                        <p class="text-xs text-blue-700 dark:text-blue-300">
                          Team members will gain access according to their roles
                        </p>
                      </div>
                    </div>
                  </div>

                  <Button
                    @click="transferToTeam"
                    :disabled="!selectedTeam || isTransferring"
                    class="w-full"
                  >
                    <Users class="h-4 w-4 mr-2" />
                    {{ isTransferring ? 'Transferring...' : 'Transfer to Team' }}
                  </Button>
                </div>
              </div>

              <div v-else-if="transferOptions" class="text-center py-4">
                <div class="rounded-lg bg-muted dark:bg-muted p-4">
                  <Shield class="h-8 w-8 text-muted-foreground mx-auto mb-2" />
                  <p class="text-sm text-foreground dark:text-muted-foreground">No teams available for transfer</p>
                  <p class="text-xs text-muted-foreground dark:text-muted-foreground mt-1">You need OWNER, ADMIN, or MANAGER role in a team</p>
                </div>
              </div>
            </div>

            <!-- Transfer to Personal -->
            <div class="space-y-4">
              <div class="flex items-center space-x-2">
                <ArrowRightLeft class="h-4 w-4 text-green-600 dark:text-green-400" />
                <h3 class="text-lg font-semibold text-foreground dark:text-foreground">Transfer to Personal</h3>
              </div>

              <div class="space-y-3">
                <p class="text-sm text-foreground dark:text-muted-foreground">
                  Move this website back to your personal account
                </p>

                <Button
                  @click="transferToPersonal"
                  :disabled="isTransferring || (transferOptions && transferOptions.current_owner.type === 'personal')"
                  variant="outline"
                  class="w-full"
                >
                  <User class="h-4 w-4 mr-2" />
                  {{ isTransferring ? 'Transferring...' : 'Transfer to Personal' }}
                </Button>

                <p v-if="transferOptions && transferOptions.current_owner.type === 'personal'" class="text-xs text-muted-foreground dark:text-muted-foreground text-center">
                  Already owned personally
                </p>
              </div>
            </div>
          </div>

          <!-- Loading State -->
          <div v-if="isLoadingTransferOptions" class="text-center py-4">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-gray-900 dark:border-border mx-auto"></div>
            <p class="text-sm text-foreground dark:text-muted-foreground mt-2">Loading transfer options...</p>
          </div>
        </CardContent>
      </Card>

      <!-- Historical Data Section -->
      <section v-if="website.monitor_id" class="space-y-6">
        <h2 class="text-2xl font-bold text-foreground">Historical Data</h2>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Uptime Trend -->
          <UptimeTrendCard
            :monitor-id="website.monitor_id"
            period="7d"
          />

          <!-- SSL Expiration Trend -->
          <SslExpirationTrendCard
            :monitor-id="website.monitor_id"
          />

          <!-- Recent Checks Timeline -->
          <RecentChecksTimeline
            :monitor-id="website.monitor_id"
            :limit="10"
          />
        </div>

        <!-- Response Time Chart -->
        <div class="glass-card-strong p-6">
          <h3 class="text-lg font-semibold text-foreground mb-4">
            Response Time Trend (7 Days)
          </h3>
          <MonitoringHistoryChart
            :monitor-id="website.monitor_id"
            period="7d"
            :height="300"
          />
        </div>
      </section>

      <!-- Monitoring Statistics -->
      <Card v-if="monitoringStatistics || isLoadingStatistics">
        <CardHeader>
          <CardTitle class="flex items-center space-x-2">
            <div class="rounded-lg bg-muted p-2">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
            </div>
            <span>Monitoring Statistics</span>
          </CardTitle>
          <CardDescription>
            Performance overview for the last {{ monitoringStatistics?.period_days || 30 }} days
          </CardDescription>
        </CardHeader>
        <CardContent>
          <!-- Loading State -->
          <div v-if="isLoadingStatistics" class="text-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto mb-4"></div>
            <p class="text-muted-foreground">Loading statistics...</p>
          </div>

          <!-- Error State -->
          <div v-else-if="statisticsError" class="text-center py-8">
            <div class="rounded-lg bg-destructive/10 p-4">
              <p class="text-destructive">{{ statisticsError }}</p>
            </div>
          </div>

          <!-- Statistics Display -->
          <div v-else-if="monitoringStatistics" class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <div class="text-center">
              <div class="text-2xl font-bold text-primary">{{ monitoringStatistics.statistics.total_checks }}</div>
              <div class="text-sm text-muted-foreground">Total Checks</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-green-600">{{ monitoringStatistics.statistics.success_rate }}%</div>
              <div class="text-sm text-muted-foreground">Success Rate</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-blue-600">{{ monitoringStatistics.statistics.avg_response_time_ms || '--' }}ms</div>
              <div class="text-sm text-muted-foreground">Avg Response</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-orange-600">{{ monitoringStatistics.statistics.scheduled_checks }}</div>
              <div class="text-sm text-muted-foreground">Scheduled</div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Historical Monitoring Data -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center space-x-2">
            <div class="rounded-lg bg-muted p-2">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <span>Historical Monitoring Data</span>
          </CardTitle>
          <CardDescription>
            Detailed monitoring history with filtering and analysis
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-6">
          <!-- Filters -->
          <div class="flex flex-wrap gap-4 p-4 bg-muted rounded-lg">
            <select v-model="historyFilters.check_type" @change="loadMonitoringHistory()" class="px-3 py-2 border rounded-md bg-background">
              <option value="">All Check Types</option>
              <option value="ssl">SSL Only</option>
              <option value="uptime">Uptime Only</option>
              <option value="both">Both</option>
            </select>

            <select v-model="historyFilters.status" @change="loadMonitoringHistory()" class="px-3 py-2 border rounded-md bg-background">
              <option value="">All Statuses</option>
              <option value="success">Success</option>
              <option value="failure">Failure</option>
            </select>

            <select v-model="historyFilters.trigger_type" @change="loadMonitoringHistory()" class="px-3 py-2 border rounded-md bg-background">
              <option value="">All Triggers</option>
              <option value="scheduled">Scheduled</option>
              <option value="manual_immediate">Manual</option>
            </select>

            <button @click="loadMonitoringHistory()" class="px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90">
              Refresh
            </button>
          </div>

          <!-- Loading State -->
          <div v-if="isLoadingHistory" class="text-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto mb-4"></div>
            <p class="text-muted-foreground">Loading monitoring history...</p>
          </div>

          <!-- Error State -->
          <div v-else-if="historyError" class="text-center py-8">
            <div class="rounded-lg bg-destructive/10 p-4">
              <p class="text-destructive">{{ historyError }}</p>
            </div>
          </div>

          <!-- History Table -->
          <div v-else-if="monitoringHistory.length > 0" class="space-y-4">
            <div class="rounded-lg border overflow-hidden">
              <div class="overflow-x-auto">
                <table class="w-full">
                  <thead class="bg-muted">
                    <tr>
                      <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Time</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Type</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Status</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Response</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">SSL</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Duration</th>
                    </tr>
                  </thead>
                  <tbody class="bg-card divide-y divide-border">
                    <tr v-for="result in monitoringHistory.slice(0, 20)" :key="result.id" class="hover:bg-muted/50">
                      <td class="px-4 py-4 whitespace-nowrap text-sm">
                        <div>{{ new Date(result.started_at).toLocaleDateString() }}</div>
                        <div class="text-muted-foreground">{{ new Date(result.started_at).toLocaleTimeString() }}</div>
                      </td>
                      <td class="px-4 py-4 whitespace-nowrap text-sm">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                          {{ result.check_type }}
                        </span>
                      </td>
                      <td class="px-4 py-4 whitespace-nowrap text-sm">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                              :class="result.status === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'">
                          {{ result.status }}
                        </span>
                      </td>
                      <td class="px-4 py-4 whitespace-nowrap text-sm">
                        <div v-if="result.response_time_ms">{{ result.response_time_ms }}ms</div>
                        <div v-else class="text-muted-foreground">--</div>
                      </td>
                      <td class="px-4 py-4 whitespace-nowrap text-sm">
                        <div v-if="result.ssl_status">
                          <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                :class="result.ssl_status === 'valid' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'">
                            {{ result.ssl_status }}
                          </span>
                        </div>
                        <div v-else class="text-muted-foreground">--</div>
                      </td>
                      <td class="px-4 py-4 whitespace-nowrap text-sm">
                        {{ result.duration_ms }}ms
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div v-if="monitoringHistory.length > 20" class="text-center text-sm text-muted-foreground">
              Showing 20 of {{ monitoringHistory.length }} records. Use API for complete data.
            </div>
          </div>

          <!-- Empty State -->
          <div v-else class="text-center py-8">
            <div class="rounded-lg bg-muted p-8">
              <svg class="h-12 w-12 text-muted-foreground mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <p class="text-muted-foreground">No monitoring history found</p>
              <p class="text-sm text-muted-foreground mt-1">Historical data will appear here once monitoring runs</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- SSL Certificates -->
      <div class="rounded-lg bg-card text-card-foreground p-6 shadow-sm">
        <h3 class="text-lg font-semibold mb-4">SSL Certificates</h3>
        <div v-if="website.ssl_certificates.length === 0" class="text-center py-8 text-muted-foreground">
          No SSL certificates found
        </div>
        <div v-else class="space-y-4">
          <div
            v-for="cert in website.ssl_certificates"
            :key="cert.id"
            class="border border-border rounded-lg p-4"
          >
            <div class="flex items-center justify-between mb-2">
              <span
                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                :class="{
                  'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': cert.status === 'valid',
                  'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': cert.status === 'expired',
                  'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': cert.status === 'expiring_soon'
                }"
              >
                {{ cert.status }}
              </span>
              <span class="text-sm text-muted-foreground">
                {{ new Date(cert.created_at).toLocaleDateString() }}
              </span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
              <div>
                <strong>Issuer:</strong> {{ cert.issuer }}
              </div>
              <div>
                <strong>Expires:</strong> {{ new Date(cert.expires_at).toLocaleDateString() }}
              </div>
              <div class="md:col-span-2">
                <strong>Subject:</strong> {{ cert.subject }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent SSL Checks -->
      <div class="rounded-lg bg-card text-card-foreground p-6 shadow-sm">
        <h3 class="text-lg font-semibold mb-4">Recent SSL Checks</h3>
        <div v-if="website.recent_ssl_checks.length === 0" class="text-center py-8 text-muted-foreground">
          No SSL checks performed yet
        </div>
        <div v-else class="space-y-3">
          <div
            v-for="check in website.recent_ssl_checks"
            :key="check.id"
            class="flex items-center justify-between border border-border rounded-lg p-3"
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
                <div v-if="check.error_message" class="text-xs text-destructive">
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
    </div>
  </DashboardLayout>
</template>