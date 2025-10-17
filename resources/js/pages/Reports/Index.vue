<template>
  <DashboardLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-muted-foreground">
            Advanced Reporting Dashboard
          </h2>
          <p class="mt-1 text-sm text-foreground dark:text-muted-foreground">
            Comprehensive SSL certificate and performance reporting
          </p>
        </div>

        <div class="flex space-x-4">
          <Button @click="openReportBuilder" size="sm" class="flex items-center space-x-2">
            <Plus class="h-4 w-4" />
            <span>Create Report</span>
          </Button>
          <Button @click="refreshReports" size="sm" variant="outline">
            <RefreshCw class="h-4 w-4 mr-2" :class="{ 'animate-spin': isRefreshing }" />
            Refresh
          </Button>
        </div>
      </div>
    </template>

    <div class="space-y-8">
      <!-- Report Categories -->
      <div class="border-b border-border dark:border-border">
        <nav class="-mb-px flex space-x-8">
          <button
            v-for="category in reportCategories"
            :key="category.id"
            @click="activeCategory = category.id"
            :class="[
              'py-2 px-1 border-b-2 font-medium text-sm',
              activeCategory === category.id
                ? 'border-blue-500 text-primary dark:text-blue-400'
                : 'border-transparent text-muted-foreground hover:text-foreground dark:text-muted-foreground dark:hover:text-gray-300 hover:border-border dark:hover:border-border'
            ]"
          >
            <component :is="category.icon" class="h-4 w-4 mr-2 inline" />
            {{ category.name }}
          </button>
        </nav>
      </div>

      <!-- Quick Reports -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center space-x-2">
            <Zap class="h-5 w-5" />
            <span>Quick Reports</span>
          </CardTitle>
          <CardDescription>
            Generate instant reports with pre-configured templates
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div
              v-for="template in filteredQuickReports"
              :key="template.id"
              class="p-4 border rounded-lg hover:shadow-md transition-shadow cursor-pointer"
              @click="generateQuickReport(template)"
              :class="{ 'opacity-50 cursor-not-allowed': template.generating }"
            >
              <div class="flex items-center space-x-3">
                <div class="rounded-lg p-2" :class="template.iconBg">
                  <component :is="template.icon" class="h-5 w-5" :class="template.iconColor" />
                </div>
                <div class="flex-1">
                  <h3 class="font-medium text-foreground dark:text-foreground">{{ template.name }}</h3>
                  <p class="text-sm text-foreground dark:text-muted-foreground">{{ template.description }}</p>
                  <div class="flex items-center justify-between mt-2">
                    <span class="text-xs text-muted-foreground dark:text-muted-foreground">{{ template.estimatedTime }}</span>
                    <div v-if="template.generating" class="flex items-center space-x-1">
                      <div class="animate-spin rounded-full h-3 w-3 border-b-2 border-primary"></div>
                      <span class="text-xs text-primary">Generating...</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Recent Reports -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center justify-between">
            <div class="flex items-center space-x-2">
              <FileText class="h-5 w-5" />
              <span>Recent Reports</span>
            </div>
            <div class="flex space-x-2">
              <select
                v-model="reportsFilter"
                @change="filterReports"
                class="px-3 py-1 text-sm border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
              >
                <option value="all">All Reports</option>
                <option value="ssl">SSL Reports</option>
                <option value="performance">Performance Reports</option>
                <option value="security">Security Reports</option>
                <option value="custom">Custom Reports</option>
              </select>
              <Button @click="clearReportHistory" size="sm" variant="outline">
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
                  <th class="py-2 font-medium text-foreground dark:text-foreground">Report Name</th>
                  <th class="py-2 font-medium text-foreground dark:text-foreground">Type</th>
                  <th class="py-2 font-medium text-foreground dark:text-foreground">Status</th>
                  <th class="py-2 font-medium text-foreground dark:text-foreground">Generated</th>
                  <th class="py-2 font-medium text-foreground dark:text-foreground">Size</th>
                  <th class="py-2 font-medium text-foreground dark:text-foreground">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-border">
                <tr v-for="report in filteredReports" :key="report.id" class="hover:bg-muted dark:hover:bg-gray-800">
                  <td class="py-3">
                    <div class="flex items-center space-x-2">
                      <component :is="getReportIcon(report.type)" class="h-4 w-4" :class="getReportIconColor(report.type)" />
                      <div>
                        <div class="font-medium text-foreground dark:text-foreground">{{ report.name }}</div>
                        <div class="text-xs text-foreground dark:text-muted-foreground">{{ report.description }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="py-3">
                    <Badge :variant="getTypeVariant(report.type)">
                      {{ report.type }}
                    </Badge>
                  </td>
                  <td class="py-3">
                    <Badge :variant="getStatusVariant(report.status)">
                      {{ report.status }}
                    </Badge>
                  </td>
                  <td class="py-3 text-foreground dark:text-muted-foreground">{{ formatRelativeTime(report.generatedAt) }}</td>
                  <td class="py-3 text-foreground dark:text-muted-foreground">{{ report.fileSize }}</td>
                  <td class="py-3">
                    <div class="flex space-x-2">
                      <Button @click="viewReport(report)" size="sm" variant="outline">
                        <Eye class="h-4 w-4" />
                      </Button>
                      <Button
                        v-if="report.status === 'completed'"
                        @click="downloadReport(report)"
                        size="sm"
                        variant="outline"
                      >
                        <Download class="h-4 w-4" />
                      </Button>
                      <Button
                        v-if="report.status === 'completed'"
                        @click="shareReport(report)"
                        size="sm"
                        variant="outline"
                      >
                        <Share class="h-4 w-4" />
                      </Button>
                      <Button @click="deleteReport(report.id)" size="sm" variant="outline">
                        <Trash2 class="h-4 w-4" />
                      </Button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Empty State -->
          <div v-if="filteredReports.length === 0" class="text-center py-8 text-muted-foreground dark:text-muted-foreground">
            <FileText class="h-12 w-12 mx-auto mb-2 opacity-50" />
            <p class="font-medium">No reports found</p>
            <p class="text-sm mt-1">Generate your first report using the quick templates above</p>
          </div>
        </CardContent>
      </Card>

      <!-- Report Analytics -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Usage Statistics -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center space-x-2">
              <BarChart3 class="h-5 w-5" />
              <span>Report Usage</span>
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-4">
              <div v-for="stat in usageStats" :key="stat.type" class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                  <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: stat.color }"></div>
                  <span class="text-sm font-medium text-foreground dark:text-muted-foreground">{{ stat.type }}</span>
                </div>
                <div class="flex items-center space-x-3">
                  <span class="text-sm text-foreground dark:text-muted-foreground">{{ stat.count }} reports</span>
                  <div class="w-20 h-2 bg-muted dark:bg-muted rounded-full overflow-hidden">
                    <div
                      class="h-full transition-all duration-300"
                      :style="{
                        width: `${stat.percentage}%`,
                        backgroundColor: stat.color
                      }"
                    ></div>
                  </div>
                  <span class="text-sm font-medium text-foreground dark:text-foreground w-8 text-right">
                    {{ stat.percentage }}%
                  </span>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Scheduled Reports -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center justify-between">
              <div class="flex items-center space-x-2">
                <Clock class="h-5 w-5" />
                <span>Scheduled Reports</span>
              </div>
              <Button @click="openScheduler" size="sm" variant="outline">
                <Plus class="h-4 w-4 mr-2" />
                Schedule New
              </Button>
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-3">
              <div v-for="schedule in scheduledReports" :key="schedule.id" class="p-3 border rounded-lg">
                <div class="flex items-center justify-between">
                  <div>
                    <h4 class="font-medium text-foreground dark:text-foreground">{{ schedule.name }}</h4>
                    <p class="text-sm text-foreground dark:text-muted-foreground">{{ schedule.description }}</p>
                    <div class="flex items-center space-x-4 text-xs text-muted-foreground dark:text-muted-foreground mt-1">
                      <span>{{ schedule.frequency }}</span>
                      <span>Next run: {{ schedule.nextRun }}</span>
                    </div>
                  </div>
                  <div class="flex items-center space-x-2">
                    <Badge :variant="schedule.enabled ? 'default' : 'secondary'">
                      {{ schedule.enabled ? 'Active' : 'Paused' }}
                    </Badge>
                    <Button @click="toggleSchedule(schedule.id)" size="sm" variant="outline">
                      <component :is="schedule.enabled ? Pause : Play" class="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>

    <!-- Report Builder Modal -->
    <ReportBuilderModal
      :isOpen="showReportBuilder"
      @close="showReportBuilder = false"
      @create="handleReportCreate"
    />

    <!-- Report Viewer Modal -->
    <ReportViewerModal
      :isOpen="showReportViewer"
      :report="selectedReport"
      @close="showReportViewer = false"
    />

    <!-- Report Scheduler Modal -->
    <ReportSchedulerModal
      :isOpen="showScheduler"
      @close="showScheduler = false"
      @schedule="handleReportSchedule"
    />

    <!-- Share Report Modal -->
    <ShareReportModal
      :isOpen="showShareModal"
      :report="selectedReport"
      @close="showShareModal = false"
    />
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import ReportBuilderModal from '@/components/reports/ReportBuilderModal.vue';
import ReportViewerModal from '@/components/reports/ReportViewerModal.vue';
import ReportSchedulerModal from '@/components/reports/ReportSchedulerModal.vue';
import ShareReportModal from '@/components/reports/ShareReportModal.vue';
import {
  Plus,
  RefreshCw,
  Zap,
  FileText,
  Trash2,
  Eye,
  Download,
  Share,
  BarChart3,
  Clock,
  Pause,
  Play,
  Shield,
  TrendingUp,
  AlertTriangle,
  Award,
  Globe,
  Users
} from 'lucide-vue-next';

