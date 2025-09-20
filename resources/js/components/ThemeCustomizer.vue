<script setup lang="ts">
import { ref, computed } from 'vue'
import { useThemeStore } from '@/stores/theme'
import {
  Settings,
  Sun,
  Moon,
  Monitor,
  Palette,
  Layout,
  Sidebar,
  X,
  Check
} from 'lucide-vue-next'

const themeStore = useThemeStore()
const isOpen = ref(false)

// Color scheme options
const colorSchemes = [
  { name: 'Primary Blue', value: 'blue', color: 'bg-blue-500' },
  { name: 'Green', value: 'green', color: 'bg-green-500' },
  { name: 'Purple', value: 'purple', color: 'bg-purple-500' },
  { name: 'Pink', value: 'pink', color: 'bg-pink-500' },
  { name: 'Orange', value: 'orange', color: 'bg-orange-500' },
  { name: 'Red', value: 'red', color: 'bg-red-500' }
]

// Theme options
const themeOptions = [
  { name: 'Light', value: 'light', icon: Sun },
  { name: 'Dark', value: 'dark', icon: Moon },
  { name: 'System', value: 'system', icon: Monitor }
]

// Navigation options (VRISTO pattern)
const navigationOptions = [
  { name: 'Vertical', value: 'vertical', description: 'Full sidebar on left' },
  { name: 'Horizontal', value: 'horizontal', description: 'Header navigation menu' }
]

// Layout options (VRISTO pattern)
const layoutOptions = [
  { name: 'Full Screen', value: 'full', description: 'Full width layout' },
  { name: 'Boxed', value: 'boxed-layout', description: 'Centered boxed layout' }
]

function toggleCustomizer() {
  isOpen.value = !isOpen.value
}

function resetToDefaults() {
  themeStore.resetToDefaults()
}

defineExpose({
  toggleCustomizer
})
</script>

