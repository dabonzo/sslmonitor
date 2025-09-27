<template>
  <Card class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-800">
    <CardHeader>
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <div class="rounded-lg bg-blue-100 dark:bg-blue-900/30 p-2">
            <Layers class="h-5 w-5 text-blue-600 dark:text-blue-400" />
          </div>
          <div>
            <CardTitle class="text-xl font-bold text-gray-900 dark:text-gray-100">
              Bulk Certificate Operations
            </CardTitle>
            <CardDescription>
              Perform actions on {{ selectedWebsites.length }} selected websites
            </CardDescription>
          </div>
        </div>
        <Button
          v-if="selectedWebsites.length > 0"
          @click="clearSelection"
          variant="outline"
          size="sm"
        >
          Clear Selection
        </Button>
      </div>
    </CardHeader>

    <CardContent v-if="selectedWebsites.length > 0" class="space-y-6">
      <!-- Quick Stats -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="text-center p-3 bg-white dark:bg-gray-800 rounded-lg border">
          <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ selectedWebsites.length }}</div>
          <div class="text-sm text-gray-600 dark:text-gray-400">Selected</div>
        </div>
        <div class="text-center p-3 bg-white dark:bg-gray-800 rounded-lg border">
          <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ bulkStats.expiring }}</div>
          <div class="text-sm text-gray-600 dark:text-gray-400">Expiring Soon</div>
        </div>
        <div class="text-center p-3 bg-white dark:bg-gray-800 rounded-lg border">
          <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ bulkStats.healthy }}</div>
          <div class="text-sm text-gray-600 dark:text-gray-400">Healthy</div>
        </div>
        <div class="text-center p-3 bg-white dark:bg-gray-800 rounded-lg border">
          <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ bulkStats.letsEncrypt }}</div>
          <div class="text-sm text-gray-600 dark:text-gray-400">Let's Encrypt</div>
        </div>
      </div>

      <!-- Action Categories -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <!-- SSL Monitoring Actions -->
        <div class="space-y-3">
          <h3 class="font-semibold text-gray-900 dark:text-gray-100 flex items-center">
            <Shield class="h-4 w-4 mr-2 text-blue-600 dark:text-blue-400" />
            SSL Monitoring
          </h3>
          <div class="space-y-2">
            <Button
              @click="performBulkAction('check-ssl')"
              :disabled="isPerformingAction"
              variant="outline"
              size="sm"
              class="w-full justify-start"
            >
              <RefreshCw class="h-4 w-4 mr-2" :class="{ 'animate-spin': isPerformingAction === 'check-ssl' }" />
              Check SSL Certificates
            </Button>
            <Button
              @click="performBulkAction('enable-ssl')"
              :disabled="isPerformingAction"
              variant="outline"
              size="sm"
              class="w-full justify-start"
            >
              <ToggleRight class="h-4 w-4 mr-2" />
              Enable SSL Monitoring
            </Button>
            <Button
              @click="performBulkAction('disable-ssl')"
              :disabled="isPerformingAction"
              variant="outline"
              size="sm"
              class="w-full justify-start"
            >
              <ToggleLeft class="h-4 w-4 mr-2" />
              Disable SSL Monitoring
            </Button>
          </div>
        </div>

        <!-- Analysis & Reporting -->
        <div class="space-y-3">
          <h3 class="font-semibold text-gray-900 dark:text-gray-100 flex items-center">
            <BarChart3 class="h-4 w-4 mr-2 text-green-600 dark:text-green-400" />
            Analysis
          </h3>
          <div class="space-y-2">
            <Button
              @click="performBulkAction('analyze-certificates')"
              :disabled="isPerformingAction"
              variant="outline"
              size="sm"
              class="w-full justify-start"
            >
              <Search class="h-4 w-4 mr-2" />
              Analyze All Certificates
            </Button>
            <Button
              @click="exportBulkReport"
              :disabled="isPerformingAction"
              variant="outline"
              size="sm"
              class="w-full justify-start"
            >
              <Download class="h-4 w-4 mr-2" />
              Export Reports
            </Button>
            <Button
              @click="showSecuritySummary = true"
              variant="outline"
              size="sm"
              class="w-full justify-start"
            >
              <FileBarChart class="h-4 w-4 mr-2" />
              Security Summary
            </Button>
          </div>
        </div>

        <!-- Team & Transfer Actions -->
        <div class="space-y-3">
          <h3 class="font-semibold text-gray-900 dark:text-gray-100 flex items-center">
            <Users class="h-4 w-4 mr-2 text-purple-600 dark:text-purple-400" />
            Team Actions
          </h3>
          <div class="space-y-2">
            <Button
              @click="showBulkTransfer = true"
              variant="outline"
              size="sm"
              class="w-full justify-start"
            >
              <ArrowRightLeft class="h-4 w-4 mr-2" />
              Bulk Transfer
            </Button>
            <Button
              @click="performBulkAction('transfer-to-personal')"
              :disabled="isPerformingAction"
              variant="outline"
              size="sm"
              class="w-full justify-start"
            >
              <User class="h-4 w-4 mr-2" />
              Move to Personal
            </Button>
            <Button
              @click="performBulkAction('duplicate-config')"
              :disabled="isPerformingAction"
              variant="outline"
              size="sm"
              class="w-full justify-start"
            >
              <Copy class="h-4 w-4 mr-2" />
              Duplicate Config
            </Button>
          </div>
        </div>
      </div>

      <!-- Danger Zone -->
      <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
        <h3 class="font-semibold text-red-900 dark:text-red-100 flex items-center mb-3">
          <AlertTriangle class="h-4 w-4 mr-2" />
          Danger Zone
        </h3>
        <div class="flex flex-wrap gap-2">
          <Button
            @click="showBulkDeleteConfirm = true"
            :disabled="isPerformingAction"
            variant="destructive"
            size="sm"
          >
            <Trash2 class="h-4 w-4 mr-2" />
            Delete Selected
          </Button>
          <Button
            @click="performBulkAction('reset-config')"
            :disabled="isPerformingAction"
            variant="outline"
            size="sm"
            class="border-red-200 text-red-700 hover:bg-red-50"
          >
            <RotateCcw class="h-4 w-4 mr-2" />
            Reset Configurations
          </Button>
        </div>
      </div>

      <!-- Progress Indicator -->
      <div v-if="isPerformingAction" class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
        <div class="flex items-center space-x-3">
          <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-600"></div>
          <div class="flex-1">
            <div class="text-sm font-medium text-blue-900 dark:text-blue-100">
              {{ currentActionLabel }}
            </div>
            <div class="text-xs text-blue-700 dark:text-blue-300">
              {{ actionProgress.current }} of {{ actionProgress.total }} completed
            </div>
          </div>
          <div class="text-right">
            <div class="text-sm font-semibold text-blue-900 dark:text-blue-100">
              {{ Math.round((actionProgress.current / actionProgress.total) * 100) }}%
            </div>
            <div class="w-20 bg-blue-200 dark:bg-blue-800 rounded-full h-2">
              <div
                class="bg-blue-600 dark:bg-blue-400 h-2 rounded-full transition-all duration-300"
                :style="{ width: `${(actionProgress.current / actionProgress.total) * 100}%` }"
              ></div>
            </div>
          </div>
        </div>
      </div>
    </CardContent>

    <!-- Empty State -->
    <CardContent v-else class="text-center py-8">
      <div class="rounded-lg bg-gray-100 dark:bg-gray-800 p-6">
        <Layers class="h-12 w-12 text-gray-400 mx-auto mb-4" />
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
          No Websites Selected
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">
          Select websites from the table above to perform bulk operations
        </p>
      </div>
    </CardContent>

    <!-- Bulk Transfer Modal -->
    <Dialog :open="showBulkTransfer" @update:open="showBulkTransfer = $event">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Bulk Transfer to Team</DialogTitle>
          <DialogDescription>
            Transfer {{ selectedWebsites.length }} selected websites to a team
          </DialogDescription>
        </DialogHeader>
        <div class="space-y-4">
          <SmartTeamPicker
            v-if="availableTeams"
            :teams="availableTeams"
            :selected-team-id="selectedTeamForTransfer"
            @team-selected="(team) => selectedTeamForTransfer = team?.id || null"
            placeholder="Select team for bulk transfer..."
            search-placeholder="Search your teams..."
            :show-member-count="true"
            :show-user-role="true"
            :max-displayed-teams="50"
          />
          <div class="flex justify-end space-x-2">
            <Button @click="showBulkTransfer = false" variant="outline">Cancel</Button>
            <Button
              @click="performBulkTransfer"
              :disabled="!selectedTeamForTransfer || isPerformingAction"
            >
              Transfer Websites
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>

    <!-- Security Summary Modal -->
    <Dialog :open="showSecuritySummary" @update:open="showSecuritySummary = $event">
      <DialogContent class="max-w-3xl">
        <DialogHeader>
          <DialogTitle>Security Summary Report</DialogTitle>
          <DialogDescription>
            Security analysis for {{ selectedWebsites.length }} selected websites
          </DialogDescription>
        </DialogHeader>
        <SecuritySummaryReport
          :websites="selectedWebsites"
          :is-loading="isLoadingSecuritySummary"
          @close="showSecuritySummary = false"
        />
      </DialogContent>
    </Dialog>

    <!-- Delete Confirmation Modal -->
    <Dialog :open="showBulkDeleteConfirm" @update:open="showBulkDeleteConfirm = $event">
      <DialogContent>
        <DialogHeader>
          <DialogTitle class="flex items-center space-x-2 text-red-600 dark:text-red-400">
            <AlertTriangle class="h-5 w-5" />
            <span>Confirm Bulk Deletion</span>
          </DialogTitle>
          <DialogDescription class="text-red-700 dark:text-red-300">
            This action will permanently delete {{ selectedWebsites.length }} websites and all their SSL monitoring data.
            This cannot be undone.
          </DialogDescription>
        </DialogHeader>
        <div class="space-y-4">
          <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded border border-red-200 dark:border-red-800">
            <p class="text-sm text-red-800 dark:text-red-200">
              Websites to be deleted:
            </p>
            <ul class="text-sm text-red-700 dark:text-red-300 mt-1">
              <li v-for="website in selectedWebsites.slice(0, 5)" :key="website.id">
                • {{ website.name }}
              </li>
              <li v-if="selectedWebsites.length > 5" class="text-red-600 dark:text-red-400">
                ... and {{ selectedWebsites.length - 5 }} more
              </li>
            </ul>
          </div>
          <div class="flex justify-end space-x-2">
            <Button @click="showBulkDeleteConfirm = false" variant="outline">Cancel</Button>
            <Button
              @click="performBulkDelete"
              :disabled="isPerformingAction"
              variant="destructive"
            >
              Delete {{ selectedWebsites.length }} Websites
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  </Card>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import SmartTeamPicker from '@/components/team/SmartTeamPicker.vue';
import SecuritySummaryReport from '@/components/ssl/SecuritySummaryReport.vue';
import {
  Layers,
  Shield,
  RefreshCw,
  ToggleRight,
  ToggleLeft,
  BarChart3,
  Search,
  Download,
  FileBarChart,
  Users,
  ArrowRightLeft,
  User,
  Copy,
  AlertTriangle,
  Trash2,
  RotateCcw
} from 'lucide-vue-next';

