<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ssl from '@/routes/ssl';
import AlertDashboard from '@/components/alerts/AlertDashboard.vue';
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
  Plus,
  Search,
  X,
  XCircle,
  ExternalLink,
  RefreshCw,
  Edit,
  Bell,
  Upload
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
  website_id?: number;
  website_name: string;
  status: string;
  checked_at: string;
  time_ago: string;
  failure_reason?: string;
}

interface UptimeActivity {
  id: number;
  website_id?: number;
  website_name: string;
  status: string;
  checked_at: string;
  time_ago: string;
  response_time: number;
  failure_reason?: string;
  content_failure_reason?: string;
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

interface ExpirationTimelineItem {
  website_id?: number;
  website_name: string;
  expires_at: string;
  days_until_expiry: number;
}

interface ExpirationTimeline {
  expiring_7_days: ExpirationTimelineItem[];
  expiring_30_days: ExpirationTimelineItem[];
  expiring_90_days: ExpirationTimelineItem[];
}

interface Props {
  sslStatistics: SslStatistics;
  uptimeStatistics: UptimeStatistics;
  recentSslActivity: SslActivity[];
  recentUptimeActivity: UptimeActivity[];
  criticalAlerts: SslAlert[];
  transferSuggestions: TransferSuggestions;
  expirationTimeline: ExpirationTimeline;
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
    color: 'text-foreground dark:text-muted-foreground',
    bgGradient: 'bg-muted dark:bg-card',
    iconBg: 'bg-muted dark:bg-muted',
    iconColor: 'text-foreground dark:text-muted-foreground'
  },
  {
    title: 'SSL Certificates',
    value: props.sslStatistics.valid_certificates.toString(),
    change: props.sslStatistics.total_websites > 0
      ? `${Math.round((props.sslStatistics.valid_certificates / props.sslStatistics.total_websites) * 100)}% valid`
      : '0% valid',
    trend: 'up',
    icon: Shield,
    color: 'text-foreground dark:text-muted-foreground',
    bgGradient: 'bg-muted dark:bg-card',
    iconBg: 'bg-green-100 dark:bg-green-900/30',
    iconColor: 'text-green-600 dark:text-green-400'
  },
  {
    title: 'Uptime Status',
    value: props.uptimeStatistics.uptime_percentage.toString() + '%',
    change: `${props.uptimeStatistics.healthy_monitors}/${props.uptimeStatistics.total_monitors} healthy`,
    trend: props.uptimeStatistics.uptime_percentage >= 95 ? 'up' : 'down',
    icon: Zap,
    color: 'text-foreground dark:text-muted-foreground',
    bgGradient: 'bg-muted dark:bg-card',
    iconBg: props.uptimeStatistics.uptime_percentage >= 95 ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30',
    iconColor: props.uptimeStatistics.uptime_percentage >= 95 ? 'text-green-600 dark:text-green-400' : 'text-destructive dark:text-red-400'
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
    color: 'text-foreground dark:text-muted-foreground',
    bgGradient: 'bg-muted dark:bg-card',
    iconBg: 'bg-blue-100 dark:bg-blue-900/30',
    iconColor: 'text-primary dark:text-blue-400'
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
    .sort((a, b) => a.time.localeCompare(b.time));
});

// Display only 4 items on dashboard
const dashboardActivity = computed(() => recentActivity.value.slice(0, 4));

// Modal state
const showActivityModal = ref(false);
const searchQuery = ref('');
const statusFilter = ref<'all' | 'success' | 'error' | 'warning'>('all');

// Filtered activity for modal
const filteredActivity = computed(() => {
  let filtered = recentActivity.value;

  // Apply search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(activity =>
      activity.title.toLowerCase().includes(query) ||
      activity.description.toLowerCase().includes(query)
    );
  }

  // Apply status filter
  if (statusFilter.value !== 'all') {
    filtered = filtered.filter(activity => activity.type === statusFilter.value);
  }

  return filtered;
});

// Use real critical alerts data
const criticalAlerts = computed(() => props.criticalAlerts);

