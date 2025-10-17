<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { History, Clock, AlertTriangle, Filter } from 'lucide-vue-next';

interface Props {
  alertHistory?: any[];
  filters?: {
    type: string;
    level: string;
    date_range: string;
  };
}

defineProps<Props>();
</script>

<template>
  <Head title="Alert History" />

  <DashboardLayout title="Alert History">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-foreground">Alert History</h1>
          <p class="text-muted-foreground">View past alerts and monitoring events</p>
        </div>
      </div>

      <!-- Filter Bar -->
      <div class="rounded-lg bg-card border border-border p-6">
        <div class="flex items-center gap-4">
          <div class="flex items-center gap-2">
            <Filter class="h-4 w-4 text-muted-foreground" />
            <span class="text-sm font-medium text-foreground">Filters:</span>
          </div>

          <select class="px-3 py-2 border border-border rounded-md bg-background text-foreground text-sm">
            <option value="all">All Types</option>
            <option value="ssl">SSL Alerts</option>
            <option value="uptime">Uptime Alerts</option>
          </select>

          <select class="px-3 py-2 border border-border rounded-md bg-background text-foreground text-sm">
            <option value="all">All Levels</option>
            <option value="critical">Critical</option>
            <option value="warning">Warning</option>
            <option value="info">Info</option>
          </select>

          <select class="px-3 py-2 border border-border rounded-md bg-background text-foreground text-sm">
            <option value="7_days">Last 7 days</option>
            <option value="30_days">Last 30 days</option>
            <option value="90_days">Last 90 days</option>
          </select>
        </div>
      </div>

      <!-- Statistics -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="p-6 rounded-lg bg-card border border-border">
          <div class="flex items-center space-x-3">
            <div class="rounded-lg bg-blue-100 dark:bg-blue-900/30 p-2">
              <AlertTriangle class="h-5 w-5 text-primary dark:text-blue-400" />
            </div>
            <div>
              <div class="text-2xl font-bold text-foreground">0</div>
              <div class="text-sm text-muted-foreground">Total Alerts</div>
            </div>
          </div>
        </div>

        <div class="p-6 rounded-lg bg-card border border-border">
          <div class="flex items-center space-x-3">
            <div class="rounded-lg bg-red-100 dark:bg-red-900/30 p-2">
              <AlertTriangle class="h-5 w-5 text-destructive dark:text-red-400" />
            </div>
            <div>
              <div class="text-2xl font-bold text-foreground">0</div>
              <div class="text-sm text-muted-foreground">Critical</div>
            </div>
          </div>
        </div>

        <div class="p-6 rounded-lg bg-card border border-border">
          <div class="flex items-center space-x-3">
            <div class="rounded-lg bg-yellow-100 dark:bg-yellow-900/30 p-2">
              <AlertTriangle class="h-5 w-5 text-yellow-600 dark:text-yellow-400" />
            </div>
            <div>
              <div class="text-2xl font-bold text-foreground">0</div>
              <div class="text-sm text-muted-foreground">Warnings</div>
            </div>
          </div>
        </div>

        <div class="p-6 rounded-lg bg-card border border-border">
          <div class="flex items-center space-x-3">
            <div class="rounded-lg bg-green-100 dark:bg-green-900/30 p-2">
              <Clock class="h-5 w-5 text-green-600 dark:text-green-400" />
            </div>
            <div>
              <div class="text-2xl font-bold text-foreground">-</div>
              <div class="text-sm text-muted-foreground">Last Alert</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Alert History Table -->
      <div class="rounded-lg bg-card border border-border p-6">
        <div class="text-center py-12">
          <History class="h-12 w-12 text-muted-foreground mx-auto mb-4" />
          <h3 class="text-lg font-semibold text-foreground mb-2">Alert History</h3>
          <p class="text-muted-foreground mb-6">
            No alerts have been recorded yet. When alerts are triggered, they will appear here.
          </p>
          <p class="text-sm text-muted-foreground">
            Configure alert rules in
            <a href="/settings/alerts" class="text-primary hover:underline">Settings → Alerts</a>
            to start monitoring your websites.
          </p>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>