interface ReportCategory {
  id: string;
  name: string;
  icon: any;
}

interface QuickReportTemplate {
  id: string;
  name: string;
  description: string;
  category: string;
  estimatedTime: string;
  icon: any;
  iconBg: string;
  iconColor: string;
  generating?: boolean;
}

interface Report {
  id: string;
  name: string;
  description: string;
  type: string;
  status: 'completed' | 'generating' | 'failed';
  generatedAt: string;
  fileSize: string;
}

interface ScheduledReport {
  id: string;
  name: string;
  description: string;
  frequency: string;
  nextRun: string;
  enabled: boolean;
}

interface UsageStat {
  type: string;
  count: number;
  percentage: number;
  color: string;
}

const activeCategory = ref('all');
const reportsFilter = ref('all');
const isRefreshing = ref(false);
const showReportBuilder = ref(false);
const showReportViewer = ref(false);
const showScheduler = ref(false);
const showShareModal = ref(false);
const selectedReport = ref<Report | null>(null);

const reportCategories: ReportCategory[] = [
  { id: 'all', name: 'All Reports', icon: FileText },
  { id: 'ssl', name: 'SSL Certificates', icon: Shield },
  { id: 'performance', name: 'Performance', icon: TrendingUp },
  { id: 'security', name: 'Security', icon: AlertTriangle },
  { id: 'compliance', name: 'Compliance', icon: Award }
];

