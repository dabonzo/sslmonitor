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
  Monitor,
  ChevronDown,
  Palette,
  Shield,
  BarChart3,
  AlertTriangle,
  Users
} from 'lucide-vue-next'

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

// Horizontal menu items (for horizontal navigation mode)
const horizontalMenuItems = [
  {
    title: 'Dashboard',
    icon: BarChart3,
    href: '/dashboard',
    children: [
      { title: 'Overview', href: '/dashboard' },
      { title: 'Analytics', href: '/analytics' },
      { title: 'Reports', href: '/reports' }
    ]
  },
  {
    title: 'SSL Certificates',
    icon: Shield,
    href: '/certificates',
    children: [
      { title: 'All Certificates', href: '/certificates' },
      { title: 'Expiring Soon', href: '/certificates/expiring' },
      { title: 'Add Certificate', href: '/certificates/create' }
    ]
  },
  {
    title: 'Monitoring',
    icon: Monitor,
    href: '/monitors',
    children: [
      { title: 'All Monitors', href: '/monitors' },
      { title: 'Uptime Checks', href: '/monitors/uptime' },
      { title: 'Add Monitor', href: '/monitors/create' }
    ]
  },
  {
    title: 'Alerts',
    icon: AlertTriangle,
    href: '/alerts',
    children: [
      { title: 'Alert Rules', href: '/alerts' },
      { title: 'Notifications', href: '/alerts/notifications' },
      { title: 'History', href: '/alerts/history' }
    ]
  },
  {
    title: 'Team',
    icon: Users,
    href: '/team',
    children: [
      { title: 'Members', href: '/team' },
      { title: 'Roles', href: '/team/roles' },
      { title: 'Invitations', href: '/team/invitations' }
    ]
  }
]

// Active horizontal menu dropdown
const activeHorizontalDropdown = ref<string | null>(null)

