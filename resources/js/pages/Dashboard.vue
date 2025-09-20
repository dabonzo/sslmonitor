<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
  Shield,
  CheckCircle,
  AlertTriangle,
  Clock,
  TrendingUp,
  TrendingDown
} from 'lucide-vue-next';

// Define TypeScript interfaces for SSL data
interface SslStatistics {
  total_websites: number;
  valid_certificates: number;
  expiring_soon: number;
  expired_certificates: number;
  avg_response_time: number;
}

interface SslActivity {
  id: number;
  website_name: string;
  status: string;
  checked_at: string;
  time_ago: string;
}

interface SslAlert {
  type: string;
  website_name: string;
  message: string;
  expires_at: string;
}

interface Props {
  sslStatistics: SslStatistics;
  recentSslActivity: SslActivity[];
  criticalAlerts: SslAlert[];
}

const props = defineProps<Props>();

// Transform real SSL data into stats cards
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
    title: 'Valid Certificates',
    value: props.sslStatistics.valid_certificates.toString(),
    change: props.sslStatistics.total_websites > 0
      ? `${Math.round((props.sslStatistics.valid_certificates / props.sslStatistics.total_websites) * 100)}%`
      : '0%',
    trend: 'up',
    icon: CheckCircle,
    color: 'text-green-600'
  },
  {
    title: 'Expiring Soon',
    value: props.sslStatistics.expiring_soon.toString(),
    change: props.sslStatistics.total_websites > 0
      ? `${Math.round((props.sslStatistics.expiring_soon / props.sslStatistics.total_websites) * 100)}%`
      : '0%',
    trend: props.sslStatistics.expiring_soon > 0 ? 'down' : 'up',
    icon: AlertTriangle,
    color: 'text-yellow-600'
  },
  {
    title: 'Avg Response Time',
    value: props.sslStatistics.avg_response_time > 0
      ? `${props.sslStatistics.avg_response_time}ms`
      : 'N/A',
    change: props.sslStatistics.avg_response_time > 0
      ? (props.sslStatistics.avg_response_time < 1000 ? 'Fast' : 'Slow')
      : 'No data',
    trend: props.sslStatistics.avg_response_time < 1000 ? 'up' : 'down',
    icon: Clock,
    color: 'text-purple-600'
  }
]);

// Use real SSL activity data
const recentActivity = computed(() =>
  props.recentSslActivity.map(activity => ({
    title: `SSL Check: ${activity.status}`,
    description: `${activity.website_name} - ${activity.status}`,
    time: activity.time_ago,
    type: activity.status === 'valid' ? 'success' :
          activity.status === 'expired' ? 'error' : 'warning'
  }))
);

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

            <!-- SSL Status Chart -->
            <div class="lg:col-span-2 rounded-lg bg-card text-card-foreground p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-foreground">
                        Certificate Status Overview
                    </h3>
                    <button class="text-sm text-primary hover:text-primary/80">
                        View All
                    </button>
                </div>

                <!-- Placeholder for chart -->
                <div class="flex h-64 items-center justify-center rounded-lg bg-muted">
                    <div class="text-center">
                        <Shield class="mx-auto h-12 w-12 text-muted-foreground" />
                        <p class="mt-2 text-sm text-muted-foreground">
                            SSL Certificate Status Chart
                        </p>
                        <p class="text-xs text-muted-foreground/80">Chart visualization will be implemented</p>
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
                        :key="activity.title"
                        class="flex items-start space-x-3"
                    >
                        <div
                            class="mt-1 h-2 w-2 rounded-full"
                            :class="{
                                'bg-green-400': activity.type === 'success',
                                'bg-blue-400': activity.type === 'info',
                                'bg-yellow-400': activity.type === 'warning'
                            }"
                        />
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
                    <button class="rounded-lg border border-border p-3 text-center hover:bg-muted">
                        <Shield class="mx-auto h-6 w-6 text-primary" />
                        <p class="mt-1 text-sm font-medium text-foreground">Add Certificate</p>
                    </button>

                    <button class="rounded-lg border border-border p-3 text-center hover:bg-muted">
                        <Clock class="mx-auto h-6 w-6 text-primary" />
                        <p class="mt-1 text-sm font-medium text-foreground">Create Monitor</p>
                    </button>

                    <button class="rounded-lg border border-border p-3 text-center hover:bg-muted">
                        <TrendingUp class="mx-auto h-6 w-6 text-primary" />
                        <p class="mt-1 text-sm font-medium text-foreground">View Reports</p>
                    </button>

                    <button class="rounded-lg border border-border p-3 text-center hover:bg-muted">
                        <AlertTriangle class="mx-auto h-6 w-6 text-primary" />
                        <p class="mt-1 text-sm font-medium text-foreground">Alert Rules</p>
                    </button>
                </div>
            </div>
        </div>

    </DashboardLayout>
</template>
