<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Appearance Settings</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Customize how the application looks and feels</p>
      </div>

      <!-- Theme Settings -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Theme</h2>
          <p class="text-sm text-gray-600 dark:text-gray-400">Choose your preferred color scheme</p>
        </div>
        <div class="p-6">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div
              @click="setTheme('light')"
              :class="[
                'cursor-pointer border-2 rounded-lg p-4 transition-colors',
                currentTheme === 'light'
                  ? 'border-blue-500 bg-blue-50 dark:bg-blue-900'
                  : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
              ]"
            >
              <div class="bg-white rounded border border-gray-200 h-24 mb-3 flex items-center justify-center">
                <div class="text-gray-900 text-sm">Light Theme</div>
              </div>
              <div class="flex items-center space-x-2">
                <input
                  type="radio"
                  :checked="currentTheme === 'light'"
                  class="text-blue-600"
                  readonly
                />
                <span class="text-sm font-medium text-gray-900 dark:text-white">Light</span>
              </div>
            </div>

            <div
              @click="setTheme('dark')"
              :class="[
                'cursor-pointer border-2 rounded-lg p-4 transition-colors',
                currentTheme === 'dark'
                  ? 'border-blue-500 bg-blue-50 dark:bg-blue-900'
                  : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
              ]"
            >
              <div class="bg-gray-800 rounded border border-gray-600 h-24 mb-3 flex items-center justify-center">
                <div class="text-white text-sm">Dark Theme</div>
              </div>
              <div class="flex items-center space-x-2">
                <input
                  type="radio"
                  :checked="currentTheme === 'dark'"
                  class="text-blue-600"
                  readonly
                />
                <span class="text-sm font-medium text-gray-900 dark:text-white">Dark</span>
              </div>
            </div>

            <div
              @click="setTheme('system')"
              :class="[
                'cursor-pointer border-2 rounded-lg p-4 transition-colors',
                currentTheme === 'system'
                  ? 'border-blue-500 bg-blue-50 dark:bg-blue-900'
                  : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
              ]"
            >
              <div class="bg-gradient-to-r from-white to-gray-800 rounded border border-gray-400 h-24 mb-3 flex items-center justify-center">
                <div class="text-gray-600 text-sm">System</div>
              </div>
              <div class="flex items-center space-x-2">
                <input
                  type="radio"
                  :checked="currentTheme === 'system'"
                  class="text-blue-600"
                  readonly
                />
                <span class="text-sm font-medium text-gray-900 dark:text-white">System</span>
              </div>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Follows your device setting</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Layout Settings -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Layout</h2>
          <p class="text-sm text-gray-600 dark:text-gray-400">Customize the layout and display options</p>
        </div>
        <div class="p-6 space-y-6">
          <!-- Compact Mode -->
          <div class="flex items-center justify-between">
            <div>
              <label for="compact_mode" class="text-sm font-medium text-gray-700 dark:text-gray-300">Compact Mode</label>
              <p class="text-sm text-gray-600 dark:text-gray-400">Reduce spacing and make the interface more dense</p>
            </div>
            <input
              type="checkbox"
              id="compact_mode"
              v-model="layoutForm.compact_mode"
              @change="updateLayout"
              class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            />
          </div>

          <!-- Show Website Favicons -->
          <div class="flex items-center justify-between">
            <div>
              <label for="show_favicons" class="text-sm font-medium text-gray-700 dark:text-gray-300">Show Website Favicons</label>
              <p class="text-sm text-gray-600 dark:text-gray-400">Display website favicons in lists and cards</p>
            </div>
            <input
              type="checkbox"
              id="show_favicons"
              v-model="layoutForm.show_favicons"
              @change="updateLayout"
              class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            />
          </div>

          <!-- Animation Speed -->
          <div>
            <label for="animation_speed" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Animation Speed</label>
            <select
              id="animation_speed"
              v-model="layoutForm.animation_speed"
              @change="updateLayout"
              class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
              <option value="disabled">Disabled</option>
              <option value="slow">Slow</option>
              <option value="normal">Normal</option>
              <option value="fast">Fast</option>
            </select>
          </div>

          <!-- Items per Page -->
          <div>
            <label for="items_per_page" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Items per Page</label>
            <select
              id="items_per_page"
              v-model="layoutForm.items_per_page"
              @change="updateLayout"
              class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
              <option :value="10">10</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Dashboard Settings -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Dashboard</h2>
          <p class="text-sm text-gray-600 dark:text-gray-400">Customize your dashboard experience</p>
        </div>
        <div class="p-6 space-y-6">
          <!-- Auto-refresh Dashboard -->
          <div class="flex items-center justify-between">
            <div>
              <label for="auto_refresh" class="text-sm font-medium text-gray-700 dark:text-gray-300">Auto-refresh Dashboard</label>
              <p class="text-sm text-gray-600 dark:text-gray-400">Automatically refresh dashboard data every 30 seconds</p>
            </div>
            <input
              type="checkbox"
              id="auto_refresh"
              v-model="dashboardForm.auto_refresh"
              @change="updateDashboard"
              class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            />
          </div>

          <!-- Show Recent Activity -->
          <div class="flex items-center justify-between">
            <div>
              <label for="show_recent_activity" class="text-sm font-medium text-gray-700 dark:text-gray-300">Show Recent Activity</label>
              <p class="text-sm text-gray-600 dark:text-gray-400">Display recent SSL checks and uptime events</p>
            </div>
            <input
              type="checkbox"
              id="show_recent_activity"
              v-model="dashboardForm.show_recent_activity"
              @change="updateDashboard"
              class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            />
          </div>

          <!-- Default Time Range -->
          <div>
            <label for="default_time_range" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Default Time Range</label>
            <select
              id="default_time_range"
              v-model="dashboardForm.default_time_range"
              @change="updateDashboard"
              class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
              <option value="24h">Last 24 hours</option>
              <option value="7d">Last 7 days</option>
              <option value="30d">Last 30 days</option>
              <option value="90d">Last 90 days</option>
            </select>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  user: Object
})

const currentTheme = ref('system')

// Layout form
const layoutForm = useForm({
  compact_mode: false,
  show_favicons: true,
  animation_speed: 'normal',
  items_per_page: 25,
})

// Dashboard form
const dashboardForm = useForm({
  auto_refresh: true,
  show_recent_activity: true,
  default_time_range: '7d',
})

onMounted(() => {
  // Load current theme from localStorage or system preference
  const savedTheme = localStorage.getItem('theme')
  if (savedTheme) {
    currentTheme.value = savedTheme
  } else {
    currentTheme.value = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
  }
})

function setTheme(theme) {
  currentTheme.value = theme
  localStorage.setItem('theme', theme)

  if (theme === 'dark') {
    document.documentElement.classList.add('dark')
  } else if (theme === 'light') {
    document.documentElement.classList.remove('dark')
  } else {
    // System theme
    const isDark = window.matchMedia('(prefers-color-scheme: dark)').matches
    if (isDark) {
      document.documentElement.classList.add('dark')
    } else {
      document.documentElement.classList.remove('dark')
    }
  }

  // Save to server
  layoutForm.patch('/settings/appearance/theme', {
    preserveScroll: true,
    data: { theme }
  })
}

function updateLayout() {
  layoutForm.patch('/settings/appearance/layout', {
    preserveScroll: true
  })
}

function updateDashboard() {
  dashboardForm.patch('/settings/appearance/dashboard', {
    preserveScroll: true
  })
}
</script>