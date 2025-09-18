<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue'
import { useThemeStore } from '@/stores/theme'
import AppSidebar from './components/AppSidebar.vue'
import AppHeader from './components/AppHeader.vue'
import AppFooter from './components/AppFooter.vue'

interface Props {
  title?: string
}

defineProps<Props>()

const themeStore = useThemeStore()

// Initialize theme on mount
onMounted(() => {
  themeStore.initializeTheme()

  // Handle sidebar close on outside click for mobile
  document.addEventListener('click', handleDocumentClick)

  // Handle window resize
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  document.removeEventListener('click', handleDocumentClick)
  window.removeEventListener('resize', handleResize)
})

function handleDocumentClick(event: Event) {
  const target = event.target as HTMLElement

  // Close sidebar if clicking outside on mobile
  if (window.innerWidth < 1024 && themeStore.sidebarOpen) {
    const sidebar = document.querySelector('.sidebar')
    const sidebarToggle = document.querySelector('[data-sidebar-toggle]')

    if (sidebar && !sidebar.contains(target) && !sidebarToggle?.contains(target)) {
      themeStore.setSidebarOpen(false)
    }
  }
}

function handleResize() {
  // Auto-close sidebar on mobile
  if (window.innerWidth < 1024) {
    themeStore.setSidebarOpen(false)
  }
  // Auto-open sidebar on desktop
  else if (window.innerWidth >= 1024 && themeStore.menu === 'vertical') {
    themeStore.setSidebarOpen(true)
  }
}

function scrollToTop() {
  window.scrollTo({
    top: 0,
    behavior: 'smooth'
  })
}
</script>

<template>
  <div
    class="relative overflow-x-hidden font-nunito text-sm font-normal antialiased bg-background text-foreground"
    :class="themeStore.appClasses"
  >
    <!-- Sidebar overlay for mobile -->
    <div
      v-show="themeStore.sidebarOpen"
      class="fixed inset-0 z-50 bg-black/60 lg:hidden"
      @click="themeStore.setSidebarOpen(false)"
    />

    <!-- Sidebar -->
    <AppSidebar />

    <!-- Main container -->
    <div class="main-container min-h-screen">
      <!-- Main content wrapper -->
      <div class="main-content flex min-h-screen flex-col">
        <!-- Header -->
        <AppHeader :title="title" />

        <!-- Page content -->
        <div class="animate__animated p-6" :class="themeStore.animation">
          <slot />
        </div>

        <!-- Footer -->
        <AppFooter />
      </div>
    </div>

    <!-- Scroll to top button -->
    <div class="fixed bottom-6 right-6 z-50">
      <button
        type="button"
        class="btn btn-outline-primary animate-pulse rounded-full bg-white p-2 shadow-lg hover:bg-primary hover:text-white dark:bg-gray-800 dark:hover:bg-primary"
        @click="scrollToTop"
      >
        <svg
          class="h-4 w-4"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M5 10l7-7m0 0l7 7m-7-7v18"
          />
        </svg>
      </button>
    </div>
  </div>
</template>

<style scoped>
/* Main container adjustments for sidebar */
.main-container {
  transition: margin-left 0.3s ease;
}

.vertical .main-container {
  margin-left: 260px;
}

.collapsible-vertical .main-container {
  margin-left: 70px;
}

.horizontal .main-container {
  margin-left: 0;
}

/* Mobile responsive */
@media (max-width: 1023px) {
  .main-container {
    margin-left: 0 !important;
  }
}

/* Toggle sidebar state */
.toggle-sidebar.vertical .main-container {
  margin-left: 0;
}

.toggle-sidebar.collapsible-vertical .main-container {
  margin-left: 260px;
}

/* Semi-dark mode */
.semi-dark .sidebar {
  background: linear-gradient(135deg, #191e3a 0%, #0e1726 100%);
}

.semi-dark .header {
  background: linear-gradient(135deg, #191e3a 0%, #0e1726 100%);
}
</style>