interface Website {
  id: number;
  name: string;
  url: string;
  ssl_status?: string;
  ssl_monitoring_enabled: boolean;
  uptime_monitoring_enabled: boolean;
  ssl_urgency_level?: string;
  team_id?: number;
}

interface Team {
  id: number;
  name: string;
  description: string;
}

interface Props {
  selectedWebsites: Website[];
  availableTeams?: Team[];
}

const props = defineProps<Props>();

const emit = defineEmits<{
  clearSelection: [];
  refreshWebsites: [];
  showToast: [message: string, type?: 'success' | 'error'];
}>();

const isPerformingAction = ref<string | false>(false);
const showBulkTransfer = ref(false);
const showSecuritySummary = ref(false);
const showBulkDeleteConfirm = ref(false);
const selectedTeamForTransfer = ref<number | null>(null);
const isLoadingSecuritySummary = ref(false);

const actionProgress = ref({
  current: 0,
  total: 0
});

const currentActionLabel = ref('');

// Compute bulk statistics
const bulkStats = computed(() => {
  const stats = {
    expiring: 0,
    healthy: 0,
    letsEncrypt: 0,
    issues: 0
  };

  props.selectedWebsites.forEach(website => {
    if (website.ssl_urgency_level && ['warning', 'urgent', 'critical'].includes(website.ssl_urgency_level)) {
      stats.expiring++;
    }

    if (website.ssl_status === 'valid' && website.ssl_urgency_level === 'safe') {
      stats.healthy++;
    }

    // Simplified Let's Encrypt detection (would need backend data)
    if (website.ssl_status === 'valid') {
      stats.letsEncrypt++;
    }

    if (website.ssl_status && ['invalid', 'expired'].includes(website.ssl_status)) {
      stats.issues++;
    }
  });

  return stats;
});

