<template>
  <DashboardLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-muted-foreground">
            Analytics & Insights
          </h2>
          <p class="mt-1 text-sm text-foreground dark:text-muted-foreground">
            Comprehensive SSL certificate performance and security analytics
          </p>
        </div>

        <div class="flex space-x-4">
          <Button @click="refreshAllData" size="sm" :disabled="isRefreshing">
            <RefreshCw class="h-4 w-4 mr-2" :class="{ 'animate-spin': isRefreshing }" />
            {{ isRefreshing ? 'Refreshing...' : 'Refresh All' }}
          </Button>
          <Button @click="exportAnalytics" size="sm" variant="outline">
            <Download class="h-4 w-4 mr-2" />
            Export Report
          </Button>
        </div>
      </div>
    </template>

    <div class="space-y-8">
      <!-- Analytics Navigation Tabs -->
      <div class="border-b border-border dark:border-border">
        <nav class="-mb-px flex space-x-8">
          <button
            v-for="tab in analyticsTabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              'py-2 px-1 border-b-2 font-medium text-sm',
              activeTab === tab.id
                ? 'border-blue-500 text-primary dark:text-blue-400'
                : 'border-transparent text-muted-foreground hover:text-foreground dark:text-muted-foreground dark:hover:text-gray-300 hover:border-border dark:hover:border-border'
            ]"
          >
            <component :is="tab.icon" class="h-4 w-4 mr-2 inline" />
            {{ tab.name }}
          </button>
        </nav>
      </div>

      <!-- Tab Content -->
      <div class="min-h-screen">
        <!-- Performance Dashboard -->
        <div v-if="activeTab === 'performance'" class="space-y-6">
          <PerformanceDashboard />
        </div>

        <!-- Historical Trends -->
        <div v-if="activeTab === 'trends'" class="space-y-6">
          <HistoricalTrends />
        </div>

        <!-- Security Insights -->
        <div v-if="activeTab === 'security'" class="space-y-6">
          <SecurityInsights />
        </div>

        <!-- Custom Reports -->
        <div v-if="activeTab === 'reports'" class="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center space-x-2">
                <FileText class="h-5 w-5" />
                <span>Custom Reports</span>
              </CardTitle>
              <CardDescription>
                Create and manage custom analytics reports
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Predefined Report Templates -->
                <div v-for="template in reportTemplates" :key="template.id" class="p-4 border rounded-lg hover:shadow-md transition-shadow cursor-pointer">
                  <div class="flex items-start space-x-3">
                    <div class="rounded-lg p-2" :class="template.iconBg">
                      <component :is="template.icon" class="h-5 w-5" :class="template.iconColor" />
                    </div>
                    <div class="flex-1">
                      <h3 class="font-medium text-foreground dark:text-foreground">{{ template.name }}</h3>
                      <p class="text-sm text-foreground dark:text-muted-foreground mt-1">{{ template.description }}</p>
                      <div class="flex items-center space-x-4 mt-3">
                        <Button size="sm" @click="generateReport(template)">
                          Generate
                        </Button>
                        <span class="text-xs text-muted-foreground dark:text-muted-foreground">{{ template.frequency }}</span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Custom Report Builder -->
                <div class="p-4 border-2 border-dashed border-border dark:border-border rounded-lg hover:border-blue-500 transition-colors cursor-pointer" @click="openReportBuilder">
                  <div class="text-center">
                    <Plus class="h-8 w-8 mx-auto text-muted-foreground mb-2" />
                    <h3 class="font-medium text-foreground dark:text-foreground">Create Custom Report</h3>
                    <p class="text-sm text-foreground dark:text-muted-foreground mt-1">Build your own analytics report</p>
                  </div>
                </div>
              </div>

              <!-- Recent Reports -->
              <div class="mt-8">
                <h3 class="text-lg font-medium text-foreground dark:text-foreground mb-4">Recent Reports</h3>
                <div class="space-y-3">
                  <div v-for="report in recentReports" :key="report.id" class="flex items-center justify-between p-3 border rounded-lg">
                    <div class="flex items-center space-x-3">
                      <component :is="getReportIcon(report.type)" class="h-4 w-4 text-foreground dark:text-muted-foreground" />
                      <div>
                        <h4 class="font-medium text-foreground dark:text-foreground">{{ report.name }}</h4>
                        <p class="text-sm text-foreground dark:text-muted-foreground">{{ report.generatedAt }}</p>
                      </div>
                    </div>
                    <div class="flex items-center space-x-2">
                      <Badge :variant="report.status === 'ready' ? 'default' : 'secondary'">
                        {{ report.status }}
                      </Badge>
                      <Button size="sm" variant="outline" @click="downloadReport(report)">
                        <Download class="h-4 w-4" />
                      </Button>
                    </div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>

    <!-- Report Builder Modal -->
    <ReportBuilderModal
      :isOpen="showReportBuilder"
      @close="showReportBuilder = false"
      @create="handleReportCreate"
    />
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import PerformanceDashboard from '@/components/analytics/PerformanceDashboard.vue';
import HistoricalTrends from '@/components/analytics/HistoricalTrends.vue';
import SecurityInsights from '@/components/analytics/SecurityInsights.vue';
import ReportBuilderModal from '@/components/analytics/ReportBuilderModal.vue';
import {
  RefreshCw,
  Download,
  TrendingUp,
  BarChart3,
  Shield,
  FileText,
  Plus,
  Award,
  Clock,
  Target,
  AlertTriangle,
  CheckCircle
} from 'lucide-vue-next';

