<template>
  <DashboardLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-muted-foreground">
            Bulk Certificate Operations
          </h2>
          <p class="mt-1 text-sm text-foreground dark:text-muted-foreground">
            Manage multiple SSL certificates with advanced batch operations
          </p>
        </div>

        <div class="flex space-x-4">
          <Button @click="openWebsiteSelector" size="sm" class="flex items-center space-x-2">
            <Plus class="h-4 w-4" />
            <span>New Bulk Operation</span>
          </Button>
          <Button @click="refreshOperations" size="sm" variant="outline">
            <RefreshCw class="h-4 w-4 mr-2" :class="{ 'animate-spin': isRefreshing }" />
            Refresh
          </Button>
        </div>
      </div>
    </template>

    <div class="space-y-8">
      <!-- Quick Actions -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center space-x-2">
            <Zap class="h-5 w-5" />
            <span>Quick Actions</span>
          </CardTitle>
          <CardDescription>
            Perform common bulk operations across all or filtered websites
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div v-for="quickAction in quickActions" :key="quickAction.id" class="p-4 border rounded-lg hover:shadow-md transition-shadow cursor-pointer" @click="executeQuickAction(quickAction)">
              <div class="flex items-center space-x-3">
                <div class="rounded-lg p-2" :class="quickAction.iconBg">
                  <component :is="quickAction.icon" class="h-5 w-5" :class="quickAction.iconColor" />
                </div>
                <div>
                  <h3 class="font-medium text-foreground dark:text-foreground">{{ quickAction.name }}</h3>
                  <p class="text-sm text-foreground dark:text-muted-foreground">{{ quickAction.description }}</p>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Active Operations -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center justify-between">
            <div class="flex items-center space-x-2">
              <Activity class="h-5 w-5" />
              <span>Active Operations</span>
            </div>
            <Badge v-if="activeOperations.length > 0" variant="default">
              {{ activeOperations.length }} running
            </Badge>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div v-if="activeOperations.length === 0" class="text-center py-8 text-muted-foreground dark:text-muted-foreground">
            <Activity class="h-12 w-12 mx-auto mb-2 opacity-50" />
            <p class="font-medium">No active operations</p>
            <p class="text-sm mt-1">All bulk operations have completed</p>
          </div>
          <div v-else class="space-y-4">
            <div v-for="operation in activeOperations" :key="operation.id" class="p-4 border rounded-lg">
              <div class="flex items-center justify-between mb-3">
                <div class="flex items-center space-x-3">
                  <component :is="getOperationIcon(operation.type)" class="h-5 w-5 text-primary dark:text-blue-400" />
                  <div>
                    <h4 class="font-medium text-foreground dark:text-foreground">{{ operation.name }}</h4>
                    <p class="text-sm text-foreground dark:text-muted-foreground">{{ operation.description }}</p>
                  </div>
                </div>
                <div class="flex items-center space-x-2">
                  <Badge variant="default">{{ operation.status }}</Badge>
                  <Button @click="pauseOperation(operation.id)" size="sm" variant="outline">
                    <Pause class="h-4 w-4" />
                  </Button>
                  <Button @click="cancelOperation(operation.id)" size="sm" variant="outline">
                    <X class="h-4 w-4" />
                  </Button>
                </div>
              </div>

              <!-- Progress Section -->
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-foreground dark:text-muted-foreground">Progress</span>
                  <span class="text-foreground dark:text-foreground">
                    {{ operation.progress.current }} / {{ operation.progress.total }}
                  </span>
                </div>
                <div class="w-full bg-muted dark:bg-muted rounded-full h-2">
                  <div
                    class="bg-blue-600 h-2 rounded-full transition-all duration-500"
                    :style="{ width: `${(operation.progress.current / operation.progress.total) * 100}%` }"
                  ></div>
                </div>
                <div class="text-xs text-muted-foreground dark:text-muted-foreground">
                  ETA: {{ operation.eta }} | Rate: {{ operation.rate }}/min
                </div>
              </div>

              <!-- Current Task -->
              <div v-if="operation.currentTask" class="mt-3 p-2 bg-blue-50 dark:bg-blue-900/20 rounded">
                <div class="text-sm text-blue-800 dark:text-blue-200">
                  <strong>Current:</strong> {{ operation.currentTask }}
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Operation History -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center justify-between">
            <div class="flex items-center space-x-2">
              <History class="h-5 w-5" />
              <span>Operation History</span>
            </div>
            <div class="flex space-x-2">
              <select
                v-model="historyFilter"
                @change="filterHistory"
                class="px-3 py-1 text-sm border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
              >
                <option value="all">All Operations</option>
                <option value="completed">Completed</option>
                <option value="failed">Failed</option>
                <option value="cancelled">Cancelled</option>
              </select>
              <Button @click="clearHistory" size="sm" variant="outline">
                <Trash2 class="h-4 w-4 mr-1" />
                Clear History
              </Button>
            </div>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="border-b border-border">
                <tr class="text-left">
                  <th class="py-2 font-medium text-foreground dark:text-foreground">Operation</th>
                  <th class="py-2 font-medium text-foreground dark:text-foreground">Status</th>
                  <th class="py-2 font-medium text-foreground dark:text-foreground">Websites</th>
                  <th class="py-2 font-medium text-foreground dark:text-foreground">Duration</th>
                  <th class="py-2 font-medium text-foreground dark:text-foreground">Completed</th>
                  <th class="py-2 font-medium text-foreground dark:text-foreground">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-border">
                <tr v-for="operation in filteredHistory" :key="operation.id" class="hover:bg-muted dark:hover:bg-gray-800">
                  <td class="py-3">
                    <div class="flex items-center space-x-2">
                      <component :is="getOperationIcon(operation.type)" class="h-4 w-4" :class="getStatusIconColor(operation.status)" />
                      <div>
                        <div class="font-medium text-foreground dark:text-foreground">{{ operation.name }}</div>
                        <div class="text-xs text-foreground dark:text-muted-foreground">{{ operation.type }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="py-3">
                    <Badge :variant="getStatusVariant(operation.status)">
                      {{ operation.status }}
                    </Badge>
                  </td>
                  <td class="py-3 text-foreground dark:text-muted-foreground">{{ operation.websiteCount }}</td>
                  <td class="py-3 text-foreground dark:text-muted-foreground">{{ operation.duration }}</td>
                  <td class="py-3 text-foreground dark:text-muted-foreground">{{ formatRelativeTime(operation.completedAt) }}</td>
                  <td class="py-3">
                    <div class="flex space-x-2">
                      <Button @click="viewOperationDetails(operation)" size="sm" variant="outline">
                        <Eye class="h-4 w-4" />
                      </Button>
                      <Button v-if="operation.status === 'completed'" @click="downloadResults(operation)" size="sm" variant="outline">
                        <Download class="h-4 w-4" />
                      </Button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Website Selector Modal -->
    <WebsiteSelectorModal
      :isOpen="showWebsiteSelector"
      @close="showWebsiteSelector = false"
      @selected="handleWebsiteSelection"
    />

    <!-- Bulk Operations Manager -->
    <Dialog :open="showBulkManager" @update:open="showBulkManager = $event">
      <DialogContent class="max-w-6xl max-h-[90vh] overflow-hidden">
        <BulkOperationsManager @close="showBulkManager = false" />
      </DialogContent>
    </Dialog>

    <!-- Operation Details Modal -->
    <OperationDetailsModal
      :isOpen="showOperationDetails"
      :operation="selectedOperation"
      @close="showOperationDetails = false"
    />
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent } from '@/components/ui/dialog';
import BulkOperationsManager from '@/components/ssl/BulkOperationsManager.vue';
import WebsiteSelectorModal from '@/components/ssl/WebsiteSelectorModal.vue';
import OperationDetailsModal from '@/components/ssl/OperationDetailsModal.vue';
import {
  Plus,
  RefreshCw,
  Zap,
  Activity,
  History,
  Pause,
  X,
  Trash2,
  Eye,
  Download,
  Shield,
  AlertTriangle,
  CheckCircle,
  Settings,
  Clock,
  Upload
} from 'lucide-vue-next';

