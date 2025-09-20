<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Team Management</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Manage your team and collaborate on website monitoring</p>
      </div>

      <!-- Current Team (if user has one) -->
      <div v-if="team" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ team.name }}</h2>
          <p class="text-sm text-gray-600 dark:text-gray-400">Your current team</p>
        </div>
        <div class="p-6">
          <!-- Team Members -->
          <div class="mb-8">
            <h3 class="text-base font-medium text-gray-900 dark:text-white mb-4">Team Members ({{ teamMembers.length }})</h3>
            <div class="space-y-3">
              <div v-for="member in teamMembers" :key="member.id" class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div class="flex items-center space-x-3">
                  <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center text-white font-semibold">
                    {{ member.name.charAt(0).toUpperCase() }}
                  </div>
                  <div>
                    <p class="font-medium text-gray-900 dark:text-white">{{ member.name }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ member.email }}</p>
                  </div>
                  <div v-if="member.id === user.id" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                    You
                  </div>
                </div>
                <div v-if="member.id !== user.id" class="flex items-center space-x-2">
                  <button
                    @click="removeMember(member)"
                    class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Invite New Member -->
          <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
            <h3 class="text-base font-medium text-gray-900 dark:text-white mb-4">Invite Team Member</h3>
            <form @submit.prevent="inviteMember" class="flex space-x-3">
              <div class="flex-1">
                <input
                  type="email"
                  v-model="inviteForm.email"
                  placeholder="Enter email address"
                  class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  :class="{'border-red-500': inviteForm.errors.email}"
                />
                <div v-if="inviteForm.errors.email" class="mt-1 text-sm text-red-600 dark:text-red-400">
                  {{ inviteForm.errors.email }}
                </div>
              </div>
              <button
                type="submit"
                :disabled="inviteForm.processing"
                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-medium rounded-md transition-colors"
              >
                <svg v-if="inviteForm.processing" class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                {{ inviteForm.processing ? 'Inviting...' : 'Send Invite' }}
              </button>
            </form>
          </div>

          <!-- Leave Team -->
          <div class="border-t border-gray-200 dark:border-gray-700 pt-6 mt-8">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-base font-medium text-red-600 dark:text-red-400">Leave Team</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">Remove yourself from this team</p>
              </div>
              <button
                @click="confirmingTeamLeave = true"
                class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-md transition-colors"
              >
                Leave Team
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- No Team -->
      <div v-else class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Create or Join a Team</h2>
          <p class="text-sm text-gray-600 dark:text-gray-400">Start collaborating with your team members</p>
        </div>
        <div class="p-6">
          <div class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No team yet</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Create a new team or accept an invitation to get started.</p>

            <div class="mt-6">
              <button
                @click="showCreateTeam = true"
                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md transition-colors"
              >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Create New Team
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Team Modal -->
    <Modal :show="showCreateTeam" @close="showCreateTeam = false">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900 dark:text-white">Create New Team</h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
          Create a new team to start collaborating with others.
        </p>

        <form @submit.prevent="createTeam" class="mt-6">
          <div>
            <label for="team_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Team Name</label>
            <input
              type="text"
              id="team_name"
              v-model="createTeamForm.name"
              class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
              :class="{'border-red-500': createTeamForm.errors.name}"
            />
            <div v-if="createTeamForm.errors.name" class="mt-2 text-sm text-red-600 dark:text-red-400">
              {{ createTeamForm.errors.name }}
            </div>
          </div>

          <div class="mt-6 flex justify-end space-x-3">
            <button
              type="button"
              @click="showCreateTeam = false"
              class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 dark:hover:bg-gray-500 rounded-md transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="createTeamForm.processing"
              class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-medium rounded-md transition-colors"
            >
              <svg v-if="createTeamForm.processing" class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
              </svg>
              {{ createTeamForm.processing ? 'Creating...' : 'Create Team' }}
            </button>
          </div>
        </form>
      </div>
    </Modal>

    <!-- Leave Team Confirmation Modal -->
    <Modal :show="confirmingTeamLeave" @close="confirmingTeamLeave = false">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900 dark:text-white">
          Leave Team
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
          Are you sure you want to leave this team? You will lose access to all team websites and data.
        </p>

        <div class="mt-6 flex justify-end space-x-3">
          <button
            @click="confirmingTeamLeave = false"
            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 dark:hover:bg-gray-500 rounded-md transition-colors"
          >
            Cancel
          </button>
          <button
            @click="leaveTeam"
            :disabled="leaveTeamForm.processing"
            class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 disabled:bg-red-400 text-white font-medium rounded-md transition-colors"
          >
            <svg v-if="leaveTeamForm.processing" class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            {{ leaveTeamForm.processing ? 'Leaving...' : 'Leave Team' }}
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
  user: Object,
  team: Object,
  teamMembers: Array
})

const showCreateTeam = ref(false)
const confirmingTeamLeave = ref(false)

// Create team form
const createTeamForm = useForm({
  name: '',
})

// Invite member form
const inviteForm = useForm({
  email: '',
})

// Leave team form
const leaveTeamForm = useForm({})

function createTeam() {
  createTeamForm.post('/settings/team', {
    preserveScroll: true,
    onSuccess: () => {
      showCreateTeam.value = false
      createTeamForm.reset()
    }
  })
}

function inviteMember() {
  inviteForm.post('/settings/team/invite', {
    preserveScroll: true,
    onSuccess: () => {
      inviteForm.reset()
    }
  })
}

function removeMember(member) {
  if (confirm(`Are you sure you want to remove ${member.name} from the team?`)) {
    // Implementation would go here
  }
}

function leaveTeam() {
  leaveTeamForm.delete('/settings/team/leave', {
    preserveScroll: true,
    onSuccess: () => {
      confirmingTeamLeave.value = false
    }
  })
}
</script>