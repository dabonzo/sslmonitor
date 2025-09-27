<template>
  <div class="space-y-6">
    <!-- Alert Header -->
    <div class="flex items-start justify-between">
      <div class="flex items-start space-x-3">
        <div class="rounded-lg p-3" :class="getAlertIconClass(alert.severity)">
          <component :is="getAlertIcon(alert.type)" class="h-6 w-6" />
        </div>
        <div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ alert.title }}</h3>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ alert.message }}</p>
          <div class="flex items-center space-x-2 mt-2">
            <Badge :variant="getSeverityVariant(alert.severity)">
              {{ alert.severity.toUpperCase() }}
            </Badge>
            <Badge variant="outline">{{ getAlertTypeLabel(alert.type) }}</Badge>
            <span class="text-xs text-gray-500 dark:text-gray-400">
              {{ formatDate(alert.created_at) }}
            </span>
          </div>
        </div>
      </div>

      <div class="flex items-center space-x-2">
        <Button
          v-if="!alert.acknowledged"
          @click="$emit('acknowledge', alert)"
          variant="outline"
          size="sm"
        >
          <Check class="h-4 w-4 mr-2" />
          Acknowledge
        </Button>
        <Button @click="$emit('close')" variant="ghost" size="sm">
          <X class="h-4 w-4" />
        </Button>
      </div>
    </div>

    <!-- Website Information -->
    <Card>
      <CardHeader>
        <CardTitle class="text-lg">Affected Website</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Website Name</label>
            <p class="font-medium">{{ alert.website_name }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-600 dark:text-gray-400">URL</label>
            <p class="font-medium">
              <a :href="alert.website_url" target="_blank" class="text-blue-600 hover:underline">
                {{ alert.website_url }}
              </a>
            </p>
          </div>
          <div v-if="alert.rule_name">
            <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Triggered by Rule</label>
            <p class="font-medium">{{ alert.rule_name }}</p>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Alert Details -->
    <Card v-if="alert.details && Object.keys(alert.details).length > 0">
      <CardHeader>
        <CardTitle class="text-lg">Technical Details</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div v-for="(value, key) in alert.details" :key="key">
            <label class="text-sm font-medium text-gray-600 dark:text-gray-400">
              {{ formatDetailKey(key) }}
            </label>
            <p class="font-medium" :class="getDetailValueClass(key, value)">
              {{ formatDetailValue(key, value) }}
            </p>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Timeline -->
    <Card>
      <CardHeader>
        <CardTitle class="text-lg">Alert Timeline</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="space-y-4">
          <div class="flex items-start space-x-3">
            <div class="rounded-full bg-red-100 p-1 mt-1">
              <AlertCircle class="h-3 w-3 text-red-600" />
            </div>
            <div class="flex-1">
              <p class="text-sm font-medium">Alert Triggered</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(alert.created_at) }}</p>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ alert.message }}</p>
            </div>
          </div>

          <div v-if="alert.acknowledged" class="flex items-start space-x-3">
            <div class="rounded-full bg-blue-100 p-1 mt-1">
              <Check class="h-3 w-3 text-blue-600" />
            </div>
            <div class="flex-1">
              <p class="text-sm font-medium">Alert Acknowledged</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(alert.created_at) }}</p>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Alert was acknowledged by user</p>
            </div>
          </div>

          <!-- Simulated future events -->
          <div class="flex items-start space-x-3 opacity-50">
            <div class="rounded-full bg-gray-100 p-1 mt-1">
              <Clock class="h-3 w-3 text-gray-600" />
            </div>
            <div class="flex-1">
              <p class="text-sm font-medium">Auto-resolve (pending)</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">Expected in 2 hours</p>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Alert will auto-resolve when conditions improve</p>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Suggested Actions -->
    <Card v-if="alert.suggested_actions && alert.suggested_actions.length > 0">
      <CardHeader>
        <CardTitle class="text-lg flex items-center">
          <Lightbulb class="h-5 w-5 mr-2" />
          Suggested Actions
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div class="space-y-3">
          <div
            v-for="(action, index) in alert.suggested_actions"
            :key="index"
            class="flex items-start space-x-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg"
          >
            <div class="rounded-full bg-blue-100 dark:bg-blue-900/30 p-1 mt-0.5">
              <span class="text-xs font-bold text-blue-600 dark:text-blue-400 w-4 h-4 flex items-center justify-center">
                {{ index + 1 }}
              </span>
            </div>
            <p class="text-sm text-blue-800 dark:text-blue-200 flex-1">{{ action }}</p>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Related Resources -->
    <Card>
      <CardHeader>
        <CardTitle class="text-lg">Related Resources</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="space-y-2">
          <a
            href="#"
            class="flex items-center space-x-3 p-3 rounded-lg border hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
          >
            <ExternalLink class="h-4 w-4 text-gray-600 dark:text-gray-400" />
            <div>
              <p class="font-medium">SSL Certificate Documentation</p>
              <p class="text-sm text-gray-600 dark:text-gray-400">Learn about SSL certificate management</p>
            </div>
          </a>
          <a
            href="#"
            class="flex items-center space-x-3 p-3 rounded-lg border hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
          >
            <ExternalLink class="h-4 w-4 text-gray-600 dark:text-gray-400" />
            <div>
              <p class="font-medium">Troubleshooting Guide</p>
              <p class="text-sm text-gray-600 dark:text-gray-400">Step-by-step problem resolution</p>
            </div>
          </a>
          <a
            href="#"
            class="flex items-center space-x-3 p-3 rounded-lg border hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
          >
            <ExternalLink class="h-4 w-4 text-gray-600 dark:text-gray-400" />
            <div>
              <p class="font-medium">Contact Support</p>
              <p class="text-sm text-gray-600 dark:text-gray-400">Get help from our technical team</p>
            </div>
          </a>
        </div>
      </CardContent>
    </Card>

    <!-- Action Buttons -->
    <div class="flex justify-end space-x-3 pt-4 border-t">
      <Button @click="$emit('dismiss', alert)" variant="outline">
        Dismiss Alert
      </Button>
      <Button v-if="!alert.acknowledged" @click="$emit('acknowledge', alert)">
        <Check class="h-4 w-4 mr-2" />
        Acknowledge & Close
      </Button>
      <Button v-else @click="$emit('close')" variant="outline">
        Close
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Check,
  X,
  AlertCircle,
  Clock,
  Lightbulb,
  ExternalLink,
  Shield,
  Server,
  TrendingUp,
  Wifi
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
  details?: Record<string, any>;
  suggested_actions?: string[];
}

