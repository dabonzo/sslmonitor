<template>
  <Dialog :open="isOpen" @update:open="handleClose">
    <DialogContent class="max-w-6xl max-h-[90vh] overflow-hidden">
      <DialogHeader>
        <DialogTitle class="flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <div class="rounded-lg p-2" :class="getReportIconBg(report?.type)">
              <component :is="getReportIcon(report?.type)" class="h-5 w-5" :class="getReportIconColor(report?.type)" />
            </div>
            <div>
              <div class="text-xl font-bold text-foreground dark:text-foreground">
                {{ report?.name }}
              </div>
              <div class="text-sm text-foreground dark:text-muted-foreground mt-1">
                {{ report?.description }}
              </div>
            </div>
          </div>
          <div class="flex space-x-2">
            <Button @click="downloadReport" size="sm" variant="outline">
              <Download class="h-4 w-4 mr-2" />
              Download
            </Button>
            <Button @click="printReport" size="sm" variant="outline">
              <Printer class="h-4 w-4 mr-2" />
              Print
            </Button>
          </div>
        </DialogTitle>
      </DialogHeader>

      <div v-if="report" class="space-y-6 py-4 max-h-[70vh] overflow-y-auto">
        <!-- Report Summary -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <Card>
            <CardContent class="p-4">
              <div class="flex items-center space-x-3">
                <div class="rounded-lg bg-blue-100 dark:bg-blue-900/30 p-2">
                  <Calendar class="h-4 w-4 text-primary dark:text-blue-400" />
                </div>
                <div>
                  <p class="text-xs font-medium text-foreground dark:text-muted-foreground">Generated</p>
                  <p class="text-sm font-bold text-foreground dark:text-foreground">{{ formatDate(report.generatedAt) }}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent class="p-4">
              <div class="flex items-center space-x-3">
                <div class="rounded-lg bg-green-100 dark:bg-green-900/30 p-2">
                  <FileText class="h-4 w-4 text-green-600 dark:text-green-400" />
                </div>
                <div>
                  <p class="text-xs font-medium text-foreground dark:text-muted-foreground">File Size</p>
                  <p class="text-sm font-bold text-foreground dark:text-foreground">{{ report.fileSize }}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent class="p-4">
              <div class="flex items-center space-x-3">
                <div class="rounded-lg bg-purple-100 dark:bg-purple-900/30 p-2">
                  <BarChart3 class="h-4 w-4 text-purple-600 dark:text-purple-400" />
                </div>
                <div>
                  <p class="text-xs font-medium text-foreground dark:text-muted-foreground">Data Points</p>
                  <p class="text-sm font-bold text-foreground dark:text-foreground">{{ reportData.dataPoints }}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent class="p-4">
              <div class="flex items-center space-x-3">
                <div class="rounded-lg bg-orange-100 dark:bg-orange-900/30 p-2">
                  <TrendingUp class="h-4 w-4 text-orange-600 dark:text-orange-400" />
                </div>
                <div>
                  <p class="text-xs font-medium text-foreground dark:text-muted-foreground">Overall Score</p>
                  <p class="text-sm font-bold text-foreground dark:text-foreground">{{ reportData.overallScore }}/100</p>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Executive Summary -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center space-x-2">
              <Star class="h-5 w-5" />
              <span>Executive Summary</span>
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div class="prose dark:prose-invert max-w-none">
              <p class="text-foreground dark:text-muted-foreground leading-relaxed">
                {{ reportData.executiveSummary }}
              </p>
            </div>
          </CardContent>
        </Card>

        <!-- Key Findings -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center space-x-2">
              <Target class="h-5 w-5" />
              <span>Key Findings</span>
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Positive Findings -->
              <div>
                <h4 class="font-medium text-green-900 dark:text-green-100 mb-3 flex items-center">
                  <CheckCircle class="h-4 w-4 mr-2 text-green-600 dark:text-green-400" />
                  Strengths
                </h4>
                <ul class="space-y-2">
                  <li v-for="finding in reportData.positiveFindings" :key="finding" class="text-sm text-foreground dark:text-muted-foreground flex items-start">
                    <Check class="h-3 w-3 mr-2 text-green-600 dark:text-green-400 mt-1 flex-shrink-0" />
                    {{ finding }}
                  </li>
                </ul>
              </div>

              <!-- Areas for Improvement -->
              <div>
                <h4 class="font-medium text-orange-900 dark:text-orange-100 mb-3 flex items-center">
                  <AlertCircle class="h-4 w-4 mr-2 text-orange-600 dark:text-orange-400" />
                  Areas for Improvement
                </h4>
                <ul class="space-y-2">
                  <li v-for="finding in reportData.improvementAreas" :key="finding" class="text-sm text-foreground dark:text-muted-foreground flex items-start">
                    <AlertTriangle class="h-3 w-3 mr-2 text-orange-600 dark:text-orange-400 mt-1 flex-shrink-0" />
                    {{ finding }}
                  </li>
                </ul>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Charts and Visualizations -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- SSL Status Distribution -->
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center space-x-2">
                <PieChart class="h-5 w-5" />
                <span>SSL Certificate Status</span>
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div class="h-64 flex items-center justify-center bg-muted dark:bg-card rounded-lg">
                <div class="text-center">
                  <PieChart class="h-16 w-16 mx-auto text-muted-foreground mb-4" />
                  <p class="text-sm text-foreground dark:text-muted-foreground">SSL Status Distribution Chart</p>
                  <div class="grid grid-cols-2 gap-4 mt-4 text-xs">
                    <div class="text-center">
                      <div class="text-2xl font-bold text-green-600 dark:text-green-400">85%</div>
                      <div class="text-muted-foreground">Valid</div>
                    </div>
                    <div class="text-center">
                      <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">12%</div>
                      <div class="text-muted-foreground">Expiring</div>
                    </div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Performance Trends -->
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center space-x-2">
                <LineChart class="h-5 w-5" />
                <span>Performance Trends</span>
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div class="h-64 flex items-center justify-center bg-muted dark:bg-card rounded-lg">
                <div class="text-center">
                  <LineChart class="h-16 w-16 mx-auto text-muted-foreground mb-4" />
                  <p class="text-sm text-foreground dark:text-muted-foreground">Response Time Trend Analysis</p>
                  <div class="flex justify-center space-x-6 mt-4 text-xs">
                    <div class="text-center">
                      <div class="text-lg font-bold text-primary dark:text-blue-400">342ms</div>
                      <div class="text-muted-foreground">Avg Response</div>
                    </div>
                    <div class="text-center">
                      <div class="text-lg font-bold text-green-600 dark:text-green-400">99.2%</div>
                      <div class="text-muted-foreground">Uptime</div>
                    </div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Recommendations -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center space-x-2">
              <Lightbulb class="h-5 w-5" />
              <span>Recommendations</span>
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-4">
              <div v-for="(recommendation, index) in reportData.recommendations" :key="index" class="p-4 border rounded-lg">
                <div class="flex items-start space-x-3">
                  <div class="rounded-full p-1" :class="getRecommendationBgClass(recommendation.priority)">
                    <component :is="getRecommendationIcon(recommendation.priority)" class="h-4 w-4" :class="getRecommendationIconClass(recommendation.priority)" />
                  </div>
                  <div class="flex-1">
                    <div class="flex items-center space-x-2 mb-2">
                      <h4 class="font-medium text-foreground dark:text-foreground">{{ recommendation.title }}</h4>
                      <Badge :variant="getPriorityVariant(recommendation.priority)">
                        {{ recommendation.priority }}
                      </Badge>
                    </div>
                    <p class="text-sm text-foreground dark:text-muted-foreground mb-2">{{ recommendation.description }}</p>
                    <div class="text-xs text-muted-foreground dark:text-muted-foreground">
                      Expected impact: {{ recommendation.impact }} | Effort: {{ recommendation.effort }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Detailed Data Table -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center space-x-2">
              <Table class="h-5 w-5" />
              <span>Detailed Data</span>
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="border-b border-border">
                  <tr class="text-left">
                    <th class="py-2 font-medium text-foreground dark:text-foreground">Website</th>
                    <th class="py-2 font-medium text-foreground dark:text-foreground">SSL Status</th>
                    <th class="py-2 font-medium text-foreground dark:text-foreground">Security Score</th>
                    <th class="py-2 font-medium text-foreground dark:text-foreground">Response Time</th>
                    <th class="py-2 font-medium text-foreground dark:text-foreground">Uptime</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-border">
                  <tr v-for="row in reportData.detailedData" :key="row.website">
                    <td class="py-2 font-medium text-foreground dark:text-foreground">{{ row.website }}</td>
                    <td class="py-2">
                      <Badge :variant="getStatusVariant(row.sslStatus)">
                        {{ row.sslStatus }}
                      </Badge>
                    </td>
                    <td class="py-2 text-foreground dark:text-muted-foreground">{{ row.securityScore }}/100</td>
                    <td class="py-2 text-foreground dark:text-muted-foreground">{{ row.responseTime }}ms</td>
                    <td class="py-2 text-foreground dark:text-muted-foreground">{{ row.uptime }}%</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </CardContent>
        </Card>
      </div>

      <div class="flex justify-end space-x-3 pt-4 border-t">
        <Button @click="handleClose" variant="outline">
          Close
        </Button>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Download,
  Printer,
  Calendar,
  FileText,
  BarChart3,
  TrendingUp,
  Star,
  Target,
  CheckCircle,
  AlertCircle,
  Check,
  AlertTriangle,
  PieChart,
  LineChart,
  Lightbulb,
  Table,
  Shield,
  Zap,
  Award
} from 'lucide-vue-next';

