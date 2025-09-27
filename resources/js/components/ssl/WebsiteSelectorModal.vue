<template>
  <Dialog :open="isOpen" @update:open="handleClose">
    <DialogContent class="max-w-2xl max-h-[90vh] overflow-hidden">
      <DialogHeader>
        <DialogTitle class="flex items-center space-x-3">
          <div class="rounded-lg bg-blue-100 dark:bg-blue-900/30 p-2">
            <Globe class="h-5 w-5 text-blue-600 dark:text-blue-400" />
          </div>
          <div>
            <div class="text-xl font-bold text-gray-900 dark:text-gray-100">
              Select Websites
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
              Choose which websites to include in the bulk operation
            </div>
          </div>
        </DialogTitle>
      </DialogHeader>

      <div class="space-y-4 py-4 max-h-[60vh] overflow-y-auto">
        <!-- Search and Filters -->
        <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-4">
          <div class="flex-1">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search websites..."
              class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
            />
          </div>
          <select
            v-model="statusFilter"
            class="px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
          >
            <option value="all">All Status</option>
            <option value="valid">Valid SSL</option>
            <option value="expiring">Expiring Soon</option>
            <option value="expired">Expired</option>
            <option value="invalid">Invalid</option>
          </select>
        </div>

        <!-- Selection Actions -->
        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
          <div class="flex items-center space-x-4">
            <Button @click="selectAll" size="sm" variant="outline">
              <CheckSquare class="h-4 w-4 mr-2" />
              Select All
            </Button>
            <Button @click="selectNone" size="sm" variant="outline">
              <Square class="h-4 w-4 mr-2" />
              Select None
            </Button>
            <Button @click="invertSelection" size="sm" variant="outline">
              <RotateCcw class="h-4 w-4 mr-2" />
              Invert
            </Button>
          </div>
          <div class="text-sm text-gray-600 dark:text-gray-400">
            {{ selectedWebsites.length }} of {{ filteredWebsites.length }} selected
          </div>
        </div>

        <!-- Website List -->
        <div class="space-y-2">
          <div
            v-for="website in filteredWebsites"
            :key="website.id"
            class="flex items-center space-x-3 p-3 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer"
            :class="{ 'border-primary bg-primary/5': selectedWebsites.includes(website.id) }"
            @click="toggleWebsite(website.id)"
          >
            <Checkbox
              :checked="selectedWebsites.includes(website.id)"
              @update:checked="toggleWebsite(website.id)"
              @click.stop
            />

            <div class="flex-1 min-w-0">
              <div class="flex items-center space-x-2">
                <h4 class="font-medium text-gray-900 dark:text-gray-100 truncate">{{ website.name }}</h4>
                <Badge :variant="getStatusVariant(website.sslStatus)">
                  {{ website.sslStatus }}
                </Badge>
              </div>
              <p class="text-sm text-gray-600 dark:text-gray-400 truncate">{{ website.url }}</p>
              <div class="flex items-center space-x-4 text-xs text-gray-500 dark:text-gray-500 mt-1">
                <span>SSL Score: {{ website.sslScore }}/100</span>
                <span>Expires: {{ formatExpiryDate(website.expiresAt) }}</span>
                <span v-if="website.team">Team: {{ website.team }}</span>
              </div>
            </div>

            <!-- SSL Status Icon -->
            <div class="flex-shrink-0">
              <component
                :is="getSslStatusIcon(website.sslStatus)"
                class="h-5 w-5"
                :class="getSslStatusIconColor(website.sslStatus)"
              />
            </div>
          </div>
        </div>

        <!-- No Results -->
        <div v-if="filteredWebsites.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
          <Globe class="h-12 w-12 mx-auto mb-2 opacity-50" />
          <p class="font-medium">No websites found</p>
          <p class="text-sm mt-1">Try adjusting your search or filter criteria</p>
        </div>
      </div>

      <div class="flex justify-between items-center pt-4 border-t">
        <div class="text-sm text-gray-600 dark:text-gray-400">
          {{ selectedWebsites.length }} website{{ selectedWebsites.length !== 1 ? 's' : '' }} selected
        </div>
        <div class="flex space-x-3">
          <Button @click="handleClose" variant="outline">
            Cancel
          </Button>
          <Button
            @click="proceedWithSelection"
            :disabled="selectedWebsites.length === 0"
            class="flex items-center space-x-2"
          >
            <ArrowRight class="h-4 w-4" />
            <span>Proceed ({{ selectedWebsites.length }})</span>
          </Button>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import {
  Globe,
  CheckSquare,
  Square,
  RotateCcw,
  ArrowRight,
  Shield,
  AlertTriangle,
  XCircle,
  CheckCircle
} from 'lucide-vue-next';

