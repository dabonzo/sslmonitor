<template>
  <div class="space-y-6">
    <!-- Analytics Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
      <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Performance Analytics</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">Monitor SSL certificate performance and trends</p>
      </div>

      <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-4">
        <select
          v-model="selectedTimeRange"
          @change="updateAnalytics"
          class="px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary text-sm"
        >
          <option value="7d">Last 7 days</option>
          <option value="30d">Last 30 days</option>
          <option value="90d">Last 90 days</option>
          <option value="1y">Last year</option>
        </select>

        <Button @click="refreshAnalytics" size="sm" :disabled="isLoading">
          <RefreshCw class="h-4 w-4 mr-2" :class="{ 'animate-spin': isLoading }" />
          Refresh
        </Button>
      </div>
    </div>

    <!-- Performance Metrics Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <Card>
        <CardContent class="p-4">
          <div class="flex items-center space-x-3">
            <div class="rounded-lg bg-green-100 dark:bg-green-900/30 p-2">
              <TrendingUp class="h-5 w-5 text-green-600 dark:text-green-400" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Avg Response Time</p>
              <p class="text-xl font-bold text-gray-900 dark:text-gray-100">
                {{ performanceMetrics.avgResponseTime }}ms
              </p>
              <p class="text-xs" :class="getTrendClass(performanceMetrics.responseTimeTrend)">
                {{ formatTrend(performanceMetrics.responseTimeTrend) }}% vs last period
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardContent class="p-4">
          <div class="flex items-center space-x-3">
            <div class="rounded-lg bg-blue-100 dark:bg-blue-900/30 p-2">
              <Activity class="h-5 w-5 text-blue-600 dark:text-blue-400" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Uptime</p>
              <p class="text-xl font-bold text-gray-900 dark:text-gray-100">
                {{ performanceMetrics.uptime }}%
              </p>
              <p class="text-xs" :class="getTrendClass(performanceMetrics.uptimeTrend)">
                {{ formatTrend(performanceMetrics.uptimeTrend) }}% vs last period
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardContent class="p-4">
          <div class="flex items-center space-x-3">
            <div class="rounded-lg bg-orange-100 dark:bg-orange-900/30 p-2">
              <Shield class="h-5 w-5 text-orange-600 dark:text-orange-400" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">SSL Health Score</p>
              <p class="text-xl font-bold text-gray-900 dark:text-gray-100">
                {{ performanceMetrics.avgSslScore }}/100
              </p>
              <p class="text-xs" :class="getTrendClass(performanceMetrics.sslScoreTrend)">
                {{ formatTrend(performanceMetrics.sslScoreTrend) }} points vs last period
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardContent class="p-4">
          <div class="flex items-center space-x-3">
            <div class="rounded-lg bg-red-100 dark:bg-red-900/30 p-2">
              <AlertTriangle class="h-5 w-5 text-red-600 dark:text-red-400" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Critical Alerts</p>
              <p class="text-xl font-bold text-gray-900 dark:text-gray-100">
                {{ performanceMetrics.criticalAlerts }}
              </p>
              <p class="text-xs" :class="getTrendClass(-performanceMetrics.alertsTrend)">
                {{ formatTrend(performanceMetrics.alertsTrend) }} vs last period
              </p>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Response Time Trend Chart -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center space-x-2">
            <TrendingUp class="h-5 w-5" />
            <span>Response Time Trends</span>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="h-64 flex items-center justify-center bg-gray-50 dark:bg-gray-800 rounded-lg">
            <div v-if="isLoading" class="flex items-center space-x-2 text-gray-500 dark:text-gray-400">
              <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary"></div>
              <span class="text-sm">Loading chart data...</span>
            </div>
            <div v-else class="text-center space-y-2">
              <LineChart class="h-8 w-8 mx-auto text-gray-400" />
              <p class="text-sm text-gray-600 dark:text-gray-400">Response time chart visualization</p>
              <p class="text-xs text-gray-500 dark:text-gray-500">
                Showing {{ responseTimeData.length }} data points over {{ selectedTimeRange }}
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- SSL Certificate Status Distribution -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center space-x-2">
            <Shield class="h-5 w-5" />
            <span>SSL Certificate Status</span>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <div v-for="status in sslStatusData" :key="status.name" class="flex items-center justify-between">
              <div class="flex items-center space-x-3">
                <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: status.color }"></div>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ status.name }}</span>
              </div>
              <div class="flex items-center space-x-3">
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ status.count }} sites</span>
                <div class="w-20 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                  <div
                    class="h-full transition-all duration-300"
                    :style="{
                      width: `${status.percentage}%`,
                      backgroundColor: status.color
                    }"
                  ></div>
                </div>
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100 w-12 text-right">
                  {{ status.percentage }}%
                </span>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Uptime Performance -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center space-x-2">
            <Activity class="h-5 w-5" />
            <span>Uptime Performance</span>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="h-64 flex items-center justify-center bg-gray-50 dark:bg-gray-800 rounded-lg">
            <div v-if="isLoading" class="flex items-center space-x-2 text-gray-500 dark:text-gray-400">
              <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary"></div>
              <span class="text-sm">Loading uptime data...</span>
            </div>
            <div v-else class="text-center space-y-2">
              <BarChart class="h-8 w-8 mx-auto text-gray-400" />
              <p class="text-sm text-gray-600 dark:text-gray-400">Uptime performance visualization</p>
              <div class="grid grid-cols-3 gap-4 text-xs">
                <div class="text-center">
                  <div class="font-medium text-green-600 dark:text-green-400">99.9%</div>
                  <div class="text-gray-500">Best</div>
                </div>
                <div class="text-center">
                  <div class="font-medium text-blue-600 dark:text-blue-400">{{ performanceMetrics.uptime }}%</div>
                  <div class="text-gray-500">Average</div>
                </div>
                <div class="text-center">
                  <div class="font-medium text-orange-600 dark:text-orange-400">97.2%</div>
                  <div class="text-gray-500">Lowest</div>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Alert Frequency Analysis -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center space-x-2">
            <AlertTriangle class="h-5 w-5" />
            <span>Alert Frequency</span>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <div v-for="alertType in alertFrequencyData" :key="alertType.type">
              <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ alertType.label }}</span>
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ alertType.count }} alerts</span>
              </div>
              <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div
                  class="h-2 rounded-full transition-all duration-300"
                  :class="alertType.colorClass"
                  :style="{ width: `${alertType.percentage}%` }"
                ></div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Detailed Performance Table -->
    <Card>
      <CardHeader>
        <CardTitle>Website Performance Details</CardTitle>
        <CardDescription>
          Detailed performance metrics for all monitored websites over the selected time period
        </CardDescription>
      </CardHeader>
      <CardContent>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="border-b border-border">
              <tr class="text-left">
                <th class="py-2 font-medium text-gray-900 dark:text-gray-100">Website</th>
                <th class="py-2 font-medium text-gray-900 dark:text-gray-100">Avg Response</th>
                <th class="py-2 font-medium text-gray-900 dark:text-gray-100">Uptime</th>
                <th class="py-2 font-medium text-gray-900 dark:text-gray-100">SSL Score</th>
                <th class="py-2 font-medium text-gray-900 dark:text-gray-100">Alerts</th>
                <th class="py-2 font-medium text-gray-900 dark:text-gray-100">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border">
              <tr v-for="website in websitePerformance" :key="website.id">
                <td class="py-3">
                  <div>
                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ website.name }}</div>
                    <div class="text-xs text-gray-600 dark:text-gray-400">{{ website.url }}</div>
                  </div>
                </td>
                <td class="py-3">
                  <div class="flex items-center space-x-2">
                    <span class="text-gray-900 dark:text-gray-100">{{ website.avgResponse }}ms</span>
                    <component
                      :is="website.responseTimeTrend > 0 ? TrendingUp : TrendingDown"
                      class="h-3 w-3"
                      :class="website.responseTimeTrend > 0 ? 'text-red-500' : 'text-green-500'"
                    />
                  </div>
                </td>
                <td class="py-3">
                  <Badge
                    :variant="website.uptime >= 99.5 ? 'default' : website.uptime >= 98 ? 'secondary' : 'destructive'"
                  >
                    {{ website.uptime }}%
                  </Badge>
                </td>
                <td class="py-3">
                  <div class="flex items-center space-x-2">
                    <div class="w-8 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                      <div
                        class="h-full rounded-full transition-all duration-300"
                        :class="getSslScoreColor(website.sslScore)"
                        :style="{ width: `${website.sslScore}%` }"
                      ></div>
                    </div>
                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ website.sslScore }}</span>
                  </div>
                </td>
                <td class="py-3">
                  <span class="text-sm text-gray-700 dark:text-gray-300">{{ website.alertCount }}</span>
                </td>
                <td class="py-3">
                  <Badge :variant="getStatusVariant(website.status)">
                    {{ website.status }}
                  </Badge>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardContent>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  TrendingUp,
  TrendingDown,
  Activity,
  Shield,
  AlertTriangle,
  RefreshCw,
  LineChart,
  BarChart
} from 'lucide-vue-next';

