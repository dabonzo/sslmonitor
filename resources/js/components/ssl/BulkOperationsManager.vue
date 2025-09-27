<template>
  <div class="space-y-6">
    <!-- Bulk Operations Header -->
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center space-x-2">
          <Layers class="h-5 w-5" />
          <span>Bulk Certificate Operations</span>
        </CardTitle>
        <CardDescription>
          Manage multiple SSL certificates simultaneously with advanced batch operations
        </CardDescription>
      </CardHeader>
      <CardContent>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <!-- Operation Selection -->
          <div class="space-y-4">
            <h4 class="font-medium text-gray-900 dark:text-gray-100">Available Operations</h4>
            <div class="space-y-2">
              <div v-for="operation in availableOperations" :key="operation.id" class="flex items-center space-x-3">
                <Checkbox
                  :checked="selectedOperations.includes(operation.id)"
                  @update:checked="toggleOperation(operation.id, $event)"
                />
                <div class="flex items-center space-x-2">
                  <component :is="operation.icon" class="h-4 w-4" :class="operation.iconColor" />
                  <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ operation.name }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Scheduling Options -->
          <div class="space-y-4">
            <h4 class="font-medium text-gray-900 dark:text-gray-100">Schedule Options</h4>
            <div class="space-y-3">
              <div class="flex items-center space-x-3">
                <input
                  v-model="schedulingMode"
                  type="radio"
                  value="immediate"
                  id="immediate"
                  class="text-primary focus:ring-primary"
                />
                <label for="immediate" class="text-sm text-gray-700 dark:text-gray-300">Execute immediately</label>
              </div>
              <div class="flex items-center space-x-3">
                <input
                  v-model="schedulingMode"
                  type="radio"
                  value="scheduled"
                  id="scheduled"
                  class="text-primary focus:ring-primary"
                />
                <label for="scheduled" class="text-sm text-gray-700 dark:text-gray-300">Schedule for later</label>
              </div>
              <div v-if="schedulingMode === 'scheduled'" class="ml-6 space-y-2">
                <input
                  v-model="scheduledDateTime"
                  type="datetime-local"
                  class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                />
              </div>
            </div>
          </div>

          <!-- Notification Settings -->
          <div class="space-y-4">
            <h4 class="font-medium text-gray-900 dark:text-gray-100">Notifications</h4>
            <div class="space-y-3">
              <div class="flex items-center space-x-3">
                <Checkbox
                  :checked="notificationSettings.email"
                  @update:checked="notificationSettings.email = $event"
                />
                <span class="text-sm text-gray-700 dark:text-gray-300">Email notifications</span>
              </div>
              <div class="flex items-center space-x-3">
                <Checkbox
                  :checked="notificationSettings.slack"
                  @update:checked="notificationSettings.slack = $event"
                />
                <span class="text-sm text-gray-700 dark:text-gray-300">Slack notifications</span>
              </div>
              <div class="flex items-center space-x-3">
                <Checkbox
                  :checked="notificationSettings.dashboard"
                  @update:checked="notificationSettings.dashboard = $event"
                />
                <span class="text-sm text-gray-700 dark:text-gray-300">Dashboard alerts</span>
              </div>
            </div>
          </div>
        </div>

        <div class="flex justify-end space-x-4 mt-6 pt-4 border-t">
          <Button @click="$emit('close')" variant="outline">
            Cancel
          </Button>
          <Button
            @click="executeOperations"
            :disabled="selectedOperations.length === 0 || isExecuting"
            class="flex items-center space-x-2"
          >
            <Play v-if="!isExecuting" class="h-4 w-4" />
            <div v-else class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
            <span>{{ isExecuting ? 'Executing...' : 'Execute Operations' }}</span>
          </Button>
        </div>
      </CardContent>
    </Card>

    <!-- Operation Queue -->
    <Card v-if="operationQueue.length > 0">
      <CardHeader>
        <CardTitle class="flex items-center space-x-2">
          <Clock class="h-5 w-5" />
          <span>Operation Queue</span>
        </CardTitle>
        <CardDescription>
          Current and pending bulk operations
        </CardDescription>
      </CardHeader>
      <CardContent>
        <div class="space-y-4">
          <div v-for="operation in operationQueue" :key="operation.id" class="p-4 border rounded-lg">
            <div class="flex items-center justify-between mb-2">
              <div class="flex items-center space-x-3">
                <component :is="getOperationIcon(operation.type)" class="h-4 w-4" :class="getOperationIconColor(operation.status)" />
                <div>
                  <h4 class="font-medium text-gray-900 dark:text-gray-100">{{ operation.name }}</h4>
                  <p class="text-sm text-gray-600 dark:text-gray-400">{{ operation.description }}</p>
                </div>
              </div>
              <Badge :variant="getStatusVariant(operation.status)">
                {{ operation.status }}
              </Badge>
            </div>

            <!-- Progress Bar -->
            <div v-if="operation.status === 'executing'" class="space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">Progress</span>
                <span class="text-gray-900 dark:text-gray-100">{{ operation.progress.current }}/{{ operation.progress.total }}</span>
              </div>
              <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div
                  class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                  :style="{ width: `${(operation.progress.current / operation.progress.total) * 100}%` }"
                ></div>
              </div>
            </div>

            <!-- Operation Details -->
            <div class="mt-3 text-xs text-gray-500 dark:text-gray-500">
              <div>Scheduled: {{ formatDateTime(operation.scheduledAt) }}</div>
              <div v-if="operation.startedAt">Started: {{ formatDateTime(operation.startedAt) }}</div>
              <div v-if="operation.completedAt">Completed: {{ formatDateTime(operation.completedAt) }}</div>
              <div>Websites: {{ operation.websiteCount }}</div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end space-x-2 mt-3">
              <Button
                v-if="operation.status === 'pending'"
                @click="cancelOperation(operation.id)"
                size="sm"
                variant="outline"
              >
                <X class="h-4 w-4 mr-1" />
                Cancel
              </Button>
              <Button
                v-if="operation.status === 'completed'"
                @click="viewOperationResults(operation.id)"
                size="sm"
                variant="outline"
              >
                <FileText class="h-4 w-4 mr-1" />
                View Results
              </Button>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Operation Templates -->
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center space-x-2">
          <Bookmark class="h-5 w-5" />
          <span>Operation Templates</span>
        </CardTitle>
        <CardDescription>
          Save and reuse common bulk operation configurations
        </CardDescription>
      </CardHeader>
      <CardContent>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="template in operationTemplates" :key="template.id" class="p-4 border rounded-lg hover:shadow-md transition-shadow cursor-pointer">
            <div class="flex items-start space-x-3">
              <div class="rounded-lg p-2" :class="template.iconBg">
                <component :is="template.icon" class="h-4 w-4" :class="template.iconColor" />
              </div>
              <div class="flex-1">
                <h4 class="font-medium text-gray-900 dark:text-gray-100">{{ template.name }}</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ template.description }}</p>
                <div class="flex items-center space-x-4 mt-3">
                  <Button size="sm" @click="loadTemplate(template)">
                    Load Template
                  </Button>
                  <span class="text-xs text-gray-500 dark:text-gray-500">
                    {{ template.operations.length }} operations
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Create New Template -->
          <div class="p-4 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg hover:border-blue-500 transition-colors cursor-pointer" @click="createNewTemplate">
            <div class="text-center">
              <Plus class="h-6 w-6 mx-auto text-gray-400 mb-2" />
              <h4 class="font-medium text-gray-900 dark:text-gray-100">Create Template</h4>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Save current configuration</p>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
  </div>

  <!-- Template Creation Modal -->
  <TemplateCreationModal
    :isOpen="showTemplateModal"
    :operations="selectedOperations"
    :notification-settings="notificationSettings"
    @close="showTemplateModal = false"
    @create="handleTemplateCreate"
  />
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import TemplateCreationModal from '@/components/ssl/TemplateCreationModal.vue';
import {
  Layers,
  Clock,
  Play,
  X,
  FileText,
  Bookmark,
  Plus,
  Shield,
  RefreshCw,
  Download,
  Upload,
  Settings,
  AlertTriangle,
  CheckCircle,
  Zap
} from 'lucide-vue-next';

