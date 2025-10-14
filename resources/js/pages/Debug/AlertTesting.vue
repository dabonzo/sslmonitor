<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { Head } from '@inertiajs/vue3'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import {
  Mail,
  Activity,
  Shield,
  Globe,
  Timer,
  Send,
  RefreshCw,
  ExternalLink,
  Settings
} from 'lucide-vue-next'

interface Website {
  id: number
  name: string
  url: string
  ssl_monitoring_enabled: boolean
  uptime_monitoring_enabled: boolean
}

interface AlertType {
  name: string
  description: string
  levels: Array<{
    days?: number
    status?: string
    response_time?: number
    level: string
    label: string
  }>
}

interface Props {
  websites: Website[]
  alertTypes: Record<string, AlertType>
  stats: {
    total_websites: number
    ssl_monitoring_enabled: number
    uptime_monitoring_enabled: number
  }
}

defineProps<Props>()

const page = usePage()

// Form state
const selectedWebsite = ref<number | null>(null)
const selectedSslDays = ref<number[]>([7, 3, 0])
const selectedUptimeStatus = ref<string[]>(['down'])
const selectedResponseTimes = ref<number[]>([5000, 10000])

// Loading states
const isLoading = ref(false)
const testResults = ref<any[]>([])

// Computed properties
const selectedWebsiteData = computed(() => {
  return page.props.websites?.find((w: Website) => w.id === selectedWebsite.value)
})

// Test all alerts
async function testAllAlerts() {
  if (!selectedWebsite.value) return

  isLoading.value = true
  testResults.value = []

  try {
    const response = await fetch(`/debug/alerts/test-all`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        website_id: selectedWebsite.value
      })
    })

    if (!response.ok) {
      // Handle HTTP errors (419 CSRF, 500, etc.)
      const errorText = await response.text()
      console.error('HTTP Error:', response.status, errorText)

      // Check if it's a CSRF error
      if (response.status === 419) {
        throw new Error('CSRF token mismatch. Please refresh the page and try again.')
      }

      throw new Error(`Server error: ${response.status}`)
    }

    const result = await response.json()

    if (result.success) {
      testResults.value = result.results || []

      // Show success message
      const message = document.createElement('div')
      message.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center space-x-2'
      message.innerHTML = `
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        <span>${result.message}</span>
        <a href="${result.mailpit_url}" target="_blank" class="ml-2 underline hover:no-underline">View in Mailpit</a>
      `
      document.body.appendChild(message)

      setTimeout(() => {
        document.body.removeChild(message)
      }, 5000)
    } else {
      throw new Error(result.message)
    }
  } catch (error: any) {
    console.error('Test failed:', error)

    // Handle JSON parsing errors specifically
    if (error.message.includes('Unexpected token') || error.message.includes('JSON')) {
      console.error('JSON parsing error. This usually means the server returned HTML instead of JSON.')
      console.error('Check the browser console for the actual response.')
    }

    const message = document.createElement('div')
    message.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50'
    message.textContent = error.message || 'Test failed'
    document.body.appendChild(message)

    setTimeout(() => {
      document.body.removeChild(message)
    }, 5000)
  } finally {
    isLoading.value = false
  }
}

// Test SSL alerts
async function testSslAlerts() {
  if (!selectedWebsite.value || selectedSslDays.value.length === 0) return

  isLoading.value = true

  try {
    const response = await fetch(`/debug/alerts/test-ssl`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        website_id: selectedWebsite.value,
        days: selectedSslDays.value
      })
    })

    const result = await response.json()

    if (result.success) {
      const message = document.createElement('div')
      message.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center space-x-2'
      message.innerHTML = `
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        <span>${result.message}</span>
        <a href="${result.mailpit_url}" target="_blank" class="ml-2 underline hover:no-underline">View in Mailpit</a>
      `
      document.body.appendChild(message)

      setTimeout(() => {
        document.body.removeChild(message)
      }, 5000)
    }
  } catch (error: any) {
    const message = document.createElement('div')
    message.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50'
    message.textContent = error.message || 'SSL test failed'
    document.body.appendChild(message)

    setTimeout(() => {
      document.body.removeChild(message)
    }, 5000)
  } finally {
    isLoading.value = false
  }
}

