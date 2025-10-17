<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { X, Bell, Shield, Zap, Clock, Info, Check } from 'lucide-vue-next';

interface Website {
  id: number;
  name: string;
  url: string;
}

interface AlertConfiguration {
  id: number;
  alert_type: string;
  alert_type_label: string;
  enabled: boolean;
  alert_level: string;
  alert_level_label: string;
  alert_level_color: string;
  threshold_days: number | null;
  threshold_response_time: number | null;
  notification_channels: string[];
  custom_message: string | null;
  last_triggered_at: string | null;
}

interface Props {
  show: boolean;
  website: Website | null;
  alertConfigurations: AlertConfiguration[];
  loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
});

const emit = defineEmits<{
  close: [];
  updateAlert: [alertId: number, updates: Partial<AlertConfiguration>];
  testAlert: [alertId: number];
}>();

// Group alerts by type
const sslExpiryAlerts = computed(() => {
  const sslAlerts = props.alertConfigurations.filter(alert =>
    alert.alert_type.includes('ssl') || alert.alert_type.includes('expiry')
  );

  // Group by threshold days for better organization
  const grouped = sslAlerts.reduce((acc, alert) => {
    const key = alert.threshold_days || 0;
    if (!acc[key]) {
      acc[key] = [];
    }
    acc[key].push(alert);
    return acc;
  }, {} as Record<number, AlertConfiguration[]>);

  return Object.entries(grouped)
    .sort(([a], [b]) => Number(b) - Number(a)) // Sort descending (30, 14, 7, 3, 0)
    .map(([days, alerts]) => ({ days: Number(days), alert: alerts[0] })); // Take first alert for each threshold
});

const uptimeAlerts = computed(() => {
  return props.alertConfigurations.filter(alert =>
    alert.alert_type.includes('uptime') || alert.alert_type.includes('down')
  );
});

const responseTimeAlerts = computed(() => {
  return props.alertConfigurations.filter(alert =>
    alert.alert_type.includes('response_time')
  );
});

const handleToggleAlert = (alert: AlertConfiguration) => {
  emit('updateAlert', alert.id, {
    enabled: !alert.enabled
  });
};

const handleTestAlert = (alert: AlertConfiguration) => {
  emit('testAlert', alert.id);
};

const getAlertIcon = (alertType: string) => {
  if (alertType.includes('ssl') || alertType.includes('expiry')) {
    return Shield;
  }
  if (alertType.includes('uptime') || alertType.includes('down')) {
    return Zap;
  }
  if (alertType.includes('response_time')) {
    return Clock;
  }
  return Bell;
};

const getDaysLabel = (days: number | null) => {
  if (days === 0) return 'EXPIRED';
  if (days === 1) return '1 day';
  return `${days} days`;
};

const getExpiryColor = (days: number | null) => {
  if (days === 0) return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
  if (days <= 3) return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
  if (days <= 7) return 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200';
  if (days <= 14) return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
  return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
};
</script>