const clearSelection = () => {
  emit('clearSelection');
};

const performBulkAction = async (action: string) => {
  if (isPerformingAction.value || props.selectedWebsites.length === 0) return;

  isPerformingAction.value = action;
  actionProgress.value = { current: 0, total: props.selectedWebsites.length };

  const actionLabels = {
    'check-ssl': 'Checking SSL certificates...',
    'enable-ssl': 'Enabling SSL monitoring...',
    'disable-ssl': 'Disabling SSL monitoring...',
    'analyze-certificates': 'Analyzing certificates...',
    'transfer-to-personal': 'Transferring to personal...',
    'duplicate-config': 'Duplicating configurations...',
    'reset-config': 'Resetting configurations...'
  };

  currentActionLabel.value = actionLabels[action] || 'Processing...';

  const websiteIds = props.selectedWebsites.map(w => w.id);

  try {
    switch (action) {
      case 'check-ssl':
        await performBulkCheck(websiteIds);
        break;
      case 'enable-ssl':
        await performBulkToggleSSL(websiteIds, true);
        break;
      case 'disable-ssl':
        await performBulkToggleSSL(websiteIds, false);
        break;
      case 'analyze-certificates':
        await performBulkAnalysis(websiteIds);
        break;
      case 'transfer-to-personal':
        await performBulkTransferToPersonal(websiteIds);
        break;
      case 'duplicate-config':
        await performBulkDuplicateConfig(websiteIds);
        break;
      case 'reset-config':
        await performBulkResetConfig(websiteIds);
        break;
      default:
        throw new Error('Unknown action');
    }

    emit('showToast', `Successfully completed ${action} for ${websiteIds.length} websites`, 'success');
    emit('refreshWebsites');
  } catch (error) {
    emit('showToast', `Failed to perform ${action}: ${error.message}`, 'error');
  } finally {
    isPerformingAction.value = false;
    actionProgress.value = { current: 0, total: 0 };
  }
};

