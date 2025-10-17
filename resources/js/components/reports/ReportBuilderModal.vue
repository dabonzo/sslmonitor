<template>
  <Dialog :open="isOpen" @update:open="handleClose">
    <DialogContent class="max-w-4xl max-h-[90vh] overflow-hidden">
      <DialogHeader>
        <DialogTitle class="flex items-center space-x-3">
          <div class="rounded-lg bg-blue-100 dark:bg-blue-900/30 p-2">
            <FileText class="h-5 w-5 text-primary dark:text-blue-400" />
          </div>
          <div>
            <div class="text-xl font-bold text-foreground dark:text-foreground">
              Advanced Report Builder
            </div>
            <div class="text-sm text-foreground dark:text-muted-foreground mt-1">
              Create comprehensive custom reports with advanced configuration
            </div>
          </div>
        </DialogTitle>
      </DialogHeader>

      <div class="space-y-6 py-4 max-h-[60vh] overflow-y-auto">
        <!-- Report Configuration -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Basic Information -->
          <Card>
            <CardHeader>
              <CardTitle class="text-lg">Report Information</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="space-y-2">
                <label class="text-sm font-medium text-foreground dark:text-muted-foreground">Report Name</label>
                <input
                  v-model="reportConfig.name"
                  type="text"
                  placeholder="Custom SSL Analysis Report"
                  class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                />
              </div>

              <div class="space-y-2">
                <label class="text-sm font-medium text-foreground dark:text-muted-foreground">Description</label>
                <textarea
                  v-model="reportConfig.description"
                  rows="3"
                  placeholder="Detailed description of what this report includes..."
                  class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                ></textarea>
              </div>

              <div class="space-y-2">
                <label class="text-sm font-medium text-foreground dark:text-muted-foreground">Report Category</label>
                <select
                  v-model="reportConfig.category"
                  class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                >
                  <option value="ssl">SSL Certificates</option>
                  <option value="performance">Performance</option>
                  <option value="security">Security</option>
                  <option value="compliance">Compliance</option>
                  <option value="custom">Custom</option>
                </select>
              </div>
            </CardContent>
          </Card>

          <!-- Time Range -->
          <Card>
            <CardHeader>
              <CardTitle class="text-lg">Time Range</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="space-y-2">
                <label class="text-sm font-medium text-foreground dark:text-muted-foreground">Period</label>
                <select
                  v-model="reportConfig.timePeriod"
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

              <div v-if="reportConfig.timePeriod === 'custom'" class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                  <label class="text-sm font-medium text-foreground dark:text-muted-foreground">Start Date</label>
                  <input
                    v-model="reportConfig.startDate"
                    type="date"
                    class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                  />
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-medium text-foreground dark:text-muted-foreground">End Date</label>
                  <input
                    v-model="reportConfig.endDate"
                    type="date"
                    class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                  />
                </div>
              </div>

              <div class="space-y-2">
                <label class="text-sm font-medium text-foreground dark:text-muted-foreground">Data Grouping</label>
                <select
                  v-model="reportConfig.grouping"
                  class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                >
                  <option value="daily">Daily</option>
                  <option value="weekly">Weekly</option>
                  <option value="monthly">Monthly</option>
                  <option value="quarterly">Quarterly</option>
                </select>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Data Sources -->
        <Card>
          <CardHeader>
            <CardTitle class="text-lg">Data Sources</CardTitle>
            <CardDescription>Select which data to include in your report</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div v-for="source in dataSources" :key="source.id" class="flex items-center space-x-3 p-3 border rounded-lg">
                <Checkbox
                  :checked="reportConfig.selectedSources.includes(source.id)"
                  @update:checked="toggleDataSource(source.id, $event)"
                />
                <div class="flex items-center space-x-2 flex-1">
                  <component :is="source.icon" class="h-4 w-4" :class="source.iconColor" />
                  <div>
                    <div class="font-medium text-foreground dark:text-foreground">{{ source.name }}</div>
                    <div class="text-sm text-foreground dark:text-muted-foreground">{{ source.description }}</div>
                  </div>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Metrics Selection -->
        <Card>
          <CardHeader>
            <CardTitle class="text-lg">Metrics & KPIs</CardTitle>
            <CardDescription>Choose which metrics to include in your report</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div v-for="category in metricCategories" :key="category.name">
                <h4 class="font-medium text-foreground dark:text-foreground mb-3">{{ category.name }}</h4>
                <div class="space-y-2">
                  <div v-for="metric in category.metrics" :key="metric.id" class="flex items-center space-x-2">
                    <Checkbox
                      :checked="reportConfig.selectedMetrics.includes(metric.id)"
                      @update:checked="toggleMetric(metric.id, $event)"
                    />
                    <label class="text-sm text-foreground dark:text-muted-foreground cursor-pointer">
                      {{ metric.name }}
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Visualization Options -->
        <Card>
          <CardHeader>
            <CardTitle class="text-lg">Visualizations</CardTitle>
            <CardDescription>Select chart types and visual elements</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              <div v-for="visualization in visualizations" :key="visualization.id" class="flex flex-col items-center p-3 border rounded-lg cursor-pointer hover:shadow-md transition-shadow" :class="{ 'border-primary bg-primary/5': reportConfig.selectedVisualizations.includes(visualization.id) }" @click="toggleVisualization(visualization.id)">
                <component :is="visualization.icon" class="h-8 w-8 mb-2" :class="visualization.iconColor" />
                <span class="text-sm font-medium text-foreground dark:text-foreground">{{ visualization.name }}</span>
                <span class="text-xs text-foreground dark:text-muted-foreground text-center mt-1">{{ visualization.description }}</span>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Output Configuration -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Format Options -->
          <Card>
            <CardHeader>
              <CardTitle class="text-lg">Output Format</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="space-y-3">
                <div v-for="format in outputFormats" :key="format.id" class="flex items-center space-x-3">
                  <Checkbox
                    :checked="reportConfig.outputFormats.includes(format.id)"
                    @update:checked="toggleOutputFormat(format.id, $event)"
                  />
                  <div class="flex items-center space-x-2">
                    <component :is="format.icon" class="h-4 w-4" :class="format.iconColor" />
                    <span class="text-sm font-medium text-foreground dark:text-muted-foreground">{{ format.name }}</span>
                    <span class="text-xs text-muted-foreground dark:text-muted-foreground">({{ format.size }})</span>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Distribution Options -->
          <Card>
            <CardHeader>
              <CardTitle class="text-lg">Distribution</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="space-y-3">
                <div class="flex items-center space-x-3">
                  <Checkbox
                    :checked="reportConfig.emailReport"
                    @update:checked="reportConfig.emailReport = $event"
                  />
                  <span class="text-sm text-foreground dark:text-muted-foreground">Email report upon completion</span>
                </div>

                <div v-if="reportConfig.emailReport" class="ml-6 space-y-2">
                  <input
                    v-model="reportConfig.emailRecipients"
                    type="email"
                    placeholder="email1@example.com, email2@example.com"
                    class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                  />
                </div>

                <div class="flex items-center space-x-3">
                  <Checkbox
                    :checked="reportConfig.slackNotification"
                    @update:checked="reportConfig.slackNotification = $event"
                  />
                  <span class="text-sm text-foreground dark:text-muted-foreground">Send Slack notification</span>
                </div>

                <div class="flex items-center space-x-3">
                  <Checkbox
                    :checked="reportConfig.dashboardPublication"
                    @update:checked="reportConfig.dashboardPublication = $event"
                  />
                  <span class="text-sm text-foreground dark:text-muted-foreground">Publish to team dashboard</span>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      <div class="flex justify-between items-center pt-4 border-t">
        <div class="text-sm text-foreground dark:text-muted-foreground">
          Estimated generation time: {{ estimatedTime }}
        </div>
        <div class="flex space-x-3">
          <Button @click="saveAsTemplate" variant="outline">
            <Bookmark class="h-4 w-4 mr-2" />
            Save as Template
          </Button>
          <Button @click="handleClose" variant="outline">
            Cancel
          </Button>
          <Button @click="createReport" :disabled="!isFormValid">
            <FileText class="h-4 w-4 mr-2" />
            Generate Report
          </Button>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
  FileText,
  Shield,
  TrendingUp,
  AlertTriangle,
  Activity,
  Globe,
  BarChart3,
  LineChart,
  PieChart,
  Download,
  Mail,
  Bookmark
} from 'lucide-vue-next';