interface Report {
  id: string;
  name: string;
  description: string;
  type: string;
  status: string;
  generatedAt: string;
  fileSize: string;
}

interface Props {
  isOpen: boolean;
  report: Report | null;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  close: [];
}>();

// Mock report data - in a real app this would come from an API
const reportData = computed(() => ({
  dataPoints: 1247,
  overallScore: 87,
  executiveSummary: "This report provides a comprehensive analysis of SSL certificate health and website performance across 32 monitored websites. The overall security posture shows strong compliance with 87% of certificates in good standing. Key areas requiring attention include 3 certificates expiring within 30 days and 2 websites showing performance degradation.",
  positiveFindings: [
    "92% of SSL certificates are from trusted Certificate Authorities",
    "Average website uptime exceeds 99.2% SLA requirements",
    "Zero critical security vulnerabilities detected",
    "All Let's Encrypt certificates have auto-renewal enabled"
  ],
  improvementAreas: [
    "3 certificates require manual renewal within 30 days",
    "Legacy API endpoints show elevated response times",
    "2 websites lack proper certificate chain configuration",
    "Security headers missing on 15% of monitored sites"
  ],
  recommendations: [
    {
      title: "Enable Automated Certificate Renewal",
      description: "Configure automatic renewal for manually managed certificates to prevent expiration incidents.",
      priority: "high",
      impact: "High security risk reduction",
      effort: "Medium"
    },
    {
      title: "Optimize Legacy API Performance",
      description: "Review and optimize database queries and caching strategies for legacy endpoints.",
      priority: "medium",
      impact: "Improved user experience",
      effort: "High"
    },
    {
      title: "Implement Security Headers",
      description: "Deploy comprehensive security headers across all websites to enhance protection.",
      priority: "medium",
      impact: "Enhanced security posture",
      effort: "Low"
    }
  ],
  detailedData: [
    {
      website: "omp.office-manager-pro.com",
      sslStatus: "valid",
      securityScore: 92,
      responseTime: 497,
      uptime: 99.8
    },
    {
      website: "www.redgas.at",
      sslStatus: "expiring",
      securityScore: 88,
      responseTime: 182,
      uptime: 99.9
    },
    {
      website: "www.fairnando.at",
      sslStatus: "valid",
      securityScore: 76,
      responseTime: 407,
      uptime: 98.2
    }
  ]
}));