// Test uptime alerts
async function testUptimeAlerts() {
  if (!selectedWebsite.value || selectedUptimeStatus.value.length === 0) return

  isLoading.value = true

  try {
    const response = await fetch(`/debug/alerts/test-uptime`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        website_id: selectedWebsite.value,
        status: selectedUptimeStatus.value
      })
    })

    const result = await response.json()

    if (result.success) {
      const message = document.createElement('div')
      message.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center space-x-2'
      message.innerHTML = `
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        <span>${result.message}</span>
        <a href="${result.mailpit_url}" target="_blank" class="ml-2 underline hover:no-underline">View in Mailpit</a>
      `
      document.body.appendChild(message)

      setTimeout(() => {
        document.body.removeChild(message)
      }, 5000)
    }
  } catch (error: any) {
    const message = document.createElement('div')
    message.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50'
    message.textContent = error.message || 'Uptime test failed'
    document.body.appendChild(message)

    setTimeout(() => {
      document.body.removeChild(message)
    }, 5000)
  } finally {
    isLoading.value = false
  }
}

// Test response time alerts
async function testResponseTimeAlerts() {
  if (!selectedWebsite.value || selectedResponseTimes.value.length === 0) return

  isLoading.value = true

  try {
    const response = await fetch(`/debug/alerts/test-response-time`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        website_id: selectedWebsite.value,
        response_times: selectedResponseTimes.value
      })
    })

    const result = await response.json()

    if (result.success) {
      const message = document.createElement('div')
      message.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center space-x-2'
      message.innerHTML = `
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        <span>${result.message}</span>
        <a href="${result.mailpit_url}" target="_blank" class="ml-2 underline hover:no-underline">View in Mailpit</a>
      `
      document.body.appendChild(message)

      setTimeout(() => {
        document.body.removeChild(message)
      }, 5000)
    }
  } catch (error: any) {
    const message = document.createElement('div')
    message.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50'
    message.textContent = error.message || 'Response time test failed'
    document.body.appendChild(message)

    setTimeout(() => {
      document.body.removeChild(message)
    }, 5000)
  } finally {
    isLoading.value = false
  }
}

// Toggle SSL day selection
function toggleSslDay(day: number) {
  const index = selectedSslDays.value.indexOf(day)
  if (index > -1) {
    selectedSslDays.value.splice(index, 1)
  } else {
    selectedSslDays.value.push(day)
  }
}

// Toggle uptime status selection
function toggleUptimeStatus(status: string) {
  const index = selectedUptimeStatus.value.indexOf(status)
  if (index > -1) {
    selectedUptimeStatus.value.splice(index, 1)
  } else {
    selectedUptimeStatus.value.push(status)
  }
}

// Toggle response time selection
function toggleResponseTime(time: number) {
  const index = selectedResponseTimes.value.indexOf(time)
  if (index > -1) {
    selectedResponseTimes.value.splice(index, 1)
  } else {
    selectedResponseTimes.value.push(time)
  }
}
</script>

