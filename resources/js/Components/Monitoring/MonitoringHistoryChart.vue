<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { Line } from 'vue-chartjs';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  TimeScale
} from 'chart.js';
import 'chartjs-adapter-date-fns';
import { TrendingUp, AlertTriangle, RefreshCw, BarChart3 } from 'lucide-vue-next';

// TypeScript interfaces
interface TrendData {
  labels: string[];
  data: number[];
  avg: number;
}

interface ChartDataset {
  label: string;
  data: (number | null)[];
  borderColor?: string;
  backgroundColor?: string;
  fill?: boolean;
  tension?: number;
  borderDash?: number[];
}

interface ProcessedChartData {
  labels: string[];
  datasets: ChartDataset[];
}

// Register Chart.js components
ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  TimeScale
);

// Props interface
interface Props {
  monitorId: number;
  period?: '7d' | '30d' | '90d';
  height?: number;
}

const props = withDefaults(defineProps<Props>(), {
  period: '7d',
  height: 300
});

// Component state
const loading = ref(true);
const error = ref<string | null>(null);
const chartData = ref<TrendData | null>(null);
const retryCount = ref(0);
const maxRetries = 3;

// Chart options with semantic color tokens
const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  height: props.height,
  scales: {
    x: {
      type: 'category',
      grid: {
        color: 'hsl(var(--border))',
        borderColor: 'hsl(var(--border))'
      },
      ticks: {
        color: 'hsl(var(--muted-foreground))',
        maxRotation: 45,
        minRotation: 0
      }
    },
    y: {
      beginAtZero: true,
      title: {
        display: true,
        text: 'Response Time (ms)',
        color: 'hsl(var(--foreground))'
      },
      grid: {
        color: 'hsl(var(--border))',
        borderColor: 'hsl(var(--border))'
      },
      ticks: {
        color: 'hsl(var(--muted-foreground))'
      }
    }
  },
  plugins: {
    legend: {
      display: true,
      position: 'top',
      labels: {
        color: 'hsl(var(--foreground))',
        usePointStyle: true,
        padding: 20
      }
    },
    tooltip: {
      backgroundColor: 'hsl(var(--card))',
      titleColor: 'hsl(var(--foreground))',
      bodyColor: 'hsl(var(--card-foreground))',
      borderColor: 'hsl(var(--border))',
      borderWidth: 1,
      padding: 12,
      displayColors: true,
      callbacks: {
        label: function(context: any) {
          return `${context.dataset.label}: ${context.parsed.y}ms`;
        }
      }
    }
  },
  interaction: {
    intersect: false,
    mode: 'index'
  },
  elements: {
    line: {
      tension: 0.4
    },
    point: {
      radius: 3,
      hoverRadius: 5
    }
  }
}));

// Process chart data for display
const processedChartData = computed<ProcessedChartData | null>(() => {
  if (!chartData.value || !chartData.value.data) return null;

  // Transform API data structure to Chart.js format
  // The API returns: { labels: string[], data: number[], avg: number }
  const datasets: ChartDataset[] = [
    {
      label: 'Response Time (ms)',
      data: chartData.value.data.map(value => value || null),
      borderColor: 'hsl(var(--primary))',
      backgroundColor: 'hsl(var(--primary) / 0.1)',
      fill: true,
      tension: 0.4,
    }
  ];

  return {
    labels: chartData.value.labels,
    datasets
  };
});

// Calculate statistics from chart data
const statistics = computed(() => {
  if (!chartData.value || !chartData.value.data || !chartData.value.data.length) {
    return {
      average: null,
      min: null,
      max: null,
      dataPoints: 0
    };
  }

  const validData = chartData.value.data.filter(value => value !== null && value !== undefined) as number[];

  if (validData.length === 0) {
    return {
      average: null,
      min: null,
      max: null,
      dataPoints: 0
    };
  }

  return {
    average: validData.reduce((sum, val) => sum + val, 0) / validData.length,
    min: Math.min(...validData),
    max: Math.max(...validData),
    dataPoints: validData.length
  };
});

// Fetch data from API
const fetchData = async () => {
  loading.value = true;
  error.value = null;

  try {
    const response = await fetch(`/api/monitors/${props.monitorId}/trends?period=${props.period}`);

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();
    chartData.value = data;
    retryCount.value = 0;
  } catch (err) {
    console.error('Failed to fetch monitoring trends:', err);

    if (retryCount.value < maxRetries) {
      retryCount.value++;
      setTimeout(() => {
        fetchData();
      }, 1000 * retryCount.value);
    } else {
      error.value = err instanceof Error ? err.message : 'Failed to load chart data';
    }
  } finally {
    loading.value = false;
  }
};