const getReportIcon = (type?: string) => {
  const iconMap = {
    ssl: Shield,
    performance: TrendingUp,
    security: AlertTriangle,
    compliance: Award
  };
  return iconMap[type || ''] || FileText;
};

const getReportIconBg = (type?: string): string => {
  const bgMap = {
    ssl: 'bg-blue-100 dark:bg-blue-900/30',
    performance: 'bg-green-100 dark:bg-green-900/30',
    security: 'bg-red-100 dark:bg-red-900/30',
    compliance: 'bg-purple-100 dark:bg-purple-900/30'
  };
  return bgMap[type || ''] || 'bg-muted dark:bg-gray-800';
};

const getReportIconColor = (type?: string): string => {
  const colorMap = {
    ssl: 'text-primary dark:text-blue-400',
    performance: 'text-green-600 dark:text-green-400',
    security: 'text-destructive dark:text-red-400',
    compliance: 'text-purple-600 dark:text-purple-400'
  };
  return colorMap[type || ''] || 'text-foreground dark:text-muted-foreground';
};

const getRecommendationBgClass = (priority: string): string => {
  switch (priority) {
    case 'high':
      return 'bg-red-100 dark:bg-red-900/30';
    case 'medium':
      return 'bg-yellow-100 dark:bg-yellow-900/30';
    case 'low':
      return 'bg-blue-100 dark:bg-blue-900/30';
    default:
      return 'bg-muted dark:bg-gray-800';
  }
};

const getRecommendationIcon = (priority: string) => {
  switch (priority) {
    case 'high':
      return AlertTriangle;
    case 'medium':
      return AlertCircle;
    case 'low':
      return Lightbulb;
    default:
      return Lightbulb;
  }
};

const getRecommendationIconClass = (priority: string): string => {
  switch (priority) {
    case 'high':
      return 'text-destructive dark:text-red-400';
    case 'medium':
      return 'text-yellow-600 dark:text-yellow-400';
    case 'low':
      return 'text-primary dark:text-blue-400';
    default:
      return 'text-foreground dark:text-muted-foreground';
  }
};

const getPriorityVariant = (priority: string) => {
  switch (priority) {
    case 'high':
      return 'destructive';
    case 'medium':
      return 'secondary';
    case 'low':
      return 'outline';
    default:
      return 'outline';
  }
};

const getStatusVariant = (status: string) => {
  switch (status) {
    case 'valid':
      return 'default';
    case 'expiring':
      return 'secondary';
    case 'expired':
      return 'destructive';
    default:
      return 'outline';
  }
};

const formatDate = (dateString: string): string => {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const downloadReport = () => {
  if (!props.report) return;

  // Simulate report download
  const reportContent = `SSL Monitor Report: ${props.report.name}\nGenerated: ${props.report.generatedAt}\nOverall Score: ${reportData.value.overallScore}/100`;
  const blob = new Blob([reportContent], { type: 'text/plain;charset=utf-8;' });

  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  link.setAttribute('href', url);
  link.setAttribute('download', `${props.report.name.replace(/\s+/g, '_').toLowerCase()}.txt`);
  link.style.visibility = 'hidden';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

const printReport = () => {
  window.print();
};

const handleClose = () => {
  emit('close');
};
</script>