interface BulkOperation {
  id: string;
  name: string;
  type: string;
  description: string;
  status: 'pending' | 'executing' | 'completed' | 'failed' | 'cancelled';
  scheduledAt: string;
  startedAt?: string;
  completedAt?: string;
  progress: {
    current: number;
    total: number;
  };
  websiteCount: number;
}

interface OperationTemplate {
  id: string;
  name: string;
  description: string;
  operations: string[];
  icon: any;
  iconBg: string;
  iconColor: string;
}

const emit = defineEmits<{
  close: [];
}>();

const selectedOperations = ref<string[]>([]);
const schedulingMode = ref('immediate');
const scheduledDateTime = ref('');
const isExecuting = ref(false);
const showTemplateModal = ref(false);

const notificationSettings = ref({
  email: true,
  slack: false,
  dashboard: true
});

const availableOperations = [
  {
    id: 'certificate_check',
    name: 'Certificate Status Check',
    icon: Shield,
    iconColor: 'text-blue-600 dark:text-blue-400'
  },
  {
    id: 'force_renewal',
    name: 'Force Certificate Renewal',
    icon: RefreshCw,
    iconColor: 'text-green-600 dark:text-green-400'
  },
  {
    id: 'security_scan',
    name: 'Security Vulnerability Scan',
    icon: AlertTriangle,
    iconColor: 'text-red-600 dark:text-red-400'
  },
  {
    id: 'backup_certificates',
    name: 'Backup Certificates',
    icon: Download,
    iconColor: 'text-purple-600 dark:text-purple-400'
  },
  {
    id: 'update_configurations',
    name: 'Update SSL Configurations',
    icon: Settings,
    iconColor: 'text-orange-600 dark:text-orange-400'
  },
  {
    id: 'performance_test',
    name: 'SSL Performance Test',
    icon: Zap,
    iconColor: 'text-yellow-600 dark:text-yellow-400'
  }
];

