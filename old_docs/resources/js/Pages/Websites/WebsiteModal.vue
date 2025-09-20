<template>
  <Modal :show="show" @close="$emit('close')" maxWidth="4xl">
    <div class="p-6">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
          {{ website ? 'Edit Website' : 'Add New Website' }}
        </h2>
        <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <!-- Basic Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Website Name
            </label>
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-blue-500 focus:border-blue-500"
              placeholder="My Website"
            />
            <div v-if="errors.name" class="text-red-600 text-sm mt-1">{{ errors.name }}</div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Website URL
            </label>
            <div class="relative">
              <input
                v-model="form.url"
                type="url"
                required
                @blur="checkCertificatePreview"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-blue-500 focus:border-blue-500"
                placeholder="https://example.com"
              />
              <button
                v-if="form.url"
                type="button"
                @click="checkCertificatePreview"
                :disabled="isCheckingCertificate"
                class="absolute right-2 top-2 p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
              >
                <svg v-if="isCheckingCertificate" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </button>
            </div>
            <div v-if="errors.url" class="text-red-600 text-sm mt-1">{{ errors.url }}</div>
          </div>
        </div>

        <!-- Certificate Preview -->
        <div v-if="certificatePreview" class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
          <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Certificate Preview</h3>

          <div v-if="certificatePreview.status === 'error'" class="text-red-600 dark:text-red-400 text-sm">
            {{ certificatePreview.error_message }}
          </div>

          <div v-else class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-600 dark:text-gray-400">Status:</span>
              <span :class="getCertificateStatusClass(certificatePreview.status)" class="font-medium">
                {{ getCertificateStatusText(certificatePreview.status) }}
              </span>
            </div>

            <div v-if="certificatePreview.expires_at" class="flex justify-between">
              <span class="text-gray-600 dark:text-gray-400">Expires:</span>
              <span class="font-medium text-gray-900 dark:text-white">
                {{ new Date(certificatePreview.expires_at).toLocaleDateString() }}
              </span>
            </div>

            <div v-if="certificatePreview.issuer" class="flex justify-between">
              <span class="text-gray-600 dark:text-gray-400">Issuer:</span>
              <span class="font-medium text-gray-900 dark:text-white">{{ certificatePreview.issuer }}</span>
            </div>

            <div v-if="certificatePreview.days_until_expiry !== null" class="flex justify-between">
              <span class="text-gray-600 dark:text-gray-400">Days until expiry:</span>
              <span class="font-medium text-gray-900 dark:text-white">{{ certificatePreview.days_until_expiry }}</span>
            </div>
          </div>
        </div>

        <!-- Uptime Monitoring Toggle -->
        <div class="border-t border-gray-200 dark:border-gray-600 pt-6">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-medium text-gray-900 dark:text-white">Uptime Monitoring</h3>
              <p class="text-sm text-gray-600 dark:text-gray-400">Monitor website availability and response times</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input
                v-model="form.uptime_monitoring"
                type="checkbox"
                class="sr-only peer"
              />
              <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
            </label>
          </div>

          <!-- Uptime Monitoring Settings -->
          <div v-if="form.uptime_monitoring" class="mt-6 space-y-6 border-l-2 border-blue-200 dark:border-blue-800 pl-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Expected Status Code
                </label>
                <input
                  v-model.number="form.expected_status_code"
                  type="number"
                  min="100"
                  max="599"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-blue-500 focus:border-blue-500"
                />
                <div v-if="errors.expected_status_code" class="text-red-600 text-sm mt-1">{{ errors.expected_status_code }}</div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Max Response Time (ms)
                </label>
                <input
                  v-model.number="form.max_response_time"
                  type="number"
                  min="1000"
                  max="120000"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-blue-500 focus:border-blue-500"
                />
                <div v-if="errors.max_response_time" class="text-red-600 text-sm mt-1">{{ errors.max_response_time }}</div>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Expected Content (optional)
              </label>
              <input
                v-model="form.expected_content"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-blue-500 focus:border-blue-500"
                placeholder="Text that should be present on the page"
              />
              <div v-if="errors.expected_content" class="text-red-600 text-sm mt-1">{{ errors.expected_content }}</div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Forbidden Content (optional)
              </label>
              <input
                v-model="form.forbidden_content"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-blue-500 focus:border-blue-500"
                placeholder="Text that should NOT be present on the page"
              />
              <div v-if="errors.forbidden_content" class="text-red-600 text-sm mt-1">{{ errors.forbidden_content }}</div>
            </div>

            <div class="flex items-center space-x-6">
              <label class="flex items-center">
                <input
                  v-model="form.javascript_enabled"
                  type="checkbox"
                  class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                />
                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Enable JavaScript</span>
              </label>

              <label class="flex items-center">
                <input
                  v-model="form.follow_redirects"
                  type="checkbox"
                  class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                />
                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Follow Redirects</span>
              </label>
            </div>

            <div v-if="form.follow_redirects">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Max Redirects
              </label>
              <input
                v-model.number="form.max_redirects"
                type="number"
                min="1"
                max="10"
                class="w-32 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-blue-500 focus:border-blue-500"
              />
              <div v-if="errors.max_redirects" class="text-red-600 text-sm mt-1">{{ errors.max_redirects }}</div>
            </div>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-600">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 dark:hover:bg-gray-500 rounded-md transition-colors"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="processing"
            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 rounded-md transition-colors"
          >
            <span v-if="processing">Saving...</span>
            <span v-else>{{ website ? 'Update Website' : 'Add Website' }}</span>
          </button>
        </div>
      </form>
    </div>
  </Modal>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import Modal from '../../Components/Modal.vue'