<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-screen items-center justify-center p-4">
      <div class="fixed inset-0 bg-black/50" @click="emit('close')"></div>

      <div class="relative bg-background dark:bg-gray-900 rounded-xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
        <!-- Header -->
        <div class="flex items-center justify-between p-6 border-b border-border dark:border-border">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
              <Bell class="h-5 w-5 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
              <h2 class="text-xl font-semibold text-foreground dark:text-foreground">
                Configure Alerts
              </h2>
              <p class="text-sm text-muted-foreground dark:text-muted-foreground">
                {{ website?.name || 'Website' }} Alert Settings
              </p>
            </div>
          </div>
          <button
            @click="emit('close')"
            class="p-2 text-muted-foreground hover:text-foreground dark:hover:text-gray-300 rounded-lg hover:bg-muted dark:hover:bg-gray-800 transition-colors"
          >
            <X class="h-5 w-5" />
          </button>
        </div>

        <!-- Content -->
        <div v-if="loading" class="flex items-center justify-center py-20">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
        </div>

        <div v-else-if="!website" class="text-center py-20 text-muted-foreground dark:text-muted-foreground">
          No website selected
        </div>

        <div v-else class="p-6 space-y-8 max-h-[calc(90vh-120px)] overflow-y-auto">
          <!-- SSL Certificate Expiry Alerts -->
          <div>
            <h3 class="text-lg font-medium text-foreground dark:text-foreground mb-4 flex items-center gap-2">
              <Shield class="h-5 w-5 text-green-600" />
              SSL Certificate Expiry Alerts
            </h3>
            <div class="space-y-3">
              <div
                v-for="({ days, alert } in sslExpiryAlerts"
                :key="alert.id"
                class="flex items-center justify-between p-4 bg-muted dark:bg-card rounded-lg border border-border dark:border-border"
              >
                <div class="flex items-center gap-3">
                  <div class="w-12 h-12 rounded-lg flex items-center justify-center" :class="getExpiryColor(days)">
                    <Shield class="h-6 w-6" />
                  </div>
                  <div>
                    <h4 class="font-medium text-foreground dark:text-foreground">
                      {{ getDaysLabel(days) }}{{ days > 0 ? ' before expiry' : '' }}
                    </h4>
                    <p class="text-sm text-muted-foreground dark:text-muted-foreground">
                      {{ alert.alert_level_label }}
                    </p>
                  </div>
                </div>
                <div class="flex items-center gap-3">
                  <button
                    @click="handleTestAlert(alert)"
                    class="px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100 transition-colors"
                    :disabled="!alert.enabled"
                    :title="alert.enabled ? 'Send test alert' : 'Enable alert to test'"
                  >
                    Test
                  </button>
                  <button
                    @click="handleToggleAlert(alert)"
                    :class="[
                      'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                      alert.enabled ? 'bg-purple-600' : 'bg-gray-300 dark:bg-gray-600'
                    ]"
                  >
                    <span
                      :class="[
                        'inline-block h-4 w-4 transform rounded-full bg-background transition-transform',
                        alert.enabled ? 'translate-x-6' : 'translate-x-1'
                      ]"
                    />
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Uptime Alerts -->
          <div>
            <h3 class="text-lg font-medium text-foreground dark:text-foreground mb-4 flex items-center gap-2">
              <Zap class="h-5 w-5 text-primary" />
              Uptime Monitoring Alerts
            </h3>
            <div class="space-y-3">
              <div
                v-for="alert in uptimeAlerts"
                :key="alert.id"
                class="flex items-center justify-between p-4 bg-muted dark:bg-card rounded-lg border border-border dark:border-border"
              >
                <div class="flex items-center gap-3">
                  <div :class="['w-12 h-12 rounded-lg flex items-center justify-center', alert.alert_level_color]">
                    <component :is="getAlertIcon(alert.alert_type)" class="h-6 w-6" />
                  </div>
                  <div>
                    <h4 class="font-medium text-foreground dark:text-foreground">
                      {{ alert.alert_type_label }}
                    </h4>
                    <p class="text-sm text-muted-foreground dark:text-muted-foreground">
                      {{ alert.alert_level_label }}
                    </p>
                  </div>
                </div>
                <div class="flex items-center gap-3">
                  <button
                    @click="handleTestAlert(alert)"
                    class="px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100 transition-colors"
                    :disabled="!alert.enabled"
                    :title="alert.enabled ? 'Send test alert' : 'Enable alert to test'"
                  >
                    Test
                  </button>
                  <button
                    @click="handleToggleAlert(alert)"
                    :class="[
                      'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                      alert.enabled ? 'bg-purple-600' : 'bg-gray-300 dark:bg-gray-600'
                    ]"
                  >
                    <span
                      :class="[
                        'inline-block h-4 w-4 transform rounded-full bg-background transition-transform',
                        alert.enabled ? 'translate-x-6' : 'translate-x-1'
                      ]"
                    />
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Response Time Alerts -->
          <div v-if="responseTimeAlerts.length > 0">
            <h3 class="text-lg font-medium text-foreground dark:text-foreground mb-4 flex items-center gap-2">
              <Clock class="h-5 w-5 text-orange-600" />
              Response Time Alerts
            </h3>
            <div class="space-y-3">
              <div
                v-for="alert in responseTimeAlerts"
                :key="alert.id"
                class="flex items-center justify-between p-4 bg-muted dark:bg-card rounded-lg border border-border dark:border-border"
              >
                <div class="flex items-center gap-3">
                  <div :class="['w-12 h-12 rounded-lg flex items-center justify-center', alert.alert_level_color]">
                    <component :is="getAlertIcon(alert.alert_type)" class="h-6 w-6" />
                  </div>
                  <div>
                    <h4 class="font-medium text-foreground dark:text-foreground">
                      {{ alert.alert_type_label }}
                    </h4>
                    <p class="text-sm text-muted-foreground dark:text-muted-foreground">
                      Threshold: {{ alert.threshold_response_time }}ms
                    </p>
                  </div>
                </div>
                <div class="flex items-center gap-3">
                  <button
                    @click="handleTestAlert(alert)"
                    class="px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100 transition-colors"
                    :disabled="!alert.enabled"
                    :title="alert.enabled ? 'Send test alert' : 'Enable alert to test'"
                  >
                    Test
                  </button>
                  <button
                    @click="handleToggleAlert(alert)"
                    :class="[
                      'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                      alert.enabled ? 'bg-purple-600' : 'bg-gray-300 dark:bg-gray-600'
                    ]"
                  >
                    <span
                      :class="[
                        'inline-block h-4 w-4 transform rounded-full bg-background transition-transform',
                        alert.enabled ? 'translate-x-6' : 'translate-x-1'
                      ]"
                    />
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Info Section -->
          <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <div class="flex items-start gap-3">
              <Info class="h-5 w-5 text-primary dark:text-blue-400 mt-0.5" />
              <div class="text-sm text-blue-800 dark:text-blue-200">
                <h4 class="font-medium mb-1">About Alert Configuration</h4>
                <p class="text-blue-700 dark:text-blue-300">
                  These alerts apply only to this website. Global default settings are used for new websites.
                  You can override these settings at any time without affecting other websites.
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-end gap-3 p-6 border-t border-border dark:border-border bg-muted dark:bg-gray-800">
          <button
            @click="emit('close')"
            class="px-4 py-2 text-sm font-medium text-foreground dark:text-muted-foreground bg-background dark:bg-muted border border-border dark:border-border rounded-md hover:bg-muted dark:hover:bg-gray-600 transition-colors"
          >
            Done
          </button>
        </div>
      </div>
    </div>
  </div>
</template>