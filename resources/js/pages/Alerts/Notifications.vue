<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Bell, Mail, MessageSquare } from 'lucide-vue-next';

interface Props {
  recentAlerts?: any[];
  notificationSettings?: {
    email_enabled: boolean;
    slack_enabled: boolean;
    webhook_enabled: boolean;
  };
}

defineProps<Props>();
</script>

<template>
  <Head title="Notifications" />

  <DashboardLayout title="Notifications">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-foreground">Notifications</h1>
          <p class="text-muted-foreground">Manage how you receive alerts and notifications</p>
        </div>
      </div>

      <!-- Notification Channels -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="p-6 rounded-lg bg-card border border-border">
          <div class="flex items-center space-x-3 mb-4">
            <div class="rounded-lg bg-blue-100 dark:bg-blue-900/30 p-2">
              <Mail class="h-5 w-5 text-primary dark:text-blue-400" />
            </div>
            <div>
              <h3 class="font-semibold text-foreground">Email</h3>
              <p class="text-sm text-muted-foreground">Get alerts via email</p>
            </div>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-muted-foreground">Status:</span>
            <span class="text-sm font-medium text-green-600">Enabled</span>
          </div>
        </div>

        <div class="p-6 rounded-lg bg-card border border-border">
          <div class="flex items-center space-x-3 mb-4">
            <div class="rounded-lg bg-green-100 dark:bg-green-900/30 p-2">
              <MessageSquare class="h-5 w-5 text-green-600 dark:text-green-400" />
            </div>
            <div>
              <h3 class="font-semibold text-foreground">Slack</h3>
              <p class="text-sm text-muted-foreground">Team notifications</p>
            </div>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-muted-foreground">Status:</span>
            <span class="text-sm font-medium text-muted-foreground">Coming Soon</span>
          </div>
        </div>

        <div class="p-6 rounded-lg bg-card border border-border">
          <div class="flex items-center space-x-3 mb-4">
            <div class="rounded-lg bg-purple-100 dark:bg-purple-900/30 p-2">
              <Bell class="h-5 w-5 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
              <h3 class="font-semibold text-foreground">Webhooks</h3>
              <p class="text-sm text-muted-foreground">Custom integrations</p>
            </div>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-muted-foreground">Status:</span>
            <span class="text-sm font-medium text-muted-foreground">Coming Soon</span>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <div class="rounded-lg bg-card border border-border p-6">
        <div class="text-center py-12">
          <Bell class="h-12 w-12 text-muted-foreground mx-auto mb-4" />
          <h3 class="text-lg font-semibold text-foreground mb-2">Notification Management</h3>
          <p class="text-muted-foreground mb-6">
            Advanced notification settings and delivery preferences will be available here.
          </p>
          <p class="text-sm text-muted-foreground">
            For now, you can configure basic notification preferences in
            <a href="/settings/alerts" class="text-primary hover:underline">Settings → Alerts</a>
          </p>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>