interface BulkOperation {
  id: string;
  name: string;
  type: string;
  description: string;
  status: 'pending' | 'running' | 'paused' | 'completed' | 'failed' | 'cancelled';
  progress: {
    current: number;
    total: number;
  };
  websiteCount: number;
  duration: string;
  completedAt: string;
  eta?: string;
  rate?: number;
  currentTask?: string;
}

interface QuickAction {
  id: string;
  name: string;
  description: string;
  icon: any;
  iconBg: string;
  iconColor: string;
  type: string;
}

const isRefreshing = ref(false);
const showWebsiteSelector = ref(false);
const showBulkManager = ref(false);
const showOperationDetails = ref(false);
const selectedOperation = ref<BulkOperation | null>(null);
const historyFilter = ref('all');

const quickActions: QuickAction[] = [
  {
    id: '1',
    name: 'Check All Certificates',
    description: 'Verify SSL status for all websites',
    icon: Shield,
    iconBg: 'bg-blue-100 dark:bg-blue-900/30',
    iconColor: 'text-primary dark:text-blue-400',
    type: 'certificate_check'
  },
  {
    id: '2',
    name: 'Security Scan',
    description: 'Run vulnerability scan on all sites',
    icon: AlertTriangle,
    iconBg: 'bg-red-100 dark:bg-red-900/30',
    iconColor: 'text-destructive dark:text-red-400',
    type: 'security_scan'
  },
  {
    id: '3',
    name: 'Backup Certificates',
    description: 'Create backups of all certificates',
    icon: Upload,
    iconBg: 'bg-green-100 dark:bg-green-900/30',
    iconColor: 'text-green-600 dark:text-green-400',
    type: 'backup_certificates'
  },
  {
    id: '4',
    name: 'Update Configurations',
    description: 'Apply latest SSL configurations',
    icon: Settings,
    iconBg: 'bg-purple-100 dark:bg-purple-900/30',
    iconColor: 'text-purple-600 dark:text-purple-400',
    type: 'update_configurations'
  }
];