const operationQueue = ref<BulkOperation[]>([
  {
    id: '1',
    name: 'Certificate Renewal Batch',
    type: 'force_renewal',
    description: 'Renewing certificates for 15 websites',
    status: 'executing',
    scheduledAt: '2024-09-24T10:00:00Z',
    startedAt: '2024-09-24T10:00:00Z',
    progress: { current: 8, total: 15 },
    websiteCount: 15
  },
  {
    id: '2',
    name: 'Security Scan Queue',
    type: 'security_scan',
    description: 'Security vulnerability scanning for team websites',
    status: 'pending',
    scheduledAt: '2024-09-24T15:00:00Z',
    progress: { current: 0, total: 25 },
    websiteCount: 25
  }
]);

const operationTemplates = ref<OperationTemplate[]>([
  {
    id: '1',
    name: 'Weekly Maintenance',
    description: 'Standard weekly certificate maintenance routine',
    operations: ['certificate_check', 'security_scan', 'backup_certificates'],
    icon: RefreshCw,
    iconBg: 'bg-blue-100 dark:bg-blue-900/30',
    iconColor: 'text-blue-600 dark:text-blue-400'
  },
  {
    id: '2',
    name: 'Emergency Response',
    description: 'Critical security response operations',
    operations: ['certificate_check', 'force_renewal', 'security_scan'],
    icon: AlertTriangle,
    iconBg: 'bg-red-100 dark:bg-red-900/30',
    iconColor: 'text-red-600 dark:text-red-400'
  },
  {
    id: '3',
    name: 'Performance Audit',
    description: 'Comprehensive performance and security audit',
    operations: ['performance_test', 'security_scan', 'certificate_check'],
    icon: Zap,
    iconBg: 'bg-yellow-100 dark:bg-yellow-900/30',
    iconColor: 'text-yellow-600 dark:text-yellow-400'
  }
]);