interface Props {
  isOpen: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  close: [];
  create: [config: any];
}>();

const reportConfig = ref({
  name: '',
  description: '',
  category: 'ssl',
  timePeriod: '30d',
  startDate: '',
  endDate: '',
  grouping: 'daily',
  selectedSources: ['ssl_certificates', 'uptime_monitoring'],
  selectedMetrics: ['ssl_status', 'response_time', 'uptime_percentage'],
  selectedVisualizations: ['bar_chart', 'line_chart'],
  outputFormats: ['pdf'],
  emailReport: false,
  emailRecipients: '',
  slackNotification: false,
  dashboardPublication: true
});

const dataSources = [
  {
    id: 'ssl_certificates',
    name: 'SSL Certificates',
    description: 'Certificate status, expiry, security scores',
    icon: Shield,
    iconColor: 'text-primary dark:text-blue-400'
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
    iconColor: 'text-destructive dark:text-red-400'
  },
  {
    id: 'performance_metrics',
    name: 'Performance Metrics',
    description: 'Response times and performance trends',
    icon: TrendingUp,
    iconColor: 'text-purple-600 dark:text-purple-400'
  }
];

const metricCategories = [
  {
    name: 'SSL Metrics',
    metrics: [
      { id: 'ssl_status', name: 'Certificate Status' },
      { id: 'ssl_expiry', name: 'Days Until Expiry' },
      { id: 'ssl_score', name: 'Security Score' },
      { id: 'ssl_issuer', name: 'Certificate Authority' }
    ]
  },
  {
    name: 'Performance',
    metrics: [
      { id: 'response_time', name: 'Response Time' },
      { id: 'uptime_percentage', name: 'Uptime %' },
      { id: 'downtime_incidents', name: 'Downtime Incidents' },
      { id: 'availability_sla', name: 'SLA Compliance' }
    ]
  },
  {
    name: 'Security',
    metrics: [
      { id: 'vulnerability_count', name: 'Vulnerabilities' },
      { id: 'security_score', name: 'Security Score' },
      { id: 'compliance_status', name: 'Compliance Status' },
      { id: 'threat_level', name: 'Threat Level' }
    ]
  }
];

