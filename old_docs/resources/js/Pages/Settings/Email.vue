<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Email Settings</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Configure how and when you receive email notifications</p>
      </div>

      <!-- Email Notifications -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Email Notifications</h2>
          <p class="text-sm text-gray-600 dark:text-gray-400">Choose which notifications you want to receive via email.</p>
        </div>
        <form @submit.prevent="updateEmailSettings" class="p-6">
          <div class="space-y-6">
            <!-- SSL Certificate Notifications -->
            <div>
              <h3 class="text-base font-medium text-gray-900 dark:text-white mb-3">SSL Certificate Alerts</h3>
              <div class="space-y-3">
                <div class="flex items-center justify-between">
                  <div>
                    <label for="ssl_expiring_soon" class="text-sm font-medium text-gray-700 dark:text-gray-300">Certificate Expiring Soon</label>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Get notified when SSL certificates are about to expire</p>
                  </div>
                  <input
                    type="checkbox"
                    id="ssl_expiring_soon"
                    v-model="emailForm.ssl_expiring_soon"
                    class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  />
                </div>

                <div class="flex items-center justify-between">
                  <div>
                    <label for="ssl_expired" class="text-sm font-medium text-gray-700 dark:text-gray-300">Certificate Expired</label>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Get notified when SSL certificates have expired</p>
                  </div>
                  <input
                    type="checkbox"
                    id="ssl_expired"
                    v-model="emailForm.ssl_expired"
                    class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  />
                </div>

                <div class="flex items-center justify-between">
                  <div>
                    <label for="ssl_errors" class="text-sm font-medium text-gray-700 dark:text-gray-300">SSL Check Errors</label>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Get notified when SSL certificate checks fail</p>
                  </div>
                  <input
                    type="checkbox"
                    id="ssl_errors"
                    v-model="emailForm.ssl_errors"
                    class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  />
                </div>
              </div>
            </div>

            <!-- Uptime Notifications -->
            <div>
              <h3 class="text-base font-medium text-gray-900 dark:text-white mb-3">Uptime Monitoring</h3>
              <div class="space-y-3">
                <div class="flex items-center justify-between">
                  <div>
                    <label for="uptime_down" class="text-sm font-medium text-gray-700 dark:text-gray-300">Website Down</label>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Get notified when your websites go offline</p>
                  </div>
                  <input
                    type="checkbox"
                    id="uptime_down"
                    v-model="emailForm.uptime_down"
                    class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  />
                </div>

                <div class="flex items-center justify-between">
                  <div>
                    <label for="uptime_back_up" class="text-sm font-medium text-gray-700 dark:text-gray-300">Website Back Online</label>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Get notified when your websites come back online</p>
                  </div>
                  <input
                    type="checkbox"
                    id="uptime_back_up"
                    v-model="emailForm.uptime_back_up"
                    class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  />
                </div>

                <div class="flex items-center justify-between">
                  <div>
                    <label for="uptime_slow" class="text-sm font-medium text-gray-700 dark:text-gray-300">Slow Response Time</label>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Get notified when response times exceed thresholds</p>
                  </div>
                  <input
                    type="checkbox"
                    id="uptime_slow"
                    v-model="emailForm.uptime_slow"
                    class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  />
                </div>
              </div>
            </div>

            <!-- General Notifications -->
            <div>
              <h3 class="text-base font-medium text-gray-900 dark:text-white mb-3">General</h3>
              <div class="space-y-3">
                <div class="flex items-center justify-between">
                  <div>
                    <label for="weekly_reports" class="text-sm font-medium text-gray-700 dark:text-gray-300">Weekly Reports</label>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Receive weekly summary reports of your website status</p>
                  </div>
                  <input
                    type="checkbox"
                    id="weekly_reports"
                    v-model="emailForm.weekly_reports"
                    class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  />
                </div>

                <div class="flex items-center justify-between">
                  <div>
                    <label for="security_alerts" class="text-sm font-medium text-gray-700 dark:text-gray-300">Security Alerts</label>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Important security notifications and account changes</p>
                  </div>
                  <input
                    type="checkbox"
                    id="security_alerts"
                    v-model="emailForm.security_alerts"
                    class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  />
                </div>
              </div>
            </div>
          </div>

          <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
            <div class="flex justify-between items-center">
              <button
                type="button"
                @click="testEmail"
                :disabled="testForm.processing"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 dark:hover:bg-gray-500 rounded-md transition-colors"
              >
                <svg v-if="testForm.processing" class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                {{ testForm.processing ? 'Sending...' : 'Send Test Email' }}
              </button>

              <button
                type="submit"
                :disabled="emailForm.processing"
                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-medium rounded-md transition-colors"
              >
                <svg v-if="emailForm.processing" class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                {{ emailForm.processing ? 'Saving...' : 'Save Email Settings' }}
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  user: Object
})

// Initialize form with default values (these would come from user's email settings)
const emailForm = useForm({
  ssl_expiring_soon: true,
  ssl_expired: true,
  ssl_errors: true,
  uptime_down: true,
  uptime_back_up: true,
  uptime_slow: false,
  weekly_reports: true,
  security_alerts: true,
})

// Test email form
const testForm = useForm({})

function updateEmailSettings() {
  emailForm.patch('/settings/email', {
    preserveScroll: true,
    onSuccess: () => {
      // Handle success
    }
  })
}

function testEmail() {
  testForm.post('/settings/email/test', {
    preserveScroll: true,
    onSuccess: () => {
      // Handle success
    }
  })
}
</script>