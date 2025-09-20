<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-4">
            <div :class="[getSslStatusClass(website.latest_ssl_check), 'w-4 h-4 rounded-full flex-shrink-0']"></div>
            <div>
              <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ website.name }}</h1>
              <a :href="website.url" target="_blank" rel="noopener noreferrer" class="text-blue-600 dark:text-blue-400 hover:underline">
                {{ website.url }}
              </a>
            </div>
          </div>
          <div class="flex items-center space-x-3">
            <!-- Real-time indicator -->
            <div v-if="isPolling" class="flex items-center px-3 py-2 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-md text-sm font-medium">
              <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse mr-2"></div>
              Auto-updating
            </div>

            <button @click="checkNow" :disabled="isChecking" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-medium rounded-md transition-colors">
              <svg v-if="isChecking" class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
              </svg>
              <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
              </svg>
              {{ isChecking ? 'Checking...' : 'Check Now' }}
            </button>
            <Link :href="route('websites.index')" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 dark:hover:bg-gray-500 rounded-md transition-colors">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
              </svg>
              Back to Websites
            </Link>
          </div>
        </div>
      </div>

      <!-- Status Overview -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- SSL Status -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
              </div>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">SSL Certificate</p>
              <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ getSslStatusText(website.latest_ssl_check) }}</p>
              <p v-if="website.latest_ssl_check?.expires_at" class="text-sm text-gray-500 dark:text-gray-400">
                Expires: {{ formatDate(website.latest_ssl_check.expires_at) }}
              </p>
            </div>
          </div>
        </div>

        <!-- Uptime Status -->
        <div v-if="website.uptime_monitoring" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
              </div>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Uptime Status</p>
              <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ getUptimeStatusText(website.latest_uptime_check) }}</p>
              <p v-if="website.latest_uptime_check?.response_time_ms" class="text-sm text-gray-500 dark:text-gray-400">
                {{ website.latest_uptime_check.response_time_ms }}ms
              </p>
            </div>
          </div>
        </div>

        <!-- Days Until Expiry -->
        <div v-if="website.latest_ssl_check?.days_until_expiry !== null" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </div>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Days Until Expiry</p>
              <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ website.latest_ssl_check.days_until_expiry }}</p>
            </div>
          </div>
        </div>

        <!-- Last Checked -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </div>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Last Checked</p>
              <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ formatDate(getLastCheckedDate()) }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- SSL Certificate Details -->
      <div v-if="website.latest_ssl_check" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">SSL Certificate Details</h2>
        </div>
        <div class="p-6">
          <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white capitalize">{{ website.latest_ssl_check.status.replace('_', ' ') }}</dd>
            </div>
            <div v-if="website.latest_ssl_check.issuer">
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Issuer</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ website.latest_ssl_check.issuer }}</dd>
            </div>
            <div v-if="website.latest_ssl_check.expires_at">
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Expires At</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ formatDate(website.latest_ssl_check.expires_at) }}</dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Valid Certificate</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ website.latest_ssl_check.is_valid ? 'Yes' : 'No' }}</dd>
            </div>
            <div v-if="website.latest_ssl_check.error_message" class="md:col-span-2">
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Error Message</dt>
              <dd class="mt-1 text-sm text-red-600 dark:text-red-400">{{ website.latest_ssl_check.error_message }}</dd>
            </div>
          </dl>
        </div>
      </div>

      <!-- Uptime Details -->
      <div v-if="website.uptime_monitoring && website.latest_uptime_check" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Uptime Monitoring Details</h2>
        </div>
        <div class="p-6">
          <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white capitalize">{{ website.latest_uptime_check.status }}</dd>
            </div>
            <div v-if="website.latest_uptime_check.http_status_code">
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">HTTP Status Code</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ website.latest_uptime_check.http_status_code }}</dd>
            </div>
            <div v-if="website.latest_uptime_check.response_time_ms">
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Response Time</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ website.latest_uptime_check.response_time_ms }}ms</dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Content Check</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ website.latest_uptime_check.content_check_passed ? 'Passed' : 'Failed' }}</dd>
            </div>
            <div v-if="website.latest_uptime_check.error_message" class="md:col-span-2">
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Error Message</dt>
              <dd class="mt-1 text-sm text-red-600 dark:text-red-400">{{ website.latest_uptime_check.error_message }}</dd>
            </div>
          </dl>
        </div>
      </div>

      <!-- Check History -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- SSL Check History -->
        <div v-if="website.ssl_checks && website.ssl_checks.length > 0" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
          <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">SSL Check History</h2>
          </div>
          <div class="p-6">
            <div class="space-y-4">
              <div v-for="check in website.ssl_checks" :key="check.id" class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div class="flex items-center space-x-3">
                  <div :class="[getSslStatusClass(check), 'w-3 h-3 rounded-full']"></div>
                  <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white capitalize">{{ check.status.replace('_', ' ') }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ formatDate(check.checked_at) }}</p>
                  </div>
                </div>
                <div v-if="check.days_until_expiry !== null" class="text-right">
                  <p class="text-sm font-medium text-gray-900 dark:text-white">{{ check.days_until_expiry }} days</p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">until expiry</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Uptime Check History -->
        <div v-if="website.uptime_monitoring && website.uptime_checks && website.uptime_checks.length > 0" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
          <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Uptime Check History</h2>
          </div>
          <div class="p-6">
            <div class="space-y-4">
              <div v-for="check in website.uptime_checks" :key="check.id" class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div class="flex items-center space-x-3">
                  <div :class="[getUptimeStatusClass(check), 'w-3 h-3 rounded-full']"></div>
                  <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white capitalize">{{ check.status }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ formatDate(check.checked_at) }}</p>
                  </div>
                </div>
                <div v-if="check.response_time_ms" class="text-right">
                  <p class="text-sm font-medium text-gray-900 dark:text-white">{{ check.response_time_ms }}ms</p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">response time</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Website Configuration -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Configuration</h2>
        </div>
        <div class="p-6">
          <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Uptime Monitoring</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ website.uptime_monitoring ? 'Enabled' : 'Disabled' }}</dd>
            </div>
            <div v-if="website.uptime_monitoring">
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">JavaScript Enabled</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ website.javascript_enabled ? 'Yes' : 'No' }}</dd>
            </div>
            <div v-if="website.uptime_monitoring && website.expected_status_code">
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Expected Status Code</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ website.expected_status_code }}</dd>
            </div>
            <div v-if="website.uptime_monitoring && website.max_response_time">
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Max Response Time</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ website.max_response_time }}ms</dd>
            </div>
            <div v-if="website.uptime_monitoring && website.expected_content" class="md:col-span-2">
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Expected Content</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ website.expected_content }}</dd>
            </div>
            <div v-if="website.uptime_monitoring && website.forbidden_content" class="md:col-span-2">
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Forbidden Content</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ website.forbidden_content }}</dd>
            </div>
            <div v-if="website.team">
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Team</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ website.team.name }}</dd>
            </div>
            <div v-if="website.added_by">
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Added By</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ website.added_by.name }}</dd>
            </div>
          </dl>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  website: Object
})