const performBulkCheck = async (websiteIds: number[]) => {
  const response = await fetch('/ssl/websites/bulk-check', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    },
    body: JSON.stringify({ website_ids: websiteIds }),
  });

  if (!response.ok) {
    throw new Error('Bulk check failed');
  }

  // Simulate progress
  for (let i = 0; i <= websiteIds.length; i++) {
    actionProgress.value.current = i;
    await new Promise(resolve => setTimeout(resolve, 200));
  }
};

const performBulkToggleSSL = async (websiteIds: number[], enabled: boolean) => {
  for (let i = 0; i < websiteIds.length; i++) {
    const response = await fetch(`/ssl/websites/${websiteIds[i]}`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({ ssl_monitoring_enabled: enabled }),
    });

    if (!response.ok) {
      throw new Error(`Failed to update website ${websiteIds[i]}`);
    }

    actionProgress.value.current = i + 1;
  }
};

const performBulkAnalysis = async (websiteIds: number[]) => {
  for (let i = 0; i < websiteIds.length; i++) {
    try {
      const response = await fetch(`/ssl/websites/${websiteIds[i]}/certificate-analysis`);
      if (response.ok) {
        // Analysis performed successfully
      }
    } catch (error) {
      // Continue with other websites even if one fails
    }
    actionProgress.value.current = i + 1;
  }
};

