<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useThemeStore } from '@/stores/theme'
import AppLogoIcon from '@/components/AppLogoIcon.vue'
import {
  Shield,
  BarChart3,
  Settings,
  AlertTriangle,
  Monitor,
  Clock,
  Users,
  HelpCircle,
  ChevronDown
} from 'lucide-vue-next'

const themeStore = useThemeStore()

// Active dropdown state
const activeDropdown = ref<string>('')

// Hover state for collapsible mode
const isHovered = ref<boolean>(false)

// Computed property to determine if sub-menus should be visible
const shouldShowSubMenus = computed(() => {
  if (themeStore.menu === 'collapsible-vertical') {
    // In collapsible mode, only show sub-menus when hovered (and dropdown is active)
    return isHovered.value
  }
  // In other modes, always show sub-menus when dropdown is active
  return true
})

// Navigation menu items
const menuItems = [
  {
    key: 'dashboard',
    title: 'Dashboard',
    icon: BarChart3,
    children: [
      { title: 'Overview', href: '/dashboard' },
      { title: 'Analytics', href: '/analytics' },
      { title: 'Reports', href: '/reports' }
    ]
  },
  {
    key: 'certificates',
    title: 'SSL Certificates',
    icon: Shield,
    children: [
      { title: 'All Certificates', href: '/certificates' },
      { title: 'Expiring Soon', href: '/certificates/expiring' },
      { title: 'Add Certificate', href: '/certificates/create' }
    ]
  },
  {
    key: 'monitors',
    title: 'Monitoring',
    icon: Monitor,
    children: [
      { title: 'All Monitors', href: '/monitors' },
      { title: 'Uptime Checks', href: '/monitors/uptime' },
      { title: 'Add Monitor', href: '/monitors/create' }
    ]
  },
  {
    key: 'alerts',
    title: 'Alerts',
    icon: AlertTriangle,
    children: [
      { title: 'Alert Rules', href: '/alerts' },
      { title: 'Notifications', href: '/alerts/notifications' },
      { title: 'History', href: '/alerts/history' }
    ]
  },
  {
    key: 'team',
    title: 'Team',
    icon: Users,
    children: [
      { title: 'Members', href: '/team' },
      { title: 'Roles', href: '/team/roles' },
      { title: 'Invitations', href: '/team/invitations' }
    ]
  }
]

const bottomMenuItems = [
  {
    key: 'settings',
    title: 'Settings',
    icon: Settings,
    children: [
      { title: 'General', href: '/settings' },
      { title: 'Notifications', href: '/settings/notifications' },
      { title: 'Integrations', href: '/settings/integrations' }
    ]
  },
  {
    key: 'help',
    title: 'Help & Support',
    icon: HelpCircle,
    children: [
      { title: 'Documentation', href: '/help' },
      { title: 'Contact Support', href: '/support' },
      { title: 'API Reference', href: '/api-docs' }
    ]
  }
]

function toggleDropdown(key: string) {
  activeDropdown.value = activeDropdown.value === key ? '' : key
}

function isActiveRoute(href: string) {
  return window.location.pathname === href
}
</script>