interface AnalyticsTab {
  id: string;
  name: string;
  icon: any;
}

interface ReportTemplate {
  id: string;
  name: string;
  description: string;
  frequency: string;
  icon: any;
  iconBg: string;
  iconColor: string;
  type: string;
}

interface RecentReport {
  id: string;
  name: string;
  type: string;
  generatedAt: string;
  status: 'ready' | 'generating' | 'failed';
}

const activeTab = ref('performance');
const isRefreshing = ref(false);
const showReportBuilder = ref(false);

const analyticsTabs: AnalyticsTab[] = [
  { id: 'performance', name: 'Performance', icon: TrendingUp },
  { id: 'trends', name: 'Historical Trends', icon: BarChart3 },
  { id: 'security', name: 'Security Insights', icon: Shield },
  { id: 'reports', name: 'Reports', icon: FileText }
];

const reportTemplates = ref<ReportTemplate[]>([
  {
    id: '1',
    name: 'Weekly SSL Summary',
    description: 'Comprehensive weekly SSL certificate status and performance summary',
    frequency: 'Weekly',
    icon: Award,
    iconBg: 'bg-blue-100 dark:bg-blue-900/30',
    iconColor: 'text-primary dark:text-blue-400',
    type: 'ssl_summary'
  },
  {
    id: '2',
    name: 'Monthly Performance Report',
    description: 'Detailed monthly performance metrics and trend analysis',
    frequency: 'Monthly',
    icon: TrendingUp,
    iconBg: 'bg-green-100 dark:bg-green-900/30',
    iconColor: 'text-green-600 dark:text-green-400',
    type: 'performance'
  },
  {
    id: '3',
    name: 'Security Audit Report',
    description: 'Comprehensive security analysis with recommendations',
    frequency: 'Monthly',
    icon: Shield,
    iconBg: 'bg-red-100 dark:bg-red-900/30',
    iconColor: 'text-destructive dark:text-red-400',
    type: 'security_audit'
  },
  {
    id: '4',
    name: 'Compliance Status Report',
    description: 'Current compliance status for industry standards',
    frequency: 'Quarterly',
    icon: CheckCircle,
    iconBg: 'bg-purple-100 dark:bg-purple-900/30',
    iconColor: 'text-purple-600 dark:text-purple-400',
    type: 'compliance'
  },
  {
    id: '5',
    name: 'Expiration Forecast',
    description: 'Upcoming certificate expirations and renewal planning',
    frequency: 'Weekly',
    icon: Clock,
    iconBg: 'bg-orange-100 dark:bg-orange-900/30',
    iconColor: 'text-orange-600 dark:text-orange-400',
    type: 'expiration'
  },
  {
    id: '6',
    name: 'Alert Analysis Report',
    description: 'Alert frequency analysis and response time metrics',
    frequency: 'Monthly',
    icon: AlertTriangle,
    iconBg: 'bg-yellow-100 dark:bg-yellow-900/30',
    iconColor: 'text-yellow-600 dark:text-yellow-400',
    type: 'alerts'
  }
]);

