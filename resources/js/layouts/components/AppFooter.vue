<script setup lang="ts">
import { computed } from 'vue'

const currentYear = computed(() => new Date().getFullYear())
</script>

<template>
  <footer class="mt-auto p-6 text-center text-sm text-gray-500 dark:text-gray-400 sm:text-left">
    <div class="flex flex-col items-center justify-between sm:flex-row">
      <div>
        © {{ currentYear }} SSL Monitor. All rights reserved.
      </div>
      <div class="mt-2 flex space-x-4 sm:mt-0">
        <a href="/help" class="hover:text-primary transition-colors">Help</a>
        <a href="/privacy" class="hover:text-primary transition-colors">Privacy</a>
        <a href="/terms" class="hover:text-primary transition-colors">Terms</a>
      </div>
    </div>
  </footer>
</template>