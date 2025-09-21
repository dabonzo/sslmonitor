<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import ssl from '@/routes/ssl';
import {
  Shield,
  CheckCircle,
  AlertTriangle,
  Clock,
  TrendingUp,
  TrendingDown,
  Wifi,
  Activity
} from 'lucide-vue-next';

// Define TypeScript interfaces for SSL and Uptime data
interface SslStatistics {
  total_websites: number;
  valid_certificates: number;
  expiring_soon: number;
  expired_certificates: number;
  avg_response_time: number;
}

interface UptimeStatistics {
  total_monitors: number;
  healthy_monitors: number;
  down_monitors: number;
  avg_response_time: number;
  uptime_percentage: number;
}

interface SslActivity {
  id: number;
  website_name: string;
  status: string;
  checked_at: string;
  time_ago: string;
}

interface UptimeActivity {
  id: number;
  website_name: string;
  status: string;
  checked_at: string;
  time_ago: string;
  response_time: number;
}

interface SslAlert {
  type: string;
  website_name: string;
  message: string;
  expires_at: string;
}

interface Props {
  sslStatistics: SslStatistics;
  uptimeStatistics: UptimeStatistics;
  recentSslActivity: SslActivity[];
  recentUptimeActivity: UptimeActivity[];
  criticalAlerts: SslAlert[];
}

const props = defineProps<Props>();

// Transform real SSL and Uptime data into stats cards
const stats = computed(() => [
  {
    title: 'Total Websites',
    value: props.sslStatistics.total_websites.toString(),
    change: `${props.sslStatistics.total_websites} monitored`,
    trend: 'up',
    icon: Shield,
    color: 'text-blue-600'
  },
  {
    title: 'SSL Certificates',
    value: props.sslStatistics.valid_certificates.toString(),
    change: props.sslStatistics.total_websites > 0
      ? `${Math.round((props.sslStatistics.valid_certificates / props.sslStatistics.total_websites) * 100)}% valid`
      : '0% valid',
    trend: 'up',
    icon: CheckCircle,
    color: 'text-green-600'
  },
  {
    title: 'Uptime Status',
    value: props.uptimeStatistics.uptime_percentage.toString() + '%',
    change: `${props.uptimeStatistics.healthy_monitors}/${props.uptimeStatistics.total_monitors} healthy`,
    trend: props.uptimeStatistics.uptime_percentage >= 95 ? 'up' : 'down',
    icon: Wifi,
    color: 'text-emerald-600'
  },
  {
    title: 'Response Time',
    value: props.sslStatistics.avg_response_time > 0
      ? `${props.sslStatistics.avg_response_time}ms`
      : 'N/A',
    change: props.sslStatistics.avg_response_time > 0
      ? (props.sslStatistics.avg_response_time < 1000 ? 'Fast SSL' : 'Slow SSL')
      : 'No data',
    trend: props.sslStatistics.avg_response_time < 1000 ? 'up' : 'down',
    icon: Activity,
    color: 'text-purple-600'
  }
]);

// Combine real SSL and Uptime activity data
const recentActivity = computed(() => {
  const sslActivity = props.recentSslActivity.map(activity => ({
    title: `SSL Check: ${activity.status}`,
    description: `${activity.website_name} - ${activity.status}`,
    time: activity.time_ago,
    type: activity.status === 'valid' ? 'success' :
          activity.status === 'expired' ? 'error' : 'warning',
    category: 'ssl'
  }));

  const uptimeActivity = props.recentUptimeActivity.map(activity => ({
    title: `Uptime Check: ${activity.status}`,
    description: `${activity.website_name} - ${activity.status} (${activity.response_time}ms)`,
    time: activity.time_ago,
    type: activity.status === 'up' ? 'success' :
          activity.status === 'down' ? 'error' : 'warning',
    category: 'uptime'
  }));

  // Combine and sort by most recent
  return [...sslActivity, ...uptimeActivity]
    .sort((a, b) => a.time.localeCompare(b.time))
    .slice(0, 10);
});

// Use real critical alerts data
const criticalAlerts = computed(() => props.criticalAlerts);
</script>