const recentReports = ref<RecentReport[]>([
  {
    id: '1',
    name: 'Weekly SSL Summary - Sep 2024',
    type: 'ssl_summary',
    generatedAt: '2 hours ago',
    status: 'ready'
  },
  {
    id: '2',
    name: 'Monthly Performance Report - Aug 2024',
    type: 'performance',
    generatedAt: '1 day ago',
    status: 'ready'
  },
  {
    id: '3',
    name: 'Security Audit Report - Q3 2024',
    type: 'security_audit',
    generatedAt: '3 days ago',
    status: 'generating'
  }
]);

const getReportIcon = (type: string) => {
  const iconMap = {
    ssl_summary: Award,
    performance: TrendingUp,
    security_audit: Shield,
    compliance: CheckCircle,
    expiration: Clock,
    alerts: AlertTriangle
  };
  return iconMap[type] || FileText;
};

const refreshAllData = async () => {
  isRefreshing.value = true;

  // Simulate refreshing all analytics data
  await new Promise(resolve => setTimeout(resolve, 2000));

  isRefreshing.value = false;
};

const exportAnalytics = () => {
  // Simulate comprehensive analytics export
  const exportData = {
    timestamp: new Date().toISOString(),
    activeTab: activeTab.value,
    summary: 'Complete SSL Monitor Analytics Export'
  };

  const blob = new Blob([JSON.stringify(exportData, null, 2)], {
    type: 'application/json;charset=utf-8;'
  });

  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  link.setAttribute('href', url);
  link.setAttribute('download', `ssl_monitor_analytics_${new Date().toISOString().split('T')[0]}.json`);
  link.style.visibility = 'hidden';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

const generateReport = async (template: ReportTemplate) => {
  // Add new report to generating state
  const newReport: RecentReport = {
    id: Date.now().toString(),
    name: `${template.name} - ${new Date().toLocaleDateString()}`,
    type: template.type,
    generatedAt: 'Just now',
    status: 'generating'
  };

  recentReports.value.unshift(newReport);

  // Simulate report generation
  setTimeout(() => {
    const reportIndex = recentReports.value.findIndex(r => r.id === newReport.id);
    if (reportIndex !== -1) {
      recentReports.value[reportIndex].status = 'ready';
      recentReports.value[reportIndex].generatedAt = 'Just now';
    }
  }, 3000);
};

const downloadReport = (report: RecentReport) => {
  if (report.status !== 'ready') return;

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

const openReportBuilder = () => {
  showReportBuilder.value = true;
};

const handleReportCreate = (reportConfig: any) => {
  console.log('Creating custom report:', reportConfig);
  showReportBuilder.value = false;

  // Add to recent reports
  generateReport({
    id: 'custom',
    name: reportConfig.name || 'Custom Report',
    description: 'Custom generated report',
    frequency: 'Custom',
    icon: FileText,
    iconBg: 'bg-muted',
    iconColor: 'text-foreground',
    type: 'custom'
  });
};

onMounted(() => {
  // Component initialization
});
</script>