const quickReportTemplates = ref<QuickReportTemplate[]>([
  {
    id: '1',
    name: 'SSL Status Summary',
    description: 'Complete SSL certificate status overview',
    category: 'ssl',
    estimatedTime: '2 min',
    icon: Shield,
    iconBg: 'bg-blue-100 dark:bg-blue-900/30',
    iconColor: 'text-primary dark:text-blue-400'
  },
  {
    id: '2',
    name: 'Performance Dashboard',
    description: 'Website performance metrics and trends',
    category: 'performance',
    estimatedTime: '3 min',
    icon: TrendingUp,
    iconBg: 'bg-green-100 dark:bg-green-900/30',
    iconColor: 'text-green-600 dark:text-green-400'
  },
  {
    id: '3',
    name: 'Security Audit Report',
    description: 'Comprehensive security analysis',
    category: 'security',
    estimatedTime: '5 min',
    icon: AlertTriangle,
    iconBg: 'bg-red-100 dark:bg-red-900/30',
    iconColor: 'text-destructive dark:text-red-400'
  },
  {
    id: '4',
    name: 'Compliance Check',
    description: 'Industry compliance status report',
    category: 'compliance',
    estimatedTime: '4 min',
    icon: Award,
    iconBg: 'bg-purple-100 dark:bg-purple-900/30',
    iconColor: 'text-purple-600 dark:text-purple-400'
  },
  {
    id: '5',
    name: 'Executive Summary',
    description: 'High-level overview for management',
    category: 'all',
    estimatedTime: '1 min',
    icon: BarChart3,
    iconBg: 'bg-indigo-100 dark:bg-indigo-900/30',
    iconColor: 'text-indigo-600 dark:text-indigo-400'
  },
  {
    id: '6',
    name: 'Team Activity Report',
    description: 'Team collaboration and activity metrics',
    category: 'all',
    estimatedTime: '2 min',
    icon: Users,
    iconBg: 'bg-teal-100 dark:bg-teal-900/30',
    iconColor: 'text-teal-600 dark:text-teal-400'
  }
]);