<template>
  <nav
    v-if="themeStore.menu !== 'horizontal'"
    class="sidebar fixed bottom-0 top-0 z-50 h-full min-h-screen shadow-[5px_0_25px_0_rgba(94,92,154,0.1)] transition-all duration-300 bg-sidebar text-sidebar-foreground"
    :class="{
      'ltr:-left-[260px] rtl:right-[260px]': !themeStore.sidebarOpen,
      'ltr:left-0 rtl:right-0': themeStore.sidebarOpen,
      'w-[260px]': themeStore.menu !== 'collapsible-vertical',
      '!w-[70px] lg:hover:!w-[260px]': themeStore.menu === 'collapsible-vertical'
    }"
    @mouseenter="isHovered = true"
    @mouseleave="isHovered = false"
  >
    <div class="h-full bg-sidebar">
      <!-- Logo section -->
      <div class="flex items-center justify-between px-4 py-3">
        <Link href="/dashboard" class="main-logo flex shrink-0 items-center">
          <AppLogoIcon class="ml-[5px] h-8 w-8 flex-none fill-current text-primary" />
          <span
            class="text-2xl font-semibold align-middle text-sidebar-primary ltr:ml-1.5 rtl:mr-1.5 text-sidebar-foreground"
            :class="{
              'lg:hidden': themeStore.menu === 'collapsible-vertical'
            }"
          >
            SSL Monitor
          </span>
        </Link>
      </div>

      <!-- Navigation menu -->
      <div class="perfect-scrollbar relative h-[calc(100vh-60px)] overflow-y-auto overflow-x-hidden p-4 py-0">
        <ul class="relative space-y-0.5 p-4 py-0 font-semibold">

          <!-- Main menu items -->
          <template v-for="item in menuItems" :key="item.key">
            <li class="menu nav-item">
              <button
                type="button"
                class="nav-link group w-full"
                :class="{ 'active': activeDropdown === item.key }"
                @click="toggleDropdown(item.key)"
              >
                <div class="flex items-center">
                  <component :is="item.icon" class="shrink-0 group-hover:!text-primary" />
                  <span
                    class="text-sidebar-foreground group-hover:text-sidebar-primary ltr:pl-3 rtl:pr-3"
                    :class="{
                      'lg:hidden': themeStore.menu === 'collapsible-vertical'
                    }"
                  >
                    {{ item.title }}
                  </span>
                </div>

                <div
                  class="rtl:rotate-180"
                  :class="{
                    '!rotate-90': activeDropdown === item.key,
                    'lg:hidden': themeStore.menu === 'collapsible-vertical'
                  }"
                >
                  <ChevronDown class="h-4 w-4" />
                </div>
              </button>

              <Transition name="slide-down">
                <ul v-show="activeDropdown === item.key && shouldShowSubMenus" class="sub-menu text-gray-500">
                  <li v-for="child in item.children" :key="child.href">
                    <Link
                      :href="child.href"
                      :class="{ 'active': isActiveRoute(child.href) }"
                    >
                      {{ child.title }}
                    </Link>
                  </li>
                </ul>
              </Transition>
            </li>
          </template>

          <!-- Separator -->
          <li class="h-px w-full bg-white-light dark:bg-[#1b2e4b] my-4"></li>

          <!-- Bottom menu items -->
          <template v-for="item in bottomMenuItems" :key="item.key">
            <li class="menu nav-item">
              <button
                type="button"
                class="nav-link group w-full"
                :class="{ 'active': activeDropdown === item.key }"
                @click="toggleDropdown(item.key)"
              >
                <div class="flex items-center">
                  <component :is="item.icon" class="shrink-0 group-hover:!text-primary" />
                  <span
                    class="text-sidebar-foreground group-hover:text-sidebar-primary ltr:pl-3 rtl:pr-3"
                    :class="{
                      'lg:hidden': themeStore.menu === 'collapsible-vertical'
                    }"
                  >
                    {{ item.title }}
                  </span>
                </div>

                <div
                  class="rtl:rotate-180"
                  :class="{
                    '!rotate-90': activeDropdown === item.key,
                    'lg:hidden': themeStore.menu === 'collapsible-vertical'
                  }"
                >
                  <ChevronDown class="h-4 w-4" />
                </div>
              </button>

              <Transition name="slide-down">
                <ul v-show="activeDropdown === item.key && shouldShowSubMenus" class="sub-menu text-gray-500">
                  <li v-for="child in item.children" :key="child.href">
                    <Link
                      :href="child.href"
                      :class="{ 'active': isActiveRoute(child.href) }"
                    >
                      {{ child.title }}
                    </Link>
                  </li>
                </ul>
              </Transition>
            </li>
          </template>

        </ul>
      </div>
    </div>
  </nav>
</template>

<style scoped>
/* Navigation styles */
.nav-link {
  display: flex;
  cursor: pointer;
  align-items: center;
  justify-content: space-between;
  border-radius: 0.375rem;
  padding: 0.5rem;
  transition: all 0.2s ease;
}

.nav-link:hover {
  background-color: rgb(248 250 252);
  color: rgb(67 97 238);
}

.dark .nav-link:hover {
  background-color: rgb(24 31 50);
  color: rgb(67 97 238);
}

.nav-link.active {
  background-color: rgb(243 244 246);
  color: rgb(67 97 238);
}

.dark .nav-link.active {
  background-color: rgb(24 31 50);
  color: rgb(67 97 238);
}

.sub-menu {
  margin-left: 1.5rem;
  list-style: none;
}

.sub-menu li {
  margin-bottom: 0.25rem;
  list-style: none;
}

.sub-menu a {
  display: block;
  border-radius: 0.375rem;
  padding: 0.5rem;
  transition: all 0.2s ease;
}

.sub-menu a:hover {
  background-color: rgb(243 244 246);
  color: rgb(67 97 238);
}

.dark .sub-menu a:hover {
  background-color: rgb(24 31 50);
  color: rgb(67 97 238);
}

.sub-menu a.active {
  background-color: rgb(243 244 246);
  color: rgb(67 97 238);
}

.dark .sub-menu a.active {
  background-color: rgb(24 31 50);
  color: rgb(67 97 238);
}

/* Slide down animation */
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.3s ease;
  overflow: hidden;
}

.slide-down-enter-from,
.slide-down-leave-to {
  opacity: 0;
  max-height: 0;
}

.slide-down-enter-to,
.slide-down-leave-from {
  opacity: 1;
  max-height: 500px;
}

/* Scrollbar styling */
.perfect-scrollbar::-webkit-scrollbar {
  width: 6px;
}

.perfect-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.perfect-scrollbar::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

.dark .perfect-scrollbar::-webkit-scrollbar-thumb {
  background: #475569;
}
</style>