<template>
  <Head title="Alert Testing" />

  <DashboardLayout title="Alert Testing">
    <div class="debug-alert-testing space-y-6">
      <!-- Page Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-foreground">Alert Testing</h1>
          <p class="text-muted-foreground">DEBUG • Test all email alert templates for your monitoring configuration</p>
        </div>

        <!-- Quick Navigation Links -->
        <div class="flex items-center space-x-2">
          <Link
            href="http://localhost:8025"
            target="_blank"
            class="flex items-center space-x-2 glass-card hover:bg-card px-4 py-2 rounded-xl transition-all duration-300 button-ghost"
          >
            <ExternalLink class="h-4 w-4 text-primary" />
            <span class="text-sm font-medium text-primary">Mailpit</span>
          </Link>
          <Link
            href="/debug/ssl-overrides"
            class="flex items-center space-x-2 glass-card hover:bg-card px-4 py-2 rounded-xl transition-all duration-300 button-ghost"
          >
            <Settings class="h-4 w-4 text-primary" />
            <span class="text-sm font-medium text-primary">SSL Overrides</span>
          </Link>
        </div>
      </div>

      <!-- Main Content -->

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="glass-card-strong rounded-2xl p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Total Websites</p>
                <p class="text-2xl font-bold text-foreground">{{ stats.total_websites }}</p>
              </div>
              <div class="status-badge-info p-3">
                <Globe class="h-6 w-6" />
              </div>
            </div>
          </div>

          <div class="glass-card-strong rounded-2xl p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-muted-foreground">SSL Monitoring</p>
                <p class="text-2xl font-bold text-foreground">{{ stats.ssl_monitoring_enabled }}</p>
              </div>
              <div class="status-badge-success p-3">
                <Shield class="h-6 w-6" />
              </div>
            </div>
          </div>

          <div class="glass-card-strong rounded-2xl p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Uptime Monitoring</p>
                <p class="text-2xl font-bold text-foreground">{{ stats.uptime_monitoring_enabled }}</p>
              </div>
              <div class="status-badge-info p-3">
                <Activity class="h-6 w-6" />
              </div>
            </div>
          </div>
        </div>

        <!-- Website Selection -->
        <div class="glass-card-strong rounded-2xl p-6">
          <h2 class="text-xl font-bold text-foreground mb-4">Select Website for Testing</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
              v-for="website in websites"
              :key="website.id"
              @click="selectedWebsite = website.id"
              :class="[
                'p-4 rounded-xl border-2 cursor-pointer transition-all duration-300',
                selectedWebsite === website.id
                  ? 'border-primary bg-primary/10 selected-state'
                  : 'border-border hover:border-border hover-lift'
              ]"
            >
              <div class="flex items-center justify-between">
                <div>
                  <h3 class="font-semibold text-foreground">{{ website.name }}</h3>
                  <p class="text-sm text-muted-foreground truncate">{{ website.url }}</p>
                </div>
                <div class="flex space-x-2">
                  <div
                    v-if="website.ssl_monitoring_enabled"
                    class="status-badge-success p-2"
                  >
                    <Shield class="h-4 w-4" />
                  </div>
                  <div
                    v-if="website.uptime_monitoring_enabled"
                    class="status-badge-info p-2"
                  >
                    <Activity class="h-4 w-4" />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Alert Testing Controls -->
        <div v-if="selectedWebsite" class="space-y-6">
          <!-- Quick Actions -->
          <div class="glass-card-strong rounded-2xl p-6">
            <h2 class="text-xl font-bold text-foreground mb-4">Quick Actions</h2>
            <div class="flex flex-wrap gap-4">
              <button
                @click="testAllAlerts"
                :disabled="isLoading"
                class="flex items-center space-x-2 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white px-6 py-3 rounded-xl transition-all duration-300 shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <Send class="h-5 w-5" />
                <span>Test All Alerts</span>
                <RefreshCw v-if="isLoading" class="h-4 w-4 animate-spin" />
              </button>
            </div>
            <p class="text-sm text-muted-foreground mt-2">
              Sends all available alert types (SSL, Uptime, Response Time) for <strong class="text-foreground">{{ selectedWebsiteData?.name }}</strong>
            </p>
          </div>

          <!-- SSL Certificate Alerts -->
          <div class="glass-card-strong rounded-2xl p-6">
            <h2 class="text-xl font-bold text-foreground mb-4 flex items-center space-x-2">
              <div class="status-badge-info p-2">
                <Shield class="h-6 w-6" />
              </div>
              <span>SSL Certificate Alerts</span>
            </h2>

            <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-4">
              <div
                v-for="level in alertTypes.ssl_expiry.levels"
                :key="level.days"
                @click="toggleSslDay(level.days!)"
                :class="[
                  'p-3 rounded-xl border-2 cursor-pointer transition-all duration-300',
                  selectedSslDays.includes(level.days!)
                    ? 'border-primary bg-primary/10 selected-state'
                    : 'border-border hover:border-border hover-lift'
                ]"
              >
                <div class="text-center">
                  <p class="font-semibold text-foreground">{{ level.days }} days</p>
                  <p class="text-xs text-muted-foreground">{{ level.level }}</p>
                </div>
              </div>
            </div>

            <button
              @click="testSslAlerts"
              :disabled="isLoading || selectedSslDays.length === 0"
              class="flex items-center space-x-2 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <Send class="h-4 w-4" />
              <span>Test SSL Alerts ({{ selectedSslDays.length }} selected)</span>
            </button>
          </div>

          <!-- Uptime Alerts -->
          <div class="glass-card-strong rounded-2xl p-6">
            <h2 class="text-xl font-bold text-foreground mb-4 flex items-center space-x-2">
              <div class="status-badge-success p-2">
                <Activity class="h-6 w-6" />
              </div>
              <span>Uptime Alerts</span>
            </h2>

            <div class="grid grid-cols-2 md:grid-cols-2 gap-3 mb-4">
              <div
                v-for="level in alertTypes.uptime_down.levels"
                :key="level.status"
                @click="toggleUptimeStatus(level.status!)"
                :class="[
                  'p-3 rounded-xl border-2 cursor-pointer transition-all duration-300',
                  selectedUptimeStatus.includes(level.status!)
                    ? 'border-primary bg-primary/10 selected-state'
                    : 'border-border hover:border-border hover-lift'
                ]"
              >
                <div class="text-center">
                  <p class="font-semibold text-foreground capitalize">{{ level.status }}</p>
                  <p class="text-xs text-muted-foreground">{{ level.level }}</p>
                </div>
              </div>
            </div>

            <button
              @click="testUptimeAlerts"
              :disabled="isLoading || selectedUptimeStatus.length === 0"
              class="flex items-center space-x-2 bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <Send class="h-4 w-4" />
              <span>Test Uptime Alerts ({{ selectedUptimeStatus.length }} selected)</span>
            </button>
          </div>

          <!-- Response Time Alerts -->
          <div class="glass-card-strong rounded-2xl p-6">
            <h2 class="text-xl font-bold text-foreground mb-4 flex items-center space-x-2">
              <div class="status-badge-warning p-2">
                <Timer class="h-6 w-6" />
              </div>
              <span>Response Time Alerts</span>
            </h2>

            <div class="grid grid-cols-2 md:grid-cols-2 gap-3 mb-4">
              <div
                v-for="level in alertTypes.response_time.levels"
                :key="level.response_time"
                @click="toggleResponseTime(level.response_time!)"
                :class="[
                  'p-3 rounded-xl border-2 cursor-pointer transition-all duration-300',
                  selectedResponseTimes.includes(level.response_time!)
                    ? 'border-primary bg-primary/10 selected-state'
                    : 'border-border hover:border-border hover-lift'
                ]"
              >
                <div class="text-center">
                  <p class="font-semibold text-foreground">{{ level.response_time }}ms</p>
                  <p class="text-xs text-muted-foreground">{{ level.level }}</p>
                </div>
              </div>
            </div>

            <button
              @click="testResponseTimeAlerts"
              :disabled="isLoading || selectedResponseTimes.length === 0"
              class="flex items-center space-x-2 bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <Send class="h-4 w-4" />
              <span>Test Response Time Alerts ({{ selectedResponseTimes.length }} selected)</span>
            </button>
          </div>
        </div>

        <!-- No Website Selected -->
        <div v-else class="glass-card-strong rounded-2xl p-12 text-center">
          <div class="status-badge-destructive p-4 inline-block mb-4">
            <Globe class="h-16 w-16" />
          </div>
          <h3 class="text-xl font-semibold text-foreground mb-2">Select a Website</h3>
          <p class="text-muted-foreground">Choose a website above to start testing alerts</p>
        </div>
    </div>
  </DashboardLayout>
</template>