interface PerformanceMetrics {
  avgResponseTime: number;
  responseTimeTrend: number;
  uptime: number;
  uptimeTrend: number;
  avgSslScore: number;
  sslScoreTrend: number;
  criticalAlerts: number;
  alertsTrend: number;
}

interface SslStatusData {
  name: string;
  count: number;
  percentage: number;
  color: string;
}

interface AlertFrequencyData {
  type: string;
  label: string;
  count: number;
  percentage: number;
  colorClass: string;
}

interface WebsitePerformance {
  id: string;
  name: string;
  url: string;
  avgResponse: number;
  responseTimeTrend: number;
  uptime: number;
  sslScore: number;
  alertCount: number;
  status: 'healthy' | 'warning' | 'critical';
}

const selectedTimeRange = ref('30d');
const isLoading = ref(false);

const performanceMetrics = ref<PerformanceMetrics>({
  avgResponseTime: 342,
  responseTimeTrend: -12.5,
  uptime: 99.2,
  uptimeTrend: 0.3,
  avgSslScore: 87,
  sslScoreTrend: 2.1,
  criticalAlerts: 3,
  alertsTrend: -40.0
});

const responseTimeData = ref([
  { date: '2024-09-01', value: 380 },
  { date: '2024-09-08', value: 365 },
  { date: '2024-09-15', value: 342 },
  { date: '2024-09-22', value: 298 }
]);