const activeOperations = ref<BulkOperation[]>([
  {
    id: '1',
    name: 'Certificate Status Check',
    type: 'certificate_check',
    description: 'Checking SSL certificate status for all websites',
    status: 'running',
    progress: { current: 15, total: 32 },
    websiteCount: 32,
    duration: '00:05:23',
    completedAt: '',
    eta: '00:03:45',
    rate: 4.2,
    currentTask: 'Checking certificate for omp.office-manager-pro.com'
  }
]);

const operationHistory = ref<BulkOperation[]>([
  {
    id: '2',
    name: 'Weekly Security Scan',
    type: 'security_scan',
    description: 'Completed vulnerability scan for all websites',
    status: 'completed',
    progress: { current: 32, total: 32 },
    websiteCount: 32,
    duration: '00:12:45',
    completedAt: '2024-09-23T14:30:00Z'
  },
  {
    id: '3',
    name: 'Certificate Backup',
    type: 'backup_certificates',
    description: 'Failed to backup certificates due to storage limit',
    status: 'failed',
    progress: { current: 18, total: 32 },
    websiteCount: 32,
    duration: '00:08:12',
    completedAt: '2024-09-22T10:15:00Z'
  },
  {
    id: '4',
    name: 'Configuration Update',
    type: 'update_configurations',
    description: 'Updated SSL configurations for team websites',
    status: 'completed',
    progress: { current: 25, total: 25 },
    websiteCount: 25,
    duration: '00:06:30',
    completedAt: '2024-09-21T16:45:00Z'
  }
]);

const filteredHistory = computed(() => {
  if (historyFilter.value === 'all') {
    return operationHistory.value;
  }
  return operationHistory.value.filter(op => op.status === historyFilter.value);
});

const getOperationIcon = (type: string) => {
  const iconMap = {
    certificate_check: Shield,
    security_scan: AlertTriangle,
    backup_certificates: Upload,
    update_configurations: Settings,
    force_renewal: RefreshCw
  };
  return iconMap[type] || Activity;
};

const getStatusIconColor = (status: string): string => {
  switch (status) {
    case 'running':
      return 'text-primary dark:text-blue-400';
    case 'completed':
      return 'text-green-600 dark:text-green-400';
    case 'failed':
      return 'text-destructive dark:text-red-400';
    case 'cancelled':
      return 'text-foreground dark:text-muted-foreground';
    default:
      return 'text-yellow-600 dark:text-yellow-400';
  }
};

