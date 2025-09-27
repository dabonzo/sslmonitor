<template>
  <div class="space-y-6">
    <!-- Alert Statistics Overview -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
      <Card class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-900/30 border-red-200 dark:border-red-800">
        <CardContent class="p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-red-600 dark:text-red-400">Critical Alerts</p>
              <p class="text-3xl font-bold text-red-700 dark:text-red-300">{{ alertStats.critical }}</p>
              <p class="text-xs text-red-500 dark:text-red-400 mt-1">Last 24 hours</p>
            </div>
            <div class="rounded-lg bg-red-500/10 p-3">
              <AlertTriangle class="h-8 w-8 text-red-600 dark:text-red-400" />
            </div>
          </div>
        </CardContent>
      </Card>

      <Card class="bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-900/30 border-orange-200 dark:border-orange-800">
        <CardContent class="p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-orange-600 dark:text-orange-400">High Priority</p>
              <p class="text-3xl font-bold text-orange-700 dark:text-orange-300">{{ alertStats.high }}</p>
              <p class="text-xs text-orange-500 dark:text-orange-400 mt-1">Require attention</p>
            </div>
            <div class="rounded-lg bg-orange-500/10 p-3">
              <Zap class="h-8 w-8 text-orange-600 dark:text-orange-400" />
            </div>
          </div>
        </CardContent>
      </Card>

      <Card class="bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900/20 dark:to-yellow-900/30 border-yellow-200 dark:border-yellow-800">
        <CardContent class="p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-yellow-600 dark:text-yellow-400">Warnings</p>
              <p class="text-3xl font-bold text-yellow-700 dark:text-yellow-300">{{ alertStats.warning }}</p>
              <p class="text-xs text-yellow-500 dark:text-yellow-400 mt-1">Monitor closely</p>
            </div>
            <div class="rounded-lg bg-yellow-500/10 p-3">
              <AlertCircle class="h-8 w-8 text-yellow-600 dark:text-yellow-400" />
            </div>
          </div>
        </CardContent>
      </Card>

      <Card class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-900/30 border-green-200 dark:border-green-800">
        <CardContent class="p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-green-600 dark:text-green-400">Healthy</p>
              <p class="text-3xl font-bold text-green-700 dark:text-green-300">{{ alertStats.healthy }}</p>
              <p class="text-xs text-green-500 dark:text-green-400 mt-1">No issues detected</p>
            </div>
            <div class="rounded-lg bg-green-500/10 p-3">
              <CheckCircle class="h-8 w-8 text-green-600 dark:text-green-400" />
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Real-time Alert Feed -->
    <Card>
      <CardHeader>
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <div class="rounded-lg bg-blue-100 dark:bg-blue-900/30 p-2">
              <Activity class="h-5 w-5 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
              <CardTitle class="text-xl font-bold text-gray-900 dark:text-gray-100">
                Real-time Alert Feed
              </CardTitle>
              <CardDescription>
                Live updates from your monitoring systems
              </CardDescription>
            </div>
          </div>
          <div class="flex items-center space-x-2">
            <div class="flex items-center space-x-1">
              <div class="h-2 w-2 bg-green-500 rounded-full animate-pulse"></div>
              <span class="text-sm text-gray-600 dark:text-gray-400">Live</span>
            </div>
            <Button @click="clearAllAlerts" variant="outline" size="sm">
              <Trash2 class="h-4 w-4 mr-2" />
              Clear All
            </Button>
          </div>
        </div>
      </CardHeader>

      <CardContent>
        <!-- Alert Filters -->
        <div class="flex flex-wrap items-center gap-3 mb-6">
          <div class="flex items-center space-x-2">
            <Filter class="h-4 w-4 text-gray-600 dark:text-gray-400" />
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Filter by:</span>
          </div>
          <Button
            v-for="filter in alertFilters"
            :key="filter.key"
            @click="activeFilter = filter.key"
            :variant="activeFilter === filter.key ? 'default' : 'outline'"
            size="sm"
            class="flex items-center space-x-1"
          >
            <component :is="filter.icon" class="h-3 w-3" />
            <span>{{ filter.label }}</span>
            <Badge v-if="filter.count > 0" variant="secondary" class="ml-1 text-xs">
              {{ filter.count }}
            </Badge>
          </Button>
        </div>

        <!-- Alert List -->
        <div class="space-y-3">
          <div
            v-for="alert in filteredAlerts"
            :key="alert.id"
            class="p-4 rounded-lg border transition-all duration-200 hover:shadow-md"
            :class="getAlertCardClass(alert.severity)"
          >
            <div class="flex items-start justify-between">
              <div class="flex items-start space-x-3 flex-1">
                <!-- Alert Icon -->
                <div class="rounded-lg p-2 flex-shrink-0" :class="getAlertIconClass(alert.severity)">
                  <component :is="getAlertIcon(alert.type)" class="h-5 w-5" />
                </div>

                <!-- Alert Content -->
                <div class="flex-1 min-w-0">
                  <div class="flex items-center space-x-2 mb-1">
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100">{{ alert.title }}</h4>
                    <Badge :variant="getSeverityVariant(alert.severity)" class="text-xs">
                      {{ alert.severity.toUpperCase() }}
                    </Badge>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                      {{ formatAlertTime(alert.created_at) }}
                    </span>
                  </div>

                  <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                    {{ alert.message }}
                  </p>

                  <div class="flex flex-wrap items-center gap-2 text-xs">
                    <div class="flex items-center space-x-1">
                      <Globe class="h-3 w-3 text-gray-400" />
                      <span class="text-gray-600 dark:text-gray-400">{{ alert.website_name }}</span>
                    </div>
                    <div v-if="alert.rule_name" class="flex items-center space-x-1">
                      <Settings class="h-3 w-3 text-gray-400" />
                      <span class="text-gray-600 dark:text-gray-400">{{ alert.rule_name }}</span>
                    </div>
                    <div class="flex items-center space-x-1">
                      <Clock class="h-3 w-3 text-gray-400" />
                      <span class="text-gray-600 dark:text-gray-400">{{ alert.created_at }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Alert Actions -->
              <div class="flex items-center space-x-2 ml-4">
                <Button
                  v-if="!alert.acknowledged"
                  @click="acknowledgeAlert(alert)"
                  variant="outline"
                  size="sm"
                  class="flex items-center space-x-1"
                >
                  <Check class="h-3 w-3" />
                  <span>Ack</span>
                </Button>

                <DropdownMenu>
                  <DropdownMenuTrigger asChild>
                    <Button variant="ghost" size="sm">
                      <MoreVertical class="h-4 w-4" />
                    </Button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end">
                    <DropdownMenuItem @click="viewAlertDetails(alert)">
                      <Eye class="h-4 w-4 mr-2" />
                      View Details
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="muteAlert(alert)">
                      <VolumeX class="h-4 w-4 mr-2" />
                      Mute for 1 hour
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="createRuleFromAlert(alert)">
                      <Plus class="h-4 w-4 mr-2" />
                      Create Rule
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem @click="dismissAlert(alert)" class="text-red-600 dark:text-red-400">
                      <Trash2 class="h-4 w-4 mr-2" />
                      Dismiss
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
            </div>

            <!-- Expanded Details -->
            <div v-if="alert.expanded" class="mt-4 pt-4 border-t space-y-3">
              <div v-if="alert.details">
                <h5 class="font-medium text-gray-900 dark:text-gray-100 mb-2">Alert Details</h5>
                <div class="grid grid-cols-2 gap-3 text-sm">
                  <div v-for="(value, key) in alert.details" :key="key">
                    <span class="text-gray-600 dark:text-gray-400">{{ formatDetailKey(key) }}:</span>
                    <span class="ml-2 font-medium">{{ value }}</span>
                  </div>
                </div>
              </div>

              <div v-if="alert.suggested_actions && alert.suggested_actions.length > 0">
                <h5 class="font-medium text-gray-900 dark:text-gray-100 mb-2">Suggested Actions</h5>
                <ul class="list-disc list-inside space-y-1 text-sm text-gray-600 dark:text-gray-400">
                  <li v-for="action in alert.suggested_actions" :key="action">{{ action }}</li>
                </ul>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="filteredAlerts.length === 0" class="text-center py-12">
            <div class="rounded-lg bg-gray-100 dark:bg-gray-800 p-8">
              <component :is="getEmptyStateIcon()" class="h-16 w-16 text-gray-400 mx-auto mb-4" />
              <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                {{ getEmptyStateTitle() }}
              </h3>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ getEmptyStateMessage() }}
              </p>
            </div>
          </div>
        </div>

        <!-- Load More -->
        <div v-if="hasMoreAlerts" class="text-center pt-6">
          <Button @click="loadMoreAlerts" variant="outline" :disabled="loadingMoreAlerts">
            {{ loadingMoreAlerts ? 'Loading...' : 'Load More Alerts' }}
          </Button>
        </div>
      </CardContent>
    </Card>

    <!-- Alert Detail Modal -->
    <Dialog :open="showAlertDetail" @update:open="showAlertDetail = $event">
      <DialogContent class="max-w-2xl">
        <DialogHeader>
          <DialogTitle>Alert Details</DialogTitle>
        </DialogHeader>
        <AlertDetailView
          v-if="selectedAlert"
          :alert="selectedAlert"
          @close="showAlertDetail = false"
          @acknowledge="acknowledgeAlert"
          @dismiss="dismissAlert"
        />
      </DialogContent>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import AlertDetailView from './AlertDetailView.vue';