const recentReports = ref<Report[]>([
  {
    id: '1',
    name: 'Weekly SSL Summary - Sep 2024',
    description: 'SSL certificate status and upcoming renewals',
    type: 'ssl',
    status: 'completed',
    generatedAt: '2024-09-24T08:30:00Z',
    fileSize: '2.4 MB'
  },
  {
    id: '2',
    name: 'Performance Analysis - Q3 2024',
    description: 'Website performance metrics and optimization recommendations',
    type: 'performance',
    status: 'completed',
    generatedAt: '2024-09-23T14:15:00Z',
    fileSize: '1.8 MB'
  },
  {
    id: '3',
    name: 'Security Audit - September',
    description: 'Monthly security assessment and vulnerability scan results',
    type: 'security',
    status: 'generating',
    generatedAt: '2024-09-24T10:00:00Z',
    fileSize: '-'
  },
  {
    id: '4',
    name: 'Compliance Report - GDPR',
    description: 'GDPR compliance status and recommendations',
    type: 'compliance',
    status: 'completed',
    generatedAt: '2024-09-22T16:45:00Z',
    fileSize: '950 KB'
  }
]);

const scheduledReports = ref<ScheduledReport[]>([
  {
    id: '1',
    name: 'Weekly SSL Digest',
    description: 'Automated weekly SSL certificate summary',
    frequency: 'Weekly (Mon 9:00 AM)',
    nextRun: 'Sep 30, 9:00 AM',
    enabled: true
  },
  {
    id: '2',
    name: 'Monthly Security Report',
    description: 'Comprehensive monthly security analysis',
    frequency: 'Monthly (1st, 8:00 AM)',
    nextRun: 'Oct 1, 8:00 AM',
    enabled: true
  },
  {
    id: '3',
    name: 'Quarterly Compliance',
    description: 'Quarterly compliance status report',
    frequency: 'Quarterly',
    nextRun: 'Dec 1, 9:00 AM',
    enabled: false
  }
]);

const usageStats: UsageStat[] = [
  { type: 'SSL Reports', count: 45, percentage: 40, color: '#3b82f6' },
  { type: 'Performance', count: 28, percentage: 25, color: '#10b981' },
  { type: 'Security', count: 22, percentage: 20, color: '#ef4444' },
  { type: 'Compliance', count: 12, percentage: 10, color: '#8b5cf6' },
  { type: 'Custom', count: 6, percentage: 5, color: '#f59e0b' }
];

const filteredQuickReports = computed(() => {
  if (activeCategory.value === 'all') {
    return quickReportTemplates.value;
  }
  return quickReportTemplates.value.filter(template => template.category === activeCategory.value || template.category === 'all');
});

const filteredReports = computed(() => {
  if (reportsFilter.value === 'all') {
    return recentReports.value;
  }
  return recentReports.value.filter(report => report.type === reportsFilter.value);
});

