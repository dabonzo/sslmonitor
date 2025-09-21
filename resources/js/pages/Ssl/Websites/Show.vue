<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head } from '@inertiajs/vue3';

interface SslCertificate {
  id: number;
  status: string;
  expires_at: string;
  issuer: string;
  subject: string;
  is_valid: boolean;
  created_at: string;
}

interface SslCheck {
  id: number;
  status: string;
  checked_at: string;
  response_time: number;
  error_message: string | null;
}

interface Website {
  id: number;
  name: string;
  url: string;
  ssl_monitoring_enabled: boolean;
  uptime_monitoring_enabled: boolean;
  ssl_status: string;
  ssl_certificates: SslCertificate[];
  recent_ssl_checks: SslCheck[];
  created_at: string;
  updated_at: string;
}

interface Props {
  website: Website;
}

defineProps<Props>();
</script>

<template>
  <Head :title="`${website.name} - SSL Details`" />

  <DashboardLayout :title="website.name">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-foreground">{{ website.name }}</h1>
          <p class="text-muted-foreground">{{ website.url }}</p>
        </div>
        <div class="flex items-center space-x-3">
          <button class="btn btn-outline">Edit</button>
          <button class="btn btn-primary">Check SSL</button>
        </div>
      </div>

      <!-- SSL Status Overview -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="rounded-lg bg-card text-card-foreground p-6 shadow-sm">
          <h3 class="text-lg font-semibold mb-2">SSL Status</h3>
          <span
            class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium"
            :class="{
              'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': website.ssl_status === 'valid',
              'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': website.ssl_status === 'expired',
              'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': website.ssl_status === 'expiring_soon',
              'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200': website.ssl_status === 'unknown'
            }"
          >
            {{ website.ssl_status }}
          </span>
        </div>

        <div class="rounded-lg bg-card text-card-foreground p-6 shadow-sm">
          <h3 class="text-lg font-semibold mb-2">SSL Monitoring</h3>
          <span :class="website.ssl_monitoring_enabled ? 'text-green-600' : 'text-gray-500'">
            {{ website.ssl_monitoring_enabled ? 'Enabled' : 'Disabled' }}
          </span>
        </div>

        <div class="rounded-lg bg-card text-card-foreground p-6 shadow-sm">
          <h3 class="text-lg font-semibold mb-2">Uptime Monitoring</h3>
          <span :class="website.uptime_monitoring_enabled ? 'text-green-600' : 'text-gray-500'">
            {{ website.uptime_monitoring_enabled ? 'Enabled' : 'Disabled' }}
          </span>
        </div>
      </div>

      <!-- SSL Certificates -->
      <div class="rounded-lg bg-card text-card-foreground p-6 shadow-sm">
        <h3 class="text-lg font-semibold mb-4">SSL Certificates</h3>
        <div v-if="website.ssl_certificates.length === 0" class="text-center py-8 text-muted-foreground">
          No SSL certificates found
        </div>
        <div v-else class="space-y-4">
          <div
            v-for="cert in website.ssl_certificates"
            :key="cert.id"
            class="border border-border rounded-lg p-4"
          >
            <div class="flex items-center justify-between mb-2">
              <span
                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                :class="{
                  'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': cert.status === 'valid',
                  'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': cert.status === 'expired',
                  'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': cert.status === 'expiring_soon'
                }"
              >
                {{ cert.status }}
              </span>
              <span class="text-sm text-muted-foreground">
                {{ new Date(cert.created_at).toLocaleDateString() }}
              </span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
              <div>
                <strong>Issuer:</strong> {{ cert.issuer }}
              </div>
              <div>
                <strong>Expires:</strong> {{ new Date(cert.expires_at).toLocaleDateString() }}
              </div>
              <div class="md:col-span-2">
                <strong>Subject:</strong> {{ cert.subject }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent SSL Checks -->
      <div class="rounded-lg bg-card text-card-foreground p-6 shadow-sm">
        <h3 class="text-lg font-semibold mb-4">Recent SSL Checks</h3>
        <div v-if="website.recent_ssl_checks.length === 0" class="text-center py-8 text-muted-foreground">
          No SSL checks performed yet
        </div>
        <div v-else class="space-y-3">
          <div
            v-for="check in website.recent_ssl_checks"
            :key="check.id"
            class="flex items-center justify-between border border-border rounded-lg p-3"
          >
            <div class="flex items-center space-x-3">
              <span
                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                :class="{
                  'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': check.status === 'valid',
                  'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': check.status === 'failed',
                  'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': check.status === 'warning'
                }"
              >
                {{ check.status }}
              </span>
              <div>
                <div class="text-sm font-medium">
                  {{ new Date(check.checked_at).toLocaleString() }}
                </div>
                <div v-if="check.error_message" class="text-xs text-red-600">
                  {{ check.error_message }}
                </div>
              </div>
            </div>
            <div class="text-sm text-muted-foreground">
              {{ check.response_time }}ms
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>