interface Props {
  alert: Alert;
}

defineProps<Props>();

defineEmits<{
  close: [];
  acknowledge: [alert: Alert];
  dismiss: [alert: Alert];
}>();

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

const getAlertTypeLabel = (type: string): string => {
  const labels = {
    ssl_expiry: 'SSL Expiry',
    ssl_invalid: 'SSL Invalid',
    lets_encrypt_renewal: 'Let\'s Encrypt',
    uptime_down: 'Uptime',
    response_time: 'Performance'
  };
  return labels[type] || type;
};

const formatDetailKey = (key: string): string => {
  return key.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};

const formatDetailValue = (key: string, value: any): string => {
  if (value === null || value === undefined) return 'N/A';

  // Format specific keys
  switch (key) {
    case 'days_remaining':
      return `${value} days`;
    case 'response_time':
      return value === 'Timeout' ? value : `${value}ms`;
    case 'uptime_percentage':
      return `${value}%`;
    case 'auto_renewal':
      return value ? 'Enabled' : 'Disabled';
    default:
      return String(value);
  }
};

const getDetailValueClass = (key: string, value: any): string => {
  // Add color coding for specific values
  switch (key) {
    case 'days_remaining':
      if (typeof value === 'number') {
        if (value <= 3) return 'text-red-600 dark:text-red-400';
        if (value <= 7) return 'text-orange-600 dark:text-orange-400';
        if (value <= 14) return 'text-yellow-600 dark:text-yellow-400';
      }
      break;
    case 'status_code':
      return value === 200 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
    case 'consecutive_failures':
      return typeof value === 'number' && value > 0 ? 'text-red-600 dark:text-red-400' : '';
  }
  return '';
};

const formatDate = (dateString: string): string => {
  const date = new Date(dateString);
  return date.toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};
</script>