<template>
    <Head title="Dashboard" />

    <DashboardLayout title="Dashboard">
        <!-- Stats Cards -->
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div
                v-for="stat in stats"
                :key="stat.title"
                class="rounded-lg bg-card text-card-foreground p-6 shadow-sm"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">
                            {{ stat.title }}
                        </p>
                        <p class="text-2xl font-bold text-foreground">
                            {{ stat.value }}
                        </p>
                        <p class="flex items-center text-sm" :class="{
                            'text-green-600': stat.trend === 'up',
                            'text-red-600': stat.trend === 'down'
                        }">
                            <TrendingUp v-if="stat.trend === 'up'" class="mr-1 h-4 w-4" />
                            <TrendingDown v-else class="mr-1 h-4 w-4" />
                            {{ stat.change }}
                        </p>
                    </div>
                    <div class="rounded-lg bg-muted p-3">
                        <component :is="stat.icon" class="h-6 w-6" :class="stat.color" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            <!-- SSL & Uptime Status Chart -->
            <div class="lg:col-span-2 rounded-lg bg-card text-card-foreground p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-foreground">
                        SSL & Uptime Monitoring Overview
                    </h3>
                    <button class="text-sm text-primary hover:text-primary/80">
                        View All
                    </button>
                </div>

                <!-- Enhanced status grid -->
                <div class="grid grid-cols-2 gap-4 h-64">
                    <!-- SSL Status -->
                    <div class="rounded-lg bg-muted p-4">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-sm font-medium text-foreground">SSL Certificates</h4>
                            <Shield class="h-5 w-5 text-blue-500" />
                        </div>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-muted-foreground">Valid</span>
                                <div class="flex items-center">
                                    <div class="w-12 bg-gray-200 rounded-full h-2 mr-2">
                                        <div class="bg-green-500 h-2 rounded-full"
                                             :style="{ width: `${props.sslStatistics.total_websites > 0 ? (props.sslStatistics.valid_certificates / props.sslStatistics.total_websites) * 100 : 0}%` }"></div>
                                    </div>
                                    <span class="text-sm font-medium">{{ props.sslStatistics.valid_certificates }}</span>
                                </div>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-muted-foreground">Expiring</span>
                                <div class="flex items-center">
                                    <div class="w-12 bg-gray-200 rounded-full h-2 mr-2">
                                        <div class="bg-yellow-500 h-2 rounded-full"
                                             :style="{ width: `${props.sslStatistics.total_websites > 0 ? (props.sslStatistics.expiring_soon / props.sslStatistics.total_websites) * 100 : 0}%` }"></div>
                                    </div>
                                    <span class="text-sm font-medium">{{ props.sslStatistics.expiring_soon }}</span>
                                </div>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-muted-foreground">Expired</span>
                                <div class="flex items-center">
                                    <div class="w-12 bg-gray-200 rounded-full h-2 mr-2">
                                        <div class="bg-red-500 h-2 rounded-full"
                                             :style="{ width: `${props.sslStatistics.total_websites > 0 ? (props.sslStatistics.expired_certificates / props.sslStatistics.total_websites) * 100 : 0}%` }"></div>
                                    </div>
                                    <span class="text-sm font-medium">{{ props.sslStatistics.expired_certificates }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Uptime Status -->
                    <div class="rounded-lg bg-muted p-4">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-sm font-medium text-foreground">Uptime Monitors</h4>
                            <Wifi class="h-5 w-5 text-emerald-500" />
                        </div>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-muted-foreground">Healthy</span>
                                <div class="flex items-center">
                                    <div class="w-12 bg-gray-200 rounded-full h-2 mr-2">
                                        <div class="bg-green-500 h-2 rounded-full"
                                             :style="{ width: `${props.uptimeStatistics.total_monitors > 0 ? (props.uptimeStatistics.healthy_monitors / props.uptimeStatistics.total_monitors) * 100 : 0}%` }"></div>
                                    </div>
                                    <span class="text-sm font-medium">{{ props.uptimeStatistics.healthy_monitors }}</span>
                                </div>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-muted-foreground">Down</span>
                                <div class="flex items-center">
                                    <div class="w-12 bg-gray-200 rounded-full h-2 mr-2">
                                        <div class="bg-red-500 h-2 rounded-full"
                                             :style="{ width: `${props.uptimeStatistics.total_monitors > 0 ? (props.uptimeStatistics.down_monitors / props.uptimeStatistics.total_monitors) * 100 : 0}%` }"></div>
                                    </div>
                                    <span class="text-sm font-medium">{{ props.uptimeStatistics.down_monitors }}</span>
                                </div>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-muted-foreground">Uptime</span>
                                <div class="flex items-center">
                                    <div class="w-12 bg-gray-200 rounded-full h-2 mr-2">
                                        <div class="bg-emerald-500 h-2 rounded-full"
                                             :style="{ width: `${props.uptimeStatistics.uptime_percentage}%` }"></div>
                                    </div>
                                    <span class="text-sm font-medium">{{ props.uptimeStatistics.uptime_percentage }}%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="rounded-lg bg-card text-card-foreground p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-foreground">
                        Recent Activity
                    </h3>
                    <button class="text-sm text-primary hover:text-primary/80">
                        View All
                    </button>
                </div>

                <div class="space-y-4">
                    <div
                        v-for="activity in recentActivity"
                        :key="activity.title + activity.time"
                        class="flex items-start space-x-3"
                    >
                        <div class="flex items-center mt-1">
                            <div
                                class="h-2 w-2 rounded-full"
                                :class="{
                                    'bg-green-400': activity.type === 'success',
                                    'bg-red-400': activity.type === 'error',
                                    'bg-yellow-400': activity.type === 'warning'
                                }"
                            />
                            <div class="ml-2">
                                <Shield v-if="activity.category === 'ssl'" class="h-3 w-3 text-blue-500" />
                                <Wifi v-else class="h-3 w-3 text-emerald-500" />
                            </div>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-foreground">
                                {{ activity.title }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{ activity.description }}
                            </p>
                            <p class="text-xs text-muted-foreground/80">
                                {{ activity.time }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Content Row -->
        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">

            <!-- Critical Alerts -->
            <div class="rounded-lg bg-card text-card-foreground p-6 shadow-sm">
                <h3 class="mb-4 text-lg font-semibold text-foreground">
                    Critical Alerts
                </h3>

                <div class="space-y-3">
                    <div
                        v-if="criticalAlerts.length === 0"
                        class="text-center text-muted-foreground py-4"
                    >
                        <CheckCircle class="h-8 w-8 mx-auto mb-2 text-green-500" />
                        <p>No critical SSL alerts</p>
                        <p class="text-xs">All certificates are healthy</p>
                    </div>

                    <div
                        v-for="alert in criticalAlerts"
                        :key="alert.website_name"
                        class="flex items-center space-x-3 rounded-lg bg-red-50 p-3 dark:bg-red-900/20"
                    >
                        <AlertTriangle class="h-5 w-5 text-red-600" />
                        <div class="flex-1">
                            <p class="text-sm font-medium text-red-800 dark:text-red-200">
                                {{ alert.type === 'ssl_expired' ? 'Certificate Expired' : 'SSL Alert' }}
                            </p>
                            <p class="text-sm text-red-600 dark:text-red-300">
                                {{ alert.message }}
                            </p>
                            <p class="text-xs text-red-500 dark:text-red-400">
                                Expired: {{ new Date(alert.expires_at).toLocaleDateString() }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="rounded-lg bg-card text-card-foreground p-6 shadow-sm">
                <h3 class="mb-4 text-lg font-semibold text-foreground">
                    Quick Actions
                </h3>

                <div class="grid grid-cols-2 gap-3">
                    <Link
                        :href="ssl.websites.create().url"
                        class="rounded-lg border border-border p-3 text-center hover:bg-muted transition-colors"
                    >
                        <Shield class="mx-auto h-6 w-6 text-primary" />
                        <p class="mt-1 text-sm font-medium text-foreground">Add Website</p>
                    </Link>

                    <Link
                        :href="ssl.websites.index().url"
                        class="rounded-lg border border-border p-3 text-center hover:bg-muted transition-colors"
                    >
                        <Wifi class="mx-auto h-6 w-6 text-primary" />
                        <p class="mt-1 text-sm font-medium text-foreground">View Websites</p>
                    </Link>

                    <button class="rounded-lg border border-border p-3 text-center hover:bg-muted transition-colors">
                        <TrendingUp class="mx-auto h-6 w-6 text-primary" />
                        <p class="mt-1 text-sm font-medium text-foreground">View Reports</p>
                    </button>

                    <button class="rounded-lg border border-border p-3 text-center hover:bg-muted transition-colors">
                        <AlertTriangle class="mx-auto h-6 w-6 text-primary" />
                        <p class="mt-1 text-sm font-medium text-foreground">Alert Rules</p>
                    </button>
                </div>
            </div>
        </div>

    </DashboardLayout>
</template>