const getStatusVariant = (status: string) => {
  switch (status) {
    case 'running':
      return 'default';
    case 'completed':
      return 'default';
    case 'failed':
      return 'destructive';
    case 'cancelled':
      return 'secondary';
    default:
      return 'secondary';
  }
};

const formatRelativeTime = (dateString: string): string => {
  const date = new Date(dateString);
  const now = new Date();
  const diffInMinutes = Math.floor((now.getTime() - date.getTime()) / (1000 * 60));

  if (diffInMinutes < 1) return 'Just now';
  if (diffInMinutes < 60) return `${diffInMinutes} minutes ago`;
  if (diffInMinutes < 1440) return `${Math.floor(diffInMinutes / 60)} hours ago`;
  return `${Math.floor(diffInMinutes / 1440)} days ago`;
};

const executeQuickAction = async (action: QuickAction) => {
  // Create a new operation
  const newOperation: BulkOperation = {
    id: Date.now().toString(),
    name: action.name,
    type: action.type,
    description: `Executing ${action.description.toLowerCase()}`,
    status: 'running',
    progress: { current: 0, total: 32 },
    websiteCount: 32,
    duration: '00:00:00',
    completedAt: '',
    eta: '00:08:00',
    rate: 5.0,
    currentTask: 'Initializing operation...'
  };

  activeOperations.value.push(newOperation);

  // Simulate operation progress
  const interval = setInterval(() => {
    if (newOperation.progress.current < newOperation.progress.total) {
      newOperation.progress.current++;
      newOperation.currentTask = `Processing website ${newOperation.progress.current}/${newOperation.progress.total}`;
    } else {
      clearInterval(interval);
      newOperation.status = 'completed';
      newOperation.completedAt = new Date().toISOString();

      // Move to history
      operationHistory.value.unshift({
        ...newOperation,
        duration: '00:06:42'
      });

      // Remove from active
      const index = activeOperations.value.findIndex(op => op.id === newOperation.id);
      if (index !== -1) {
        activeOperations.value.splice(index, 1);
      }
    }
  }, 1000);
};

const pauseOperation = (operationId: string) => {
  const operation = activeOperations.value.find(op => op.id === operationId);
  if (operation) {
    operation.status = operation.status === 'running' ? 'paused' : 'running';
  }
};

const cancelOperation = (operationId: string) => {
  if (!confirm('Are you sure you want to cancel this operation?')) return;

  const operation = activeOperations.value.find(op => op.id === operationId);
  if (operation) {
    operation.status = 'cancelled';
    operation.completedAt = new Date().toISOString();

    // Move to history
    operationHistory.value.unshift({
      ...operation,
      duration: '00:02:15'
    });

    // Remove from active
    const index = activeOperations.value.findIndex(op => op.id === operationId);
    if (index !== -1) {
      activeOperations.value.splice(index, 1);
    }
  }
};

const refreshOperations = async () => {
  isRefreshing.value = true;
  await new Promise(resolve => setTimeout(resolve, 1000));
  isRefreshing.value = false;
};

const filterHistory = () => {
  // Reactive computed property handles this automatically
};

const clearHistory = () => {
  if (confirm('Are you sure you want to clear all operation history?')) {
    operationHistory.value = [];
  }
};

const openWebsiteSelector = () => {
  showWebsiteSelector.value = true;
};

const handleWebsiteSelection = (selectedWebsites: any[]) => {
  showWebsiteSelector.value = false;
  showBulkManager.value = true;
};

const viewOperationDetails = (operation: BulkOperation) => {
  selectedOperation.value = operation;
  showOperationDetails.value = true;
};

const downloadResults = (operation: BulkOperation) => {
  // Simulate results download
  const resultsContent = `Operation Results: ${operation.name}\n\nStatus: ${operation.status}\nWebsites: ${operation.websiteCount}\nDuration: ${operation.duration}\nCompleted: ${operation.completedAt}`;

  const blob = new Blob([resultsContent], { type: 'text/plain;charset=utf-8;' });
  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  link.setAttribute('href', url);
  link.setAttribute('download', `${operation.name.replace(/\s+/g, '_').toLowerCase()}_results.txt`);
  link.style.visibility = 'hidden';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

onMounted(() => {
  // Component initialization
});
</script>