const sslStatusData = ref<SslStatusData[]>([
  { name: 'Valid & Secure', count: 28, percentage: 70, color: '#22c55e' },
  { name: 'Expiring Soon', count: 8, percentage: 20, color: '#f59e0b' },
  { name: 'Invalid/Expired', count: 3, percentage: 7.5, color: '#ef4444' },
  { name: 'Not Monitored', count: 1, percentage: 2.5, color: '#6b7280' }
]);

const alertFrequencyData = ref<AlertFrequencyData[]>([
  { type: 'ssl_expiry', label: 'SSL Expiry Warnings', count: 12, percentage: 48, colorClass: 'bg-orange-500' },
  { type: 'uptime', label: 'Uptime Failures', count: 8, percentage: 32, colorClass: 'bg-red-500' },
  { type: 'response_time', label: 'Slow Response', count: 4, percentage: 16, colorClass: 'bg-yellow-500' },
  { type: 'ssl_invalid', label: 'SSL Certificate Issues', count: 1, percentage: 4, colorClass: 'bg-purple-500' }
]);

const websitePerformance = ref<WebsitePerformance[]>([
  {
    id: '1',
    name: 'Office Manager Pro',
    url: 'omp.office-manager-pro.com',
    avgResponse: 497,
    responseTimeTrend: 8.2,
    uptime: 99.8,
    sslScore: 92,
    alertCount: 1,
    status: 'healthy'
  },
  {
    id: '2',
    name: 'Red Gas Austria',
    url: 'www.redgas.at',
    avgResponse: 182,
    responseTimeTrend: -15.3,
    uptime: 99.9,
    sslScore: 88,
    alertCount: 0,
    status: 'healthy'
  },
  {
    id: '3',
    name: 'Fairnando',
    url: 'www.fairnando.at',
    avgResponse: 407,
    responseTimeTrend: -5.1,
    uptime: 98.2,
    sslScore: 76,
    alertCount: 3,
    status: 'warning'
  }
]);

const getTrendClass = (trend: number): string => {
  if (trend > 0) return 'text-green-600 dark:text-green-400';
  if (trend < 0) return 'text-red-600 dark:text-red-400';
  return 'text-gray-600 dark:text-gray-400';
};

const formatTrend = (trend: number): string => {
  const sign = trend > 0 ? '+' : '';
  return `${sign}${trend.toFixed(1)}`;
};

const getSslScoreColor = (score: number): string => {
  if (score >= 90) return 'bg-green-500';
  if (score >= 80) return 'bg-yellow-500';
  if (score >= 70) return 'bg-orange-500';
  return 'bg-red-500';
};

const getStatusVariant = (status: string) => {
  switch (status) {
    case 'healthy':
      return 'default';
    case 'warning':
      return 'secondary';
    case 'critical':
      return 'destructive';
    default:
      return 'outline';
  }
};

const updateAnalytics = async () => {
  isLoading.value = true;

  // Simulate API call
  await new Promise(resolve => setTimeout(resolve, 1500));

  // Update metrics based on selected time range
  switch (selectedTimeRange.value) {
    case '7d':
      performanceMetrics.value.avgResponseTime = 298;
      performanceMetrics.value.uptime = 99.5;
      break;
    case '30d':
      performanceMetrics.value.avgResponseTime = 342;
      performanceMetrics.value.uptime = 99.2;
      break;
    case '90d':
      performanceMetrics.value.avgResponseTime = 365;
      performanceMetrics.value.uptime = 98.9;
      break;
    case '1y':
      performanceMetrics.value.avgResponseTime = 398;
      performanceMetrics.value.uptime = 98.7;
      break;
  }

  isLoading.value = false;
};

const refreshAnalytics = () => {
  updateAnalytics();
};

onMounted(() => {
  updateAnalytics();
});
</script>