// Retry loading data
const retryLoading = () => {
  retryCount.value = 0;
  fetchData();
};

// Watch for prop changes
watch([() => props.monitorId, () => props.period], () => {
  fetchData();
}, { immediate: false });

// Load data on mount
onMounted(() => {
  fetchData();
});
</script>

<template>
  <div class="glass-card-strong p-6">
    <!-- Chart Header -->
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center space-x-3">
        <div class="rounded-lg bg-primary/10 p-2">
          <TrendingUp class="h-5 w-5 text-primary" />
        </div>
        <div>
          <h3 class="text-lg font-semibold text-foreground">Response Time Trends</h3>
          <p class="text-sm text-muted-foreground">
            Monitor #{{ monitorId }} •
            <span class="capitalize">{{ period }}</span>
          </p>
        </div>
      </div>

      <!-- Period Selector -->
      <div class="flex items-center space-x-1 bg-muted rounded-lg p-1">
        <button
          v-for="periodOption in [
            { value: '7d', label: '7D' },
            { value: '30d', label: '30D' },
            { value: '90d', label: '90D' }
          ]"
          :key="periodOption.value"
          @click="$emit('update:period', periodOption.value)"
          class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors"
          :class="{
            'bg-background text-foreground shadow-sm': period === periodOption.value,
            'text-muted-foreground hover:text-foreground': period !== periodOption.value
          }"
        >
          {{ periodOption.label }}
        </button>
      </div>
    </div>

    <!-- Chart Container -->
    <div class="relative" :style="{ height: `${height}px` }">
      <!-- Loading State -->
      <div v-if="loading" class="absolute inset-0 flex items-center justify-center bg-background/50 backdrop-blur-sm rounded-lg">
        <div class="flex items-center space-x-3">
          <div class="animate-spin rounded-full h-6 w-6 border-2 border-primary border-t-transparent"></div>
          <span class="text-sm text-muted-foreground">Loading chart data...</span>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="absolute inset-0 flex items-center justify-center bg-background/50 backdrop-blur-sm rounded-lg">
        <div class="text-center">
          <div class="rounded-lg bg-destructive/10 p-3 mb-3 inline-block">
            <AlertTriangle class="h-6 w-6 text-destructive" />
          </div>
          <p class="text-sm text-muted-foreground mb-3">{{ error }}</p>
          <button
            @click="retryLoading"
            class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-primary bg-primary/10 hover:bg-primary/20 rounded-md transition-colors"
          >
            <RefreshCw class="h-3 w-3 mr-1.5" />
            Retry
          </button>
        </div>
      </div>

      <!-- Chart -->
      <Line
        v-else-if="processedChartData"
        :data="processedChartData"
        :options="chartOptions"
      />

      <!-- No Data State -->
      <div v-else class="absolute inset-0 flex items-center justify-center bg-background/50 backdrop-blur-sm rounded-lg">
        <div class="text-center">
          <div class="rounded-lg bg-muted p-3 mb-3 inline-block">
            <BarChart3 class="h-6 w-6 text-muted-foreground" />
          </div>
          <p class="text-sm text-muted-foreground">No data available for this period</p>
        </div>
      </div>
    </div>

    <!-- Chart Footer -->
    <div v-if="chartData && !loading" class="mt-4 pt-4 border-t border-border">
      <div class="grid grid-cols-3 gap-4 text-sm">
        <div class="text-center">
          <p class="text-muted-foreground">Average</p>
          <p class="font-semibold text-foreground">
            {{ statistics.average ? `${Math.round(statistics.average)}ms` : 'N/A' }}
          </p>
        </div>
        <div class="text-center">
          <p class="text-muted-foreground">Min/Max</p>
          <p class="font-semibold text-foreground">
            {{ statistics.min && statistics.max
               ? `${Math.round(statistics.min)}ms / ${Math.round(statistics.max)}ms`
               : 'N/A' }}
          </p>
        </div>
        <div class="text-center">
          <p class="text-muted-foreground">Data Points</p>
          <p class="font-semibold text-foreground">
            {{ statistics.dataPoints }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>