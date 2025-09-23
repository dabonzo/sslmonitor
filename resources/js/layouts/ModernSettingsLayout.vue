<script setup lang="ts">
import { computed } from 'vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { Link, usePage } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import {
  User,
  Shield,
  Key,
  Users,
  Bell,
  Settings as SettingsIcon
} from 'lucide-vue-next'

interface Props {
  title?: string
}

defineProps<Props>()

const page = usePage()
const currentPath = computed(() =>
  typeof window !== 'undefined' ? window.location.pathname : ''
)

const settingsNavItems = [
  {
    key: 'profile',
    title: 'Profile',
    description: 'Personal information and account details',
    href: '/settings/profile',
    icon: User
  },
  {
    key: 'password',
    title: 'Password',
    description: 'Change your password and security settings',
    href: '/settings/password',
    icon: Key
  },
  {
    key: 'two-factor',
    title: 'Two-Factor Auth',
    description: 'Enable two-factor authentication for security',
    href: '/settings/two-factor',
    icon: Shield
  },
  {
    key: 'team',
    title: 'Team',
    description: 'Manage team members and collaboration',
    href: '/settings/team',
    icon: Users
  },
  {
    key: 'alerts',
    title: 'Alerts',
    description: 'Configure notifications and alert settings',
    href: '/settings/alerts',
    icon: Bell
  }
]

function isActiveRoute(href: string): boolean {
  return currentPath.value === href
}
</script>

<template>
  <DashboardLayout :title="title || 'Settings'">
    <div class="max-w-7xl mx-auto">
      <!-- Header Section -->
      <div class="mb-8">
        <div class="rounded-2xl bg-gradient-to-r from-slate-50 via-blue-50 to-indigo-50 dark:from-slate-900 dark:via-blue-900/30 dark:to-indigo-900/30 p-6 shadow-xl border border-gray-100 dark:border-gray-800">
          <div class="flex items-center space-x-4">
            <div class="rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 p-3 shadow-lg">
              <SettingsIcon class="h-8 w-8 text-white" />
            </div>
            <div>
              <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-900 via-blue-800 to-indigo-800 dark:from-white dark:via-blue-200 dark:to-indigo-200 bg-clip-text text-transparent">
                Settings
              </h1>
              <p class="text-blue-600 dark:text-blue-300 font-medium">
                Manage your account preferences and configurations
              </p>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Settings Navigation -->
        <aside class="lg:col-span-1">
          <div class="rounded-2xl bg-gradient-to-br from-white via-gray-50 to-slate-50 dark:from-gray-900 dark:via-slate-800 dark:to-gray-900 p-6 shadow-xl border border-gray-100 dark:border-gray-800">
            <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">
              Account Settings
            </h2>
            <nav class="space-y-2">
              <Link
                v-for="item in settingsNavItems"
                :key="item.key"
                :href="item.href"
                class="group flex items-start space-x-3 p-3 rounded-xl transition-all duration-300 hover:bg-white/60 dark:hover:bg-white/5"
                :class="{
                  'bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/30 dark:to-indigo-900/30 border border-blue-200 dark:border-blue-800': isActiveRoute(item.href)
                }"
              >
                <div class="rounded-lg p-2 transition-all duration-300"
                     :class="{
                       'bg-blue-500 text-white': isActiveRoute(item.href),
                       'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 group-hover:bg-blue-100 dark:group-hover:bg-blue-900/30 group-hover:text-blue-600 dark:group-hover:text-blue-400': !isActiveRoute(item.href)
                     }">
                  <component :is="item.icon" class="h-4 w-4" />
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-semibold transition-colors duration-300"
                     :class="{
                       'text-blue-900 dark:text-blue-100': isActiveRoute(item.href),
                       'text-gray-900 dark:text-gray-100 group-hover:text-blue-900 dark:group-hover:text-blue-100': !isActiveRoute(item.href)
                     }">
                    {{ item.title }}
                  </p>
                  <p class="text-xs transition-colors duration-300"
                     :class="{
                       'text-blue-600 dark:text-blue-300': isActiveRoute(item.href),
                       'text-gray-500 dark:text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-300': !isActiveRoute(item.href)
                     }">
                    {{ item.description }}
                  </p>
                </div>
              </Link>
            </nav>
          </div>
        </aside>

        <!-- Settings Content -->
        <main class="lg:col-span-3">
          <div class="rounded-2xl bg-gradient-to-br from-white via-gray-50 to-slate-50 dark:from-gray-900 dark:via-slate-800 dark:to-gray-900 p-8 shadow-xl border border-gray-100 dark:border-gray-800">
            <slot />
          </div>
        </main>
      </div>
    </div>
  </DashboardLayout>
</template>

<style scoped>
/* Smooth transitions for all interactive elements */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}
</style>