import {
  AlertTriangle,
  Zap,
  AlertCircle,
  CheckCircle,
  Activity,
  Filter,
  Trash2,
  Globe,
  Settings,
  Clock,
  Check,
  MoreVertical,
  Eye,
  VolumeX,
  Plus,
  Shield,
  Server,
  TrendingUp,
  Wifi,
  Smile
} from 'lucide-vue-next';

interface Alert {
  id: string;
  title: string;
  message: string;
  type: 'ssl_expiry' | 'ssl_invalid' | 'uptime_down' | 'response_time' | 'lets_encrypt_renewal';
  severity: 'critical' | 'high' | 'warning' | 'info';
  website_name: string;
  website_url: string;
  rule_name?: string;
  created_at: string;
  acknowledged: boolean;
  expanded?: boolean;
  details?: Record<string, any>;
  suggested_actions?: string[];
}

interface Props {
  initialAlerts?: Alert[];
}

const props = defineProps<Props>();

const emit = defineEmits<{
  alertAcknowledged: [alert: Alert];
  alertDismissed: [alert: Alert];
  createRule: [alert: Alert];
}>();

const activeFilter = ref('all');
const showAlertDetail = ref(false);
const selectedAlert = ref<Alert | null>(null);
const loadingMoreAlerts = ref(false);
const hasMoreAlerts = ref(true);

