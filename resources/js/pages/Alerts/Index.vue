<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { AlertTriangle, Settings, History, Bell } from 'lucide-vue-next';

interface Props {
  alertConfigurations?: any[];
  alertsByWebsite?: any[];
  websites?: any[];
}

defineProps<Props>();
</script>

<template>
  <Head title="Alert Rules" />

  <DashboardLayout title="Alert Rules">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-foreground">Alert Rules</h1>
          <p class="text-muted-foreground">Configure monitoring alerts and rules for your websites</p>
        </div>
      </div>

      <!-- Quick Navigation -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <a href="/alerts" class="group p-6 rounded-lg bg-card border border-border hover:bg-muted/50 transition-colors">
          <div class="flex items-center space-x-3">
            <div class="rounded-lg bg-blue-100 dark:bg-blue-900/30 p-2">
              <Settings class="h-5 w-5 text-primary dark:text-blue-400" />
            </div>
            <div>
              <h3 class="font-semibold text-foreground group-hover:text-primary">Alert Rules</h3>
              <p class="text-sm text-muted-foreground">Configure monitoring rules</p>
            </div>
          </div>
        </a>

        <a href="/alerts/notifications" class="group p-6 rounded-lg bg-card border border-border hover:bg-muted/50 transition-colors">
          <div class="flex items-center space-x-3">
            <div class="rounded-lg bg-green-100 dark:bg-green-900/30 p-2">
              <Bell class="h-5 w-5 text-green-600 dark:text-green-400" />
            </div>
            <div>
              <h3 class="font-semibold text-foreground group-hover:text-primary">Notifications</h3>
              <p class="text-sm text-muted-foreground">Manage notification settings</p>
            </div>
          </div>
        </a>

        <a href="/alerts/history" class="group p-6 rounded-lg bg-card border border-border hover:bg-muted/50 transition-colors">
          <div class="flex items-center space-x-3">
            <div class="rounded-lg bg-purple-100 dark:bg-purple-900/30 p-2">
              <History class="h-5 w-5 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
              <h3 class="font-semibold text-foreground group-hover:text-primary">Alert History</h3>
              <p class="text-sm text-muted-foreground">View past alerts and events</p>
            </div>
          </div>
        </a>
      </div>

      <!-- Main Content -->
      <div class="rounded-lg bg-card border border-border p-6">
        <div class="text-center py-12">
          <AlertTriangle class="h-12 w-12 text-muted-foreground mx-auto mb-4" />
          <h3 class="text-lg font-semibold text-foreground mb-2">Alert Rules Configuration</h3>
          <p class="text-muted-foreground mb-6">
            This section will contain the full alert rules management interface.
          </p>
          <p class="text-sm text-muted-foreground">
            For now, you can configure alert settings in
            <a href="/settings/alerts" class="text-primary hover:underline">Settings → Alerts</a>
          </p>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>