// Detect failed checks from recent activity
const failedChecks = computed(() => {
  const failures = [];

  // Check recent SSL activity for failures
  for (const activity of props.recentSslActivity) {
    if (activity.status === 'expired' || activity.status === 'invalid') {
      failures.push({
        type: 'ssl',
        website_id: activity.website_id,
        monitor_id: activity.id,
        website_name: activity.website_name,
        status: activity.status,
        message: activity.status === 'expired' ? 'SSL certificate has expired' : 'SSL certificate is invalid',
        failure_reason: activity.failure_reason,
        time_ago: activity.time_ago,
        checked_at: activity.checked_at
      });
    }
  }

  // Check recent uptime activity for failures
  for (const activity of props.recentUptimeActivity) {
    if (activity.status === 'down' || activity.status === 'content_mismatch') {
      // Determine the failure reason to display
      const failureReason = activity.content_failure_reason || activity.failure_reason;
      const message = activity.status === 'down' ? 'Website is down' : 'Content validation failed';

      failures.push({
        type: 'uptime',
        website_id: activity.website_id,
        monitor_id: activity.id,
        website_name: activity.website_name,
        status: activity.status,
        message,
        failure_reason: failureReason,
        time_ago: activity.time_ago,
        checked_at: activity.checked_at,
        response_time: activity.response_time
      });
    }
  }

  return failures;
});

// Show failure banner if there are any failures
const showFailureBanner = computed(() => failedChecks.value.length > 0);
const dismissedFailures = ref(false);

// Track which website is being checked
const checkingWebsiteId = ref<number | null>(null);

// Track test alerts sending state
const isTestingAlerts = ref(false);

// Handle immediate check
const handleCheckNow = (websiteId: number) => {
  checkingWebsiteId.value = websiteId;
  router.post(`/ssl/websites/${websiteId}/check`, {}, {
    preserveScroll: true,
    onFinish: () => {
      checkingWebsiteId.value = null;
    }
  });
};

// Handle test alerts
const handleTestAlerts = () => {
  isTestingAlerts.value = true;
  router.post('/alerts/test-all', {}, {
    preserveScroll: true,
    onFinish: () => {
      isTestingAlerts.value = false;
    }
  });
};

// AlertDashboard event handlers
const handleAlertAcknowledged = (alert: any) => {
  console.log('Alert acknowledged:', alert);
  // In production, this would send an API request to acknowledge the alert
};

const handleAlertDismissed = (alert: any) => {
  console.log('Alert dismissed:', alert);
  // In production, this would send an API request to dismiss the alert
};

