<script setup lang="ts">
import { ref, computed, watch, nextTick, onBeforeUnmount } from 'vue';
import { router } from '@inertiajs/vue3';
import { X, ArrowRightLeft, Users, AlertTriangle, CheckCircle } from 'lucide-vue-next';
import SmartTeamPicker from './SmartTeamPicker.vue';

interface Website {
  id: number;
  name: string;
  url: string;
  team_badge: {
    type: 'team' | 'personal';
    name: string | null;
    color: string;
  };
}

interface Team {
  id: number;
  name: string;
  description?: string;
  member_count?: number;
  user_role?: string;
}

interface Props {
  isOpen: boolean;
  selectedWebsites: Website[];
  availableTeams: Team[];
}

interface Emits {
  (e: 'close'): void;
  (e: 'transfer-completed'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const selectedTeamId = ref<number | null>(null);
const selectedTeam = computed<Team | null>(() => {
  if (selectedTeamId.value == null) return null;
  return props.availableTeams.find(t => t.id === selectedTeamId.value) || null;
});
const isTransferring = ref(false);
const transferMode = ref<'to-team' | 'to-personal'>('to-team');
const confirmationStep = ref(false);

const personalWebsites = computed(() =>
  props.selectedWebsites.filter(site => site.team_badge.type === 'personal')
);

const teamWebsites = computed(() =>
  props.selectedWebsites.filter(site => site.team_badge.type === 'team')
);

const canTransferToTeam = computed(() =>
  transferMode.value === 'to-team' && selectedTeam.value && personalWebsites.value.length > 0
);

const canTransferToPersonal = computed(() =>
  transferMode.value === 'to-personal' && teamWebsites.value.length > 0
);

const isValid = computed(() =>
  canTransferToTeam.value || canTransferToPersonal.value
);

const transferSummary = computed(() => {
  if (transferMode.value === 'to-team' && selectedTeam.value) {
    return {
      action: 'Transfer to Team',
      count: personalWebsites.value.length,
      target: selectedTeam.value.name,
      websites: personalWebsites.value,
    };
  } else if (transferMode.value === 'to-personal') {
    return {
      action: 'Transfer to Personal',
      count: teamWebsites.value.length,
      target: 'Personal Account',
      websites: teamWebsites.value,
    };
  }
  return null;
});

// Derived counts and modal a11y helpers
const selectedCount = computed(() =>
  transferMode.value === 'to-team' ? personalWebsites.value.length : teamWebsites.value.length
);

const modalRef = ref<HTMLElement | null>(null);
const handleEsc = (e: KeyboardEvent) => {
  if (e.key === 'Escape' && props.isOpen) {
    e.preventDefault();
    closeModal();
  }
};

// Manage focus and keyboard when modal opens/closes
watch(() => props.isOpen, async (open) => {
  if (open) {
    await nextTick();
    modalRef.value?.focus();
    window.addEventListener('keydown', handleEsc);
  } else {
    window.removeEventListener('keydown', handleEsc);
  }
});

onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleEsc);
});


const proceedToConfirmation = () => {
  if (!isValid.value) return;
  confirmationStep.value = true;
};

const goBack = () => {
  confirmationStep.value = false;
};

const executeTransfer = () => {
  if (!isValid.value || isTransferring.value) return;

  isTransferring.value = true;

  const websiteIds = transferSummary.value?.websites.map(w => w.id) || [];

  if (transferMode.value === 'to-team' && selectedTeamId.value) {
    router.post('/ssl/websites/bulk-transfer-to-team', {
      website_ids: websiteIds,
      team_id: selectedTeamId.value,
    }, {
      onSuccess: () => {
        emit('transfer-completed');
        closeModal();
      },
      onError: (errors) => {
        console.error('Bulk transfer failed:', errors);
      },
      onFinish: () => {
        isTransferring.value = false;
      }
    });
  } else if (transferMode.value === 'to-personal') {
    router.post('/ssl/websites/bulk-transfer-to-personal', {
      website_ids: websiteIds,
    }, {
      onSuccess: () => {
        emit('transfer-completed');
        closeModal();
      },
      onError: (errors) => {
        console.error('Bulk transfer failed:', errors);
      },
      onFinish: () => {
        isTransferring.value = false;
      }
    });
  }
};

