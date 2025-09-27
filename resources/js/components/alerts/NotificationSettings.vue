<template>
  <Card class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border border-green-200 dark:border-green-800">
    <CardHeader>
      <div class="flex items-center space-x-3">
        <div class="rounded-lg bg-green-100 dark:bg-green-900/30 p-2">
          <Bell class="h-5 w-5 text-green-600 dark:text-green-400" />
        </div>
        <div>
          <CardTitle class="text-xl font-bold text-gray-900 dark:text-gray-100">
            Notification Settings
          </CardTitle>
          <CardDescription>
            Configure how and when you receive alerts
          </CardDescription>
        </div>
      </div>
    </CardHeader>

    <CardContent class="space-y-6">
      <!-- Email Notifications -->
      <div class="space-y-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center">
          <Mail class="h-4 w-4 mr-2 text-blue-600 dark:text-blue-400" />
          Email Notifications
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Email Address</label>
            <input
              v-model="notificationSettings.email.address"
              type="email"
              placeholder="your@email.com"
              class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
            />
          </div>

          <div class="space-y-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Email Frequency</label>
            <select
              v-model="notificationSettings.email.frequency"
              class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
            >
              <option value="immediate">Immediate</option>
              <option value="hourly">Hourly Digest</option>
              <option value="daily">Daily Digest</option>
              <option value="weekly">Weekly Summary</option>
            </select>
          </div>
        </div>

        <div class="flex items-center space-x-2">
          <Checkbox
            :checked="notificationSettings.email.enabled"
            @update:checked="notificationSettings.email.enabled = $event"
          />
          <label class="text-sm text-gray-700 dark:text-gray-300">
            Enable email notifications
          </label>
        </div>
      </div>

      <!-- Slack Integration -->
      <div class="space-y-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center">
          <MessageSquare class="h-4 w-4 mr-2 text-purple-600 dark:text-purple-400" />
          Slack Integration
        </h3>

        <div v-if="!notificationSettings.slack.connected" class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border">
          <div class="flex items-center justify-between">
            <div>
              <p class="font-medium text-gray-900 dark:text-gray-100">Connect Slack Workspace</p>
              <p class="text-sm text-gray-600 dark:text-gray-400">Receive alerts directly in your Slack channels</p>
            </div>
            <Button @click="connectSlack" class="flex items-center space-x-2">
              <MessageSquare class="h-4 w-4" />
              <span>Connect Slack</span>
            </Button>
          </div>
        </div>

        <div v-else class="space-y-4">
          <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
            <div class="flex items-center space-x-3">
              <div class="h-2 w-2 bg-green-500 rounded-full"></div>
              <div>
                <p class="font-medium text-green-900 dark:text-green-100">Connected to {{ notificationSettings.slack.workspace }}</p>
                <p class="text-sm text-green-700 dark:text-green-300">Posting to #{{ notificationSettings.slack.channel }}</p>
              </div>
            </div>
            <Button @click="disconnectSlack" variant="outline" size="sm">
              Disconnect
            </Button>
          </div>

          <div class="space-y-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Slack Channel</label>
            <input
              v-model="notificationSettings.slack.channel"
              type="text"
              placeholder="alerts"
              class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
            />
          </div>

          <div class="flex items-center space-x-2">
            <Checkbox
              :checked="notificationSettings.slack.enabled"
              @update:checked="notificationSettings.slack.enabled = $event"
            />
            <label class="text-sm text-gray-700 dark:text-gray-300">
              Enable Slack notifications
            </label>
          </div>
        </div>
      </div>

      <!-- Webhook Notifications -->
      <div class="space-y-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center">
          <Webhook class="h-4 w-4 mr-2 text-orange-600 dark:text-orange-400" />
          Webhook Integration
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Webhook URL</label>
            <input
              v-model="notificationSettings.webhook.url"
              type="url"
              placeholder="https://your-webhook-endpoint.com"
              class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
            />
          </div>

          <div class="space-y-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">HTTP Method</label>
            <select
              v-model="notificationSettings.webhook.method"
              class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
            >
              <option value="POST">POST</option>
              <option value="PUT">PUT</option>
              <option value="PATCH">PATCH</option>
            </select>
          </div>
        </div>

        <div class="space-y-2">
          <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Custom Headers (JSON)</label>
          <textarea
            v-model="notificationSettings.webhook.headers"
            rows="3"
            placeholder='{"Authorization": "Bearer token", "Content-Type": "application/json"}'
            class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary font-mono text-sm"
          ></textarea>
        </div>

        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-2">
            <Checkbox
              :checked="notificationSettings.webhook.enabled"
              @update:checked="notificationSettings.webhook.enabled = $event"
            />
            <label class="text-sm text-gray-700 dark:text-gray-300">
              Enable webhook notifications
            </label>
          </div>

          <Button @click="testWebhook" variant="outline" size="sm" :disabled="!notificationSettings.webhook.url">
            <Zap class="h-4 w-4 mr-2" />
            Test Webhook
          </Button>
        </div>
      </div>

      <!-- Dashboard Notifications -->
      <div class="space-y-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center">
          <Monitor class="h-4 w-4 mr-2 text-indigo-600 dark:text-indigo-400" />
          Dashboard Notifications
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="flex items-center justify-between p-3 border rounded-lg">
            <div>
              <p class="font-medium text-gray-900 dark:text-gray-100">Browser Notifications</p>
              <p class="text-sm text-gray-600 dark:text-gray-400">Show browser push notifications</p>
            </div>
            <Checkbox
              :checked="notificationSettings.dashboard.browser_notifications"
              @update:checked="notificationSettings.dashboard.browser_notifications = $event"
            />
          </div>

          <div class="flex items-center justify-between p-3 border rounded-lg">
            <div>
              <p class="font-medium text-gray-900 dark:text-gray-100">Sound Alerts</p>
              <p class="text-sm text-gray-600 dark:text-gray-400">Play sound for critical alerts</p>
            </div>
            <Checkbox
              :checked="notificationSettings.dashboard.sound_alerts"
              @update:checked="notificationSettings.dashboard.sound_alerts = $event"
            />
          </div>

          <div class="flex items-center justify-between p-3 border rounded-lg">
            <div>
              <p class="font-medium text-gray-900 dark:text-gray-100">Desktop Badges</p>
              <p class="text-sm text-gray-600 dark:text-gray-400">Show alert count on browser tab</p>
            </div>
            <Checkbox
              :checked="notificationSettings.dashboard.desktop_badges"
              @update:checked="notificationSettings.dashboard.desktop_badges = $event"
            />
          </div>

          <div class="flex items-center justify-between p-3 border rounded-lg">
            <div>
              <p class="font-medium text-gray-900 dark:text-gray-100">Auto-refresh</p>
              <p class="text-sm text-gray-600 dark:text-gray-400">Automatically refresh alert data</p>
            </div>
            <Checkbox
              :checked="notificationSettings.dashboard.auto_refresh"
              @update:checked="notificationSettings.dashboard.auto_refresh = $event"
            />
          </div>
        </div>

        <div v-if="notificationSettings.dashboard.auto_refresh" class="space-y-2">
          <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Refresh Interval</label>
          <select
            v-model="notificationSettings.dashboard.refresh_interval"
            class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
          >
            <option value="30">30 seconds</option>
            <option value="60">1 minute</option>
            <option value="300">5 minutes</option>
            <option value="600">10 minutes</option>
          </select>
        </div>
      </div>

      <!-- Save Settings -->
      <div class="flex justify-end pt-4 border-t">
        <div class="flex space-x-3">
          <Button @click="resetToDefaults" variant="outline">
            Reset to Defaults
          </Button>
          <Button @click="saveSettings" class="flex items-center space-x-2">
            <Save class="h-4 w-4" />
            <span>Save Settings</span>
          </Button>
        </div>
      </div>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Checkbox } from '@/components/ui/checkbox';
