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
  Activity,
  Zap,
  Globe,
  BarChart3,
  Eye,
  ArrowRightLeft,
  Users,
  Settings,
  Plus
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

interface TransferSuggestions {
  personal_websites_count: number;
  available_teams_count: number;
  quick_transfer_teams: Array<{
    id: number;
    name: string;
    member_count: number;
  }>;
  should_show_suggestion: boolean;
}

interface Props {
  sslStatistics: SslStatistics;
  uptimeStatistics: UptimeStatistics;
  recentSslActivity: SslActivity[];
  recentUptimeActivity: UptimeActivity[];
  criticalAlerts: SslAlert[];
  transferSuggestions: TransferSuggestions;
}

const props = defineProps<Props>();

// Transform real SSL and Uptime data into stats cards
const stats = computed(() => [
  {
    title: 'Total Websites',
    value: props.sslStatistics.total_websites.toString(),
    change: `${props.sslStatistics.total_websites} monitored`,
    trend: 'up',
    icon: Globe,
    color: 'text-gray-700 dark:text-gray-200',
    bgGradient: 'bg-gradient-to-br from-gray-50 to-slate-100 dark:from-gray-800 dark:to-slate-800',
    iconBg: 'bg-gray-100 dark:bg-gray-700',
    iconColor: 'text-gray-600 dark:text-gray-400'
  },
  {
    title: 'SSL Certificates',
    value: props.sslStatistics.valid_certificates.toString(),
    change: props.sslStatistics.total_websites > 0
      ? `${Math.round((props.sslStatistics.valid_certificates / props.sslStatistics.total_websites) * 100)}% valid`
      : '0% valid',
    trend: 'up',
    icon: Shield,
    color: 'text-gray-700 dark:text-gray-200',
    bgGradient: 'bg-gradient-to-br from-gray-50 to-slate-100 dark:from-gray-800 dark:to-slate-800',
    iconBg: 'bg-green-100 dark:bg-green-900/30',
    iconColor: 'text-green-600 dark:text-green-400'
  },
  {
    title: 'Uptime Status',
    value: props.uptimeStatistics.uptime_percentage.toString() + '%',
    change: `${props.uptimeStatistics.healthy_monitors}/${props.uptimeStatistics.total_monitors} healthy`,
    trend: props.uptimeStatistics.uptime_percentage >= 95 ? 'up' : 'down',
    icon: Zap,
    color: 'text-gray-700 dark:text-gray-200',
    bgGradient: 'bg-gradient-to-br from-gray-50 to-slate-100 dark:from-gray-800 dark:to-slate-800',
    iconBg: props.uptimeStatistics.uptime_percentage >= 95 ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30',
    iconColor: props.uptimeStatistics.uptime_percentage >= 95 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'
  },
  {
    title: 'Response Time',
    value: props.uptimeStatistics.avg_response_time > 0
      ? `${props.uptimeStatistics.avg_response_time}ms`
      : 'N/A',
    change: props.uptimeStatistics.avg_response_time > 0
      ? (props.uptimeStatistics.avg_response_time < 1000 ? 'Fast' : 'Slow')
      : 'No data',
    trend: props.uptimeStatistics.avg_response_time < 1000 ? 'up' : 'down',
    icon: BarChart3,
    color: 'text-gray-700 dark:text-gray-200',
    bgGradient: 'bg-gradient-to-br from-gray-50 to-slate-100 dark:from-gray-800 dark:to-slate-800',
    iconBg: 'bg-blue-100 dark:bg-blue-900/30',
    iconColor: 'text-blue-600 dark:text-blue-400'
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
        <!-- Modern Stats Cards -->
        <div class="mb-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <div
                v-for="stat in stats"
                :key="stat.title"
                :class="stat.bgGradient"
                class="group relative overflow-hidden rounded-2xl p-6 shadow-lg border border-gray-200 dark:border-gray-700 transition-all duration-300 hover:shadow-xl cursor-pointer"
            >
                <!-- Subtle hover effect -->
                <div class="absolute inset-0 bg-gray-50/50 dark:bg-gray-700/50 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

                <div class="relative flex items-center justify-between">
                    <div class="flex-1">
                        <p :class="stat.color" class="text-sm font-medium mb-2 opacity-70">
                            {{ stat.title }}
                        </p>
                        <p :class="stat.color" class="text-3xl font-bold mb-1 tracking-tight">
                            {{ stat.value }}
                        </p>
                        <p :class="stat.color" class="flex items-center text-sm font-medium opacity-80">
                            <TrendingUp v-if="stat.trend === 'up'" class="mr-1.5 h-4 w-4 text-green-500" />
                            <TrendingDown v-else class="mr-1.5 h-4 w-4 text-red-500" />
                            {{ stat.change }}
                        </p>
                    </div>
                    <div :class="stat.iconBg" class="rounded-xl p-3 group-hover:scale-110 transition-transform duration-300">
                        <component :is="stat.icon" :class="stat.iconColor" class="h-7 w-7" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            <!-- SSL & Uptime Status Chart -->
            <div class="lg:col-span-2 rounded-2xl bg-gradient-to-br from-slate-50 via-white to-gray-50 dark:from-slate-900 dark:via-slate-800 dark:to-gray-900 p-6 shadow-xl border border-gray-100 dark:border-gray-800">
                <div class="mb-6 flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="rounded-xl bg-gray-100 dark:bg-gray-800 p-2.5">
                            <Activity class="h-6 w-6 text-gray-600 dark:text-gray-400" />
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                            SSL & Uptime Monitoring Overview
                        </h3>
                    </div>
                    <button class="text-sm font-medium text-gray-600 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 transition-colors px-3 py-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
                        View All
                    </button>
                </div>

                <!-- Enhanced status grid -->
                <div class="grid grid-cols-2 gap-6 h-80">
                    <!-- SSL Status -->
                    <div class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-50 to-slate-100 dark:from-gray-800 dark:to-slate-800 p-6 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-300">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-gray-100/20 dark:bg-gray-700/20 rounded-full -translate-y-16 translate-x-16 group-hover:scale-150 transition-transform duration-500"></div>

                        <div class="relative flex items-center justify-between mb-6">
                            <h4 class="text-lg font-bold text-gray-900 dark:text-gray-100">SSL Certificates</h4>
                            <div class="rounded-xl bg-green-100 dark:bg-green-900/30 p-3 group-hover:scale-110 transition-transform duration-300">
                                <Shield class="h-6 w-6 text-green-600 dark:text-green-400" />
                            </div>
                        </div>

                        <div class="relative space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Valid</span>
                                <div class="flex items-center">
                                    <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 mr-3">
                                        <div class="bg-green-500 h-2.5 rounded-full transition-all duration-500"
                                             :style="{ width: `${props.sslStatistics.total_websites > 0 ? (props.sslStatistics.valid_certificates / props.sslStatistics.total_websites) * 100 : 0}%` }"></div>
                                    </div>
                                    <span class="text-sm font-bold text-gray-900 dark:text-gray-100 min-w-[2rem]">{{ props.sslStatistics.valid_certificates }}</span>
                                </div>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Expiring</span>
                                <div class="flex items-center">
                                    <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 mr-3">
                                        <div class="bg-amber-500 h-2.5 rounded-full transition-all duration-500"
                                             :style="{ width: `${props.sslStatistics.total_websites > 0 ? (props.sslStatistics.expiring_soon / props.sslStatistics.total_websites) * 100 : 0}%` }"></div>
                                    </div>
                                    <span class="text-sm font-bold text-gray-900 dark:text-gray-100 min-w-[2rem]">{{ props.sslStatistics.expiring_soon }}</span>
                                </div>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Expired</span>
                                <div class="flex items-center">
                                    <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 mr-3">
                                        <div class="bg-red-500 h-2.5 rounded-full transition-all duration-500"
                                             :style="{ width: `${props.sslStatistics.total_websites > 0 ? (props.sslStatistics.expired_certificates / props.sslStatistics.total_websites) * 100 : 0}%` }"></div>
                                    </div>
                                    <span class="text-sm font-bold text-gray-900 dark:text-gray-100 min-w-[2rem]">{{ props.sslStatistics.expired_certificates }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Uptime Status -->
                    <div class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-50 to-slate-100 dark:from-gray-800 dark:to-slate-800 p-6 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-300">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-gray-100/20 dark:bg-gray-700/20 rounded-full -translate-y-16 translate-x-16 group-hover:scale-150 transition-transform duration-500"></div>

                        <div class="relative flex items-center justify-between mb-6">
                            <h4 class="text-lg font-bold text-gray-900 dark:text-gray-100">Uptime Monitors</h4>
                            <div class="rounded-xl bg-blue-100 dark:bg-blue-900/30 p-3 group-hover:scale-110 transition-transform duration-300">
                                <Wifi class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                            </div>
                        </div>

                        <div class="relative space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Healthy</span>
                                <div class="flex items-center">
                                    <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 mr-3">
                                        <div class="bg-green-500 h-2.5 rounded-full transition-all duration-500"
                                             :style="{ width: `${props.uptimeStatistics.total_monitors > 0 ? (props.uptimeStatistics.healthy_monitors / props.uptimeStatistics.total_monitors) * 100 : 0}%` }"></div>
                                    </div>
                                    <span class="text-sm font-bold text-gray-900 dark:text-gray-100 min-w-[2rem]">{{ props.uptimeStatistics.healthy_monitors }}</span>
                                </div>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Down</span>
                                <div class="flex items-center">
                                    <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 mr-3">
                                        <div class="bg-red-500 h-2.5 rounded-full transition-all duration-500"
                                             :style="{ width: `${props.uptimeStatistics.total_monitors > 0 ? (props.uptimeStatistics.down_monitors / props.uptimeStatistics.total_monitors) * 100 : 0}%` }"></div>
                                    </div>
                                    <span class="text-sm font-bold text-gray-900 dark:text-gray-100 min-w-[2rem]">{{ props.uptimeStatistics.down_monitors }}</span>
                                </div>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Uptime</span>
                                <div class="flex items-center">
                                    <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 mr-3">
                                        <div class="bg-blue-500 h-2.5 rounded-full transition-all duration-500"
                                             :style="{ width: `${props.uptimeStatistics.uptime_percentage}%` }"></div>
                                    </div>
                                    <span class="text-sm font-bold text-gray-900 dark:text-gray-100 min-w-[2rem]">{{ props.uptimeStatistics.uptime_percentage }}%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="rounded-2xl bg-gradient-to-br from-gray-50 to-slate-100 dark:from-gray-800 dark:to-slate-800 p-6 shadow-xl border border-gray-200 dark:border-gray-700">
                <div class="mb-6 flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="rounded-xl bg-gray-100 dark:bg-gray-800 p-2.5">
                            <Clock class="h-6 w-6 text-gray-600 dark:text-gray-400" />
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                            Recent Activity
                        </h3>
                    </div>
                    <button class="text-sm font-medium text-gray-600 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 transition-colors px-3 py-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
                        View All
                    </button>
                </div>

                <div class="space-y-4">
                    <div
                        v-for="activity in recentActivity"
                        :key="activity.title + activity.time"
                        class="group flex items-start space-x-4 p-3 rounded-xl bg-white/50 dark:bg-white/5 hover:bg-white/80 dark:hover:bg-white/10 transition-all duration-300 border border-white/60 dark:border-white/10"
                    >
                        <div class="flex items-center mt-1 space-x-2">
                            <div
                                class="h-3 w-3 rounded-full"
                                :class="{
                                    'bg-green-500': activity.type === 'success',
                                    'bg-red-500': activity.type === 'error',
                                    'bg-amber-500': activity.type === 'warning'
                                }"
                            />
                            <div class="rounded-lg p-1.5" :class="{
                                'bg-green-100 dark:bg-green-900/30': activity.category === 'ssl',
                                'bg-blue-100 dark:bg-blue-900/30': activity.category === 'uptime'
                            }">
                                <Shield v-if="activity.category === 'ssl'" class="h-4 w-4 text-green-600 dark:text-green-400" />
                                <Wifi v-else class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 group-hover:text-gray-700 dark:group-hover:text-gray-300 transition-colors">
                                {{ activity.title }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5 truncate">
                                {{ activity.description }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-500 mt-1 font-medium">
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
            <div class="rounded-2xl bg-gradient-to-br from-rose-50 via-red-50 to-pink-50 dark:from-rose-900/30 dark:via-red-900/30 dark:to-pink-900/30 p-6 shadow-xl border border-rose-100 dark:border-rose-800">
                <div class="mb-6 flex items-center space-x-3">
                    <div class="rounded-xl bg-gradient-to-br from-red-500 to-pink-600 p-2.5">
                        <AlertTriangle class="h-6 w-6 text-white" />
                    </div>
                    <h3 class="text-xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 dark:from-white dark:to-gray-300 bg-clip-text text-transparent">
                        Critical Alerts
                    </h3>
                </div>

                <div class="space-y-4">
                    <div
                        v-if="criticalAlerts.length === 0"
                        class="text-center py-8"
                    >
                        <div class="rounded-2xl bg-gradient-to-br from-emerald-100 to-green-100 dark:from-emerald-900/50 dark:to-green-900/50 p-6 inline-block">
                            <CheckCircle class="h-12 w-12 mx-auto mb-3 text-emerald-600 dark:text-emerald-400" />
                            <p class="text-lg font-semibold text-emerald-900 dark:text-emerald-100">No critical SSL alerts</p>
                            <p class="text-sm text-emerald-700 dark:text-emerald-300 mt-1">All certificates are healthy</p>
                        </div>
                    </div>

                    <div
                        v-for="alert in criticalAlerts"
                        :key="alert.website_name"
                        class="group flex items-start space-x-4 rounded-xl bg-gradient-to-r from-red-100 via-rose-100 to-pink-100 dark:from-red-900/40 dark:via-rose-900/40 dark:to-pink-900/40 p-4 border border-red-200 dark:border-red-700 hover:shadow-lg transition-all duration-300"
                    >
                        <div class="rounded-lg bg-red-500/10 p-2">
                            <AlertTriangle class="h-5 w-5 text-red-600 dark:text-red-400" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-red-900 dark:text-red-100">
                                {{ alert.type === 'ssl_expired' ? 'Certificate Expired' : 'SSL Alert' }}
                            </p>
                            <p class="text-sm text-red-700 dark:text-red-300 mt-1">
                                {{ alert.message }}
                            </p>
                            <p class="text-xs text-red-600 dark:text-red-400 mt-2 font-medium">
                                Expired: {{ new Date(alert.expires_at).toLocaleDateString() }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enhanced Quick Actions -->
            <div class="rounded-2xl bg-gradient-to-br from-gray-50 via-slate-50 to-zinc-50 dark:from-gray-900/50 dark:via-slate-900/50 dark:to-zinc-900/50 p-6 shadow-xl border border-gray-100 dark:border-gray-800">
                <div class="mb-6 flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="rounded-xl bg-gradient-to-br from-gray-700 to-slate-800 p-2.5">
                            <Zap class="h-6 w-6 text-white" />
                        </div>
                        <h3 class="text-xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 dark:from-white dark:to-gray-300 bg-clip-text text-transparent">
                            Quick Actions
                        </h3>
                    </div>
                    <!-- Smart Transfer Suggestion -->
                    <div v-if="transferSuggestions.should_show_suggestion" class="text-xs text-blue-600 dark:text-blue-400 font-medium">
                        {{ transferSuggestions.personal_websites_count }} sites can be transferred
                    </div>
                </div>

                <!-- Team Transfer Suggestion Banner -->
                <div v-if="transferSuggestions.should_show_suggestion" class="mb-6 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="rounded-lg bg-blue-100 dark:bg-blue-900/30 p-2">
                                <ArrowRightLeft class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-blue-900 dark:text-blue-100">
                                    Transfer {{ transferSuggestions.personal_websites_count }} personal site{{ transferSuggestions.personal_websites_count === 1 ? '' : 's' }} to team
                                </p>
                                <p class="text-xs text-blue-700 dark:text-blue-300">
                                    Collaborate with {{ transferSuggestions.available_teams_count }} team{{ transferSuggestions.available_teams_count === 1 ? '' : 's' }}
                                </p>
                            </div>
                        </div>
                        <Link
                            :href="ssl.websites.index().url + '?team=personal'"
                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 rounded-md transition-colors"
                        >
                            Manage
                        </Link>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <!-- Add Website -->
                    <Link
                        :href="ssl.websites.create().url"
                        class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30 p-4 text-center hover:from-blue-100 hover:to-indigo-200 dark:hover:from-blue-900/50 dark:hover:to-indigo-900/50 transition-all duration-300 border border-blue-200 dark:border-blue-800 hover:shadow-lg hover:scale-[1.02]"
                    >
                        <div class="absolute top-0 right-0 w-16 h-16 bg-blue-500/5 rounded-full -translate-y-8 translate-x-8 group-hover:scale-150 transition-transform duration-500"></div>
                        <div class="relative">
                            <div class="rounded-lg bg-blue-500/10 p-3 inline-block mb-2 group-hover:scale-110 transition-transform duration-300">
                                <Plus class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                            </div>
                            <p class="text-sm font-bold text-blue-900 dark:text-blue-100">Add Website</p>
                        </div>
                    </Link>

                    <!-- Manage Websites -->
                    <Link
                        :href="ssl.websites.index().url"
                        class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-emerald-50 to-green-100 dark:from-emerald-900/30 dark:to-green-900/30 p-4 text-center hover:from-emerald-100 hover:to-green-200 dark:hover:from-emerald-900/50 dark:hover:to-green-900/50 transition-all duration-300 border border-emerald-200 dark:border-emerald-800 hover:shadow-lg hover:scale-[1.02]"
                    >
                        <div class="absolute top-0 right-0 w-16 h-16 bg-emerald-500/5 rounded-full -translate-y-8 translate-x-8 group-hover:scale-150 transition-transform duration-500"></div>
                        <div class="relative">
                            <div class="rounded-lg bg-emerald-500/10 p-3 inline-block mb-2 group-hover:scale-110 transition-transform duration-300">
                                <Eye class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                            </div>
                            <p class="text-sm font-bold text-emerald-900 dark:text-emerald-100">Manage Sites</p>
                        </div>
                    </Link>

                    <!-- Team Transfer (conditional display) -->
                    <Link
                        v-if="transferSuggestions.personal_websites_count > 0"
                        :href="ssl.websites.index().url + '?team=personal'"
                        class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-purple-50 to-violet-100 dark:from-purple-900/30 dark:to-violet-900/30 p-4 text-center hover:from-purple-100 hover:to-violet-200 dark:hover:from-purple-900/50 dark:hover:to-violet-900/50 transition-all duration-300 border border-purple-200 dark:border-purple-800 hover:shadow-lg hover:scale-[1.02]"
                    >
                        <div class="absolute top-0 right-0 w-16 h-16 bg-purple-500/5 rounded-full -translate-y-8 translate-x-8 group-hover:scale-150 transition-transform duration-500"></div>
                        <div class="relative">
                            <div class="rounded-lg bg-purple-500/10 p-3 inline-block mb-2 group-hover:scale-110 transition-transform duration-300">
                                <ArrowRightLeft class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                            </div>
                            <p class="text-sm font-bold text-purple-900 dark:text-purple-100">Transfer Sites</p>
                            <p class="text-xs text-purple-700 dark:text-purple-300">{{ transferSuggestions.personal_websites_count }} personal</p>
                        </div>
                    </Link>

                    <!-- Team Management (always show if no transfers or fallback) -->
                    <Link
                        v-else
                        href="/teams"
                        class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-purple-50 to-violet-100 dark:from-purple-900/30 dark:to-violet-900/30 p-4 text-center hover:from-purple-100 hover:to-violet-200 dark:hover:from-purple-900/50 dark:hover:to-violet-900/50 transition-all duration-300 border border-purple-200 dark:border-purple-800 hover:shadow-lg hover:scale-[1.02]"
                    >
                        <div class="absolute top-0 right-0 w-16 h-16 bg-purple-500/5 rounded-full -translate-y-8 translate-x-8 group-hover:scale-150 transition-transform duration-500"></div>
                        <div class="relative">
                            <div class="rounded-lg bg-purple-500/10 p-3 inline-block mb-2 group-hover:scale-110 transition-transform duration-300">
                                <Users class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                            </div>
                            <p class="text-sm font-bold text-purple-900 dark:text-purple-100">Manage Teams</p>
                        </div>
                    </Link>

                    <!-- Settings/Alert Rules -->
                    <Link
                        href="/settings/alerts"
                        class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-orange-50 to-amber-100 dark:from-orange-900/30 dark:to-amber-900/30 p-4 text-center hover:from-orange-100 hover:to-amber-200 dark:hover:from-orange-900/50 dark:hover:to-amber-900/50 transition-all duration-300 border border-orange-200 dark:border-orange-800 hover:shadow-lg hover:scale-[1.02]"
                    >
                        <div class="absolute top-0 right-0 w-16 h-16 bg-orange-500/5 rounded-full -translate-y-8 translate-x-8 group-hover:scale-150 transition-transform duration-500"></div>
                        <div class="relative">
                            <div class="rounded-lg bg-orange-500/10 p-3 inline-block mb-2 group-hover:scale-110 transition-transform duration-300">
                                <Settings class="h-6 w-6 text-orange-600 dark:text-orange-400" />
                            </div>
                            <p class="text-sm font-bold text-orange-900 dark:text-orange-100">Settings</p>
                        </div>
                    </Link>
                </div>

                <!-- Quick Team Access (when available) -->
                <div v-if="transferSuggestions.quick_transfer_teams.length > 0" class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Quick Team Access</h4>
                        <Link
                            href="/teams"
                            class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium"
                        >
                            View All
                        </Link>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Link
                            v-for="team in transferSuggestions.quick_transfer_teams"
                            :key="team.id"
                            :href="`/teams/${team.id}`"
                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                        >
                            <Users class="h-3 w-3 mr-1" />
                            {{ team.name }}
                            <span class="ml-1 text-gray-500 dark:text-gray-400">({{ team.member_count }})</span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

    </DashboardLayout>
</template>
