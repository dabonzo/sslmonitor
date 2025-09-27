<template>
  <Dialog :open="isOpen" @update:open="handleClose">
    <DialogContent class="max-w-4xl max-h-[90vh] overflow-hidden">
      <DialogHeader>
        <DialogTitle class="flex items-center space-x-3">
          <div class="rounded-lg p-2" :class="getStatusBackgroundClass(operation?.status)">
            <component :is="getOperationIcon(operation?.type)" class="h-5 w-5" :class="getStatusIconColor(operation?.status)" />
          </div>
          <div>
            <div class="text-xl font-bold text-gray-900 dark:text-gray-100">
              {{ operation?.name }}
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
              Operation Details & Results
            </div>
          </div>
        </DialogTitle>
      </DialogHeader>

      <div v-if="operation" class="space-y-6 py-4 max-h-[60vh] overflow-y-auto">
        <!-- Operation Summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <Card>
            <CardContent class="p-4">
              <div class="flex items-center space-x-3">
                <div class="rounded-lg bg-blue-100 dark:bg-blue-900/30 p-2">
                  <Clock class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Duration</p>
                  <p class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ operation.duration }}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent class="p-4">
              <div class="flex items-center space-x-3">
                <div class="rounded-lg bg-green-100 dark:bg-green-900/30 p-2">
                  <Globe class="h-5 w-5 text-green-600 dark:text-green-400" />
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Websites</p>
                  <p class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ operation.websiteCount }}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent class="p-4">
              <div class="flex items-center space-x-3">
                <div class="rounded-lg bg-purple-100 dark:bg-purple-900/30 p-2">
                  <BarChart3 class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Success Rate</p>
                  <p class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ getSuccessRate() }}%</p>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Progress Overview -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center space-x-2">
              <TrendingUp class="h-5 w-5" />
              <span>Progress Overview</span>
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-4">
              <div class="flex justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">Overall Progress</span>
                <span class="text-gray-900 dark:text-gray-100">
                  {{ operation.progress.current }} / {{ operation.progress.total }}
                </span>
              </div>
              <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                <div
                  class="bg-blue-600 h-3 rounded-full transition-all duration-300"
                  :style="{ width: `${(operation.progress.current / operation.progress.total) * 100}%` }"
                ></div>
              </div>

              <!-- Status Breakdown -->
              <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                <div v-for="status in statusBreakdown" :key="status.label" class="text-center p-3 border rounded-lg">
                  <div class="text-2xl font-bold" :class="status.color">{{ status.count }}</div>
                  <div class="text-sm text-gray-600 dark:text-gray-400">{{ status.label }}</div>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Detailed Results -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center justify-between">
              <div class="flex items-center space-x-2">
                <FileText class="h-5 w-5" />
                <span>Detailed Results</span>
              </div>
              <div class="flex space-x-2">
                <Button @click="exportResults" size="sm" variant="outline">
                  <Download class="h-4 w-4 mr-2" />
                  Export
                </Button>
                <select
                  v-model="resultFilter"
                  class="px-3 py-1 text-sm border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                >
                  <option value="all">All Results</option>
                  <option value="success">Success Only</option>
                  <option value="failed">Failed Only</option>
                  <option value="warning">Warnings Only</option>
                </select>
              </div>
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-3 max-h-96 overflow-y-auto">
              <div v-for="result in filteredResults" :key="result.id" class="p-3 border rounded-lg">
                <div class="flex items-center justify-between mb-2">
                  <div class="flex items-center space-x-2">
                    <component :is="getResultIcon(result.status)" class="h-4 w-4" :class="getResultIconColor(result.status)" />
                    <h4 class="font-medium text-gray-900 dark:text-gray-100">{{ result.website }}</h4>
                  </div>
                  <Badge :variant="getResultVariant(result.status)">
                    {{ result.status }}
                  </Badge>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ result.message }}</p>
                <div class="flex items-center space-x-4 text-xs text-gray-500 dark:text-gray-500 mt-2">
                  <span>Duration: {{ result.duration }}</span>
                  <span>Completed: {{ formatTime(result.completedAt) }}</span>
                </div>
                <div v-if="result.details" class="mt-2 p-2 bg-gray-50 dark:bg-gray-800 rounded text-xs">
                  <pre class="whitespace-pre-wrap">{{ result.details }}</pre>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Error Log (if any) -->
        <Card v-if="operation.status === 'failed' || errorLog.length > 0">
          <CardHeader>
            <CardTitle class="flex items-center space-x-2">
              <AlertTriangle class="h-5 w-5 text-red-600 dark:text-red-400" />
              <span>Error Log</span>
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-2 max-h-48 overflow-y-auto bg-red-50 dark:bg-red-900/20 p-3 rounded-lg">
              <div v-for="error in errorLog" :key="error.id" class="text-sm">
                <div class="flex items-center space-x-2">
                  <span class="text-red-600 dark:text-red-400 font-medium">{{ formatTime(error.timestamp) }}</span>
                  <span class="text-gray-700 dark:text-gray-300">{{ error.message }}</span>
                </div>
                <div v-if="error.details" class="text-xs text-gray-600 dark:text-gray-400 ml-4 mt-1">
                  {{ error.details }}
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Recommendations -->
        <Card v-if="recommendations.length > 0">
          <CardHeader>
            <CardTitle class="flex items-center space-x-2">
              <Lightbulb class="h-5 w-5" />
              <span>Recommendations</span>
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-3">
              <div v-for="recommendation in recommendations" :key="recommendation.id" class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <div class="flex items-start space-x-3">
                  <Lightbulb class="h-4 w-4 text-blue-600 dark:text-blue-400 mt-0.5" />
                  <div class="flex-1">
                    <h4 class="font-medium text-blue-900 dark:text-blue-100">{{ recommendation.title }}</h4>
                    <p class="text-sm text-blue-800 dark:text-blue-200 mt-1">{{ recommendation.description }}</p>
                    <div v-if="recommendation.action" class="mt-2">
                      <Button @click="executeRecommendation(recommendation)" size="sm" variant="outline">
                        {{ recommendation.action }}
                      </Button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <div class="flex justify-end space-x-3 pt-4 border-t">
        <Button @click="handleClose" variant="outline">
          Close
        </Button>
        <Button v-if="operation?.status === 'failed'" @click="retryOperation" variant="default">
          <RefreshCw class="h-4 w-4 mr-2" />
          Retry Operation
        </Button>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Clock,
  Globe,
  BarChart3,
  TrendingUp,
  FileText,
  Download,
  AlertTriangle,
  Lightbulb,
  RefreshCw,
  Shield,
  CheckCircle,
  XCircle,
  AlertCircle
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
}