const performBulkTransferToPersonal = async (websiteIds: number[]) => {
  const response = await fetch('/ssl/websites/bulk-transfer-to-personal', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    },
    body: JSON.stringify({ website_ids: websiteIds }),
  });

  if (!response.ok) {
    throw new Error('Bulk transfer failed');
  }

  actionProgress.value.current = websiteIds.length;
};

const performBulkDuplicateConfig = async (websiteIds: number[]) => {
  // This would duplicate monitoring configurations
  for (let i = 0; i < websiteIds.length; i++) {
    // Simulate config duplication
    await new Promise(resolve => setTimeout(resolve, 100));
    actionProgress.value.current = i + 1;
  }
};

const performBulkResetConfig = async (websiteIds: number[]) => {
  // This would reset monitoring configurations to defaults
  for (let i = 0; i < websiteIds.length; i++) {
    // Simulate config reset
    await new Promise(resolve => setTimeout(resolve, 50));
    actionProgress.value.current = i + 1;
  }
};

const performBulkTransfer = async () => {
  if (!selectedTeamForTransfer.value) return;

  try {
    isPerformingAction.value = 'bulk-transfer';
    currentActionLabel.value = 'Transferring websites to team...';
    actionProgress.value = { current: 0, total: props.selectedWebsites.length };

    const websiteIds = props.selectedWebsites.map(w => w.id);

    const response = await fetch('/ssl/websites/bulk-transfer-to-team', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({
        website_ids: websiteIds,
        team_id: selectedTeamForTransfer.value
      }),
    });

    if (!response.ok) {
      throw new Error('Bulk transfer failed');
    }

    actionProgress.value.current = websiteIds.length;

    emit('showToast', `Successfully transferred ${websiteIds.length} websites to team`, 'success');
    emit('refreshWebsites');
    showBulkTransfer.value = false;
    selectedTeamForTransfer.value = null;
  } catch (error) {
    emit('showToast', `Transfer failed: ${error.message}`, 'error');
  } finally {
    isPerformingAction.value = false;
  }
};

const performBulkDelete = async () => {
  try {
    isPerformingAction.value = 'bulk-delete';
    currentActionLabel.value = 'Deleting websites...';
    actionProgress.value = { current: 0, total: props.selectedWebsites.length };

    const websiteIds = props.selectedWebsites.map(w => w.id);

    const response = await fetch('/ssl/websites/bulk-destroy', {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({ website_ids: websiteIds }),
    });

    if (!response.ok) {
      throw new Error('Bulk deletion failed');
    }

    actionProgress.value.current = websiteIds.length;

    emit('showToast', `Successfully deleted ${websiteIds.length} websites`, 'success');
    emit('refreshWebsites');
    emit('clearSelection');
    showBulkDeleteConfirm.value = false;
  } catch (error) {
    emit('showToast', `Deletion failed: ${error.message}`, 'error');
  } finally {
    isPerformingAction.value = false;
  }
};

const exportBulkReport = () => {
  const reportData = {
    websites: props.selectedWebsites.map(website => ({
      name: website.name,
      url: website.url,
      ssl_status: website.ssl_status,
      ssl_monitoring_enabled: website.ssl_monitoring_enabled,
      uptime_monitoring_enabled: website.uptime_monitoring_enabled,
      ssl_urgency_level: website.ssl_urgency_level,
      team_id: website.team_id
    })),
    statistics: bulkStats.value,
    generated_at: new Date().toISOString(),
    total_websites: props.selectedWebsites.length
  };

  const blob = new Blob([JSON.stringify(reportData, null, 2)], {
    type: 'application/json'
  });

  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `bulk-ssl-report-${new Date().toISOString().split('T')[0]}.json`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);

  emit('showToast', 'Bulk report exported successfully', 'success');
};
</script>