const isChecking = ref(false)
const isPolling = ref(false)
let pollInterval = null

function getSslStatusClass(sslCheck) {
  if (!sslCheck) return 'bg-gray-400'

  const statusClasses = {
    'valid': 'bg-green-500',
    'expiring_soon': 'bg-yellow-500',
    'expired': 'bg-red-500',
    'error': 'bg-red-500',
    'pending': 'bg-gray-400'
  }

  return statusClasses[sslCheck.status] || 'bg-gray-400'
}

function getSslStatusText(sslCheck) {
  if (!sslCheck) return 'Not checked'

  const statusTexts = {
    'valid': 'Valid',
    'expiring_soon': 'Expiring soon',
    'expired': 'Expired',
    'error': 'Error',
    'pending': 'Checking...'
  }

  return statusTexts[sslCheck.status] || 'Unknown'
}

function getUptimeStatusClass(uptimeCheck) {
  if (!uptimeCheck) return 'bg-gray-400'

  const statusClasses = {
    'up': 'bg-green-500',
    'down': 'bg-red-500',
    'slow': 'bg-yellow-500',
    'error': 'bg-red-500'
  }

  return statusClasses[uptimeCheck.status] || 'bg-gray-400'
}

function getUptimeStatusText(uptimeCheck) {
  if (!uptimeCheck) return 'Not checked'

  const statusTexts = {
    'up': 'Online',
    'down': 'Offline',
    'slow': 'Slow',
    'error': 'Error'
  }

  return statusTexts[uptimeCheck.status] || uptimeCheck.status
}

function formatDate(dateString) {
  if (!dateString) return 'Never'
  return new Date(dateString).toLocaleDateString()
}

function getLastCheckedDate() {
  const sslDate = props.website.latest_ssl_check?.checked_at
  const uptimeDate = props.website.latest_uptime_check?.checked_at

  if (!sslDate && !uptimeDate) return null
  if (!sslDate) return uptimeDate
  if (!uptimeDate) return sslDate

  return new Date(sslDate) > new Date(uptimeDate) ? sslDate : uptimeDate
}

async function checkNow() {
  isChecking.value = true

  try {
    // Trigger SSL check
    await fetch(route('websites.check-certificate'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({ url: props.website.url })
    })

    // Trigger uptime check if enabled
    if (props.website.uptime_monitoring) {
      await fetch(route('websites.check-uptime', props.website.id), {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
      })
    }

    // Reload the page to show updated data
    router.reload()
  } catch (error) {
    console.error('Error checking website:', error)
  } finally {
    isChecking.value = false
  }
}

function startPolling() {
  if (pollInterval) return

  isPolling.value = true
  pollInterval = setInterval(async () => {
    try {
      // Fetch latest website data without page reload
      const response = await fetch(route('websites.show', props.website.id), {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      })

      if (response.ok) {
        const data = await response.json()
        // Update website data if there are changes
        if (data.props?.website) {
          const newWebsite = data.props.website
          const currentLastCheck = getLastCheckedDate()
          const newLastCheck = getLastCheckedDateFromWebsite(newWebsite)

          // Only reload if there's actually new data
          if (newLastCheck && currentLastCheck !== newLastCheck) {
            router.reload({ only: ['website'] })
          }
        }
      }
    } catch (error) {
      console.error('Polling error:', error)
    }
  }, 10000) // Poll every 10 seconds
}

function stopPolling() {
  if (pollInterval) {
    clearInterval(pollInterval)
    pollInterval = null
    isPolling.value = false
  }
}

function getLastCheckedDateFromWebsite(website) {
  const sslDate = website.latest_ssl_check?.checked_at
  const uptimeDate = website.latest_uptime_check?.checked_at

  if (!sslDate && !uptimeDate) return null
  if (!sslDate) return uptimeDate
  if (!uptimeDate) return sslDate

  return new Date(sslDate) > new Date(uptimeDate) ? sslDate : uptimeDate
}

// Auto-start polling when component mounts
onMounted(() => {
  startPolling()
})

// Clean up when component unmounts
onUnmounted(() => {
  stopPolling()
})

// Stop polling when page becomes hidden, restart when visible
if (typeof document !== 'undefined') {
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
      stopPolling()
    } else {
      startPolling()
    }
  })
}
</script>