const closeModal = () => {
  selectedTeamId.value = null;
  confirmationStep.value = false;
  transferMode.value = 'to-team';
  emit('close');
};
</script>

<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    @click="closeModal"
  >
    <div
      ref="modalRef"
      tabindex="-1"
      role="dialog"
      aria-modal="true"
      :aria-labelledby="'bulk-transfer-title'"
      class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto"
      @click.stop
    >
      <!-- Header -->
      <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center space-x-3">
          <div class="rounded-lg bg-blue-100 dark:bg-blue-900/30 p-2">
            <ArrowRightLeft class="h-5 w-5 text-blue-600 dark:text-blue-400" />
          </div>
          <div>
            <h2 id="bulk-transfer-title" class="text-xl font-semibold text-gray-900 dark:text-gray-100">
              {{ confirmationStep ? 'Confirm Transfer' : 'Bulk Website Transfer' }}
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              {{ confirmationStep ? 'Review and confirm your transfer' : `Transfer ${selectedWebsites.length} selected websites` }}
            </p>
          </div>
        </div>
        <button
          @click="closeModal"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
        >
          <X class="h-6 w-6" />
        </button>
      </div>

      <!-- Content -->
      <div class="p-6 space-y-6">
        <!-- Step 1: Transfer Configuration -->
        <div v-if="!confirmationStep">
          <!-- Transfer Mode Selection -->
          <div class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Transfer Options</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Transfer to Team -->
              <button
                @click="transferMode = 'to-team'"
                class="p-4 rounded-lg border-2 transition-all duration-200"
                :class="{
                  'border-blue-500 bg-blue-50 dark:bg-blue-900/20': transferMode === 'to-team',
                  'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600': transferMode !== 'to-team',
                  'opacity-50 cursor-not-allowed pointer-events-none': personalWebsites.length === 0
                }"
                :disabled="personalWebsites.length === 0"
                :aria-disabled="personalWebsites.length === 0"
              >
                <div class="flex items-center space-x-3">
                  <div class="rounded-lg bg-blue-100 dark:bg-blue-900/30 p-2">
                    <Users class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                  </div>
                  <div class="text-left">
                    <p class="font-semibold text-gray-900 dark:text-gray-100">Transfer to Team</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                      {{ personalWebsites.length }} personal sites available
                    </p>
                  </div>
                </div>
              </button>

              <!-- Transfer to Personal -->
              <button
                @click="transferMode = 'to-personal'"
                class="p-4 rounded-lg border-2 transition-all duration-200"
                :class="{
                  'border-green-500 bg-green-50 dark:bg-green-900/20': transferMode === 'to-personal',
                  'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600': transferMode !== 'to-personal',
                  'opacity-50 cursor-not-allowed pointer-events-none': teamWebsites.length === 0
                }"
                :disabled="teamWebsites.length === 0"
                :aria-disabled="teamWebsites.length === 0"
              >
                <div class="flex items-center space-x-3">
                  <div class="rounded-lg bg-green-100 dark:bg-green-900/30 p-2">
                    <ArrowRightLeft class="h-5 w-5 text-green-600 dark:text-green-400" />
                  </div>
                  <div class="text-left">
                    <p class="font-semibold text-gray-900 dark:text-gray-100">Transfer to Personal</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                      {{ teamWebsites.length }} team sites available
                    </p>
                  </div>
                </div>
              </button>
            </div>
          </div>

          <!-- Team Selection (only for to-team transfers) -->
          <div v-if="transferMode === 'to-team'" class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Select Target Team</h3>
            <SmartTeamPicker
              :teams="availableTeams"
              v-model:selectedTeamId="selectedTeamId"
              placeholder="Choose a team for your websites..."
              :show-member-count="true"
              :show-user-role="true"
            />
          </div>

          <!-- Preview of websites to transfer -->
          <div class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
              Websites to Transfer
              <span class="text-sm font-normal text-gray-600 dark:text-gray-400">
                ({{ selectedCount }} selected)
              </span>
            </h3>

            <div class="max-h-64 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg">
              <div
                v-for="website in (transferMode === 'to-team' ? personalWebsites : teamWebsites)"
                :key="website.id"
                class="flex items-center justify-between p-3 border-b border-gray-100 dark:border-gray-800 last:border-b-0"
              >
                <div>
                  <p class="font-medium text-gray-900 dark:text-gray-100">{{ website.name }}</p>
                  <p class="text-sm text-gray-600 dark:text-gray-400">{{ website.url }}</p>
                </div>
                <span
                  class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                  :class="{
                    'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': website.team_badge.type === 'team',
                    'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200': website.team_badge.type === 'personal'
                  }"
                >
                  {{ website.team_badge.type === 'team' ? website.team_badge.name : 'Personal' }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Step 2: Confirmation -->
        <div v-else-if="transferSummary" class="space-y-6">
          <!-- Transfer Summary -->
          <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6">
            <div class="flex items-center space-x-3 mb-4">
              <CheckCircle class="h-6 w-6 text-blue-600 dark:text-blue-400" />
              <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100">Transfer Summary</h3>
            </div>

            <div class="space-y-3">
              <div class="flex justify-between">
                <span class="text-blue-700 dark:text-blue-300">Action:</span>
                <span class="font-semibold text-blue-900 dark:text-blue-100">{{ transferSummary.action }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-blue-700 dark:text-blue-300">Websites:</span>
                <span class="font-semibold text-blue-900 dark:text-blue-100">{{ transferSummary.count }} sites</span>
              </div>
              <div class="flex justify-between">
                <span class="text-blue-700 dark:text-blue-300">Target:</span>
                <span class="font-semibold text-blue-900 dark:text-blue-100">{{ transferSummary.target }}</span>
              </div>
            </div>
          </div>

          <!-- Warning Message -->
          <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4">
            <div class="flex items-start space-x-3">
              <AlertTriangle class="h-5 w-5 text-amber-600 dark:text-amber-400 mt-0.5" />
              <div>
                <h4 class="font-semibold text-amber-900 dark:text-amber-100">Important Notice</h4>
                <p class="text-sm text-amber-700 dark:text-amber-300 mt-1">
                  This action will immediately change ownership of the selected websites.
                  Team members will gain access to transferred sites according to their roles.
                </p>
              </div>
            </div>
          </div>

          <!-- Final Website List -->
          <div class="space-y-3">
            <h4 class="font-semibold text-gray-900 dark:text-gray-100">Websites being transferred:</h4>
            <div class="space-y-2">
              <div
                v-for="website in transferSummary.websites"
                :key="website.id"
                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
              >
                <div>
                  <p class="font-medium text-gray-900 dark:text-gray-100">{{ website.name }}</p>
                  <p class="text-sm text-gray-600 dark:text-gray-400">{{ website.url }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="flex items-center justify-between p-6 border-t border-gray-200 dark:border-gray-700">
        <div v-if="confirmationStep" class="flex space-x-3">
          <button
            @click="goBack"
            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
          >
            Back
          </button>
        </div>
        <div v-else></div>

        <div class="flex space-x-3">
          <button
            @click="closeModal"
            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
          >
            Cancel
          </button>

          <button
            v-if="!confirmationStep"
            @click="proceedToConfirmation"
            :disabled="!isValid"
            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            Continue
          </button>

          <button
            v-else
            @click="executeTransfer"
            :disabled="isTransferring"
            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            <span v-if="isTransferring">Transferring...</span>
            <span v-else>Confirm Transfer</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
