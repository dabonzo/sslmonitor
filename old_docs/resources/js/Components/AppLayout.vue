<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
    <!-- Clean Vristo-style Sticky Header -->
    <header class="sticky top-0 z-50 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm">
      <div ref="headerContent" class="max-w-7xl mx-auto px-4 flex items-center justify-between h-16">
        <!-- Mobile Menu Button -->
        <button @click="toggleMobileMenu" class="lg:hidden p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
          </svg>
        </button>

        <!-- Logo -->
        <Link :href="route('dashboard')" class="flex items-center space-x-3">
          <AppLogo class="h-8 w-8" />
          <span class="text-xl font-semibold text-gray-800 dark:text-white hidden sm:inline">SSL Monitor</span>
        </Link>

        <!-- Desktop Navigation -->
        <nav class="hidden lg:flex space-x-8">
          <Link :href="route('dashboard')"
                :class="getNavClass(route().current('dashboard'))">
            Dashboard
          </Link>
          <Link :href="route('websites')"
                :class="getNavClass(route().current('websites*'))">
            Websites
          </Link>
        </nav>

        <!-- Right Side Controls -->
        <div class="flex items-center space-x-2">
          <!-- Layout Toggle -->
          <button @click="toggleLayout"
                  class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                  title="Toggle Layout">
            <svg v-show="isBoxed" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path>
            </svg>
            <svg v-show="!isBoxed" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>

          <!-- Dark Mode Toggle -->
          <button @click="toggleTheme"
                  class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                  title="Toggle Theme">
            <svg v-show="!isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
            </svg>
            <svg v-show="isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>
          </button>

          <!-- User Profile Dropdown -->
          <div class="relative">
            <button @click="userMenuOpen = !userMenuOpen"
                    class="flex items-center space-x-3 p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
              <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center text-white font-semibold text-sm">
                {{ userInitials }}
              </div>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
              </svg>
            </button>

            <!-- Dropdown Menu -->
            <div v-show="userMenuOpen" @click.away="userMenuOpen = false"
                 class="absolute right-0 mt-2 w-64 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700">
              <!-- User Info -->
              <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-3">
                  <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center text-white font-semibold">
                    {{ userInitials }}
                  </div>
                  <div>
                    <p class="font-semibold">{{ $page.props.auth.user.name }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $page.props.auth.user.email }}</p>
                  </div>
                </div>
              </div>

              <!-- Menu Items -->
              <div class="py-2">
                <Link :href="route('settings.profile')"
                      class="flex items-center px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700">
                  <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                  </svg>
                  Profile
                </Link>
                <Link :href="route('settings.appearance')"
                      class="flex items-center px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700">
                  <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4h4a2 2 0 002-2V5z"></path>
                  </svg>
                  Appearance
                </Link>
                <Link :href="route('settings.email')"
                      class="flex items-center px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700">
                  <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                  </svg>
                  Email Settings
                </Link>
                <Link :href="route('settings.team')"
                      class="flex items-center px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700">
                  <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                  </svg>
                  Team
                </Link>
              </div>

              <!-- Logout -->
              <div class="border-t border-gray-200 dark:border-gray-700 py-2">
                <Link :href="route('logout')" method="post" as="button"
                      class="flex items-center w-full px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">
                  <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                  </svg>
                  Log Out
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Mobile Menu -->
    <div v-show="mobileMenuOpen" class="lg:hidden bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
      <div class="px-4 py-2 space-y-1">
        <Link :href="route('dashboard')"
              :class="getMobileNavClass(route().current('dashboard'))"
              @click="mobileMenuOpen = false">
          Dashboard
        </Link>
        <Link :href="route('websites')"
              :class="getMobileNavClass(route().current('websites*'))"
              @click="mobileMenuOpen = false">
          Websites
        </Link>
      </div>
    </div>

    <!-- Main Content -->
    <main ref="mainContent" class="transition-all duration-200 max-w-7xl mx-auto px-4 py-6">
      <slot />
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import AppLogo from './AppLogo.vue'

const $page = usePage()

// Reactive state
const isBoxed = ref(true)
const isDark = ref(false)
const userMenuOpen = ref(false)
const mobileMenuOpen = ref(false)

// Refs for DOM elements
const mainContent = ref(null)
const headerContent = ref(null)

// Computed properties
const userInitials = computed(() => {
  const user = $page.props.auth.user
  return user.name.split(' ').map(n => n[0]).join('').toUpperCase()
})

// Methods
function getNavClass(isActive) {
  const baseClass = 'px-3 py-2 rounded-md text-sm font-medium transition-colors'
  const activeClass = 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300'
  const inactiveClass = 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700'

  return `${baseClass} ${isActive ? activeClass : inactiveClass}`
}

function getMobileNavClass(isActive) {
  const baseClass = 'block px-3 py-2 rounded-md text-base font-medium'
  const activeClass = 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300'
  const inactiveClass = 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700'

  return `${baseClass} ${isActive ? activeClass : inactiveClass}`
}

function toggleLayout() {
  isBoxed.value = !isBoxed.value

  if (mainContent.value && headerContent.value) {
    if (isBoxed.value) {
      mainContent.value.className = 'transition-all duration-200 max-w-7xl mx-auto px-4 py-6'
      headerContent.value.className = 'max-w-7xl mx-auto px-4 flex items-center justify-between h-16'
      localStorage.setItem('boxed-layout', 'true')
    } else {
      mainContent.value.className = 'transition-all duration-200 w-full px-4 py-6'
      headerContent.value.className = 'w-full px-4 flex items-center justify-between h-16'
      localStorage.setItem('boxed-layout', 'false')
    }
  }
}

function toggleTheme() {
  isDark.value = !isDark.value

  if (isDark.value) {
    document.documentElement.classList.add('dark')
    localStorage.setItem('theme', 'dark')
  } else {
    document.documentElement.classList.remove('dark')
    localStorage.setItem('theme', 'light')
  }
}

function toggleMobileMenu() {
  mobileMenuOpen.value = !mobileMenuOpen.value
}

// Initialize on mount
onMounted(() => {
  // Initialize theme
  const savedTheme = localStorage.getItem('theme')
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches

  if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
    isDark.value = true
    document.documentElement.classList.add('dark')
  } else {
    isDark.value = false
    document.documentElement.classList.remove('dark')
  }

  // Initialize layout
  const savedLayout = localStorage.getItem('boxed-layout')
  isBoxed.value = savedLayout !== 'false'

  if (mainContent.value && headerContent.value) {
    if (isBoxed.value) {
      mainContent.value.className = 'transition-all duration-200 max-w-7xl mx-auto px-4 py-6'
      headerContent.value.className = 'max-w-7xl mx-auto px-4 flex items-center justify-between h-16'
    } else {
      mainContent.value.className = 'transition-all duration-200 w-full px-4 py-6'
      headerContent.value.className = 'w-full px-4 flex items-center justify-between h-16'
    }
  }
})
</script>