const toggleOperation = (operationId: string, checked: boolean) => {
  if (checked) {
    if (!selectedOperations.value.includes(operationId)) {
      selectedOperations.value.push(operationId);
    }
  } else {
    selectedOperations.value = selectedOperations.value.filter(id => id !== operationId);
  }
};

const executeOperations = async () => {
  if (selectedOperations.value.length === 0) return;

  isExecuting.value = true;

  // Create new operation entry
  const newOperation: BulkOperation = {
    id: Date.now().toString(),
    name: 'Custom Bulk Operation',
    type: 'mixed',
    description: `Executing ${selectedOperations.value.length} operations`,
    status: 'executing',
    scheduledAt: schedulingMode.value === 'immediate' ? new Date().toISOString() : scheduledDateTime.value,
    startedAt: schedulingMode.value === 'immediate' ? new Date().toISOString() : undefined,
    progress: { current: 0, total: 100 },
    websiteCount: 32 // This would be passed as a prop
  };

  operationQueue.value.unshift(newOperation);

  // Simulate operation execution
  try {
    for (let i = 0; i <= 100; i += 10) {
      await new Promise(resolve => setTimeout(resolve, 500));
      newOperation.progress.current = i;
    }

    newOperation.status = 'completed';
    newOperation.completedAt = new Date().toISOString();
  } catch (error) {
    newOperation.status = 'failed';
  } finally {
    isExecuting.value = false;
    selectedOperations.value = [];
  }
};

const cancelOperation = (operationId: string) => {
  const operation = operationQueue.value.find(op => op.id === operationId);
  if (operation && operation.status === 'pending') {
    operation.status = 'cancelled';
  }
};

const viewOperationResults = (operationId: string) => {
  console.log('Viewing results for operation:', operationId);
  // This would open a detailed results modal
};

const loadTemplate = (template: OperationTemplate) => {
  selectedOperations.value = [...template.operations];
};

const createNewTemplate = () => {
  if (selectedOperations.value.length === 0) {
    alert('Please select at least one operation to create a template');
    return;
  }
  showTemplateModal.value = true;
};

const handleTemplateCreate = (templateData: any) => {
  const newTemplate: OperationTemplate = {
    id: Date.now().toString(),
    name: templateData.name,
    description: templateData.description,
    operations: [...selectedOperations.value],
    icon: Bookmark,
    iconBg: 'bg-gray-100 dark:bg-gray-800',
    iconColor: 'text-gray-600 dark:text-gray-400'
  };

  operationTemplates.value.push(newTemplate);
  showTemplateModal.value = false;
};

const getOperationIcon = (type: string) => {
  const iconMap = {
    certificate_check: Shield,
    force_renewal: RefreshCw,
    security_scan: AlertTriangle,
    backup_certificates: Download,
    update_configurations: Settings,
    performance_test: Zap,
    mixed: Layers
  };
  return iconMap[type] || Layers;
};

const getOperationIconColor = (status: string): string => {
  switch (status) {
    case 'executing':
      return 'text-blue-600 dark:text-blue-400';
    case 'completed':
      return 'text-green-600 dark:text-green-400';
    case 'failed':
      return 'text-red-600 dark:text-red-400';
    case 'cancelled':
      return 'text-gray-600 dark:text-gray-400';
    default:
      return 'text-yellow-600 dark:text-yellow-400';
  }
};

const getStatusVariant = (status: string) => {
  switch (status) {
    case 'executing':
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

const formatDateTime = (dateString: string): string => {
  return new Date(dateString).toLocaleString();
};
</script>