function toggleHorizontalDropdown(itemTitle: string) {
  activeHorizontalDropdown.value = activeHorizontalDropdown.value === itemTitle ? null : itemTitle
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
  <header class="z-40 bg-white shadow-sm dark:bg-[#0e1726]">
    <div class="flex items-center justify-between p-4">

      <!-- Left side: Sidebar toggle + Title -->
      <div class="flex items-center space-x-4">
        <!-- Sidebar toggle button -->
        <button
          type="button"
          class="block rounded-full bg-white-light/40 p-2 hover:bg-white-light hover:text-primary dark:bg-dark/40 dark:hover:bg-dark dark:hover:text-primary lg:hidden"
          data-sidebar-toggle
          @click="themeStore.toggleSidebar()"
        >
          <Menu class="h-5 w-5" />
        </button>

        <!-- Page title/breadcrumb -->
        <div class="hidden sm:block">
          <h1 class="text-xl font-semibold text-[#3b3f5c] dark:text-white-light">
            {{ title || 'Dashboard' }}
          </h1>
        </div>
      </div>

      <!-- Right side: Search, Theme, Notifications, User menu -->
      <div class="flex items-center space-x-2">

        <!-- Search -->
        <div class="dropdown relative">
          <button
            type="button"
            class="flex h-9 w-9 items-center justify-center rounded-full bg-white-light/40 hover:bg-white-light hover:text-primary dark:bg-dark/40 dark:hover:bg-dark dark:hover:text-primary"
            @click="showSearch = !showSearch"
          >
            <Search class="h-4 w-4" />
          </button>

          <div
            v-show="showSearch"
            class="absolute top-12 right-0 z-50 w-80 rounded-md bg-white p-4 shadow-lg dark:bg-[#1b2e4b]"
          >
            <div class="relative">
              <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
              <input
                type="text"
                placeholder="Search certificates, monitors..."
                class="w-full rounded-lg border border-gray-200 py-2 pl-10 pr-4 text-sm focus:border-primary focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
              />
            </div>
          </div>
        </div>

        <!-- Theme toggle -->
        <button
          type="button"
          class="flex h-9 w-9 items-center justify-center rounded-full bg-white-light/40 hover:bg-white-light hover:text-primary dark:bg-dark/40 dark:hover:bg-dark dark:hover:text-primary"
          @click="themeStore.toggleTheme(themeStore.theme === 'dark' ? 'light' : 'dark')"
        >
          <Sun v-if="themeStore.resolvedTheme === 'dark'" class="h-4 w-4" />
          <Moon v-else class="h-4 w-4" />
        </button>

        <!-- Theme customizer toggle -->
        <button
          type="button"
          class="flex h-9 w-9 items-center justify-center rounded-full bg-white-light/40 hover:bg-white-light hover:text-primary dark:bg-dark/40 dark:hover:bg-dark dark:hover:text-primary"
          data-test="theme-customizer-toggle"
          @click="toggleCustomizer"
        >
          <Palette class="h-4 w-4" />
        </button>

        <!-- Notifications -->
        <div class="dropdown relative">
          <button
            type="button"
            class="relative flex h-9 w-9 items-center justify-center rounded-full bg-white-light/40 hover:bg-white-light hover:text-primary dark:bg-dark/40 dark:hover:bg-dark dark:hover:text-primary"
            @click="showNotifications = !showNotifications"
          >
            <Bell class="h-4 w-4" />
            <!-- Notification badge -->
            <span class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-danger text-[10px] text-white">
              {{ notifications.length }}
            </span>
          </button>

          <div
            v-show="showNotifications"
            class="absolute top-12 right-0 z-50 w-80 rounded-md bg-white shadow-lg dark:bg-[#1b2e4b]"
          >
            <div class="border-b border-gray-200 p-4 dark:border-gray-600">
              <h3 class="font-semibold text-gray-900 dark:text-white">Notifications</h3>
            </div>

            <div class="max-h-64 overflow-y-auto">
              <div
                v-for="notification in notifications"
                :key="notification.id"
                class="border-b border-gray-100 p-4 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700"
              >
                <div class="flex items-start space-x-3">
                  <div
                    class="mt-1 h-2 w-2 rounded-full"
                    :class="{
                      'bg-yellow-400': notification.type === 'warning',
                      'bg-red-400': notification.type === 'error',
                      'bg-green-400': notification.type === 'success'
                    }"
                  />
                  <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                      {{ notification.title }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                      {{ notification.message }}
                    </p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">
                      {{ notification.time }}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <div class="p-4">
              <Link href="/notifications" class="text-sm text-primary hover:text-primary/80">
                View all notifications
              </Link>
            </div>
          </div>
        </div>

        <!-- User menu -->
        <div class="dropdown relative">
          <button
            type="button"
            class="flex items-center space-x-2 rounded-lg p-2 hover:bg-white-light hover:text-primary dark:hover:bg-dark dark:hover:text-primary"
            @click="showUserMenu = !showUserMenu"
          >
            <div class="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center">
              <User class="h-4 w-4 text-primary" />
            </div>
            <span class="hidden sm:block text-sm font-medium">{{ user?.name }}</span>
            <ChevronDown class="h-4 w-4" />
          </button>

          <div
            v-show="showUserMenu"
            class="absolute top-12 right-0 z-50 w-48 rounded-md bg-white shadow-lg dark:bg-[#1b2e4b]"
          >
            <div class="border-b border-gray-200 p-4 dark:border-gray-600">
              <p class="text-sm font-medium text-gray-900 dark:text-white">{{ user?.name }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">{{ user?.email }}</p>
            </div>

            <div class="py-2">
              <Link
                href="/settings/profile"
                class="flex items-center space-x-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
              >
                <Settings class="h-4 w-4" />
                <span>Settings</span>
              </Link>

              <button
                type="button"
                class="flex w-full items-center space-x-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                @click="logout"
              >
                <LogOut class="h-4 w-4" />
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
      class="horizontal-menu border-t border-[#ebedf2] bg-white px-6 py-1.5 font-semibold text-black dark:border-[#191e3a] dark:bg-[#0e1726] dark:text-white-light"
    >
      <nav class="flex space-x-8">
        <div
          v-for="item in horizontalMenuItems"
          :key="item.title"
          class="relative dropdown"
        >
          <Link
            :href="item.href"
            class="flex items-center space-x-2 px-3 py-2 text-sm font-medium text-gray-700 transition-colors hover:text-primary dark:text-gray-300 dark:hover:text-primary"
            @click="toggleHorizontalDropdown(item.title)"
          >
            <component :is="item.icon" class="h-4 w-4" />
            <span>{{ item.title }}</span>
            <ChevronDown
              v-if="item.children"
              class="h-3 w-3 transition-transform"
              :class="{ 'rotate-180': activeHorizontalDropdown === item.title }"
            />
          </Link>

          <!-- Dropdown menu -->
          <div
            v-if="item.children && activeHorizontalDropdown === item.title"
            class="absolute top-full left-0 z-50 mt-1 w-48 rounded-md bg-white shadow-lg dark:bg-[#1b2e4b]"
          >
            <div class="py-1">
              <Link
                v-for="child in item.children"
                :key="child.title"
                :href="child.href"
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
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