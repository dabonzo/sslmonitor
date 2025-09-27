<template>
  <Dialog :open="isOpen" @update:open="handleClose">
    <DialogContent class="max-w-2xl max-h-[90vh] overflow-hidden">
      <DialogHeader>
        <DialogTitle class="flex items-center space-x-3">
          <div class="rounded-lg bg-blue-100 dark:bg-blue-900/30 p-2">
            <Settings class="h-5 w-5 text-blue-600 dark:text-blue-400" />
          </div>
          <div>
            <div class="text-xl font-bold text-gray-900 dark:text-gray-100">
              Custom Report Builder
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
              Create a customized analytics report
            </div>
          </div>
        </DialogTitle>
      </DialogHeader>

      <div class="space-y-6 py-4 max-h-[60vh] overflow-y-auto">
        <!-- Report Basic Information -->
        <div class="space-y-4">
          <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Report Configuration</h3>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-2">
              <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Report Name</label>
              <input
                v-model="reportConfig.name"
                type="text"
                placeholder="My Custom Report"
                class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
              />
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Report Type</label>
              <select
                v-model="reportConfig.type"
                class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
              >
                <option value="summary">Summary Report</option>
                <option value="detailed">Detailed Analysis</option>
                <option value="comparison">Comparison Report</option>
                <option value="trend">Trend Analysis</option>
                <option value="security">Security Focus</option>
              </select>
            </div>
          </div>

          <div class="space-y-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
            <textarea
              v-model="reportConfig.description"
              rows="2"
              placeholder="Describe what this report should include..."
              class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
            ></textarea>
          </div>
        </div>

        <!-- Time Range Selection -->
        <div class="space-y-4">
          <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Time Range</h3>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-2">
              <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Period</label>
              <select
                v-model="reportConfig.timeRange"
                @change="updateDateRange"
                class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
              >
                <option value="7d">Last 7 days</option>
                <option value="30d">Last 30 days</option>
                <option value="90d">Last 90 days</option>
                <option value="6m">Last 6 months</option>
                <option value="1y">Last year</option>
                <option value="custom">Custom range</option>
              </select>
            </div>

            <div v-if="reportConfig.timeRange === 'custom'" class="space-y-2">
              <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Custom Date Range</label>
              <div class="flex space-x-2">
                <input
                  v-model="reportConfig.startDate"
                  type="date"
                  class="flex-1 px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                />
                <input
                  v-model="reportConfig.endDate"
                  type="date"
                  class="flex-1 px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Data Sources Selection -->
        <div class="space-y-4">
          <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Data Sources</h3>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-for="source in dataSources" :key="source.id" class="flex items-center space-x-3 p-3 border rounded-lg">
              <Checkbox
                :checked="reportConfig.selectedSources.includes(source.id)"
                @update:checked="toggleDataSource(source.id, $event)"
              />
              <div class="flex items-center space-x-2 flex-1">
                <component :is="source.icon" class="h-4 w-4" :class="source.iconColor" />
                <div>
                  <div class="font-medium text-gray-900 dark:text-gray-100">{{ source.name }}</div>
                  <div class="text-sm text-gray-600 dark:text-gray-400">{{ source.description }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Metrics Selection -->
        <div class="space-y-4">
          <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Metrics to Include</h3>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
            <div v-for="metric in availableMetrics" :key="metric.id" class="flex items-center space-x-3">
              <Checkbox
                :checked="reportConfig.selectedMetrics.includes(metric.id)"
                @update:checked="toggleMetric(metric.id, $event)"
              />
              <label class="text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                {{ metric.name }}
              </label>
            </div>
          </div>
        </div>

        <!-- Website Scope -->
        <div class="space-y-4">
          <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Website Scope</h3>

          <div class="space-y-3">
            <div class="flex items-center space-x-3">
              <input
                v-model="reportConfig.websiteScope"
                type="radio"
                value="all"
                id="scope-all"
                class="text-primary focus:ring-primary"
              />
              <label for="scope-all" class="text-sm text-gray-700 dark:text-gray-300">All websites</label>
            </div>

            <div class="flex items-center space-x-3">
              <input
                v-model="reportConfig.websiteScope"
                type="radio"
                value="team"
                id="scope-team"
                class="text-primary focus:ring-primary"
              />
              <label for="scope-team" class="text-sm text-gray-700 dark:text-gray-300">Team websites only</label>
            </div>

            <div class="flex items-center space-x-3">
              <input
                v-model="reportConfig.websiteScope"
                type="radio"
                value="specific"
                id="scope-specific"
                class="text-primary focus:ring-primary"
              />
              <label for="scope-specific" class="text-sm text-gray-700 dark:text-gray-300">Specific websites</label>
            </div>

            <div v-if="reportConfig.websiteScope === 'specific'" class="ml-6 space-y-2">
              <div class="flex flex-wrap gap-2 mb-2">
                <Badge v-for="website in reportConfig.selectedWebsites" :key="website" variant="outline">
                  {{ website }}
                  <button @click="removeWebsite(website)" class="ml-1 hover:text-red-500">
                    <X class="h-3 w-3" />
                  </button>
                </Badge>
              </div>
              <select
                @change="addWebsite($event)"
                class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
              >
                <option value="">Select a website...</option>
                <option v-for="website in availableWebsites" :key="website.id" :value="website.name">
                  {{ website.name }}
                </option>
              </select>
            </div>
          </div>
        </div>

        <!-- Output Format -->
        <div class="space-y-4">
          <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Output Format</h3>

          <div class="grid grid-cols-3 gap-4">
            <div v-for="format in outputFormats" :key="format.id" class="flex items-center space-x-3 p-3 border rounded-lg">
              <input
                v-model="reportConfig.outputFormat"
                type="radio"
                :value="format.id"
                :id="`format-${format.id}`"
                class="text-primary focus:ring-primary"
              />
              <div class="flex items-center space-x-2">
                <component :is="format.icon" class="h-4 w-4" :class="format.iconColor" />
                <label :for="`format-${format.id}`" class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                  {{ format.name }}
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- Schedule Options -->
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Schedule Options</h3>
            <Checkbox
              :checked="reportConfig.enableSchedule"
              @update:checked="reportConfig.enableSchedule = $event"
            />
          </div>

          <div v-if="reportConfig.enableSchedule" class="space-y-4 pl-4 border-l-2 border-blue-200 dark:border-blue-800">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-2">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Frequency</label>
                <select
                  v-model="reportConfig.scheduleFrequency"
                  class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                >
                  <option value="daily">Daily</option>
                  <option value="weekly">Weekly</option>
                  <option value="monthly">Monthly</option>
                  <option value="quarterly">Quarterly</option>
                </select>
              </div>

              <div class="space-y-2">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Email Recipients</label>
                <input
                  v-model="reportConfig.emailRecipients"
                  type="email"
                  placeholder="email1@example.com, email2@example.com"
                  class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="flex justify-end space-x-3 pt-4 border-t">
        <Button @click="handleClose" variant="outline">
          Cancel
        </Button>
        <Button @click="createReport" :disabled="!isFormValid">
          <FileText class="h-4 w-4 mr-2" />
          Create Report
        </Button>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import {
  Settings,
  Shield,
  TrendingUp,
  Activity,
  AlertTriangle,
  Globe,
  FileText,
  Download,
  Mail,
  X
} from 'lucide-vue-next';

interface Props {
  isOpen: boolean;
}

interface ReportConfig {
  name: string;
  type: string;
  description: string;
  timeRange: string;
  startDate: string;
  endDate: string;
  selectedSources: string[];
  selectedMetrics: string[];
  websiteScope: 'all' | 'team' | 'specific';
  selectedWebsites: string[];
  outputFormat: string;
  enableSchedule: boolean;
  scheduleFrequency: string;
  emailRecipients: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  close: [];
  create: [config: ReportConfig];
}>();

const reportConfig = ref<ReportConfig>({
  name: '',
  type: 'summary',
  description: '',
  timeRange: '30d',
  startDate: '',
  endDate: '',
  selectedSources: ['ssl_certificates', 'uptime_monitoring'],
  selectedMetrics: ['response_time', 'uptime_percentage', 'ssl_score'],
  websiteScope: 'all',
  selectedWebsites: [],
  outputFormat: 'pdf',
  enableSchedule: false,
  scheduleFrequency: 'weekly',
  emailRecipients: ''
});

const dataSources = [
  {
    id: 'ssl_certificates',
    name: 'SSL Certificates',
    description: 'Certificate status, expiry dates, and security scores',
    icon: Shield,
    iconColor: 'text-blue-600 dark:text-blue-400'
  },
  {
    id: 'uptime_monitoring',
    name: 'Uptime Monitoring',
    description: 'Website availability and response times',
    icon: Activity,
    iconColor: 'text-green-600 dark:text-green-400'
  },
  {
    id: 'security_analysis',
    name: 'Security Analysis',
    description: 'Vulnerability scans and security insights',
    icon: AlertTriangle,
    iconColor: 'text-red-600 dark:text-red-400'
  },
  {
    id: 'performance_metrics',
    name: 'Performance Metrics',
    description: 'Response times and performance trends',
    icon: TrendingUp,
    iconColor: 'text-purple-600 dark:text-purple-400'
  }
];

const availableMetrics = [
  { id: 'response_time', name: 'Average Response Time' },
  { id: 'uptime_percentage', name: 'Uptime Percentage' },
  { id: 'ssl_score', name: 'SSL Security Score' },
  { id: 'certificate_expiry', name: 'Certificate Expiry Dates' },
  { id: 'alert_frequency', name: 'Alert Frequency' },
  { id: 'downtime_incidents', name: 'Downtime Incidents' },
  { id: 'security_vulnerabilities', name: 'Security Vulnerabilities' },
  { id: 'compliance_status', name: 'Compliance Status' }
];

const outputFormats = [
  {
    id: 'pdf',
    name: 'PDF',
    icon: FileText,
    iconColor: 'text-red-600 dark:text-red-400'
  },
  {
    id: 'excel',
    name: 'Excel',
    icon: Download,
    iconColor: 'text-green-600 dark:text-green-400'
  },
  {
    id: 'email',
    name: 'Email',
    icon: Mail,
    iconColor: 'text-blue-600 dark:text-blue-400'
  }
];

const availableWebsites = ref([
  { id: '1', name: 'Office Manager Pro' },
  { id: '2', name: 'Red Gas Austria' },
  { id: '3', name: 'Fairnando' },
  { id: '4', name: 'Example Website' }
]);

const isFormValid = computed(() => {
  return reportConfig.value.name.trim().length > 0 &&
         reportConfig.value.selectedSources.length > 0 &&
         reportConfig.value.selectedMetrics.length > 0;
});

const handleClose = () => {
  emit('close');
};

const updateDateRange = () => {
  const now = new Date();
  const today = now.toISOString().split('T')[0];

  if (reportConfig.value.timeRange !== 'custom') {
    reportConfig.value.endDate = today;

    switch (reportConfig.value.timeRange) {
      case '7d':
        reportConfig.value.startDate = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
        break;
      case '30d':
        reportConfig.value.startDate = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
        break;
      case '90d':
        reportConfig.value.startDate = new Date(now.getTime() - 90 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
        break;
      case '6m':
        reportConfig.value.startDate = new Date(now.getTime() - 180 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
        break;
      case '1y':
        reportConfig.value.startDate = new Date(now.getTime() - 365 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
        break;
    }
  }
};

const toggleDataSource = (sourceId: string, checked: boolean) => {
  if (checked) {
    if (!reportConfig.value.selectedSources.includes(sourceId)) {
      reportConfig.value.selectedSources.push(sourceId);
    }
  } else {
    reportConfig.value.selectedSources = reportConfig.value.selectedSources.filter(id => id !== sourceId);
  }
};

const toggleMetric = (metricId: string, checked: boolean) => {
  if (checked) {
    if (!reportConfig.value.selectedMetrics.includes(metricId)) {
      reportConfig.value.selectedMetrics.push(metricId);
    }
  } else {
    reportConfig.value.selectedMetrics = reportConfig.value.selectedMetrics.filter(id => id !== metricId);
  }
};

const addWebsite = (event: Event) => {
  const target = event.target as HTMLSelectElement;
  const websiteName = target.value;

  if (websiteName && !reportConfig.value.selectedWebsites.includes(websiteName)) {
    reportConfig.value.selectedWebsites.push(websiteName);
    target.value = '';
  }
};

const removeWebsite = (websiteName: string) => {
  reportConfig.value.selectedWebsites = reportConfig.value.selectedWebsites.filter(name => name !== websiteName);
};

const createReport = () => {
  if (!isFormValid.value) return;

  emit('create', { ...reportConfig.value });

  // Reset form
  reportConfig.value = {
    name: '',
    type: 'summary',
    description: '',
    timeRange: '30d',
    startDate: '',
    endDate: '',
    selectedSources: ['ssl_certificates', 'uptime_monitoring'],
    selectedMetrics: ['response_time', 'uptime_percentage', 'ssl_score'],
    websiteScope: 'all',
    selectedWebsites: [],
    outputFormat: 'pdf',
    enableSchedule: false,
    scheduleFrequency: 'weekly',
    emailRecipients: ''
  };
};

// Initialize date range on component mount
updateDateRange();
</script>