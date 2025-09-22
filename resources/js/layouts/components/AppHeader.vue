<script setup lang="ts">
import { ref } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { useThemeStore } from '@/stores/theme'
import ThemeCustomizer from '@/components/ThemeCustomizer.vue'
import {
  Menu,
  Search,
  Bell,
  Settings,
  User,
  LogOut,
  Sun,
  Moon,
  ChevronDown,
  Palette,
  Shield,
  BarChart3,
  Users
} from 'lucide-vue-next'
import { horizontalMenuItems } from '@/config/navigation'

interface Props {
  title?: string
}

defineProps<Props>()

// Get authenticated user data
const page = usePage()
const user = page.props.auth.user

const themeStore = useThemeStore()

// Dropdown states
const showNotifications = ref(false)
const showUserMenu = ref(false)
const showSearch = ref(false)

// Theme customizer
const themeCustomizer = ref<InstanceType<typeof ThemeCustomizer> | null>(null)

// Mock notifications
const notifications = ref([
  {
    id: 1,
    title: 'SSL Certificate Expiring',
    message: 'example.com certificate expires in 7 days',
    time: '5 min ago',
    type: 'warning'
  },
  {
    id: 2,
    title: 'Monitor Down',
    message: 'api.example.com is unreachable',
    time: '15 min ago',
    type: 'error'
  },
  {
    id: 3,
    title: 'New Certificate Added',
    message: 'shop.example.com has been added to monitoring',
    time: '1 hour ago',
    type: 'success'
  }
])

function logout() {
  router.post('/logout')
}

function closeDropdowns() {
  showNotifications.value = false
  showUserMenu.value = false
  showSearch.value = false
}

function toggleCustomizer() {
  themeCustomizer.value?.toggleCustomizer()
}

// No need for hardcoded menu items anymore - using centralized config

// Active horizontal menu dropdown
const activeHorizontalDropdown = ref<string | null>(null)

function toggleHorizontalDropdown(itemKey: string) {
  activeHorizontalDropdown.value = activeHorizontalDropdown.value === itemKey ? null : itemKey
}

// Close dropdowns when clicking outside
function handleDocumentClick(event: Event) {
  const target = event.target as HTMLElement
  if (!target.closest('.dropdown')) {
    closeDropdowns()
    activeHorizontalDropdown.value = null
  }
}

// Add event listener
document.addEventListener('click', handleDocumentClick)
</script>