<template>
  <!-- Overlay -->
  <Transition name="fade">
    <div
      v-if="isOpen"
      class="fixed inset-0 z-40 bg-black/50"
      @click="isOpen = false"
    />
  </Transition>

  <!-- Customizer Panel -->
  <Transition name="slide-left">
    <div
      v-if="isOpen"
      class="fixed right-0 top-0 z-50 h-full w-80 bg-white shadow-2xl dark:bg-[#0e1726]"
    >
      <!-- Header -->
      <div class="flex items-center justify-between border-b border-gray-200 p-4 dark:border-gray-600">
        <div class="flex items-center space-x-2">
          <Settings class="h-5 w-5 text-primary" />
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            Theme Customizer
          </h3>
        </div>
        <button
          type="button"
          class="rounded-lg p-2 hover:bg-gray-100 dark:hover:bg-gray-700"
          data-test="customizer-close"
          @click="isOpen = false"
        >
          <X class="h-4 w-4" />
        </button>
      </div>

      <!-- Customizer Content -->
      <div class="h-[calc(100%-120px)] overflow-y-auto p-4">

        <!-- Theme Mode -->
        <div class="mb-6">
          <div class="mb-3 flex items-center space-x-2">
            <Sun class="h-4 w-4 text-gray-600 dark:text-gray-400" />
            <h4 class="font-medium text-gray-900 dark:text-white">Theme Mode</h4>
          </div>
          <div class="grid grid-cols-1 gap-2">
            <div
              v-for="option in themeOptions"
              :key="option.value"
              class="relative cursor-pointer rounded-lg border p-3 transition-colors"
              :class="{
                'border-primary bg-primary/5': themeStore.theme === option.value,
                'border-gray-200 hover:border-gray-300 dark:border-gray-600 dark:hover:border-gray-500': themeStore.theme !== option.value
              }"
              :data-test="`theme-${option.value}`"
              @click="themeStore.setTheme(option.value)"
            >
              <div class="flex items-center space-x-3">
                <component :is="option.icon" class="h-4 w-4" />
                <span class="font-medium">{{ option.name }}</span>
                <Check
                  v-if="themeStore.theme === option.value"
                  class="ml-auto h-4 w-4 text-primary"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Navigation Mode -->
        <div class="mb-6">
          <div class="mb-3 flex items-center space-x-2">
            <Layout class="h-4 w-4 text-gray-600 dark:text-gray-400" />
            <h4 class="font-medium text-gray-900 dark:text-white">Navigation Mode</h4>
          </div>
          <div class="grid grid-cols-1 gap-2">
            <div
              v-for="option in navigationOptions"
              :key="option.value"
              class="relative cursor-pointer rounded-lg border p-3 transition-colors"
              :class="{
                'border-primary bg-primary/5': themeStore.menu === option.value,
                'border-gray-200 hover:border-gray-300 dark:border-gray-600 dark:hover:border-gray-500': themeStore.menu !== option.value
              }"
              :data-test="`navigation-${option.value}`"
              @click="themeStore.setMenu(option.value)"
            >
              <div class="flex items-center justify-between">
                <div>
                  <div class="font-medium">{{ option.name }}</div>
                  <div class="text-sm text-gray-500">{{ option.description }}</div>
                </div>
                <Check
                  v-if="themeStore.menu === option.value"
                  class="h-4 w-4 text-primary"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Layout Mode -->
        <div class="mb-6">
          <div class="mb-3 flex items-center space-x-2">
            <Layout class="h-4 w-4 text-gray-600 dark:text-gray-400" />
            <h4 class="font-medium text-gray-900 dark:text-white">Layout Mode</h4>
          </div>
          <div class="grid grid-cols-1 gap-2">
            <div
              v-for="option in layoutOptions"
              :key="option.value"
              class="relative cursor-pointer rounded-lg border p-3 transition-colors"
              :class="{
                'border-primary bg-primary/5': themeStore.layout === option.value,
                'border-gray-200 hover:border-gray-300 dark:border-gray-600 dark:hover:border-gray-500': themeStore.layout !== option.value
              }"
              :data-test="`layout-${option.value}`"
              @click="themeStore.setLayout(option.value)"
            >
              <div class="flex items-center justify-between">
                <div>
                  <div class="font-medium">{{ option.name }}</div>
                  <div class="text-sm text-gray-500">{{ option.description }}</div>
                </div>
                <Check
                  v-if="themeStore.layout === option.value"
                  class="h-4 w-4 text-primary"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Color Scheme -->
        <div class="mb-6">
          <div class="mb-3 flex items-center space-x-2">
            <Palette class="h-4 w-4 text-gray-600 dark:text-gray-400" />
            <h4 class="font-medium text-gray-900 dark:text-white">Color Scheme</h4>
          </div>
          <div class="grid grid-cols-3 gap-2">
            <div
              v-for="scheme in colorSchemes"
              :key="scheme.value"
              class="cursor-pointer rounded-lg border p-2 transition-colors hover:border-gray-300"
              :class="[
                'border-gray-200 dark:border-gray-600',
                { 'ring-2 ring-primary': scheme.value === 'blue' }
              ]"
              @click="() => {/* Color scheme functionality to be implemented */}"
            >
              <div class="text-center">
                <div class="mx-auto mb-1 h-6 w-6 rounded-full" :class="scheme.color"></div>
                <div class="text-xs font-medium">{{ scheme.name.split(' ')[0] }}</div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- Footer -->
      <div class="absolute bottom-0 left-0 right-0 border-t border-gray-200 p-4 dark:border-gray-600">
        <button
          type="button"
          class="w-full rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
          data-test="reset-defaults"
          @click="resetToDefaults"
        >
          Reset to Defaults
        </button>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-left-enter-active,
.slide-left-leave-active {
  transition: transform 0.3s ease;
}

.slide-left-enter-from,
.slide-left-leave-to {
  transform: translateX(100%);
}
</style>