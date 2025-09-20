<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
        <div class="flex justify-between items-center">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Website Management</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Manage your SSL certificates and uptime monitoring</p>
          </div>
          <button @click="showAddModal = true"
                  class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Add Website
          </button>
        </div>
      </div>

      <!-- Websites List -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Your Websites</h2>
        </div>

        <div v-if="websites.length === 0" class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0-9v9"></path>
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No websites yet</h3>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by adding your first website to monitor.</p>
          <div class="mt-6">
            <button @click="showAddModal = true"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md transition-colors">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
              </svg>
              Add Your First Website
            </button>
          </div>
        </div>

        <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
          <div v-for="website in websites" :key="website.id"
               class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-4">
                <!-- SSL Status Indicator -->
                <div :class="getSslStatusClass(website.latest_ssl_check)"
                     class="w-4 h-4 rounded-full flex-shrink-0"></div>

                <div class="min-w-0 flex-1">
                  <div class="flex items-center space-x-3">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white truncate">
                      {{ website.name }}
                    </h3>
                    <span v-if="website.team"
                          class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                      Team: {{ website.team.name }}
                    </span>
                    <span v-if="website.uptime_monitoring"
                          class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                      Uptime Monitoring
                    </span>
                  </div>
                  <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ website.url }}</p>

                  <!-- SSL & Uptime Status -->
                  <div class="flex items-center space-x-6 mt-2 text-sm">
                    <div class="flex items-center space-x-2">
                      <span class="text-gray-500 dark:text-gray-400">SSL:</span>
                      <span :class="getSslStatusTextClass(website.latest_ssl_check)" class="font-medium">
                        {{ getSslStatusText(website.latest_ssl_check) }}
                      </span>
                    </div>

                    <div v-if="website.uptime_monitoring && website.latest_uptime_check" class="flex items-center space-x-2">
                      <span class="text-gray-500 dark:text-gray-400">Uptime:</span>
                      <span :class="getUptimeStatusTextClass(website.latest_uptime_check)" class="font-medium">
                        {{ getUptimeStatusText(website.latest_uptime_check) }}
                      </span>
                    </div>

                    <div v-if="website.latest_ssl_check?.expires_at" class="flex items-center space-x-2">
                      <span class="text-gray-500 dark:text-gray-400">Expires:</span>
                      <span class="font-medium text-gray-900 dark:text-white">
                        {{ formatDate(website.latest_ssl_check.expires_at) }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Actions -->
              <div class="flex items-center space-x-2 flex-shrink-0">
                <Link :href="route('websites.show', website.id)"
                      class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 dark:hover:bg-gray-500 rounded-md transition-colors">
                  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                  </svg>
                  View Details
                </Link>

                <button @click="editWebsite(website)"
                        class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 dark:hover:bg-gray-500 rounded-md transition-colors">
                  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                  </svg>
                  Edit
                </button>

                <button @click="deleteWebsite(website)"
                        class="inline-flex items-center px-3 py-2 text-sm font-medium text-red-700 dark:text-red-400 bg-red-100 dark:bg-red-900 hover:bg-red-200 dark:hover:bg-red-800 rounded-md transition-colors">
                  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                  </svg>
                  Delete
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Add/Edit Website Modal -->
    <WebsiteModal
      v-if="showAddModal || editingWebsite"
      :show="showAddModal || !!editingWebsite"
      :website="editingWebsite"
      @close="closeModal"
      @saved="handleWebsiteSaved"
    />

    <!-- Delete Confirmation Modal -->
    <DeleteConfirmationModal
      v-if="websiteToDelete"
      :show="!!websiteToDelete"
      :title="`Delete ${websiteToDelete?.name}`"
      :message="`Are you sure you want to delete '${websiteToDelete?.name}'? This action cannot be undone and will remove all associated SSL checks and uptime data.`"
      @confirmed="confirmDelete"
      @cancelled="websiteToDelete = null"
    />
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '../../Components/AppLayout.vue'
import WebsiteModal from './WebsiteModal.vue'
import DeleteConfirmationModal from '../../Components/DeleteConfirmationModal.vue'

const props = defineProps({
  websites: Array,
  team: Object,
})

const showAddModal = ref(false)
const editingWebsite = ref(null)
const websiteToDelete = ref(null)

function getSslStatusClass(sslCheck) {
  if (!sslCheck) return 'bg-gray-400'

  const statusClasses = {
    'valid': 'bg-green-500',
    'expiring_soon': 'bg-yellow-500',
    'expired': 'bg-red-500',
    'error': 'bg-red-500',
    'pending': 'bg-gray-400'
  }

  return statusClasses[sslCheck.status] || 'bg-gray-400'
}

function getSslStatusText(sslCheck) {
  if (!sslCheck) return 'Not checked'

  const statusTexts = {
    'valid': 'Valid',
    'expiring_soon': 'Expiring soon',
    'expired': 'Expired',
    'error': 'Error',
    'pending': 'Checking...'
  }

  return statusTexts[sslCheck.status] || 'Unknown'
}

function getSslStatusTextClass(sslCheck) {
  if (!sslCheck) return 'text-gray-500'

  const statusClasses = {
    'valid': 'text-green-600 dark:text-green-400',
    'expiring_soon': 'text-yellow-600 dark:text-yellow-400',
    'expired': 'text-red-600 dark:text-red-400',
    'error': 'text-red-600 dark:text-red-400',
    'pending': 'text-gray-600 dark:text-gray-400'
  }

  return statusClasses[sslCheck.status] || 'text-gray-500'
}

function getUptimeStatusText(uptimeCheck) {
  if (!uptimeCheck) return 'Not checked'

  const statusTexts = {
    'up': 'Online',
    'down': 'Offline',
    'slow': 'Slow response',
    'error': 'Error'
  }

  return statusTexts[uptimeCheck.status] || uptimeCheck.status
}

function getUptimeStatusTextClass(uptimeCheck) {
  if (!uptimeCheck) return 'text-gray-500'

  const statusClasses = {
    'up': 'text-green-600 dark:text-green-400',
    'down': 'text-red-600 dark:text-red-400',
    'slow': 'text-yellow-600 dark:text-yellow-400',
    'error': 'text-red-600 dark:text-red-400'
  }

  return statusClasses[uptimeCheck.status] || 'text-gray-500'
}

function formatDate(dateString) {
  if (!dateString) return 'Never'
  return new Date(dateString).toLocaleDateString()
}

function editWebsite(website) {
  editingWebsite.value = website
}

function deleteWebsite(website) {
  websiteToDelete.value = website
}

function closeModal() {
  showAddModal.value = false
  editingWebsite.value = null
}

function handleWebsiteSaved() {
  closeModal()
  // Refresh the page to get updated data
  router.reload()
}

function confirmDelete() {
  if (websiteToDelete.value) {
    router.delete(route('websites.destroy', websiteToDelete.value.id), {
      onSuccess: () => {
        websiteToDelete.value = null
      }
    })
  }
}
</script>