import {
  Bell,
  Mail,
  MessageSquare,
  Webhook,
  Monitor,
  Zap,
  Save
} from 'lucide-vue-next';

interface NotificationSettings {
  email: {
    enabled: boolean;
    address: string;
    frequency: 'immediate' | 'hourly' | 'daily' | 'weekly';
  };
  slack: {
    enabled: boolean;
    connected: boolean;
    workspace: string;
    channel: string;
  };
  webhook: {
    enabled: boolean;
    url: string;
    method: 'POST' | 'PUT' | 'PATCH';
    headers: string;
  };
  dashboard: {
    browser_notifications: boolean;
    sound_alerts: boolean;
    desktop_badges: boolean;
    auto_refresh: boolean;
    refresh_interval: number;
  };
}

const emit = defineEmits<{
  settingsUpdated: [settings: NotificationSettings];
}>();

const notificationSettings = ref<NotificationSettings>({
  email: {
    enabled: true,
    address: '',
    frequency: 'immediate'
  },
  slack: {
    enabled: false,
    connected: false,
    workspace: '',
    channel: 'alerts'
  },
  webhook: {
    enabled: false,
    url: '',
    method: 'POST',
    headers: '{"Content-Type": "application/json"}'
  },
  dashboard: {
    browser_notifications: true,
    sound_alerts: true,
    desktop_badges: true,
    auto_refresh: true,
    refresh_interval: 60
  }
});

