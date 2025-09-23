<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Users, ArrowRightLeft, User, Shield } from 'lucide-vue-next';
import SmartTeamPicker from '@/components/team/SmartTeamPicker.vue';

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

interface Website {
  id: number;
  name: string;
  url: string;
  ssl_monitoring_enabled: boolean;
  uptime_monitoring_enabled: boolean;
  ssl_status: string;
  ssl_certificates: SslCertificate[];
  recent_ssl_checks: SslCheck[];
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
              'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200': website.ssl_status === 'unknown'
            }"
          >
            {{ website.ssl_status }}
          </span>
        </div>

        <div class="rounded-lg bg-card text-card-foreground p-6 shadow-sm">
          <h3 class="text-lg font-semibold mb-2">SSL Monitoring</h3>
          <span :class="website.ssl_monitoring_enabled ? 'text-green-600' : 'text-gray-500'">
            {{ website.ssl_monitoring_enabled ? 'Enabled' : 'Disabled' }}
          </span>
        </div>

        <div class="rounded-lg bg-card text-card-foreground p-6 shadow-sm">
          <h3 class="text-lg font-semibold mb-2">Uptime Monitoring</h3>
          <span :class="website.uptime_monitoring_enabled ? 'text-green-600' : 'text-gray-500'">
            {{ website.uptime_monitoring_enabled ? 'Enabled' : 'Disabled' }}
          </span>
        </div>
      </div>

      <!-- Team Management Section -->
      <Card class="bg-gradient-to-r from-gray-50 to-slate-50 dark:from-gray-900 dark:to-slate-900 border border-gray-200 dark:border-gray-700">
        <CardHeader>
          <div class="flex items-center space-x-3">
            <div class="rounded-lg bg-gray-100 dark:bg-gray-800 p-2">
              <Users class="h-5 w-5 text-gray-600 dark:text-gray-400" />
            </div>
            <div>
              <CardTitle class="text-xl font-bold text-gray-900 dark:text-gray-100">Team Management</CardTitle>
              <CardDescription>Transfer website ownership between personal and team accounts</CardDescription>
            </div>
          </div>
        </CardHeader>
        <CardContent class="space-y-6">
          <!-- Current Owner Display -->
          <div v-if="transferOptions" class="flex items-center justify-between p-4 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center space-x-3">
              <div class="rounded-lg p-2" :class="transferOptions.current_owner.type === 'team' ? 'bg-blue-100 dark:bg-blue-900/30' : 'bg-gray-100 dark:bg-gray-700'">
                <Users v-if="transferOptions.current_owner.type === 'team'" class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                <User v-else class="h-4 w-4 text-gray-600 dark:text-gray-400" />
              </div>
              <div>
                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Current Owner</p>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ transferOptions.current_owner.name }}</p>
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
                <ArrowRightLeft class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Transfer to Team</h3>
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
                        <Users class="h-4 w-4 text-blue-600 dark:text-blue-400" />
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
                <div class="rounded-lg bg-gray-100 dark:bg-gray-700 p-4">
                  <Shield class="h-8 w-8 text-gray-400 mx-auto mb-2" />
                  <p class="text-sm text-gray-600 dark:text-gray-400">No teams available for transfer</p>
                  <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">You need OWNER, ADMIN, or MANAGER role in a team</p>
                </div>
              </div>
            </div>

            <!-- Transfer to Personal -->
            <div class="space-y-4">
              <div class="flex items-center space-x-2">
                <ArrowRightLeft class="h-4 w-4 text-green-600 dark:text-green-400" />
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Transfer to Personal</h3>
              </div>

              <div class="space-y-3">
                <p class="text-sm text-gray-600 dark:text-gray-400">
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

                <p v-if="transferOptions && transferOptions.current_owner.type === 'personal'" class="text-xs text-gray-500 dark:text-gray-500 text-center">
                  Already owned personally
                </p>
              </div>
            </div>
          </div>

          <!-- Loading State -->
          <div v-if="isLoadingTransferOptions" class="text-center py-4">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-gray-900 dark:border-gray-100 mx-auto"></div>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">Loading transfer options...</p>
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
    </div>
  </DashboardLayout>
</template>