// Mock data - in production would come from real-time websocket or API
const alerts = ref<Alert[]>([
  {
    id: '1',
    title: 'SSL Certificate Expiring Soon',
    message: 'SSL certificate for example.com expires in 3 days',
    type: 'ssl_expiry',
    severity: 'critical',
    website_name: 'Example Website',
    website_url: 'https://example.com',
    rule_name: 'SSL Expiry Alert',
    created_at: new Date(Date.now() - 1000 * 60 * 30).toISOString(), // 30 minutes ago
    acknowledged: false,
    details: {
      days_remaining: 3,
      certificate_authority: "Let's Encrypt",
      expiry_date: '2024-12-15',
      auto_renewal: 'Enabled'
    },
    suggested_actions: [
      'Verify auto-renewal is working',
      'Check DNS configuration',
      'Contact hosting provider if needed'
    ]
  },
  {
    id: '2',
    title: 'Website Down',
    message: 'mysite.com is not responding to HTTP requests',
    type: 'uptime_down',
    severity: 'critical',
    website_name: 'My Site',
    website_url: 'https://mysite.com',
    rule_name: 'Uptime Monitor',
    created_at: new Date(Date.now() - 1000 * 60 * 60).toISOString(), // 1 hour ago
    acknowledged: true,
    details: {
      status_code: 0,
      response_time: 'Timeout',
      last_successful_check: '2024-12-10 14:30:00',
      consecutive_failures: 3
    },
    suggested_actions: [
      'Check server status',
      'Verify DNS settings',
      'Contact system administrator'
    ]
  }
]);

const alertStats = computed(() => {
  const stats = { critical: 0, high: 0, warning: 0, healthy: 0 };

  alerts.value.forEach(alert => {
    if (alert.acknowledged) return; // Don't count acknowledged alerts

    switch (alert.severity) {
      case 'critical':
        stats.critical++;
        break;
      case 'high':
        stats.high++;
        break;
      case 'warning':
        stats.warning++;
        break;
      default:
        stats.healthy++;
    }
  });

  return stats;
});

const alertFilters = computed(() => [
  { key: 'all', label: 'All', icon: Activity, count: alerts.value.length },
  { key: 'critical', label: 'Critical', icon: AlertTriangle, count: alertStats.value.critical },
  { key: 'ssl', label: 'SSL Issues', icon: Shield, count: alerts.value.filter(a => a.type.startsWith('ssl')).length },
  { key: 'uptime', label: 'Uptime Issues', icon: Server, count: alerts.value.filter(a => a.type.includes('uptime')).length },
  { key: 'acknowledged', label: 'Acknowledged', icon: Check, count: alerts.value.filter(a => a.acknowledged).length }
]);

const filteredAlerts = computed(() => {
  let filtered = alerts.value;

  switch (activeFilter.value) {
    case 'critical':
      filtered = filtered.filter(a => a.severity === 'critical');
      break;
    case 'ssl':
      filtered = filtered.filter(a => a.type.startsWith('ssl'));
      break;
    case 'uptime':
      filtered = filtered.filter(a => a.type.includes('uptime'));
      break;
    case 'acknowledged':
      filtered = filtered.filter(a => a.acknowledged);
      break;
  }

  return filtered.sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime());
});