<template>
  <header class="z-40 bg-gradient-to-r from-slate-50 via-blue-50 to-indigo-50 dark:from-slate-900 dark:via-blue-900/30 dark:to-indigo-900/30 shadow-lg border-b border-blue-100 dark:border-blue-800">
    <div class="flex items-center justify-between p-4">

      <!-- Left side: Sidebar toggle + Title -->
      <div class="flex items-center space-x-4">
        <!-- Sidebar toggle button -->
        <button
          type="button"
          class="block rounded-xl bg-white/60 backdrop-blur-sm p-2.5 hover:bg-white/80 hover:text-blue-600 dark:bg-white/10 dark:hover:bg-white/20 dark:hover:text-blue-400 lg:hidden transition-all duration-300 shadow-sm border border-white/40 dark:border-white/10"
          data-sidebar-toggle
          @click="themeStore.toggleSidebar()"
        >
          <Menu class="h-5 w-5" />
        </button>

        <!-- Page title/breadcrumb -->
        <div class="hidden sm:block">
          <div class="flex items-center space-x-3">
            <div class="rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 p-2.5 shadow-lg">
              <BarChart3 v-if="title === 'Dashboard'" class="h-6 w-6 text-white" />
              <Shield v-else-if="title?.includes('SSL')" class="h-6 w-6 text-white" />
              <Users v-else-if="title?.includes('Team')" class="h-6 w-6 text-white" />
              <Settings v-else-if="title?.includes('Settings')" class="h-6 w-6 text-white" />
              <BarChart3 v-else class="h-6 w-6 text-white" />
            </div>
            <div>
              <h1 class="text-2xl font-bold bg-gradient-to-r from-gray-900 via-blue-800 to-indigo-800 dark:from-white dark:via-blue-200 dark:to-indigo-200 bg-clip-text text-transparent">
                {{ title || 'Dashboard' }}
              </h1>
              <p class="text-sm text-blue-600 dark:text-blue-300 font-medium">
                SSL Monitor v4
                <span v-if="title === 'Dashboard'">• Overview</span>
                <span v-else-if="title?.includes('SSL')">• Certificate Management</span>
                <span v-else-if="title?.includes('Team')">• Team Management</span>
                <span v-else-if="title?.includes('Settings')">• Application Settings</span>
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Right side: Search, Theme, Notifications, User menu -->
      <div class="flex items-center space-x-3">

        <!-- Search -->
        <div class="dropdown relative">
          <button
            type="button"
            class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/60 backdrop-blur-sm hover:bg-white/80 hover:text-blue-600 dark:bg-white/10 dark:hover:bg-white/20 dark:hover:text-blue-400 transition-all duration-300 shadow-sm border border-white/40 dark:border-white/10"
            @click="showSearch = !showSearch"
          >
            <Search class="h-4 w-4" />
          </button>

          <div
            v-show="showSearch"
            class="absolute top-12 right-0 z-50 w-80 rounded-xl bg-white/90 backdrop-blur-lg p-4 shadow-xl border border-white/60 dark:bg-gray-900/90 dark:border-gray-700"
          >
            <div class="relative">
              <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-blue-400" />
              <input
                type="text"
                placeholder="Search certificates, monitors..."
                class="w-full rounded-xl border border-blue-200 dark:border-blue-700 py-3 pl-10 pr-4 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-800 dark:bg-gray-800 dark:text-white bg-white/80"
              />
            </div>
          </div>
        </div>

        <!-- Theme toggle -->
        <button
          type="button"
          class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/60 backdrop-blur-sm hover:bg-white/80 hover:text-amber-600 dark:bg-white/10 dark:hover:bg-white/20 dark:hover:text-amber-400 transition-all duration-300 shadow-sm border border-white/40 dark:border-white/10"
          @click="themeStore.toggleTheme(themeStore.theme === 'dark' ? 'light' : 'dark')"
        >
          <Sun v-if="themeStore.resolvedTheme === 'dark'" class="h-4 w-4" />
          <Moon v-else class="h-4 w-4" />
        </button>

        <!-- Theme customizer toggle -->
        <button
          type="button"
          class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/60 backdrop-blur-sm hover:bg-white/80 hover:text-purple-600 dark:bg-white/10 dark:hover:bg-white/20 dark:hover:text-purple-400 transition-all duration-300 shadow-sm border border-white/40 dark:border-white/10"
          data-test="theme-customizer-toggle"
          @click="toggleCustomizer"
        >
          <Palette class="h-4 w-4" />
        </button>

        <!-- Notifications -->
        <div class="dropdown relative">
          <button
            type="button"
            class="relative flex h-10 w-10 items-center justify-center rounded-xl bg-white/60 backdrop-blur-sm hover:bg-white/80 hover:text-red-600 dark:bg-white/10 dark:hover:bg-white/20 dark:hover:text-red-400 transition-all duration-300 shadow-sm border border-white/40 dark:border-white/10"
            @click="showNotifications = !showNotifications"
          >
            <Bell class="h-4 w-4" />
            <!-- Notification badge -->
            <span class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-gradient-to-r from-red-500 to-pink-500 text-[10px] text-white font-bold shadow-lg">
              {{ notifications.length }}
            </span>
          </button>

          <div
            v-show="showNotifications"
            class="absolute top-12 right-0 z-50 w-80 rounded-xl bg-white/90 backdrop-blur-lg shadow-xl border border-white/60 dark:bg-gray-900/90 dark:border-gray-700"
          >
            <div class="border-b border-blue-100 dark:border-blue-800 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/30 dark:to-indigo-900/30 rounded-t-xl">
              <h3 class="font-bold text-blue-900 dark:text-blue-100">Notifications</h3>
            </div>

            <div class="max-h-64 overflow-y-auto">
              <div
                v-for="notification in notifications"
                :key="notification.id"
                class="border-b border-gray-100/60 dark:border-gray-700/60 p-4 hover:bg-white/60 dark:hover:bg-white/5 transition-all duration-300"
              >
                <div class="flex items-start space-x-3">
                  <div
                    class="mt-1 h-3 w-3 rounded-full shadow-sm"
                    :class="{
                      'bg-gradient-to-r from-amber-400 to-orange-500': notification.type === 'warning',
                      'bg-gradient-to-r from-red-400 to-pink-500': notification.type === 'error',
                      'bg-gradient-to-r from-emerald-400 to-green-500': notification.type === 'success'
                    }"
                  />
                  <div class="flex-1">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                      {{ notification.title }}
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                      {{ notification.message }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 font-medium">
                      {{ notification.time }}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <div class="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/30 dark:to-indigo-900/30 rounded-b-xl">
              <Link href="/notifications" class="text-sm font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">
                View all notifications →
              </Link>
            </div>
          </div>
        </div>

        <!-- User menu -->
        <div class="dropdown relative">
          <button
            type="button"
            class="flex items-center space-x-3 rounded-xl p-2.5 bg-white/60 backdrop-blur-sm hover:bg-white/80 transition-all duration-300 shadow-sm border border-white/40 dark:border-white/10 dark:bg-white/10 dark:hover:bg-white/20"
            @click="showUserMenu = !showUserMenu"
          >
            <div class="h-9 w-9 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg">
              <User class="h-5 w-5 text-white" />
            </div>
            <div class="hidden sm:block text-left">
              <p class="text-sm font-bold text-gray-900 dark:text-white">{{ user?.name }}</p>
              <p class="text-xs text-blue-600 dark:text-blue-300">Admin</p>
            </div>
            <ChevronDown class="h-4 w-4 text-gray-600 dark:text-gray-300" />
          </button>

          <div
            v-show="showUserMenu"
            class="absolute top-12 right-0 z-50 w-64 rounded-xl bg-white/90 backdrop-blur-lg shadow-xl border border-white/60 dark:bg-gray-900/90 dark:border-gray-700"
          >
            <div class="border-b border-blue-100 dark:border-blue-800 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/30 dark:to-indigo-900/30 rounded-t-xl">
              <div class="flex items-center space-x-3">
                <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg">
                  <User class="h-5 w-5 text-white" />
                </div>
                <div>
                  <p class="text-sm font-bold text-blue-900 dark:text-blue-100">{{ user?.name }}</p>
                  <p class="text-xs text-blue-600 dark:text-blue-300">{{ user?.email }}</p>
                </div>
              </div>
            </div>

            <div class="py-2">
              <Link
                href="/settings/profile"
                class="flex items-center space-x-3 px-4 py-3 text-sm font-medium text-gray-700 hover:bg-white/60 dark:text-gray-300 dark:hover:bg-white/5 transition-all duration-300"
              >
                <div class="rounded-lg bg-green-100 dark:bg-green-900/30 p-2">
                  <Settings class="h-4 w-4 text-green-600 dark:text-green-400" />
                </div>
                <span>Profile Settings</span>
              </Link>

              <button
                type="button"
                class="flex w-full items-center space-x-3 px-4 py-3 text-sm font-medium text-gray-700 hover:bg-white/60 dark:text-gray-300 dark:hover:bg-white/5 transition-all duration-300"
                @click="logout"
              >
                <div class="rounded-lg bg-red-100 dark:bg-red-900/30 p-2">
                  <LogOut class="h-4 w-4 text-red-600 dark:text-red-400" />
                </div>
                <span>Sign out</span>
              </button>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Horizontal Navigation Menu (shown only in horizontal mode) -->
    <div
      v-if="themeStore.menu === 'horizontal'"
      class="horizontal-menu border-t border-blue-200 dark:border-blue-800 bg-gradient-to-r from-white via-blue-50/50 to-indigo-50/50 dark:from-gray-900 dark:via-blue-900/20 dark:to-indigo-900/20 px-6 py-2 backdrop-blur-sm"
    >
      <nav class="flex space-x-6">
        <div
          v-for="item in horizontalMenuItems"
          :key="item.key"
          class="relative dropdown"
        >
          <!-- Items with children use button to toggle dropdown -->
          <button
            v-if="item.children"
            type="button"
            class="flex items-center space-x-2 px-4 py-2.5 text-sm font-semibold text-gray-700 transition-all duration-300 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400 rounded-xl hover:bg-white/60 dark:hover:bg-white/10"
            @click="toggleHorizontalDropdown(item.key)"
          >
            <component :is="item.icon" class="h-4 w-4" />
            <span>{{ item.title }}</span>
            <ChevronDown
              class="h-3 w-3 transition-transform duration-300"
              :class="{ 'rotate-180': activeHorizontalDropdown === item.key }"
            />
          </button>

          <!-- Items without children use Link for direct navigation -->
          <Link
            v-else
            :href="item.href || '#'"
            class="flex items-center space-x-2 px-4 py-2.5 text-sm font-semibold text-gray-700 transition-all duration-300 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400 rounded-xl hover:bg-white/60 dark:hover:bg-white/10"
          >
            <component :is="item.icon" class="h-4 w-4" />
            <span>{{ item.title }}</span>
          </Link>

          <!-- Dropdown menu -->
          <div
            v-if="item.children && activeHorizontalDropdown === item.key"
            class="absolute top-full left-0 z-50 mt-2 w-56 rounded-xl bg-white/90 backdrop-blur-lg shadow-xl border border-white/60 dark:bg-gray-900/90 dark:border-gray-700"
          >
            <div class="py-2">
              <Link
                v-for="child in item.children"
                :key="child.title"
                :href="child.href"
                class="block px-4 py-3 text-sm font-medium text-gray-700 hover:bg-white/60 dark:text-gray-300 dark:hover:bg-white/5 transition-all duration-300"
              >
                {{ child.title }}
              </Link>
            </div>
          </div>
        </div>
      </nav>
    </div>

    <!-- Theme Customizer -->
    <ThemeCustomizer ref="themeCustomizer" />
  </header>
</template>

<style scoped>
.dropdown {
  position: relative;
}

/* Transition animations */
.dropdown > div {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
</style>