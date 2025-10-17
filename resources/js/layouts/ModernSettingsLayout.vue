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
        <div class="rounded-2xl bg-gradient-to-r from-background via-primary/5 to-accent/5 p-6 shadow-xl border border-border">
          <div class="flex items-center space-x-4">
            <div class="rounded-xl bg-gradient-to-br from-primary to-primary/80 p-3 shadow-lg">
              <SettingsIcon class="h-8 w-8 text-white" />
            </div>
            <div>
              <h1 class="text-3xl font-bold bg-gradient-to-r from-foreground via-primary to-accent bg-clip-text text-transparent">
                Settings
              </h1>
              <p class="text-primary font-medium">
                Manage your account preferences and configurations
              </p>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Settings Navigation -->
        <aside class="lg:col-span-1">
          <div class="rounded-2xl bg-card p-6 shadow-xl border border-border">
            <h2 class="text-lg font-bold text-foreground mb-4">
              Account Settings
            </h2>
            <nav class="space-y-2">
              <Link
                v-for="item in settingsNavItems"
                :key="item.key"
                :href="item.href"
                class="group flex items-start space-x-3 p-3 rounded-xl transition-all duration-300 hover:bg-muted/50"
                :class="{
                  'bg-primary/10 border border-primary': isActiveRoute(item.href)
                }"
              >
                <div class="rounded-lg p-2 transition-all duration-300"
                     :class="{
                       'bg-primary text-white': isActiveRoute(item.href),
                       'bg-muted text-muted-foreground group-hover:bg-primary/20 group-hover:text-primary': !isActiveRoute(item.href)
                     }">
                  <component :is="item.icon" class="h-4 w-4" />
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-semibold transition-colors duration-300"
                     :class="{
                       'text-foreground': isActiveRoute(item.href),
                       'text-foreground group-hover:text-primary': !isActiveRoute(item.href)
                     }">
                    {{ item.title }}
                  </p>
                  <p class="text-xs transition-colors duration-300"
                     :class="{
                       'text-primary': isActiveRoute(item.href),
                       'text-muted-foreground group-hover:text-primary': !isActiveRoute(item.href)
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
          <div class="rounded-2xl bg-card p-8 shadow-xl border border-border">
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