interface OperationResult {
  id: string;
  website: string;
  status: 'success' | 'failed' | 'warning';
  message: string;
  duration: string;
  completedAt: string;
  details?: string;
}

interface ErrorEntry {
  id: string;
  timestamp: string;
  message: string;
  details?: string;
}

interface Recommendation {
  id: string;
  title: string;
  description: string;
  action?: string;
}

interface Props {
  isOpen: boolean;
  operation: BulkOperation | null;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  close: [];
}>();

const resultFilter = ref('all');

// Mock data for demonstration
const operationResults: OperationResult[] = [
  {
    id: '1',
    website: 'office-manager-pro.com',
    status: 'success',
    message: 'SSL certificate validated successfully',
    duration: '2.3s',
    completedAt: '14:25:12',
    details: 'Certificate: Valid until 2025-03-15\nIssuer: Let\'s Encrypt\nSecurity Score: 92/100'
  },
  {
    id: '2',
    website: 'redgas.at',
    status: 'warning',
    message: 'Certificate expiring in 21 days',
    duration: '1.8s',
    completedAt: '14:25:14',
    details: 'Certificate: Expires 2024-10-15\nAuto-renewal: Enabled\nNext check: 2024-09-30'
  },
  {
    id: '3',
    website: 'legacy-api.example.com',
    status: 'failed',
    message: 'Certificate expired',
    duration: '0.5s',
    completedAt: '14:25:15',
    details: 'Certificate: Expired 2024-08-01\nSSL connection failed\nRequires immediate attention'
  }
];