const props = defineProps({
  show: Boolean,
  website: Object,
})

const emit = defineEmits(['close', 'saved'])

const form = reactive({
  name: '',
  url: '',
  uptime_monitoring: false,
  javascript_enabled: false,
  expected_status_code: 200,
  expected_content: '',
  forbidden_content: '',
  max_response_time: 30000,
  follow_redirects: true,
  max_redirects: 3,
})

const processing = ref(false)
const errors = ref({})
const certificatePreview = ref(null)
const isCheckingCertificate = ref(false)

// Initialize form when website prop changes
watch(() => props.website, (website) => {
  if (website) {
    Object.assign(form, {
      name: website.name || '',
      url: website.url || '',
      uptime_monitoring: website.uptime_monitoring || false,
      javascript_enabled: website.javascript_enabled || false,
      expected_status_code: website.expected_status_code || 200,
      expected_content: website.expected_content || '',
      forbidden_content: website.forbidden_content || '',
      max_response_time: website.max_response_time || 30000,
      follow_redirects: website.follow_redirects !== false,
      max_redirects: website.max_redirects || 3,
    })
  } else {
    // Reset form for new website
    Object.assign(form, {
      name: '',
      url: '',
      uptime_monitoring: false,
      javascript_enabled: false,
      expected_status_code: 200,
      expected_content: '',
      forbidden_content: '',
      max_response_time: 30000,
      follow_redirects: true,
      max_redirects: 3,
    })
  }
  certificatePreview.value = null
  errors.value = {}
}, { immediate: true })

function submit() {
  processing.value = true
  errors.value = {}

  const method = props.website ? 'patch' : 'post'
  const url = props.website
    ? route('websites.update', props.website.id)
    : route('websites.store')

  router[method](url, form, {
    onSuccess: () => {
      emit('saved')
    },
    onError: (responseErrors) => {
      errors.value = responseErrors
    },
    onFinish: () => {
      processing.value = false
    }
  })
}

async function checkCertificatePreview() {
  if (!form.url || isCheckingCertificate.value) return

  isCheckingCertificate.value = true
  certificatePreview.value = null

  try {
    const response = await fetch(route('websites.check-certificate'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
      body: JSON.stringify({ url: form.url })
    })

    const data = await response.json()

    if (data.success) {
      certificatePreview.value = data.certificate
    } else {
      certificatePreview.value = {
        status: 'error',
        error_message: data.error
      }
    }
  } catch (error) {
    certificatePreview.value = {
      status: 'error',
      error_message: 'Failed to check certificate'
    }
  } finally {
    isCheckingCertificate.value = false
  }
}

function getCertificateStatusClass(status) {
  const statusClasses = {
    'valid': 'text-green-600 dark:text-green-400',
    'expiring_soon': 'text-yellow-600 dark:text-yellow-400',
    'expired': 'text-red-600 dark:text-red-400',
    'error': 'text-red-600 dark:text-red-400',
    'pending': 'text-gray-600 dark:text-gray-400'
  }
  return statusClasses[status] || 'text-gray-500'
}

function getCertificateStatusText(status) {
  const statusTexts = {
    'valid': 'Valid',
    'expiring_soon': 'Expiring soon',
    'expired': 'Expired',
    'error': 'Error',
    'pending': 'Checking...'
  }
  return statusTexts[status] || 'Unknown'
}
</script>