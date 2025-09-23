<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Search, Users, User, ChevronDown, Check } from 'lucide-vue-next';

interface Team {
  id: number;
  name: string;
  description?: string;
  member_count?: number;
  user_role?: string;
}

interface Props {
  teams: Team[];
  selectedTeamId?: number | null;
  placeholder?: string;
  searchPlaceholder?: string;
  showMemberCount?: boolean;
  showUserRole?: boolean;
  maxDisplayedTeams?: number;
}

interface Emits {
  (e: 'update:selectedTeamId', teamId: number | null): void;
  (e: 'teamSelected', team: Team | null): void;
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Select a team...',
  searchPlaceholder: 'Search teams...',
  showMemberCount: true,
  showUserRole: false,
  maxDisplayedTeams: 100
});

const emit = defineEmits<Emits>();

const searchQuery = ref('');
const isOpen = ref(false);
const selectedTeam = ref<Team | null>(null);

// Initialize selected team from props
watch(() => props.selectedTeamId, (newId) => {
  if (newId) {
    const team = props.teams.find(t => t.id === newId);
    selectedTeam.value = team || null;
  } else {
    selectedTeam.value = null;
  }
}, { immediate: true });

const filteredTeams = computed(() => {
  if (!searchQuery.value) {
    return props.teams.slice(0, props.maxDisplayedTeams);
  }

  const query = searchQuery.value.toLowerCase();
  return props.teams
    .filter(team =>
      team.name.toLowerCase().includes(query) ||
      (team.description && team.description.toLowerCase().includes(query))
    )
    .slice(0, props.maxDisplayedTeams);
});

const selectTeam = (team: Team) => {
  selectedTeam.value = team;
  isOpen.value = false;
  searchQuery.value = '';
  emit('update:selectedTeamId', team.id);
  emit('teamSelected', team);
};

const clearSelection = () => {
  selectedTeam.value = null;
  isOpen.value = false;
  searchQuery.value = '';
  emit('update:selectedTeamId', null);
  emit('teamSelected', null);
};

const toggleDropdown = () => {
  isOpen.value = !isOpen.value;
  if (isOpen.value) {
    searchQuery.value = '';
  }
};
</script>

<template>
  <div class="relative">
    <!-- Selected Team Display / Dropdown Trigger -->
    <button
      @click="toggleDropdown"
      class="w-full flex items-center justify-between px-4 py-3 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm hover:border-gray-400 dark:hover:border-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
      :class="{
        'ring-2 ring-blue-500 border-blue-500': isOpen
      }"
    >
      <div class="flex items-center space-x-3 flex-1 text-left">
        <div v-if="selectedTeam" class="flex items-center space-x-3">
          <div class="rounded-lg bg-blue-100 dark:bg-blue-900/30 p-2">
            <Users class="h-4 w-4 text-blue-600 dark:text-blue-400" />
          </div>
          <div class="flex-1">
            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
              {{ selectedTeam.name }}
            </p>
            <p v-if="selectedTeam.description" class="text-xs text-gray-600 dark:text-gray-400 truncate">
              {{ selectedTeam.description }}
            </p>
            <div v-if="showMemberCount || showUserRole" class="flex items-center space-x-2 mt-1">
              <span v-if="showMemberCount" class="text-xs text-gray-500 dark:text-gray-500">
                {{ selectedTeam.member_count }} members
              </span>
              <span v-if="showUserRole && selectedTeam.user_role" class="text-xs px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded">
                {{ selectedTeam.user_role }}
              </span>
            </div>
          </div>
        </div>
        <div v-else class="flex items-center space-x-3">
          <div class="rounded-lg bg-gray-100 dark:bg-gray-700 p-2">
            <User class="h-4 w-4 text-gray-400 dark:text-gray-500" />
          </div>
          <span class="text-gray-500 dark:text-gray-400">{{ placeholder }}</span>
        </div>
      </div>

      <div class="flex items-center space-x-2">
        <button
          v-if="selectedTeam"
          @click.stop="clearSelection"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
        <ChevronDown
          class="h-4 w-4 text-gray-400 transition-transform duration-200"
          :class="{ 'transform rotate-180': isOpen }"
        />
      </div>
    </button>

    <!-- Dropdown Panel -->
    <div
      v-show="isOpen"
      class="absolute z-50 w-full mt-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg overflow-hidden"
    >
      <!-- Search Bar -->
      <div class="p-3 border-b border-gray-200 dark:border-gray-700">
        <div class="relative">
          <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
          <input
            v-model="searchQuery"
            type="text"
            :placeholder="searchPlaceholder"
            class="w-full pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
        </div>
      </div>

      <!-- Teams List -->
      <div class="max-h-64 overflow-y-auto">
        <div v-if="filteredTeams.length === 0" class="px-4 py-6 text-center">
          <Users class="h-8 w-8 text-gray-300 dark:text-gray-600 mx-auto mb-2" />
          <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ searchQuery ? 'No teams found matching your search' : 'No teams available' }}
          </p>
        </div>

        <button
          v-for="team in filteredTeams"
          :key="team.id"
          @click="selectTeam(team)"
          class="w-full px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700"
          :class="{
            'bg-blue-50 dark:bg-blue-900/20': selectedTeam?.id === team.id
          }"
        >
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3 flex-1">
              <div class="rounded-lg bg-blue-100 dark:bg-blue-900/30 p-2">
                <Users class="h-4 w-4 text-blue-600 dark:text-blue-400" />
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">
                  {{ team.name }}
                </p>
                <p v-if="team.description" class="text-xs text-gray-600 dark:text-gray-400 truncate">
                  {{ team.description }}
                </p>
                <div v-if="showMemberCount || showUserRole" class="flex items-center space-x-2 mt-1">
                  <span v-if="showMemberCount" class="text-xs text-gray-500 dark:text-gray-500">
                    {{ team.member_count }} members
                  </span>
                  <span v-if="showUserRole && team.user_role" class="text-xs px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded">
                    {{ team.user_role }}
                  </span>
                </div>
              </div>
            </div>
            <Check
              v-if="selectedTeam?.id === team.id"
              class="h-4 w-4 text-blue-600 dark:text-blue-400 flex-shrink-0"
            />
          </div>
        </button>
      </div>

      <!-- Footer -->
      <div v-if="filteredTeams.length > 0" class="px-4 py-2 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-750">
        <p class="text-xs text-gray-500 dark:text-gray-400 text-center">
          {{ filteredTeams.length }} {{ filteredTeams.length === 1 ? 'team' : 'teams' }} shown
          <span v-if="searchQuery && filteredTeams.length < teams.length">
            ({{ teams.length - filteredTeams.length }} filtered out)
          </span>
        </p>
      </div>
    </div>
  </div>
</template>