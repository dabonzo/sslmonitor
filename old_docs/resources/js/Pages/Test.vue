<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex items-center justify-center">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8 max-w-md w-full mx-4">
      <div class="text-center">
        <svg class="mx-auto h-16 w-16 text-green-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>

        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
          🎉 Success!
        </h1>

        <p class="text-lg text-gray-600 dark:text-gray-400 mb-4">
          {{ message }}
        </p>

        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-6">
          <p class="text-sm text-gray-500 dark:text-gray-400">
            <strong>Timestamp:</strong> {{ timestamp }}
          </p>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            <strong>Stack:</strong> Laravel 12 + Vue 3 + Inertia.js + Pinia + TailwindCSS v4
          </p>
        </div>

        <div class="space-y-3">
          <Link href="/login"
                class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
            Go to Login
          </Link>

          <button @click="testPinia"
                  class="block w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
            Test Pinia State: {{ counter }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useCounterStore } from '../stores/counter'

const props = defineProps({
  message: String,
  timestamp: String
})

// Test Pinia store
const counterStore = useCounterStore()
const counter = ref(counterStore.count)

function testPinia() {
  counterStore.increment()
  counter.value = counterStore.count
}
</script>