const getReportIcon = (type: string) => {
  const iconMap = {
    ssl: Shield,
    performance: TrendingUp,
    security: AlertTriangle,
    compliance: Award,
    custom: FileText
  };
  return iconMap[type] || FileText;
};

const getReportIconColor = (type: string): string => {
  const colorMap = {
    ssl: 'text-primary dark:text-blue-400',
    performance: 'text-green-600 dark:text-green-400',
    security: 'text-destructive dark:text-red-400',
    compliance: 'text-purple-600 dark:text-purple-400',
    custom: 'text-foreground dark:text-muted-foreground'
  };
  return colorMap[type] || 'text-foreground dark:text-muted-foreground';
};

const getTypeVariant = (type: string) => {
  const variantMap = {
    ssl: 'default',
    performance: 'default',
    security: 'destructive',
    compliance: 'secondary',
    custom: 'outline'
  };
  return variantMap[type] || 'outline';
};

const getStatusVariant = (status: string) => {
  switch (status) {
    case 'completed':
      return 'default';
    case 'generating':
      return 'secondary';
    case 'failed':
      return 'destructive';
    default:
      return 'outline';
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

const generateQuickReport = async (template: QuickReportTemplate) => {
  template.generating = true;

  // Simulate report generation
  await new Promise(resolve => setTimeout(resolve, 3000));

  const newReport: Report = {
    id: Date.now().toString(),
    name: `${template.name} - ${new Date().toLocaleDateString()}`,
    description: template.description,
    type: template.category === 'all' ? 'custom' : template.category,
    status: 'completed',
    generatedAt: new Date().toISOString(),
    fileSize: `${Math.floor(Math.random() * 3 + 1)}.${Math.floor(Math.random() * 9)}MB`
  };

  recentReports.value.unshift(newReport);
  template.generating = false;
};

const refreshReports = async () => {
  isRefreshing.value = true;
  await new Promise(resolve => setTimeout(resolve, 1000));
  isRefreshing.value = false;
};

const filterReports = () => {
  // Reactive computed property handles this automatically
};

const clearReportHistory = () => {
  if (confirm('Are you sure you want to clear all report history?')) {
    recentReports.value = [];
  }
};

const openReportBuilder = () => {
  showReportBuilder.value = true;
};

const openScheduler = () => {
  showScheduler.value = true;
};

const viewReport = (report: Report) => {
  selectedReport.value = report;
  showReportViewer.value = true;
};

const downloadReport = (report: Report) => {
  // Simulate report download
  const reportContent = `SSL Monitor Report: ${report.name}\nGenerated: ${report.generatedAt}\nType: ${report.type}`;
  const blob = new Blob([reportContent], { type: 'text/plain;charset=utf-8;' });

  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  link.setAttribute('href', url);
  link.setAttribute('download', `${report.name.replace(/\s+/g, '_').toLowerCase()}.txt`);
  link.style.visibility = 'hidden';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

const shareReport = (report: Report) => {
  selectedReport.value = report;
  showShareModal.value = true;
};

const deleteReport = (reportId: string) => {
  if (confirm('Are you sure you want to delete this report?')) {
    recentReports.value = recentReports.value.filter(report => report.id !== reportId);
  }
};

const toggleSchedule = (scheduleId: string) => {
  const schedule = scheduledReports.value.find(s => s.id === scheduleId);
  if (schedule) {
    schedule.enabled = !schedule.enabled;
  }
};

const handleReportCreate = (reportConfig: any) => {
  console.log('Creating custom report:', reportConfig);
  showReportBuilder.value = false;
};

const handleReportSchedule = (scheduleConfig: any) => {
  console.log('Scheduling report:', scheduleConfig);
  showScheduler.value = false;

  const newSchedule: ScheduledReport = {
    id: Date.now().toString(),
    name: scheduleConfig.name,
    description: scheduleConfig.description,
    frequency: scheduleConfig.frequency,
    nextRun: scheduleConfig.nextRun,
    enabled: true
  };

  scheduledReports.value.push(newSchedule);
};

onMounted(() => {
  // Component initialization
});
</script>