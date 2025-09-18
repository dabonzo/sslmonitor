import { defineStore } from 'pinia'
import { ref, computed, watch } from 'vue'

export type ThemeMode = 'light' | 'dark' | 'system'
export type LayoutMode = 'vertical' | 'horizontal' | 'collapsible'
export type MenuMode = 'horizontal' | 'vertical'

export const useThemeStore = defineStore('theme', () => {
  // Theme State
  const theme = ref<ThemeMode>('system')
  const layout = ref<LayoutMode>('vertical')
  const menu = ref<MenuMode>('vertical')
  const sidebarOpen = ref(true)
  const semiDark = ref(false)
  const animation = ref('animate__fadeIn')

  // System theme detection
  const systemTheme = ref<'light' | 'dark'>('light')

  // Computed theme (resolves 'system' to actual theme)
  const resolvedTheme = computed(() => {
    if (theme.value === 'system') {
      return systemTheme.value
    }
    return theme.value
  })

  // CSS classes for the app
  const appClasses = computed(() => {
    const classes: string[] = []

    if (sidebarOpen.value) classes.push('toggle-sidebar')
    if (resolvedTheme.value === 'dark') classes.push('dark')
    if (menu.value) classes.push(menu.value)
    if (layout.value) classes.push(layout.value)
    if (semiDark.value) classes.push('semi-dark')

    return classes.join(' ')
  })

  // Actions
  function toggleTheme(newTheme: ThemeMode) {
    theme.value = newTheme
    applyTheme()
  }

  function setTheme(newTheme: ThemeMode) {
    theme.value = newTheme
    applyTheme()
  }

  function toggleLayout(newLayout: LayoutMode) {
    layout.value = newLayout
  }

  function setLayout(newLayout: LayoutMode) {
    layout.value = newLayout
  }

  function toggleMenu(newMenu: MenuMode) {
    menu.value = newMenu
  }

  function setMenu(newMenu: MenuMode) {
    menu.value = newMenu
  }

  function toggleSidebar() {
    sidebarOpen.value = !sidebarOpen.value
  }

  function setSidebarOpen(open: boolean) {
    sidebarOpen.value = open
  }

  function toggleSemiDark() {
    semiDark.value = !semiDark.value
  }

  function setAnimation(animationClass: string) {
    animation.value = animationClass
  }

  // Apply theme to document
  function applyTheme() {
    const root = document.documentElement

    if (resolvedTheme.value === 'dark') {
      root.classList.add('dark')
    } else {
      root.classList.remove('dark')
    }
  }

  // Initialize theme
  function initializeTheme() {
    // Load from localStorage
    const savedTheme = localStorage.getItem('ssl-monitor-theme') as ThemeMode
    const savedLayout = localStorage.getItem('ssl-monitor-layout') as LayoutMode
    const savedMenu = localStorage.getItem('ssl-monitor-menu') as MenuMode
    const savedSemiDark = localStorage.getItem('ssl-monitor-semi-dark')

    if (savedTheme) theme.value = savedTheme
    if (savedLayout) layout.value = savedLayout
    if (savedMenu) menu.value = savedMenu
    if (savedSemiDark) semiDark.value = savedSemiDark === 'true'

    // Detect system theme
    if (window.matchMedia) {
      const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
      systemTheme.value = mediaQuery.matches ? 'dark' : 'light'

      mediaQuery.addEventListener('change', (e) => {
        systemTheme.value = e.matches ? 'dark' : 'light'
      })
    }

    applyTheme()

    // Set sidebar open on desktop by default
    if (window.innerWidth >= 1024) {
      sidebarOpen.value = true
    }
  }

  // Persist to localStorage
  watch(theme, (newTheme) => {
    localStorage.setItem('ssl-monitor-theme', newTheme)
    applyTheme()
  })

  watch(layout, (newLayout) => {
    localStorage.setItem('ssl-monitor-layout', newLayout)
  })

  watch(menu, (newMenu) => {
    localStorage.setItem('ssl-monitor-menu', newMenu)
  })

  watch(semiDark, (newSemiDark) => {
    localStorage.setItem('ssl-monitor-semi-dark', newSemiDark.toString())
  })

  // Close sidebar on mobile when clicking outside
  function handleOutsideClick() {
    if (window.innerWidth < 1024 && sidebarOpen.value) {
      sidebarOpen.value = false
    }
  }

  function resetToDefaults() {
    theme.value = 'system'
    layout.value = 'vertical'
    menu.value = 'vertical'
    semiDark.value = false
    applyTheme()
  }

  return {
    // State
    theme,
    layout,
    menu,
    sidebarOpen,
    semiDark,
    animation,
    systemTheme,

    // Computed
    resolvedTheme,
    appClasses,

    // Actions
    toggleTheme,
    setTheme,
    toggleLayout,
    setLayout,
    toggleMenu,
    setMenu,
    toggleSidebar,
    setSidebarOpen,
    toggleSemiDark,
    setAnimation,
    initializeTheme,
    handleOutsideClick,
    resetToDefaults,
  }
})