const visualizations = [
  {
    id: 'bar_chart',
    name: 'Bar Chart',
    description: 'Compare values',
    icon: BarChart3,
    iconColor: 'text-primary dark:text-blue-400'
  },
  {
    id: 'line_chart',
    name: 'Line Chart',
    description: 'Show trends',
    icon: LineChart,
    iconColor: 'text-green-600 dark:text-green-400'
  },
  {
    id: 'pie_chart',
    name: 'Pie Chart',
    description: 'Show distribution',
    icon: PieChart,
    iconColor: 'text-purple-600 dark:text-purple-400'
  },
  {
    id: 'trend_analysis',
    name: 'Trend Analysis',
    description: 'Analyze patterns',
    icon: TrendingUp,
    iconColor: 'text-orange-600 dark:text-orange-400'
  }
];

const outputFormats = [
  {
    id: 'pdf',
    name: 'PDF Document',
    size: '~2-5MB',
    icon: FileText,
    iconColor: 'text-destructive dark:text-red-400'
  },
  {
    id: 'excel',
    name: 'Excel Spreadsheet',
    size: '~1-3MB',
    icon: Download,
    iconColor: 'text-green-600 dark:text-green-400'
  },
  {
    id: 'html',
    name: 'HTML Dashboard',
    size: '~500KB',
    icon: Globe,
    iconColor: 'text-primary dark:text-blue-400'
  },
  {
    id: 'email',
    name: 'Email Summary',
    size: '~100KB',
    icon: Mail,
    iconColor: 'text-purple-600 dark:text-purple-400'
  }
];

const isFormValid = computed(() => {
  return reportConfig.value.name.trim().length > 0 &&
         reportConfig.value.selectedSources.length > 0 &&
         reportConfig.value.selectedMetrics.length > 0 &&
         reportConfig.value.outputFormats.length > 0;
});

const estimatedTime = computed(() => {
  const baseTime = 2;
  const sourceMultiplier = reportConfig.value.selectedSources.length * 0.5;
  const metricMultiplier = reportConfig.value.selectedMetrics.length * 0.2;
  const visualizationMultiplier = reportConfig.value.selectedVisualizations.length * 0.3;

  const totalMinutes = Math.ceil(baseTime + sourceMultiplier + metricMultiplier + visualizationMultiplier);
  return `${totalMinutes} minutes`;
});

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

const toggleVisualization = (visualizationId: string) => {
  const index = reportConfig.value.selectedVisualizations.indexOf(visualizationId);
  if (index > -1) {
    reportConfig.value.selectedVisualizations.splice(index, 1);
  } else {
    reportConfig.value.selectedVisualizations.push(visualizationId);
  }
};

const toggleOutputFormat = (formatId: string, checked: boolean) => {
  if (checked) {
    if (!reportConfig.value.outputFormats.includes(formatId)) {
      reportConfig.value.outputFormats.push(formatId);
    }
  } else {
    reportConfig.value.outputFormats = reportConfig.value.outputFormats.filter(id => id !== formatId);
  }
};

const updateDateRange = () => {
  const now = new Date();
  const today = now.toISOString().split('T')[0];

  if (reportConfig.value.timePeriod !== 'custom') {
    reportConfig.value.endDate = today;

    switch (reportConfig.value.timePeriod) {
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

const saveAsTemplate = () => {
  console.log('Saving report configuration as template');
  // This would save the current configuration as a reusable template
};

const createReport = () => {
  if (!isFormValid.value) return;

  emit('create', { ...reportConfig.value });
  handleClose();
};

const handleClose = () => {
  emit('close');
  // Reset form could go here if needed
};

// Initialize date range
updateDateRange();
</script>