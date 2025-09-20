<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Profile Settings</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Manage your account profile information and password</p>
      </div>

      <!-- Profile Information -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Profile Information</h2>
          <p class="text-sm text-gray-600 dark:text-gray-400">Update your account's profile information and email address.</p>
        </div>
        <form @submit.prevent="updateProfile" class="p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
              <input
                type="text"
                id="name"
                v-model="profileForm.name"
                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                :class="{'border-red-500': profileForm.errors.name}"
              />
              <div v-if="profileForm.errors.name" class="mt-2 text-sm text-red-600 dark:text-red-400">
                {{ profileForm.errors.name }}
              </div>
            </div>

            <div>
              <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
              <input
                type="email"
                id="email"
                v-model="profileForm.email"
                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                :class="{'border-red-500': profileForm.errors.email}"
              />
              <div v-if="profileForm.errors.email" class="mt-2 text-sm text-red-600 dark:text-red-400">
                {{ profileForm.errors.email }}
              </div>
            </div>
          </div>

          <div class="mt-6 flex justify-end">
            <button
              type="submit"
              :disabled="profileForm.processing"
              class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-medium rounded-md transition-colors"
            >
              <svg v-if="profileForm.processing" class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
              </svg>
              {{ profileForm.processing ? 'Updating...' : 'Update Profile' }}
            </button>
          </div>
        </form>
      </div>

      <!-- Update Password -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Update Password</h2>
          <p class="text-sm text-gray-600 dark:text-gray-400">Ensure your account is using a long, random password to stay secure.</p>
        </div>
        <form @submit.prevent="updatePassword" class="p-6">
          <div class="space-y-6">
            <div>
              <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Current Password</label>
              <input
                type="password"
                id="current_password"
                v-model="passwordForm.current_password"
                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                :class="{'border-red-500': passwordForm.errors.current_password}"
              />
              <div v-if="passwordForm.errors.current_password" class="mt-2 text-sm text-red-600 dark:text-red-400">
                {{ passwordForm.errors.current_password }}
              </div>
            </div>

            <div>
              <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">New Password</label>
              <input
                type="password"
                id="password"
                v-model="passwordForm.password"
                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                :class="{'border-red-500': passwordForm.errors.password}"
              />
              <div v-if="passwordForm.errors.password" class="mt-2 text-sm text-red-600 dark:text-red-400">
                {{ passwordForm.errors.password }}
              </div>
            </div>

            <div>
              <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirm Password</label>
              <input
                type="password"
                id="password_confirmation"
                v-model="passwordForm.password_confirmation"
                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                :class="{'border-red-500': passwordForm.errors.password_confirmation}"
              />
              <div v-if="passwordForm.errors.password_confirmation" class="mt-2 text-sm text-red-600 dark:text-red-400">
                {{ passwordForm.errors.password_confirmation }}
              </div>
            </div>
          </div>

          <div class="mt-6 flex justify-end">
            <button
              type="submit"
              :disabled="passwordForm.processing"
              class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-medium rounded-md transition-colors"
            >
              <svg v-if="passwordForm.processing" class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
              </svg>
              {{ passwordForm.processing ? 'Updating...' : 'Update Password' }}
            </button>
          </div>
        </form>
      </div>

      <!-- Delete Account -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-lg font-semibold text-red-600 dark:text-red-400">Delete Account</h2>
          <p class="text-sm text-gray-600 dark:text-gray-400">Permanently delete your account and all of your data.</p>
        </div>
        <div class="p-6">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-sm font-medium text-gray-900 dark:text-white">Delete your account</h3>
              <p class="text-sm text-gray-600 dark:text-gray-400">Once your account is deleted, all of its resources and data will be permanently deleted.</p>
            </div>
            <button
              @click="confirmingUserDeletion = true"
              class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-md transition-colors"
            >
              Delete Account
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete User Confirmation Modal -->
    <Modal :show="confirmingUserDeletion" @close="confirmingUserDeletion = false">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900 dark:text-white">
          Are you sure you want to delete your account?
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
          Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.
        </p>

        <div class="mt-6">
          <label for="delete_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
          <input
            type="password"
            id="delete_password"
            v-model="deleteForm.password"
            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-red-500 focus:ring-red-500"
            :class="{'border-red-500': deleteForm.errors.password}"
            @keyup.enter="deleteUser"
          />
          <div v-if="deleteForm.errors.password" class="mt-2 text-sm text-red-600 dark:text-red-400">
            {{ deleteForm.errors.password }}
          </div>
        </div>

        <div class="mt-6 flex justify-end space-x-3">
          <button
            @click="confirmingUserDeletion = false"
            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 dark:hover:bg-gray-500 rounded-md transition-colors"
          >
            Cancel
          </button>
          <button
            @click="deleteUser"
            :disabled="deleteForm.processing"
            class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 disabled:bg-red-400 text-white font-medium rounded-md transition-colors"
          >
            <svg v-if="deleteForm.processing" class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            {{ deleteForm.processing ? 'Deleting...' : 'Delete Account' }}
          </button>
        </div>
      </div>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
  user: Object
})

const confirmingUserDeletion = ref(false)

// Profile form
const profileForm = useForm({
  name: props.user.name,
  email: props.user.email,
})

// Password form
const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
})

// Delete form
const deleteForm = useForm({
  password: '',
})

function updateProfile() {
  profileForm.patch('/settings/profile', {
    preserveScroll: true,
    onSuccess: () => {
      // Handle success
    }
  })
}

function updatePassword() {
  passwordForm.put('/settings/password', {
    preserveScroll: true,
    onSuccess: () => {
      passwordForm.reset()
    }
  })
}

function deleteUser() {
  deleteForm.delete('/settings/profile', {
    preserveScroll: true,
    onSuccess: () => {
      confirmingUserDeletion.value = false
    },
    onError: () => {
      deleteForm.reset('password')
    }
  })
}
</script>