const getAlertCardClass = (severity: string): string => {
  const baseClasses = 'border';

  switch (severity) {
    case 'critical':
      return `${baseClasses} border-red-200 dark:border-red-800 bg-red-50/50 dark:bg-red-900/10`;
    case 'high':
      return `${baseClasses} border-orange-200 dark:border-orange-800 bg-orange-50/50 dark:bg-orange-900/10`;
    case 'warning':
      return `${baseClasses} border-yellow-200 dark:border-yellow-800 bg-yellow-50/50 dark:bg-yellow-900/10`;
    default:
      return `${baseClasses} border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800`;
  }
};

const getAlertIconClass = (severity: string): string => {
  switch (severity) {
    case 'critical':
      return 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400';
    case 'high':
      return 'bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400';
    case 'warning':
      return 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400';
    default:
      return 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400';
  }
};

const getAlertIcon = (type: string) => {
  switch (type) {
    case 'ssl_expiry':
    case 'ssl_invalid':
    case 'lets_encrypt_renewal':
      return Shield;
    case 'uptime_down':
      return Server;
    case 'response_time':
      return TrendingUp;
    default:
      return AlertCircle;
  }
};

const getSeverityVariant = (severity: string) => {
  switch (severity) {
    case 'critical':
    case 'high':
      return 'destructive';
    case 'warning':
      return 'secondary';
    default:
      return 'outline';
  }
};

const formatAlertTime = (timestamp: string): string => {
  const date = new Date(timestamp);
  const now = new Date();
  const diffInMinutes = Math.floor((now.getTime() - date.getTime()) / (1000 * 60));

  if (diffInMinutes < 1) return 'Just now';
  if (diffInMinutes < 60) return `${diffInMinutes}m ago`;
  if (diffInMinutes < 1440) return `${Math.floor(diffInMinutes / 60)}h ago`;
  return `${Math.floor(diffInMinutes / 1440)}d ago`;
};

const formatDetailKey = (key: string): string => {
  return key.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};

const getEmptyStateIcon = () => {
  switch (activeFilter.value) {
    case 'critical':
      return AlertTriangle;
    case 'ssl':
      return Shield;
    case 'uptime':
      return Server;
    case 'acknowledged':
      return Check;
    default:
      return Smile;
  }
};

const getEmptyStateTitle = (): string => {
  switch (activeFilter.value) {
    case 'critical':
      return 'No Critical Alerts';
    case 'ssl':
      return 'No SSL Issues';
    case 'uptime':
      return 'No Uptime Issues';
    case 'acknowledged':
      return 'No Acknowledged Alerts';
    default:
      return 'No Alerts';
  }
};

const getEmptyStateMessage = (): string => {
  switch (activeFilter.value) {
    case 'critical':
      return 'All systems are running smoothly with no critical issues detected.';
    case 'ssl':
      return 'All SSL certificates are valid and properly configured.';
    case 'uptime':
      return 'All monitored websites are online and responding normally.';
    case 'acknowledged':
      return 'You have not acknowledged any alerts yet.';
    default:
      return 'Your monitoring systems are healthy and no alerts have been triggered.';
  }
};

const acknowledgeAlert = (alert: Alert) => {
  alert.acknowledged = true;
  emit('alertAcknowledged', alert);
};

const dismissAlert = (alert: Alert) => {
  const index = alerts.value.findIndex(a => a.id === alert.id);
  if (index > -1) {
    alerts.value.splice(index, 1);
    emit('alertDismissed', alert);
  }
};

const viewAlertDetails = (alert: Alert) => {
  selectedAlert.value = alert;
  showAlertDetail.value = true;
};

const muteAlert = (alert: Alert) => {
  // In production, this would mute the alert for the specified duration
  console.log(`Muting alert ${alert.id} for 1 hour`);
};

const createRuleFromAlert = (alert: Alert) => {
  emit('createRule', alert);
};

const clearAllAlerts = () => {
  if (confirm('Are you sure you want to clear all alerts?')) {
    alerts.value = [];
  }
};

const loadMoreAlerts = () => {
  loadingMoreAlerts.value = true;
  // Simulate loading more alerts
  setTimeout(() => {
    loadingMoreAlerts.value = false;
    hasMoreAlerts.value = false;
  }, 1000);
};

// Real-time updates simulation
let updateInterval: NodeJS.Timeout;

onMounted(() => {
  // Simulate real-time alerts
  updateInterval = setInterval(() => {
    // Randomly add new alerts (for demo purposes)
    if (Math.random() < 0.1) { // 10% chance every 5 seconds
      const newAlert: Alert = {
        id: Date.now().toString(),
        title: 'New Alert',
        message: 'This is a simulated real-time alert',
        type: 'ssl_expiry',
        severity: 'warning',
        website_name: 'Test Site',
        website_url: 'https://test.com',
        created_at: new Date().toISOString(),
        acknowledged: false
      };
      alerts.value.unshift(newAlert);
    }
  }, 5000);
});

onUnmounted(() => {
  if (updateInterval) {
    clearInterval(updateInterval);
  }
});
</script>