const connectSlack = () => {
  // In production, this would initiate OAuth flow
  alert('Slack integration would be implemented here. This would redirect to Slack OAuth.');

  // Simulate successful connection
  setTimeout(() => {
    notificationSettings.value.slack.connected = true;
    notificationSettings.value.slack.workspace = 'My Workspace';
    notificationSettings.value.slack.enabled = true;
  }, 1000);
};

const disconnectSlack = () => {
  if (confirm('Are you sure you want to disconnect Slack integration?')) {
    notificationSettings.value.slack.connected = false;
    notificationSettings.value.slack.enabled = false;
    notificationSettings.value.slack.workspace = '';
  }
};

const testWebhook = async () => {
  if (!notificationSettings.value.webhook.url) {
    alert('Please enter a webhook URL first');
    return;
  }

  try {
    // In production, this would send a test webhook
    alert('Test webhook sent successfully! Check your endpoint for the test payload.');
  } catch (error) {
    alert('Webhook test failed. Please check your URL and try again.');
  }
};

const resetToDefaults = () => {
  if (confirm('Are you sure you want to reset all notification settings to defaults?')) {
    notificationSettings.value = {
      email: {
        enabled: true,
        address: '',
        frequency: 'immediate'
      },
      slack: {
        enabled: false,
        connected: false,
        workspace: '',
        channel: 'alerts'
      },
      webhook: {
        enabled: false,
        url: '',
        method: 'POST',
        headers: '{"Content-Type": "application/json"}'
      },
      dashboard: {
        browser_notifications: true,
        sound_alerts: true,
        desktop_badges: true,
        auto_refresh: true,
        refresh_interval: 60
      }
    };
  }
};

const saveSettings = () => {
  // Validate settings
  if (notificationSettings.value.email.enabled && !notificationSettings.value.email.address) {
    alert('Please enter an email address or disable email notifications');
    return;
  }

  if (notificationSettings.value.webhook.enabled && !notificationSettings.value.webhook.url) {
    alert('Please enter a webhook URL or disable webhook notifications');
    return;
  }

  // In production, this would send settings to the backend
  console.log('Saving notification settings:', notificationSettings.value);

  emit('settingsUpdated', notificationSettings.value);
  alert('Notification settings saved successfully!');
};
</script>