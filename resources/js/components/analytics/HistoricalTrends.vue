<template>
  <div class="space-y-6">
    <!-- Historical Trends Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
      <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Historical Trends</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">Long-term SSL certificate and performance analysis</p>
      </div>

      <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-4">
        <select
          v-model="selectedMetric"
          @change="updateTrendData"
          class="px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary text-sm"
        >
          <option value="response_time">Response Time</option>
          <option value="uptime">Uptime</option>
          <option value="ssl_score">SSL Score</option>
          <option value="certificate_renewals">Certificate Renewals</option>
          <option value="alert_frequency">Alert Frequency</option>
        </select>

        <select
          v-model="selectedPeriod"
          @change="updateTrendData"
          class="px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary text-sm"
        >
          <option value="6m">Last 6 months</option>
          <option value="1y">Last year</option>
          <option value="2y">Last 2 years</option>
          <option value="all">All time</option>
        </select>

        <Button @click="exportTrendData" size="sm" variant="outline">
          <Download class="h-4 w-4 mr-2" />
          Export
        </Button>
      </div>
    </div>

    <!-- Trend Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <Card>
        <CardContent class="p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Overall Trend</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                {{ trendSummary.direction }}
              </p>
            </div>
            <div class="rounded-lg p-2" :class="getTrendBackgroundClass(trendSummary.trend)">
              <component
                :is="trendSummary.trend > 0 ? TrendingUp : trendSummary.trend < 0 ? TrendingDown : Minus"
                class="h-5 w-5"
                :class="getTrendIconClass(trendSummary.trend)"
              />
            </div>
          </div>
          <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">
            {{ formatTrendPercentage(trendSummary.trend) }} change over {{ selectedPeriod }}
          </p>
        </CardContent>
      </Card>

      <Card>
        <CardContent class="p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Best Period</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                {{ trendSummary.bestPeriod.label }}
              </p>
            </div>
            <div class="rounded-lg bg-green-100 dark:bg-green-900/30 p-2">
              <Trophy class="h-5 w-5 text-green-600 dark:text-green-400" />
            </div>
          </div>
          <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">
            {{ trendSummary.bestPeriod.value }} {{ getMetricUnit() }}
          </p>
        </CardContent>
      </Card>

      <Card>
        <CardContent class="p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Data Points</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                {{ trendData.length }}
              </p>
            </div>
            <div class="rounded-lg bg-blue-100 dark:bg-blue-900/30 p-2">
              <BarChart3 class="h-5 w-5 text-blue-600 dark:text-blue-400" />
            </div>
          </div>
          <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">
            Collected over {{ selectedPeriod }}
          </p>
        </CardContent>
      </Card>
    </div>

    <!-- Main Trend Chart -->
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center space-x-2">
          <LineChart class="h-5 w-5" />
          <span>{{ getMetricLabel() }} Trend</span>
        </CardTitle>
        <CardDescription>
          Historical {{ getMetricLabel().toLowerCase() }} data showing trends over {{ selectedPeriod }}
        </CardDescription>
      </CardHeader>
      <CardContent>
        <div class="h-80 flex items-center justify-center bg-gray-50 dark:bg-gray-800 rounded-lg">
          <div v-if="isLoadingTrends" class="flex items-center space-x-2 text-gray-500 dark:text-gray-400">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary"></div>
            <span>Loading historical data...</span>
          </div>
          <div v-else class="w-full h-full p-4">
            <!-- Simulated Chart Area -->
            <div class="relative w-full h-full">
              <!-- Y-axis labels -->
              <div class="absolute left-0 top-0 h-full flex flex-col justify-between text-xs text-gray-500 dark:text-gray-400">
                <span>{{ getMaxValue() }}{{ getMetricUnit() }}</span>
                <span>{{ Math.round(getMaxValue() * 0.75) }}{{ getMetricUnit() }}</span>
                <span>{{ Math.round(getMaxValue() * 0.5) }}{{ getMetricUnit() }}</span>
                <span>{{ Math.round(getMaxValue() * 0.25) }}{{ getMetricUnit() }}</span>
                <span>0{{ getMetricUnit() }}</span>
              </div>

              <!-- Chart area -->
              <div class="ml-8 mr-4 h-full border-l-2 border-b-2 border-gray-300 dark:border-gray-600 relative">
                <!-- Data visualization -->
                <div class="absolute inset-0 flex items-end justify-between px-2">
                  <div
                    v-for="(point, index) in trendData"
                    :key="index"
                    class="flex flex-col items-center space-y-1"
                  >
                    <div
                      class="w-2 bg-blue-500 rounded-t transition-all duration-500"
                      :style="{ height: `${(point.value / getMaxValue()) * 100}%` }"
                      :title="`${point.date}: ${point.value}${getMetricUnit()}`"
                    ></div>
                    <span class="text-xs text-gray-500 dark:text-gray-400 transform -rotate-45 origin-center whitespace-nowrap">
                      {{ formatDate(point.date) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Comparative Analysis -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Month-over-Month Comparison -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center space-x-2">
            <Calendar class="h-5 w-5" />
            <span>Monthly Comparison</span>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <div v-for="month in monthlyComparison" :key="month.month" class="flex items-center justify-between">
              <div class="flex items-center space-x-3">
                <div class="w-12 text-sm text-gray-600 dark:text-gray-400">{{ month.month }}</div>
                <div class="flex-1">
                  <div class="flex items-center space-x-2">
                    <span class="font-medium text-gray-900 dark:text-gray-100">
                      {{ month.value }}{{ getMetricUnit() }}
                    </span>
                    <component
                      :is="month.change > 0 ? TrendingUp : month.change < 0 ? TrendingDown : Minus"
                      class="h-3 w-3"
                      :class="getTrendIconClass(month.change)"
                    />
                    <span class="text-xs" :class="getTrendClass(month.change)">
                      {{ formatTrendPercentage(month.change) }}
                    </span>
                  </div>
                </div>
              </div>
              <div class="w-20 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                <div
                  class="h-full bg-blue-500 transition-all duration-300"
                  :style="{ width: `${(month.value / getMaxValue()) * 100}%` }"
                ></div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Seasonality Patterns -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center space-x-2">
            <Clock class="h-5 w-5" />
            <span>Seasonality Patterns</span>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <div class="text-sm text-gray-600 dark:text-gray-400 mb-4">
              Identified patterns in {{ getMetricLabel().toLowerCase() }} based on historical data
            </div>

            <div v-for="pattern in seasonalityPatterns" :key="pattern.period" class="p-3 rounded-lg border">
              <div class="flex items-center justify-between mb-2">
                <h4 class="font-medium text-gray-900 dark:text-gray-100">{{ pattern.period }}</h4>
                <Badge :variant="pattern.impact > 0 ? 'destructive' : 'default'">
                  {{ pattern.impact > 0 ? 'Higher' : pattern.impact < 0 ? 'Lower' : 'Stable' }}
                </Badge>
              </div>
              <p class="text-sm text-gray-600 dark:text-gray-400">{{ pattern.description }}</p>
              <div class="mt-2 text-xs text-gray-500 dark:text-gray-500">
                Average impact: {{ formatTrendPercentage(pattern.impact) }}
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Anomaly Detection -->
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center space-x-2">
          <AlertCircle class="h-5 w-5" />
          <span>Anomaly Detection</span>
        </CardTitle>
        <CardDescription>
          Unusual patterns or outliers detected in historical {{ getMetricLabel().toLowerCase() }} data
        </CardDescription>
      </CardHeader>
      <CardContent>
        <div v-if="anomalies.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
          <CheckCircle class="h-12 w-12 mx-auto mb-2" />
          <p>No significant anomalies detected in the selected time period</p>
          <p class="text-sm mt-1">Your {{ getMetricLabel().toLowerCase() }} patterns appear normal</p>
        </div>
        <div v-else class="space-y-4">
          <div v-for="anomaly in anomalies" :key="anomaly.id" class="p-4 rounded-lg border border-orange-200 dark:border-orange-800 bg-orange-50 dark:bg-orange-900/20">
            <div class="flex items-start space-x-3">
              <AlertTriangle class="h-5 w-5 text-orange-600 dark:text-orange-400 mt-0.5" />
              <div class="flex-1">
                <div class="flex items-center space-x-2 mb-2">
                  <h4 class="font-medium text-orange-900 dark:text-orange-100">{{ anomaly.type }}</h4>
                  <Badge variant="outline">{{ formatDate(anomaly.date) }}</Badge>
                </div>
                <p class="text-sm text-orange-800 dark:text-orange-200 mb-2">{{ anomaly.description }}</p>
                <div class="text-xs text-orange-700 dark:text-orange-300">
                  Expected: {{ anomaly.expected }}{{ getMetricUnit() }} |
                  Actual: {{ anomaly.actual }}{{ getMetricUnit() }} |
                  Deviation: {{ formatTrendPercentage(anomaly.deviation) }}
                </div>
              </div>
            </div>
          </div>
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
  Minus,
  Trophy,
  BarChart3,
  LineChart,
  Calendar,
  Clock,
  AlertCircle,
  CheckCircle,
  AlertTriangle,
  Download
} from 'lucide-vue-next';

interface TrendData {
  date: string;
  value: number;
}

interface TrendSummary {
  direction: string;
  trend: number;
  bestPeriod: {
    label: string;
    value: number;
  };
}

interface MonthlyComparison {
  month: string;
  value: number;
  change: number;
}

interface SeasonalityPattern {
  period: string;
  impact: number;
  description: string;
}

interface Anomaly {
  id: string;
  type: string;
  date: string;
  description: string;
  expected: number;
  actual: number;
  deviation: number;
}

const selectedMetric = ref('response_time');
const selectedPeriod = ref('6m');
const isLoadingTrends = ref(false);

const trendData = ref<TrendData[]>([
  { date: '2024-03-01', value: 420 },
  { date: '2024-04-01', value: 395 },
  { date: '2024-05-01', value: 380 },
  { date: '2024-06-01', value: 365 },
  { date: '2024-07-01', value: 350 },
  { date: '2024-08-01', value: 342 },
  { date: '2024-09-01', value: 338 }
]);

const trendSummary = ref<TrendSummary>({
  direction: 'Improving',
  trend: -19.5,
  bestPeriod: {
    label: 'Sep 2024',
    value: 338
  }
});

const monthlyComparison = ref<MonthlyComparison[]>([
  { month: 'Sep', value: 338, change: -1.2 },
  { month: 'Aug', value: 342, change: -2.3 },
  { month: 'Jul', value: 350, change: -4.1 },
  { month: 'Jun', value: 365, change: -4.0 },
  { month: 'May', value: 380, change: -3.8 },
  { month: 'Apr', value: 395, change: -5.9 }
]);

const seasonalityPatterns = ref<SeasonalityPattern[]>([
  {
    period: 'Business Hours',
    impact: 8.2,
    description: 'Response times tend to be higher during peak business hours (9 AM - 5 PM) due to increased traffic.'
  },
  {
    period: 'Weekend Effect',
    impact: -12.5,
    description: 'Performance typically improves on weekends with reduced server load and traffic.'
  },
  {
    period: 'Month-end Spikes',
    impact: 15.3,
    description: 'Slight performance degradation observed at month-end, likely due to reporting activities.'
  }
]);

const anomalies = ref<Anomaly[]>([
  {
    id: '1',
    type: 'Performance Spike',
    date: '2024-08-15',
    description: 'Unusually high response times detected during maintenance window',
    expected: 340,
    actual: 850,
    deviation: 150.0
  }
]);

const getMetricLabel = (): string => {
  const labels = {
    response_time: 'Response Time',
    uptime: 'Uptime',
    ssl_score: 'SSL Score',
    certificate_renewals: 'Certificate Renewals',
    alert_frequency: 'Alert Frequency'
  };
  return labels[selectedMetric.value] || 'Metric';
};

const getMetricUnit = (): string => {
  const units = {
    response_time: 'ms',
    uptime: '%',
    ssl_score: '/100',
    certificate_renewals: '',
    alert_frequency: ' alerts'
  };
  return units[selectedMetric.value] || '';
};

const getMaxValue = (): number => {
  const maxValues = {
    response_time: 500,
    uptime: 100,
    ssl_score: 100,
    certificate_renewals: 20,
    alert_frequency: 50
  };
  return maxValues[selectedMetric.value] || 100;
};

const getTrendClass = (trend: number): string => {
  if (selectedMetric.value === 'response_time' || selectedMetric.value === 'alert_frequency') {
    // For these metrics, lower is better
    if (trend > 0) return 'text-red-600 dark:text-red-400';
    if (trend < 0) return 'text-green-600 dark:text-green-400';
  } else {
    // For uptime and ssl_score, higher is better
    if (trend > 0) return 'text-green-600 dark:text-green-400';
    if (trend < 0) return 'text-red-600 dark:text-red-400';
  }
  return 'text-gray-600 dark:text-gray-400';
};

const getTrendIconClass = (trend: number): string => {
  if (selectedMetric.value === 'response_time' || selectedMetric.value === 'alert_frequency') {
    // For these metrics, lower is better
    if (trend > 0) return 'text-red-600 dark:text-red-400';
    if (trend < 0) return 'text-green-600 dark:text-green-400';
  } else {
    // For uptime and ssl_score, higher is better
    if (trend > 0) return 'text-green-600 dark:text-green-400';
    if (trend < 0) return 'text-red-600 dark:text-red-400';
  }
  return 'text-gray-600 dark:text-gray-400';
};

const getTrendBackgroundClass = (trend: number): string => {
  if (selectedMetric.value === 'response_time' || selectedMetric.value === 'alert_frequency') {
    // For these metrics, lower is better
    if (trend > 0) return 'bg-red-100 dark:bg-red-900/30';
    if (trend < 0) return 'bg-green-100 dark:bg-green-900/30';
  } else {
    // For uptime and ssl_score, higher is better
    if (trend > 0) return 'bg-green-100 dark:bg-green-900/30';
    if (trend < 0) return 'bg-red-100 dark:bg-red-900/30';
  }
  return 'bg-gray-100 dark:bg-gray-800';
};

const formatTrendPercentage = (trend: number): string => {
  const sign = trend > 0 ? '+' : '';
  return `${sign}${trend.toFixed(1)}%`;
};

const formatDate = (dateString: string): string => {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
};

const updateTrendData = async () => {
  isLoadingTrends.value = true;

  // Simulate API call based on selected metric and period
  await new Promise(resolve => setTimeout(resolve, 1000));

  // Update trend data based on selected metric
  switch (selectedMetric.value) {
    case 'uptime':
      trendData.value = [
        { date: '2024-03-01', value: 98.2 },
        { date: '2024-04-01', value: 98.8 },
        { date: '2024-05-01', value: 99.1 },
        { date: '2024-06-01', value: 99.3 },
        { date: '2024-07-01', value: 99.2 },
        { date: '2024-08-01', value: 99.4 },
        { date: '2024-09-01', value: 99.2 }
      ];
      trendSummary.value = {
        direction: 'Stable',
        trend: 1.0,
        bestPeriod: { label: 'Aug 2024', value: 99.4 }
      };
      break;
    case 'ssl_score':
      trendData.value = [
        { date: '2024-03-01', value: 82 },
        { date: '2024-04-01', value: 84 },
        { date: '2024-05-01', value: 85 },
        { date: '2024-06-01', value: 86 },
        { date: '2024-07-01', value: 87 },
        { date: '2024-08-01', value: 87 },
        { date: '2024-09-01', value: 87 }
      ];
      trendSummary.value = {
        direction: 'Improving',
        trend: 6.1,
        bestPeriod: { label: 'Sep 2024', value: 87 }
      };
      break;
    default:
      // Keep default response_time data
      break;
  }

  isLoadingTrends.value = false;
};

const exportTrendData = () => {
  // Simulate export functionality
  const csvContent = trendData.value
    .map(point => `${point.date},${point.value}`)
    .join('\n');

  const blob = new Blob([`Date,${getMetricLabel()}\n${csvContent}`], {
    type: 'text/csv;charset=utf-8;'
  });

  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  link.setAttribute('href', url);
  link.setAttribute('download', `ssl_monitor_${selectedMetric.value}_${selectedPeriod.value}.csv`);
  link.style.visibility = 'hidden';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

onMounted(() => {
  updateTrendData();
});
</script>