interface Website {
  id: number;
  name: string;
  url: string;
  sslStatus: 'valid' | 'expiring' | 'expired' | 'invalid';
  sslScore: number;
  expiresAt: string;
  team?: string;
}

interface Props {
  isOpen: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  close: [];
  selected: [websites: Website[]];
}>();

const searchQuery = ref('');
const statusFilter = ref('all');
const selectedWebsites = ref<number[]>([]);

// Mock website data - in real app this would come from props or API
const allWebsites: Website[] = [
  {
    id: 1,
    name: 'Office Manager Pro',
    url: 'omp.office-manager-pro.com',
    sslStatus: 'valid',
    sslScore: 92,
    expiresAt: '2025-03-15T00:00:00Z',
    team: 'Production'
  },
  {
    id: 2,
    name: 'Red Gas Austria',
    url: 'www.redgas.at',
    sslStatus: 'expiring',
    sslScore: 88,
    expiresAt: '2024-10-15T00:00:00Z',
    team: 'Marketing'
  },
  {
    id: 3,
    name: 'Fairnando',
    url: 'www.fairnando.at',
    sslStatus: 'valid',
    sslScore: 76,
    expiresAt: '2025-01-20T00:00:00Z'
  },
  {
    id: 4,
    name: 'Legacy API',
    url: 'legacy-api.example.com',
    sslStatus: 'expired',
    sslScore: 45,
    expiresAt: '2024-08-01T00:00:00Z',
    team: 'Development'
  },
  {
    id: 5,
    name: 'Test Environment',
    url: 'test.example.com',
    sslStatus: 'invalid',
    sslScore: 32,
    expiresAt: '2024-12-01T00:00:00Z'
  }
];

const filteredWebsites = computed(() => {
  let filtered = allWebsites;

  // Filter by search query
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(website =>
      website.name.toLowerCase().includes(query) ||
      website.url.toLowerCase().includes(query)
    );
  }

  // Filter by status
  if (statusFilter.value !== 'all') {
    filtered = filtered.filter(website => website.sslStatus === statusFilter.value);
  }

  return filtered;
});

const getStatusVariant = (status: string) => {
  switch (status) {
    case 'valid':
      return 'default';
    case 'expiring':
      return 'secondary';
    case 'expired':
      return 'destructive';
    case 'invalid':
      return 'destructive';
    default:
      return 'outline';
  }
};

const getSslStatusIcon = (status: string) => {
  switch (status) {
    case 'valid':
      return CheckCircle;
    case 'expiring':
      return AlertTriangle;
    case 'expired':
      return XCircle;
    case 'invalid':
      return XCircle;
    default:
      return Shield;
  }
};

const getSslStatusIconColor = (status: string): string => {
  switch (status) {
    case 'valid':
      return 'text-green-600 dark:text-green-400';
    case 'expiring':
      return 'text-yellow-600 dark:text-yellow-400';
    case 'expired':
      return 'text-red-600 dark:text-red-400';
    case 'invalid':
      return 'text-red-600 dark:text-red-400';
    default:
      return 'text-gray-600 dark:text-gray-400';
  }
};

const formatExpiryDate = (dateString: string): string => {
  const date = new Date(dateString);
  const now = new Date();
  const diffInDays = Math.ceil((date.getTime() - now.getTime()) / (1000 * 60 * 60 * 24));

  if (diffInDays < 0) {
    return `Expired ${Math.abs(diffInDays)} days ago`;
  } else if (diffInDays === 0) {
    return 'Expires today';
  } else if (diffInDays === 1) {
    return 'Expires tomorrow';
  } else if (diffInDays <= 30) {
    return `${diffInDays} days`;
  } else {
    return date.toLocaleDateString();
  }
};

const toggleWebsite = (websiteId: number) => {
  const index = selectedWebsites.value.indexOf(websiteId);
  if (index > -1) {
    selectedWebsites.value.splice(index, 1);
  } else {
    selectedWebsites.value.push(websiteId);
  }
};

const selectAll = () => {
  selectedWebsites.value = filteredWebsites.value.map(website => website.id);
};

const selectNone = () => {
  selectedWebsites.value = [];
};

const invertSelection = () => {
  const currentlySelected = new Set(selectedWebsites.value);
  selectedWebsites.value = filteredWebsites.value
    .filter(website => !currentlySelected.has(website.id))
    .map(website => website.id);
};

const handleClose = () => {
  emit('close');
  // Reset state
  selectedWebsites.value = [];
  searchQuery.value = '';
  statusFilter.value = 'all';
};

const proceedWithSelection = () => {
  const selectedWebsiteData = allWebsites.filter(website =>
    selectedWebsites.value.includes(website.id)
  );

  emit('selected', selectedWebsiteData);

  // Reset state
  selectedWebsites.value = [];
  searchQuery.value = '';
  statusFilter.value = 'all';
};
</script>