const errorLog: ErrorEntry[] = [
  {
    id: '1',
    timestamp: '14:25:15',
    message: 'SSL handshake failed for legacy-api.example.com',
    details: 'Connection timeout after 30 seconds'
  },
  {
    id: '2',
    timestamp: '14:25:20',
    message: 'Certificate validation failed',
    details: 'Certificate chain incomplete'
  }
];

const recommendations: Recommendation[] = [
  {
    id: '1',
    title: 'Schedule Certificate Renewal',
    description: 'Set up automatic renewal for certificates expiring within 30 days',
    action: 'Setup Auto-Renewal'
  },
  {
    id: '2',
    title: 'Review Failed Certificates',
    description: 'Manually check and renew failed SSL certificates',
    action: 'Review Failures'
  }
];

const statusBreakdown = computed(() => [
  { label: 'Success', count: 1, color: 'text-green-600 dark:text-green-400' },
  { label: 'Warning', count: 1, color: 'text-yellow-600 dark:text-yellow-400' },
  { label: 'Failed', count: 1, color: 'text-red-600 dark:text-red-400' },
  { label: 'Pending', count: 0, color: 'text-gray-600 dark:text-gray-400' }
]);

const filteredResults = computed(() => {
  if (resultFilter.value === 'all') {
    return operationResults;
  }
  return operationResults.filter(result => result.status === resultFilter.value);
});

const getOperationIcon = (type?: string) => {
  const iconMap = {
    certificate_check: Shield,
    security_scan: AlertTriangle,
    backup_certificates: Download,
    update_configurations: RefreshCw
  };
  return iconMap[type || ''] || Shield;
};

const getStatusBackgroundClass = (status?: string): string => {
  switch (status) {
    case 'completed':
      return 'bg-green-100 dark:bg-green-900/30';
    case 'failed':
      return 'bg-red-100 dark:bg-red-900/30';
    case 'running':
      return 'bg-blue-100 dark:bg-blue-900/30';
    default:
      return 'bg-gray-100 dark:bg-gray-800';
  }
};

const getStatusIconColor = (status?: string): string => {
  switch (status) {
    case 'completed':
      return 'text-green-600 dark:text-green-400';
    case 'failed':
      return 'text-red-600 dark:text-red-400';
    case 'running':
      return 'text-blue-600 dark:text-blue-400';
    default:
      return 'text-gray-600 dark:text-gray-400';
  }
};

const getResultIcon = (status: string) => {
  switch (status) {
    case 'success':
      return CheckCircle;
    case 'failed':
      return XCircle;
    case 'warning':
      return AlertCircle;
    default:
      return Shield;
  }
};

const getResultIconColor = (status: string): string => {
  switch (status) {
    case 'success':
      return 'text-green-600 dark:text-green-400';
    case 'failed':
      return 'text-red-600 dark:text-red-400';
    case 'warning':
      return 'text-yellow-600 dark:text-yellow-400';
    default:
      return 'text-gray-600 dark:text-gray-400';
  }
};

const getResultVariant = (status: string) => {
  switch (status) {
    case 'success':
      return 'default';
    case 'failed':
      return 'destructive';
    case 'warning':
      return 'secondary';
    default:
      return 'outline';
  }
};

const getSuccessRate = (): number => {
  const successCount = operationResults.filter(r => r.status === 'success').length;
  return Math.round((successCount / operationResults.length) * 100);
};

const formatTime = (timeString: string): string => {
  return timeString;
};

const exportResults = () => {
  const csvContent = operationResults
    .map(result => `${result.website},${result.status},${result.message},${result.duration}`)
    .join('\n');

  const blob = new Blob([`Website,Status,Message,Duration\n${csvContent}`], {
    type: 'text/csv;charset=utf-8;'
  });

  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  link.setAttribute('href', url);
  link.setAttribute('download', `operation_results_${props.operation?.id}.csv`);
  link.style.visibility = 'hidden';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

const executeRecommendation = (recommendation: Recommendation) => {
  console.log('Executing recommendation:', recommendation.id);
  // This would trigger the recommended action
};

const retryOperation = () => {
  console.log('Retrying operation:', props.operation?.id);
  // This would restart the failed operation
  emit('close');
};

const handleClose = () => {
  emit('close');
};
</script>