const handleCreateRuleFromAlert = (alert: any) => {
  console.log('Creating rule from alert:', alert);
  // This would navigate to the alert settings with pre-populated data
  router.visit('/settings/alerts');
};
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
                class="group relative overflow-hidden rounded-2xl p-6 shadow-lg border border-border dark:border-border transition-all duration-300 hover:shadow-xl cursor-pointer"
            >
                <!-- Subtle hover effect -->
                <div class="absolute inset-0 bg-muted/50 dark:bg-muted/50 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

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

        <!-- Failure Alert Cards (Individual Cards) -->
        <div v-if="showFailureBanner && !dismissedFailures" class="mb-8">
            <!-- Header -->
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-2">
                    <div class="rounded-lg bg-red-500 p-2">
                        <AlertTriangle class="h-5 w-5 text-white" />
                    </div>
                    <h3 class="text-lg font-bold text-foreground dark:text-foreground">
                        {{ failedChecks.length }} Check{{ failedChecks.length === 1 ? '' : 's' }} Failed
                    </h3>
                </div>
                <div class="flex items-center space-x-2">
                    <Link
                        :href="ssl.websites.index().url + '?filter=critical'"
                        class="text-sm font-medium text-destructive hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition-colors"
                    >
                        View All
                    </Link>
                    <button
                        @click="dismissedFailures = true"
                        class="rounded-lg p-1.5 text-muted-foreground hover:bg-muted dark:hover:bg-gray-800 hover:text-foreground transition-colors"
                        title="Dismiss all"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <!-- Individual Failure Cards -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div
                    v-for="(failure, index) in failedChecks.slice(0, 4)"
                    :key="index"
                    class="group relative rounded-lg border-2 shadow-lg hover:shadow-xl transition-all duration-200"
                    :class="{
                        'border-red-400 dark:border-red-700 bg-red-50/50 dark:bg-red-950/30': failure.type === 'ssl',
                        'border-red-400 dark:border-red-700 bg-red-50/50 dark:bg-red-950/30': failure.type === 'uptime'
                    }"
                >
                    <!-- Status Bar - Thicker and more prominent -->
                    <div class="absolute top-0 left-0 right-0 h-2 rounded-t-lg bg-gradient-to-r from-red-600 to-red-500"></div>

                    <div class="p-4 pt-5">
                        <!-- Header with Icon and Actions -->
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-start space-x-3 flex-1 min-w-0">
                                <div class="rounded-lg p-2 flex-shrink-0 bg-red-100 dark:bg-red-900/30 ring-2 ring-red-200 dark:ring-red-800">
                                    <Shield v-if="failure.type === 'ssl'" class="h-5 w-5 text-red-700 dark:text-red-400" />
                                    <Wifi v-else class="h-5 w-5 text-red-700 dark:text-red-400" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-bold text-red-900 dark:text-red-100 truncate">
                                        {{ failure.website_name }}
                                    </h4>
                                    <p class="text-xs text-red-700 dark:text-red-400 mt-0.5 font-medium">
                                        {{ failure.type === 'ssl' ? 'SSL Certificate' : 'Uptime Monitor' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Error Details -->
                        <div class="mb-3 space-y-2">
                            <p class="text-sm font-bold text-red-800 dark:text-red-200">
                                {{ failure.message }}
                            </p>
                            <p v-if="failure.failure_reason" class="text-xs font-mono bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-200 px-2 py-1.5 rounded border-2 border-red-300 dark:border-red-700">
                                {{ failure.failure_reason }}
                            </p>
                            <p class="text-xs text-destructive dark:text-red-400 font-medium">
                                <Clock class="h-3 w-3 inline mr-1" />
                                {{ failure.time_ago }}
                            </p>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center space-x-2 pt-3 border-t border-border dark:border-border">
                            <Link
                                v-if="failure.website_id"
                                :href="ssl.websites.edit(failure.website_id).url"
                                class="flex-1 inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20 dark:hover:bg-blue-900/30 dark:text-blue-400 rounded-md transition-colors"
                            >
                                <Edit class="h-4 w-4 mr-1.5" />
                                Edit
                            </Link>
                            <button
                                v-if="failure.website_id"
                                @click="handleCheckNow(failure.website_id)"
                                :disabled="checkingWebsiteId === failure.website_id"
                                class="flex-1 inline-flex items-center justify-center px-3 py-2 text-sm font-medium rounded-md transition-colors"
                                :class="checkingWebsiteId === failure.website_id
                                    ? 'text-muted-foreground bg-muted dark:bg-card cursor-not-allowed'
                                    : 'text-green-700 bg-green-50 hover:bg-green-100 dark:bg-green-900/20 dark:hover:bg-green-900/30 dark:text-green-400'"
                            >
                                <RefreshCw
                                    class="h-4 w-4 mr-1.5"
                                    :class="{ 'animate-spin': checkingWebsiteId === failure.website_id }"
                                />
                                {{ checkingWebsiteId === failure.website_id ? 'Checking...' : 'Check Now' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Show More Link -->
            <div v-if="failedChecks.length > 4" class="mt-4 text-center">
                <Link
                    :href="ssl.websites.index().url + '?filter=critical'"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-red-700 bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/30 dark:text-red-400 rounded-lg transition-colors"
                >
                    <AlertTriangle class="h-4 w-4 mr-2" />
                    View {{ failedChecks.length - 4 }} More Issue{{ failedChecks.length - 4 === 1 ? '' : 's' }}
                </Link>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-10">
            <!-- Quick Actions -->
            <div class="lg:col-span-7 rounded-2xl bg-muted/50 dark:bg-card/50 p-6 shadow-xl border border-border dark:border-border">
                <div class="mb-6 flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="rounded-xl bg-slate-700 p-2.5">
                            <Zap class="h-6 w-6 text-white" />
                        </div>
                        <h3 class="text-xl font-bold bg-gradient-to-r from-foreground to-foreground dark:from-white dark:to-white bg-clip-text text-transparent">
                            Quick Actions
                        </h3>
                    </div>
                    <!-- Smart Transfer Suggestion -->
                    <div v-if="transferSuggestions.should_show_suggestion" class="text-xs text-primary dark:text-blue-400 font-medium">
                        {{ transferSuggestions.personal_websites_count }} sites can be transferred
                    </div>
                </div>

                <!-- Team Transfer Suggestion Banner -->
                <div v-if="transferSuggestions.should_show_suggestion" class="mb-6 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="rounded-lg bg-blue-100 dark:bg-blue-900/30 p-2">
                                <ArrowRightLeft class="h-4 w-4 text-primary dark:text-blue-400" />
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

                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <!-- Add Website -->
                    <Link
                        :href="ssl.websites.create().url"
                        class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30 p-4 text-center hover:from-blue-100 hover:to-indigo-200 dark:hover:from-blue-900/50 dark:hover:to-indigo-900/50 transition-all duration-300 border border-blue-200 dark:border-blue-800 hover:shadow-lg hover:scale-[1.02]"
                    >
                        <div class="absolute top-0 right-0 w-16 h-16 bg-blue-500/5 rounded-full -translate-y-8 translate-x-8 group-hover:scale-150 transition-transform duration-500"></div>
                        <div class="relative">
                            <div class="rounded-lg bg-blue-500/10 p-3 inline-block mb-2 group-hover:scale-110 transition-transform duration-300">
                                <Plus class="h-6 w-6 text-primary dark:text-blue-400" />
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
                        href="/settings/team"
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

                    <!-- Bulk Check All -->
                    <Link
                        :href="ssl.websites.index().url + '?action=bulk-check'"
                        method="post"
                        as="button"
                        class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-teal-50 to-cyan-100 dark:from-teal-900/30 dark:to-cyan-900/30 p-4 text-center hover:from-teal-100 hover:to-cyan-200 dark:hover:from-teal-900/50 dark:hover:to-cyan-900/50 transition-all duration-300 border border-teal-200 dark:border-teal-800 hover:shadow-lg hover:scale-[1.02]"
                    >
                        <div class="absolute top-0 right-0 w-16 h-16 bg-teal-500/5 rounded-full -translate-y-8 translate-x-8 group-hover:scale-150 transition-transform duration-500"></div>
                        <div class="relative">
                            <div class="rounded-lg bg-teal-500/10 p-3 inline-block mb-2 group-hover:scale-110 transition-transform duration-300">
                                <RefreshCw class="h-6 w-6 text-teal-600 dark:text-teal-400" />
                            </div>
                            <p class="text-sm font-bold text-teal-900 dark:text-teal-100">Bulk Check All</p>
                        </div>
                    </Link>

                    <!-- View Reports -->
                    <Link
                        href="/analytics"
                        class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-pink-50 to-rose-100 dark:from-pink-900/30 dark:to-rose-900/30 p-4 text-center hover:from-pink-100 hover:to-rose-200 dark:hover:from-pink-900/50 dark:hover:to-rose-900/50 transition-all duration-300 border border-pink-200 dark:border-pink-800 hover:shadow-lg hover:scale-[1.02]"
                    >
                        <div class="absolute top-0 right-0 w-16 h-16 bg-pink-500/5 rounded-full -translate-y-8 translate-x-8 group-hover:scale-150 transition-transform duration-500"></div>
                        <div class="relative">
                            <div class="rounded-lg bg-pink-500/10 p-3 inline-block mb-2 group-hover:scale-110 transition-transform duration-300">
                                <BarChart3 class="h-6 w-6 text-pink-600 dark:text-pink-400" />
                            </div>
                            <p class="text-sm font-bold text-pink-900 dark:text-pink-100">View Reports</p>
                        </div>
                    </Link>

                    <!-- Test Alerts -->
                    <button
                        @click="handleTestAlerts"
                        :disabled="isTestingAlerts"
                        class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-yellow-50 to-amber-100 dark:from-yellow-900/30 dark:to-amber-900/30 p-4 text-center hover:from-yellow-100 hover:to-amber-200 dark:hover:from-yellow-900/50 dark:hover:to-amber-900/50 transition-all duration-300 border border-yellow-200 dark:border-yellow-800 hover:shadow-lg hover:scale-[1.02] disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
                    >
                        <div class="absolute top-0 right-0 w-16 h-16 bg-yellow-500/5 rounded-full -translate-y-8 translate-x-8 group-hover:scale-150 transition-transform duration-500"></div>
                        <div class="relative">
                            <div class="rounded-lg bg-yellow-500/10 p-3 inline-block mb-2 group-hover:scale-110 transition-transform duration-300">
                                <Bell class="h-6 w-6 text-yellow-600 dark:text-yellow-400" :class="{ 'animate-pulse': isTestingAlerts }" />
                            </div>
                            <p class="text-sm font-bold text-yellow-900 dark:text-yellow-100">
                                {{ isTestingAlerts ? 'Sending...' : 'Test Alerts' }}
                            </p>
                        </div>
                    </button>

                    <!-- Import Sites -->
                    <Link
                        href="/ssl/websites/import"
                        class="group relative overflow-hidden rounded-xl bg-muted dark:bg-card p-4 text-center hover:bg-muted dark:hover:bg-card transition-all duration-300 border border-border dark:border-border hover:shadow-lg hover:scale-[1.02]"
                    >
                        <div class="absolute top-0 right-0 w-16 h-16 bg-muted/20 rounded-full -translate-y-8 translate-x-8 group-hover:scale-150 transition-transform duration-500"></div>
                        <div class="relative">
                            <div class="rounded-lg bg-muted p-3 inline-block mb-2 group-hover:scale-110 transition-transform duration-300">
                                <Upload class="h-6 w-6 text-foreground dark:text-muted-foreground" />
                            </div>
                            <p class="text-sm font-bold text-foreground dark:text-foreground">Import Sites</p>
                        </div>
                    </Link>
                </div>

                <!-- Quick Team Access (when available) -->
                <div v-if="transferSuggestions.quick_transfer_teams.length > 0" class="mt-6 pt-4 border-t border-border dark:border-border">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-sm font-semibold text-foreground dark:text-muted-foreground">Quick Team Access</h4>
                        <Link
                            href="/settings/team"
                            class="text-xs text-foreground hover:text-foreground dark:text-muted-foreground dark:hover:text-gray-300 transition-colors"
                        >
                            View All
                        </Link>
                    </div>
                    <div class="space-y-2">
                        <Link
                            v-for="team in transferSuggestions.quick_transfer_teams"
                            :key="team.id"
                            :href="`/settings/team/${team.id}`"
                            class="flex items-center justify-between p-2 rounded-lg bg-muted hover:bg-muted dark:bg-gray-800/50 dark:hover:bg-gray-800 transition-colors group"
                        >
                            <div class="flex items-center space-x-2">
                                <div class="rounded-md bg-muted dark:bg-muted p-1.5">
                                    <Users class="h-3.5 w-3.5 text-foreground dark:text-muted-foreground" />
                                </div>
                                <span class="text-sm font-medium text-foreground dark:text-foreground group-hover:text-foreground dark:group-hover:text-gray-300">{{ team.name }}</span>
                            </div>
                            <span class="text-xs text-muted-foreground dark:text-muted-foreground">({{ team.member_count }})</span>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="lg:col-span-3 rounded-2xl bg-muted dark:bg-card p-5 shadow-xl border border-border dark:border-border">
                <div class="mb-4 flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <div class="rounded-lg bg-muted dark:bg-card p-2">
                            <Clock class="h-5 w-5 text-foreground dark:text-muted-foreground" />
                        </div>
                        <h3 class="text-lg font-bold text-foreground dark:text-foreground">
                            Recent Activity
                        </h3>
                    </div>
                    <button
                        @click="showActivityModal = true"
                        class="text-sm font-medium text-foreground hover:text-foreground dark:text-muted-foreground dark:hover:text-gray-300 transition-colors px-3 py-1.5 rounded-lg hover:bg-muted dark:hover:bg-gray-800"
                    >
                        View All
                    </button>
                </div>

                <div class="space-y-4">
                    <div
                        v-for="activity in dashboardActivity"
                        :key="activity.title + activity.time"
                        class="group flex items-start space-x-4 p-3 rounded-xl bg-background/50 dark:bg-background/5 hover:bg-background/80 dark:hover:bg-background/10 transition-all duration-300 border border-white/60 dark:border-white/10"
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
                                <Wifi v-else class="h-4 w-4 text-primary dark:text-blue-400" />
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-foreground dark:text-foreground group-hover:text-foreground dark:group-hover:text-gray-300 transition-colors">
                                {{ activity.title }}
                            </p>
                            <p class="text-sm text-foreground dark:text-muted-foreground mt-0.5 truncate">
                                {{ activity.description }}
                            </p>
                            <p class="text-xs text-muted-foreground dark:text-muted-foreground mt-1 font-medium">
                                {{ activity.time }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Certificate Expiration Timeline -->
        <div class="mt-6">
            <div class="rounded-2xl bg-gradient-to-br from-amber-50 via-orange-50 to-yellow-50 dark:from-amber-900/20 dark:via-orange-900/20 dark:to-yellow-900/20 p-6 shadow-xl border border-amber-100 dark:border-amber-800">
                <div class="mb-6 flex items-center space-x-3">
                    <div class="rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 p-2.5">
                        <Clock class="h-6 w-6 text-white" />
                    </div>
                    <h3 class="text-xl font-bold bg-gradient-to-r from-foreground to-foreground dark:from-white dark:to-white bg-clip-text text-transparent">
                        Certificate Expiration Timeline
                    </h3>
                </div>

                <div v-if="props.expirationTimeline.expiring_7_days.length === 0 && props.expirationTimeline.expiring_30_days.length === 0 && props.expirationTimeline.expiring_90_days.length === 0" class="text-center py-8">
                    <div class="rounded-2xl bg-gradient-to-br from-emerald-100 to-green-100 dark:from-emerald-900/50 dark:to-green-900/50 p-6 inline-block">
                        <CheckCircle class="h-12 w-12 mx-auto mb-3 text-emerald-600 dark:text-emerald-400" />
                        <p class="text-lg font-semibold text-emerald-900 dark:text-emerald-100">All certificates are healthy</p>
                        <p class="text-sm text-emerald-700 dark:text-emerald-300 mt-1">No certificates expiring in the next 90 days</p>
                    </div>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Critical: Expiring in 7 days -->
                    <div class="rounded-xl bg-background/60 dark:bg-gray-800/60 p-5 border-2 border-red-200 dark:border-red-800">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-2">
                                <div class="rounded-lg bg-red-100 dark:bg-red-900/30 p-2">
                                    <AlertTriangle class="h-5 w-5 text-destructive dark:text-red-400" />
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-red-900 dark:text-red-100">Critical</h4>
                                    <p class="text-xs text-red-700 dark:text-red-300">Expiring in 7 days</p>
                                </div>
                            </div>
                            <div class="text-2xl font-bold text-destructive dark:text-red-400">
                                {{ props.expirationTimeline.expiring_7_days.length }}
                            </div>
                        </div>
                        <div v-if="props.expirationTimeline.expiring_7_days.length > 0" class="space-y-2">
                            <Link
                                v-for="cert in props.expirationTimeline.expiring_7_days.slice(0, 3)"
                                :key="cert.website_name"
                                :href="cert.website_id ? ssl.websites.edit(cert.website_id).url : '#'"
                                class="block p-3 rounded-lg bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors border border-red-200 dark:border-red-800"
                            >
                                <p class="text-sm font-semibold text-red-900 dark:text-red-100 truncate">
                                    {{ cert.website_name }}
                                </p>
                                <p class="text-xs text-red-700 dark:text-red-300 mt-1">
                                    {{ cert.days_until_expiry }} day{{ cert.days_until_expiry === 1 ? '' : 's' }} remaining
                                </p>
                            </Link>
                            <p v-if="props.expirationTimeline.expiring_7_days.length > 3" class="text-xs text-destructive dark:text-red-400 text-center pt-2">
                                +{{ props.expirationTimeline.expiring_7_days.length - 3 }} more
                            </p>
                        </div>
                        <p v-else class="text-sm text-muted-foreground dark:text-muted-foreground text-center py-4">
                            No certificates
                        </p>
                    </div>

                    <!-- Warning: Expiring in 30 days -->
                    <div class="rounded-xl bg-background/60 dark:bg-gray-800/60 p-5 border-2 border-amber-200 dark:border-amber-800">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-2">
                                <div class="rounded-lg bg-amber-100 dark:bg-amber-900/30 p-2">
                                    <Clock class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-amber-900 dark:text-amber-100">Warning</h4>
                                    <p class="text-xs text-amber-700 dark:text-amber-300">Expiring in 30 days</p>
                                </div>
                            </div>
                            <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">
                                {{ props.expirationTimeline.expiring_30_days.length }}
                            </div>
                        </div>
                        <div v-if="props.expirationTimeline.expiring_30_days.length > 0" class="space-y-2">
                            <Link
                                v-for="cert in props.expirationTimeline.expiring_30_days.slice(0, 3)"
                                :key="cert.website_name"
                                :href="cert.website_id ? ssl.websites.edit(cert.website_id).url : '#'"
                                class="block p-3 rounded-lg bg-amber-50 dark:bg-amber-900/20 hover:bg-amber-100 dark:hover:bg-amber-900/30 transition-colors border border-amber-200 dark:border-amber-800"
                            >
                                <p class="text-sm font-semibold text-amber-900 dark:text-amber-100 truncate">
                                    {{ cert.website_name }}
                                </p>
                                <p class="text-xs text-amber-700 dark:text-amber-300 mt-1">
                                    {{ cert.days_until_expiry }} days remaining
                                </p>
                            </Link>
                            <p v-if="props.expirationTimeline.expiring_30_days.length > 3" class="text-xs text-amber-600 dark:text-amber-400 text-center pt-2">
                                +{{ props.expirationTimeline.expiring_30_days.length - 3 }} more
                            </p>
                        </div>
                        <p v-else class="text-sm text-muted-foreground dark:text-muted-foreground text-center py-4">
                            No certificates
                        </p>
                    </div>

                    <!-- Info: Expiring in 90 days -->
                    <div class="rounded-xl bg-background/60 dark:bg-gray-800/60 p-5 border-2 border-blue-200 dark:border-blue-800">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-2">
                                <div class="rounded-lg bg-blue-100 dark:bg-blue-900/30 p-2">
                                    <Shield class="h-5 w-5 text-primary dark:text-blue-400" />
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-blue-900 dark:text-blue-100">Info</h4>
                                    <p class="text-xs text-blue-700 dark:text-blue-300">Expiring in 90 days</p>
                                </div>
                            </div>
                            <div class="text-2xl font-bold text-primary dark:text-blue-400">
                                {{ props.expirationTimeline.expiring_90_days.length }}
                            </div>
                        </div>
                        <div v-if="props.expirationTimeline.expiring_90_days.length > 0" class="space-y-2">
                            <Link
                                v-for="cert in props.expirationTimeline.expiring_90_days.slice(0, 3)"
                                :key="cert.website_name"
                                :href="cert.website_id ? ssl.websites.edit(cert.website_id).url : '#'"
                                class="block p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors border border-blue-200 dark:border-blue-800"
                            >
                                <p class="text-sm font-semibold text-blue-900 dark:text-blue-100 truncate">
                                    {{ cert.website_name }}
                                </p>
                                <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">
                                    {{ cert.days_until_expiry }} days remaining
                                </p>
                            </Link>
                            <p v-if="props.expirationTimeline.expiring_90_days.length > 3" class="text-xs text-primary dark:text-blue-400 text-center pt-2">
                                +{{ props.expirationTimeline.expiring_90_days.length - 3 }} more
                            </p>
                        </div>
                        <p v-else class="text-sm text-muted-foreground dark:text-muted-foreground text-center py-4">
                            No certificates
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Real-time Alert Feed -->
        <div class="mt-6">
            <AlertDashboard
                @alert-acknowledged="handleAlertAcknowledged"
                @alert-dismissed="handleAlertDismissed"
                @create-rule="handleCreateRuleFromAlert"
            />
        </div>

        <!-- Activity Modal -->
        <Teleport to="body">
            <div
                v-if="showActivityModal"
                class="fixed inset-0 z-50 overflow-y-auto"
                aria-labelledby="modal-title"
                role="dialog"
                aria-modal="true"
            >
                <!-- Backdrop -->
                <div
                    class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"
                    @click="showActivityModal = false"
                ></div>

                <!-- Modal -->
                <div class="flex min-h-full items-center justify-center p-4">
                    <div
                        class="relative w-full max-w-3xl rounded-2xl bg-background dark:bg-gray-900 shadow-2xl border border-border dark:border-border max-h-[80vh] flex flex-col"
                        @click.stop
                    >
                        <!-- Header -->
                        <div class="flex items-center justify-between border-b border-border dark:border-border p-6">
                            <div class="flex items-center space-x-3">
                                <div class="rounded-xl bg-muted dark:bg-card p-2.5">
                                    <Activity class="h-6 w-6 text-foreground dark:text-muted-foreground" />
                                </div>
                                <h3 class="text-xl font-bold text-foreground dark:text-foreground">
                                    All Activity
                                </h3>
                            </div>
                            <button
                                @click="showActivityModal = false"
                                class="rounded-lg p-2 text-muted-foreground hover:bg-muted dark:hover:bg-gray-800 hover:text-muted-foreground transition-colors"
                            >
                                <X class="h-5 w-5" />
                            </button>
                        </div>

                        <!-- Search and Filters -->
                        <div class="border-b border-border dark:border-border p-6 space-y-4">
                            <!-- Search -->
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-muted-foreground" />
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search activity..."
                                    class="w-full pl-10 pr-4 py-2.5 bg-muted dark:bg-card border border-border dark:border-border rounded-lg text-sm text-foreground dark:text-foreground placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                            </div>

                            <!-- Status Filter -->
                            <div class="flex items-center space-x-2">
                                <button
                                    v-for="filter in [
                                        { value: 'all', label: 'All' },
                                        { value: 'success', label: 'Success' },
                                        { value: 'error', label: 'Errors' },
                                        { value: 'warning', label: 'Warnings' }
                                    ]"
                                    :key="filter.value"
                                    @click="statusFilter = filter.value as any"
                                    class="px-3 py-1.5 text-sm font-medium rounded-lg transition-colors"
                                    :class="{
                                        'bg-blue-500 text-white': statusFilter === filter.value,
                                        'bg-muted dark:bg-card text-foreground dark:text-muted-foreground hover:bg-muted dark:hover:bg-gray-700': statusFilter !== filter.value
                                    }"
                                >
                                    {{ filter.label }}
                                </button>
                            </div>
                        </div>

                        <!-- Activity List -->
                        <div class="flex-1 overflow-y-auto p-6">
                            <div v-if="filteredActivity.length === 0" class="text-center py-12">
                                <Activity class="h-12 w-12 mx-auto mb-3 text-muted-foreground" />
                                <p class="text-muted-foreground dark:text-muted-foreground">No activity found</p>
                            </div>

                            <div v-else class="space-y-3">
                                <div
                                    v-for="(activity, index) in filteredActivity"
                                    :key="`${activity.category}-${activity.title}-${activity.time}-${index}`"
                                    class="group flex items-start space-x-4 p-4 rounded-xl bg-muted dark:bg-gray-800/50 hover:bg-muted dark:hover:bg-gray-800 transition-all duration-200 border border-border dark:border-border"
                                >
                                    <div class="flex items-center mt-1 space-x-2">
                                        <div
                                            class="h-3 w-3 rounded-full flex-shrink-0"
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
                                            <Wifi v-else class="h-4 w-4 text-primary dark:text-blue-400" />
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-foreground dark:text-foreground">
                                            {{ activity.title }}
                                        </p>
                                        <p class="text-sm text-foreground dark:text-muted-foreground mt-0.5">
                                            {{ activity.description }}
                                        </p>
                                        <p class="text-xs text-muted-foreground dark:text-muted-foreground mt-1 font-medium">
                                            {{ activity.time }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="border-t border-border dark:border-border p-4 text-center">
                            <p class="text-sm text-muted-foreground dark:text-muted-foreground">
                                Showing {{ filteredActivity.length }} of {{ recentActivity.length }} activities
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

    </DashboardLayout>
</template>
