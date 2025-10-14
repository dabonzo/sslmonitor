# SSL Monitor v4 - Complete Coding Guide

**A comprehensive guide for AI coding agents to add new pages, edit existing ones, and update the SSL Monitor v4 application efficiently.**

## Quick Reference

- **File Structure**: See [File Organization](#file-organization--naming-conventions)
- **Page Template**: See [Standard Page Template](#standard-page-template)
- **Component Patterns**: See [Common Components](#common-component-patterns)
- **Styling Rules**: See [Styling Guidelines](#styling-guidelines)

---

## 1. File Organization & Naming Conventions

### Directory Structure
```
resources/js/pages/
├── Dashboard.vue                    # Main dashboard page
├── Analytics/
│   └── Index.vue                   # Analytics index page
├── Alerts/                          # Alert management pages
│   ├── Index.vue                   # Alert rules configuration
│   ├── Notifications.vue           # Notification settings
│   └── History.vue                 # Alert history
├── Auth/                           # Authentication pages (PascalCase)
│   ├── Login.vue
│   ├── Register.vue
│   ├── TwoFactorChallenge.vue
│   └── ...
├── Debug/                          # Debug/development pages
│   ├── SslOverrides.vue
│   └── AlertTesting.vue
├── Reports/
│   └── Index.vue
├── Ssl/                            # SSL monitoring pages
│   ├── Websites/
│   │   ├── Index.vue               # Main websites list
│   │   ├── Create.vue              # Add new website
│   │   ├── Edit.vue                # Edit website
│   │   └── Show.vue                # View website details
│   └── BulkOperations/
│       └── Index.vue
└── Settings/                       # Settings pages
    ├── Profile.vue
    ├── Team.vue
    ├── Alerts.vue
    └── ...
```

### Naming Conventions
- **Page files**: PascalCase (`Dashboard.vue`, `SslOverrides.vue`)
- **Index pages**: Always `Index.vue`
- **Route files**: kebab-case (`ssl-overrides.ts`, `alert-testing.ts`)
- **Components**: PascalCase (`BulkTransferModal.vue`)
- **Props interfaces**: PascalCase (`Props`, `Website`, `AlertConfiguration`)

### Route File Locations
```
resources/js/routes/
├── ssl.ts                          # SSL-related routes
├── debug.ts                        # Debug routes
├── alerts.ts                       # Alert routes
└── index.ts                        # Route index
```

---

## 2. Standard Page Template

### Complete Page Template
```vue
<script setup lang="ts">
// Core imports - ALWAYS include these
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

// Icon imports - from lucide-vue-next
import {
  Shield,
  Plus,
  Edit,
  Trash2,
  Search,
  Filter,
  RefreshCw
} from 'lucide-vue-next';

// Feature-specific route imports
import featureRoutes from '@/routes/feature';

// Props interface - ALWAYS define props
interface Props {
  // Define your props here
  items?: any[];
  stats?: {
    total: number;
    active: number;
  };
}

const props = defineProps<Props>();

// Reactive state
const loading = ref(false);
const searchQuery = ref('');
const selectedItem = ref<number | null>(null);

// Computed properties
const filteredItems = computed(() => {
  if (!searchQuery.value) return props.items || [];
  return props.items.filter(item =>
    item.name.toLowerCase().includes(searchQuery.value.toLowerCase())
  );
});

// Methods
const handleAction = (item: any) => {
  // Action logic
};

const refreshData = async () => {
  loading.value = true;
  try {
    await router.reload();
  } finally {
    loading.value = false;
  }
};

// Lifecycle
onMounted(() => {
  // Initialization if needed
});
</script>

<template>
  <Head title="Page Title" />

  <DashboardLayout title="Page Title">
    <div class="page-name space-y-6">
      <!-- Page Header -->
      <div class="flex items-center justify-between mb-4">
        <div>
          <h1 class="text-2xl font-semibold text-foreground">Page Title</h1>
          <p class="text-muted-foreground">Page description with context</p>
        </div>

        <!-- Header Actions -->
        <div class="flex items-center space-x-2">
          <Link
            :href="featureRoutes.create.url"
            class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors"
          >
            <Plus class="h-4 w-4 mr-2" />
            Add New
          </Link>
        </div>
      </div>

      <!-- Main Content -->
      <div class="space-y-6">
        <!-- Your content sections here -->
      </div>
    </div>
  </DashboardLayout>
</template>
```

### Required Imports Pattern
```typescript
// ALWAYS include these core imports
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

// Icons from lucide-vue-next
import { IconName } from 'lucide-vue-next';

// Feature routes
import routes from '@/routes/feature';

// Optional imports based on needs
import axios from 'axios';
import { useToast } from '@/composables/useToast';
```

---

## 3. Common Component Patterns

### Stats Cards Grid
```vue
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
  <div class="glass-card-strong rounded-2xl p-6">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm font-medium text-muted-foreground">Total Items</p>
        <p class="text-2xl font-bold text-foreground">{{ stats.total }}</p>
      </div>
      <div class="status-badge-info p-3">
        <IconName class="h-6 w-6" />
      </div>
    </div>
  </div>

  <!-- Repeat for other stats -->
</div>
```

### Search and Filter Bar
```vue
<div class="flex flex-col sm:flex-row gap-4 mb-6">
  <!-- Search -->
  <div class="flex-1">
    <div class="relative">
      <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Search..."
        class="w-full pl-10 pr-4 py-2 border border-border bg-background rounded-md focus:border-primary focus:ring-1 focus:ring-primary"
      />
    </div>
  </div>

  <!-- Filters -->
  <div class="flex items-center space-x-2">
    <select
      v-model="selectedFilter"
      class="px-3 py-2 border border-border bg-background rounded-md focus:border-primary focus:ring-1 focus:ring-primary"
    >
      <option value="all">All Items</option>
      <option value="active">Active</option>
      <option value="inactive">Inactive</option>
    </select>

    <button
      @click="refreshData"
      :disabled="loading"
      class="p-2 text-muted-foreground hover:text-foreground transition-colors"
    >
      <RefreshCw :class="['h-4 w-4', loading && 'animate-spin']" />
    </button>
  </div>
</div>
```

### Status Badge
```vue
<span
  class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
  :class="{
    'status-badge-success': status === 'active' || status === 'valid',
    'status-badge-warning': status === 'warning' || status === 'expiring',
    'status-badge-destructive': status === 'error' || status === 'expired',
    'status-badge-info': status === 'info'
  }"
>
  {{ status }}
</span>
```

### Action Buttons Group
```vue
<div class="flex items-center space-x-2">
  <!-- Primary Action -->
  <button
    @click="primaryAction"
    class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
    :disabled="loading"
  >
    <IconName class="h-4 w-4 mr-2" />
    Action
  </button>

  <!-- Secondary Action -->
  <button
    @click="secondaryAction"
    class="inline-flex items-center px-3 py-2 text-foreground bg-secondary hover:bg-secondary/80 rounded-md transition-colors"
  >
    <IconName class="h-4 w-4 mr-2" />
    Secondary
  </button>

  <!-- Ghost Action -->
  <button
    @click="ghostAction"
    class="inline-flex items-center px-3 py-2 text-muted-foreground hover:text-foreground transition-colors"
  >
    <IconName class="h-4 w-4 mr-2" />
    Ghost
  </button>
</div>
```

### Table Pattern (Desktop)
```vue
<div class="hidden lg:block overflow-x-auto">
  <table class="w-full">
    <thead>
      <tr class="border-b border-border">
        <th class="text-left p-4 font-medium text-foreground">Name</th>
        <th class="text-left p-4 font-medium text-foreground">Status</th>
        <th class="text-left p-4 font-medium text-foreground">Created</th>
        <th class="text-right p-4 font-medium text-foreground">Actions</th>
      </tr>
    </thead>
    <tbody>
      <tr
        v-for="item in items"
        :key="item.id"
        class="border-b border-border hover:bg-muted/50 transition-colors"
      >
        <td class="p-4">
          <div>
            <div class="font-medium text-foreground">{{ item.name }}</div>
            <div class="text-sm text-muted-foreground">{{ item.description }}</div>
          </div>
        </td>
        <td class="p-4">
          <StatusBadge :status="item.status" />
        </td>
        <td class="p-4 text-sm text-muted-foreground">
          {{ formatDate(item.created_at) }}
        </td>
        <td class="p-4">
          <div class="flex items-center justify-end space-x-2">
            <Link
              :href="routes.edit(item.id).url"
              class="p-2 text-muted-foreground hover:text-foreground transition-colors"
            >
              <Edit class="h-4 w-4" />
            </Link>
            <button
              @click="deleteItem(item.id)"
              class="p-2 text-muted-foreground hover:text-destructive transition-colors"
            >
              <Trash2 class="h-4 w-4" />
            </button>
          </div>
        </td>
      </tr>
    </tbody>
  </table>
</div>
```

### Mobile Card Pattern
```vue
<div class="block lg:hidden space-y-4">
  <div
    v-for="item in items"
    :key="item.id"
    class="glass-card rounded-xl p-4"
  >
    <div class="flex items-start justify-between">
      <div class="flex-1">
        <h3 class="font-medium text-foreground">{{ item.name }}</h3>
        <p class="text-sm text-muted-foreground mt-1">{{ item.description }}</p>
      </div>
      <StatusBadge :status="item.status" />
    </div>

    <div class="flex items-center justify-between mt-4">
      <span class="text-sm text-muted-foreground">
        {{ formatDate(item.created_at) }}
      </span>
      <div class="flex items-center space-x-2">
        <Link
          :href="routes.edit(item.id).url"
          class="p-2 text-muted-foreground hover:text-foreground transition-colors"
        >
          <Edit class="h-4 w-4" />
        </Link>
        <button
          @click="deleteItem(item.id)"
          class="p-2 text-muted-foreground hover:text-destructive transition-colors"
        >
          <Trash2 class="h-4 w-4" />
        </button>
      </div>
    </div>
  </div>
</div>
```

### Empty State
```vue
<div v-if="items.length === 0" class="glass-card-strong rounded-2xl p-12 text-center">
  <div class="status-badge-info p-4 inline-block mb-4">
    <IconName class="h-16 w-16" />
  </div>
  <h3 class="text-xl font-semibold text-foreground mb-2">No Items Found</h3>
  <p class="text-muted-foreground mb-6">
    Get started by creating your first item.
  </p>
  <Link
    :href="routes.create.url"
    class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors"
  >
    <Plus class="h-4 w-4 mr-2" />
    Create First Item
  </Link>
</div>
```

---

## 4. Styling Guidelines

### MUST USE - Semantic Color Classes
```css
/* ALWAYS use these instead of hardcoded colors */
.text-foreground              /* Primary text */
.text-muted-foreground        /* Secondary text */
.bg-card                     /* Card backgrounds */
.text-card-foreground        /* Card text */
.bg-background              /* Main background */
.border-border              /* Borders */
.bg-primary                 /* Selected/primary states */
.text-primary-foreground     /* Text on primary bg */
.hover:bg-accent             /* Hover states */
.hover:text-accent-foreground /* Hover text */
```

### Glass Morphism Effects
```css
.glass-card                 /* Light glass effect */
.glass-card-strong          /* Stronger glass effect */
```

### Status Badge Classes
```css
.status-badge-info          /* Blue - informational */
.status-badge-success        /* Green - success */
.status-badge-warning        /* Orange - warning */
.status-badge-destructive    /* Red - error/danger */
```

### Interactive Classes
```css
.hover-lift                 /* Subtle lift on hover */
.button-ghost              /* Ghost button styling */
.button-soft               /* Soft background button */
.selected-state            /* Selected item styling */
.input-styled              /* Form input styling */
```

### NEVER USE - Manual Dark Mode Classes
```css
/* AVOID these - use semantic classes instead */
❌ dark:text-white
❌ dark:bg-gray-800
❌ bg-white dark:bg-gray-900
❌ text-gray-900 dark:text-gray-100

/* INSTEAD use these */
✅ text-foreground
✅ bg-card
✅ text-muted-foreground
✅ border-border
```

### Standard Spacing
```css
.space-y-6                  /* Section spacing */
.space-x-2                  /* Button/icon spacing */
.mb-4                       /* Header margin */
.p-6                        /* Card padding */
.gap-6                      /* Grid gap */
```

### Typography Standards
```css
/* Page titles */
.text-2xl.font-semibold.text-foreground

/* Section headers */
.text-xl.font-bold.text-foreground

/* Card titles */
.text-lg.font-semibold.text-foreground

/* Body text */
.text-foreground

/* Secondary text */
.text-muted-foreground

/* Small text */
.text-sm.text-muted-foreground
```

---

## 5. Form Patterns

### Standard Form Layout
```vue
<form @submit.prevent="handleSubmit">
  <div class="space-y-6">
    <!-- Text Input -->
    <div>
      <label class="block text-sm font-medium text-foreground mb-2">
        Field Name <span class="text-destructive">*</span>
      </label>
      <input
        v-model="form.field"
        type="text"
        class="input-styled"
        placeholder="Enter value"
        :class="{ 'border-destructive': form.errors.field }"
        required
      />
      <div v-if="form.errors.field" class="text-destructive text-xs mt-1">
        {{ form.errors.field }}
      </div>
    </div>

    <!-- Select Dropdown -->
    <div>
      <label class="block text-sm font-medium text-foreground mb-2">
        Select Option
      </label>
      <select
        v-model="form.select_field"
        class="input-styled"
      >
        <option value="">Choose an option</option>
        <option value="option1">Option 1</option>
        <option value="option2">Option 2</option>
      </select>
    </div>

    <!-- Checkbox -->
    <div class="flex items-center">
      <input
        v-model="form.checkbox_field"
        type="checkbox"
        class="rounded border-border text-primary focus:ring-primary"
        id="checkbox"
      />
      <label for="checkbox" class="ml-2 text-sm text-foreground">
        Enable this feature
      </label>
    </div>

    <!-- Form Actions -->
    <div class="flex items-center justify-end space-x-4 pt-6 border-t border-border">
      <Link
        :href="routes.index.url"
        class="px-4 py-2 text-muted-foreground hover:text-foreground transition-colors"
      >
        Cancel
      </Link>
      <button
        type="submit"
        :disabled="form.processing"
        class="px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors disabled:opacity-50"
      >
        <span v-if="form.processing">Saving...</span>
        <span v-else>Save</span>
      </button>
    </div>
  </div>
</form>
```

### Form Handling Pattern
```typescript
import { useForm } from '@inertiajs/vue3';

const form = useForm({
  name: '',
  description: '',
  is_active: true,
  settings: {
    option1: true,
    option2: false
  }
});

const handleSubmit = () => {
  form.post(route('items.store'), {
    onSuccess: () => {
      // Success message automatically shown
    },
    onError: (errors) => {
      // Errors automatically displayed
    }
  });
};
```

---

## 6. Data Flow Patterns

### Props from Controllers
```typescript
// Controller should pass structured data like this:
interface Props {
  items: PaginatedItems<Item>;           // Always paginated for lists
  filters: FilterState;                  // Current filter state
  stats: ItemStats;                     // Statistics for overview
  relatedData?: RelatedData[];           // Optional related data
}

interface PaginatedItems<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}
```

### API Calls with Axios
```typescript
const loadData = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/items');
    items.value = response.data.data;
  } catch (error) {
    console.error('Failed to load data:', error);
    // Show error message
  } finally {
    loading.value = false;
  }
};

// POST request
const createItem = async (itemData: any) => {
  try {
    const response = await axios.post('/api/items', itemData);
    // Handle success
    return response.data;
  } catch (error) {
    console.error('Failed to create item:', error);
    throw error;
  }
};
```

### Real-time Updates
```typescript
// Polling pattern for live data
const pollData = () => {
  const interval = setInterval(async () => {
    try {
      const response = await axios.get('/api/status');
      // Update reactive data
    } catch (error) {
      console.error('Polling failed:', error);
    }
  }, 15000); // 15 seconds

  onUnmounted(() => {
    clearInterval(interval);
  });
};
```

---

## 7. Route Patterns

### Standard Route Structure
```typescript
// routes/feature.ts
import { route } from 'ziggy-js';

export default {
  index: route('feature.index'),
  create: route('feature.create'),
  store: route('feature.store'),
  show: (id: number) => route('feature.show', id),
  edit: (id: number) => route('feature.edit', id),
  update: (id: number) => route('feature.update', id),
  destroy: (id: number) => route('feature.destroy', id),
};
```

### Route Usage
```typescript
import routes from '@/routes/feature';

// Navigation
<Link :href="routes.index.url">List</Link>
<Link :href="routes.create.url">Create New</Link>
<Link :href="routes.edit(item.id).url">Edit</Link>

// Form submissions
form.post(routes.store.url);
form.put(routes.update(item.id).url);
form.delete(routes.destroy(item.id).url);
```

### URL Patterns
- **List pages**: `/feature`
- **Create pages**: `/feature/create`
- **Edit pages**: `/feature/{id}/edit`
- **Detail pages**: `/feature/{id}`
- **Debug pages**: `/debug/feature-name`
- **Settings pages**: `/settings/feature-name`

---

## 8. TypeScript Patterns

### Interface Definitions
```typescript
// Always define interfaces for props and data
interface Props {
  items: Item[];
  filters: FilterState;
  stats?: Stats;
}

interface Item {
  id: number;
  name: string;
  description?: string;
  status: 'active' | 'inactive' | 'pending';
  created_at: string;
  updated_at: string;
}

interface FilterState {
  search?: string;
  status?: string;
  date_range?: {
    start: string;
    end: string;
  };
}
```

### Reactive Data Patterns
```typescript
// Primitive values
const loading = ref(false);
const searchQuery = ref('');
const selectedItem = ref<Item | null>(null);

// Arrays
const items = ref<Item[]>([]);

// Objects
const filters = ref<FilterState>({
  search: '',
  status: 'all'
});

// Computed properties
const filteredItems = computed(() => {
  return items.value.filter(item => {
    const matchesSearch = item.name.toLowerCase().includes(filters.value.search.toLowerCase());
    const matchesStatus = filters.value.status === 'all' || item.status === filters.value.status;
    return matchesSearch && matchesStatus;
  });
});

const stats = computed(() => ({
  total: items.value.length,
  active: items.value.filter(item => item.status === 'active').length,
  inactive: items.value.filter(item => item.status === 'inactive').length
}));
```

---

## 9. Common Patterns Summary

### Page Structure Checklist
- [ ] Use `DashboardLayout` with proper title
- [ ] Include `Head` with page title
- [ ] Define TypeScript interfaces for props
- [ ] Use semantic color classes only
- [ ] Include search/filter functionality for lists
- [ ] Provide both desktop table and mobile card views
- [ ] Add empty states with helpful actions
- [ ] Include loading states for async operations

### Styling Checklist
- [ ] No manual dark mode classes (`dark:text-white`)
- [ ] Use semantic classes (`text-foreground`, `bg-card`)
- [ ] Use status badge classes for status indicators
- [ ] Use glass-card classes for modern card styling
- [ ] Include hover states and transitions
- [ ] Ensure accessibility with proper contrast

### Code Quality Checklist
- [ ] TypeScript interfaces for all data structures
- [ ] Proper error handling for API calls
- [ ] Loading states for async operations
- [ ] Form validation and error display
- [ ] Responsive design (mobile + desktop)
- [ ] Accessibility considerations

---

## 10. Quick Copy-Paste Templates

### New List Page Template
```vue
<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Plus, Search, Edit, Trash2, RefreshCw } from 'lucide-vue-next';
import routes from '@/routes/feature';

interface Item {
  id: number;
  name: string;
  status: 'active' | 'inactive';
  created_at: string;
}

interface Props {
  items: Item[];
}

const props = defineProps<Props>();

const searchQuery = ref('');
const loading = ref(false);

const filteredItems = computed(() => {
  if (!searchQuery.value) return props.items;
  return props.items.filter(item =>
    item.name.toLowerCase().includes(searchQuery.value.toLowerCase())
  );
});

const refresh = () => {
  // Refresh logic
};
</script>

<template>
  <Head title="Items" />
  <DashboardLayout title="Items">
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-foreground">Items</h1>
          <p class="text-muted-foreground">Manage your items</p>
        </div>
        <Link :href="routes.create.url" class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors">
          <Plus class="h-4 w-4 mr-2" />
          Add Item
        </Link>
      </div>

      <!-- Content here -->
    </div>
  </DashboardLayout>
</template>
```

### New Form Page Template
```vue
<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import routes from '@/routes/feature';

const form = useForm({
  name: '',
  description: '',
  is_active: true
});

const handleSubmit = () => {
  form.post(routes.store.url);
};
</script>

<template>
  <Head title="Create Item" />
  <DashboardLayout title="Create Item">
    <div class="max-w-2xl">
      <div class="mb-6">
        <Link :href="routes.index.url" class="inline-flex items-center text-muted-foreground hover:text-foreground transition-colors">
          <ArrowLeft class="h-4 w-4 mr-2" />
          Back to Items
        </Link>
      </div>

      <form @submit.prevent="handleSubmit" class="glass-card-strong rounded-2xl p-6">
        <div class="space-y-6">
          <!-- Form fields here -->
        </div>

        <div class="flex items-center justify-end space-x-4 pt-6 border-t border-border">
          <Link :href="routes.index.url" class="px-4 py-2 text-muted-foreground hover:text-foreground transition-colors">
            Cancel
          </Link>
          <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors disabled:opacity-50">
            Create Item
          </button>
        </div>
      </form>
    </div>
  </DashboardLayout>
</template>
```

---

## 11. Vue Reactivity Best Practices

### 🔧 Critical: Stable Key Bindings for v-for Loops

**Problem**: Vue's reactivity system can cause DOM elements to reorder unexpectedly when data updates, leading to UI bugs where items swap positions.

**Solution**: Always use stable, unique keys that combine multiple identifying properties.

#### ❌ AVOID - Unstable Key Bindings
```vue
<!-- DANGEROUS: Can cause reordering issues -->
<div v-for="alert in responseTimeAlerts" :key="alert.id">
  {{ alert.threshold_response_time }}ms
</div>

<!-- DANGEROUS: Same issue with uptime alerts -->
<div v-for="alert in uptimeAlerts" :key="alert.id">
  {{ alert.alert_type }}
</div>
```

#### ✅ RECOMMENDED - Stable Key Bindings
```vue
<!-- STABLE: Combines type, threshold, and ID -->
<div v-for="alert in responseTimeAlerts"
     :key="`response-time-${alert.threshold_response_time}-${alert.id}`">
  {{ alert.threshold_response_time }}ms
</div>

<!-- STABLE: Combines alert type and ID -->
<div v-for="alert in uptimeAlerts"
     :key="`uptime-${alert.alert_type}-${alert.id}`">
  {{ alert.alert_type }}
</div>

<!-- STABLE: Uses unique threshold (SSL alerts have unique days) -->
<div v-for="alert in sslExpiryAlerts"
     :key="alert.threshold_days">
  {{ alert.threshold_days }} days
</div>
```

### 📊 Consistent Ordering in Computed Properties

Always sort data in computed properties to ensure consistent display order:

```typescript
// Response Time Alerts - Sort by threshold (5000ms first, 10000ms second)
const responseTimeAlerts = computed(() => {
  const alerts = props.globalAlerts?.responseTimeAlerts || [];
  return alerts.sort((a, b) => (a.threshold_response_time || 0) - (b.threshold_response_time || 0));
});

// Uptime Alerts - Sort by alert type (uptime_down first, uptime_up second)
const uptimeAlerts = computed(() => {
  const alerts = props.globalAlerts?.uptimeAlerts || [];
  return alerts.sort((a, b) => (a.alert_type).localeCompare(b.alert_type));
});

// SSL Expiry Alerts - Use fixed array for consistent ordering
const sslExpiryAlerts = computed(() => {
  // This ensures consistent ordering: 30, 14, 7, 3, 0 days
  const allPeriods = [30, 14, 7, 3, 0];
  return allPeriods.map(days => {
    // Map each period to alert data
  });
});
```

### 🎯 Pattern Template for v-for Loops

```vue
<template>
  <!-- Use this pattern for all v-for loops -->
  <div
    v-for="item in computedItems"
    :key="`${item.type}-${item.identifier}-${item.id}`"
    class="item-class"
    @click="handleAction(item)"
  >
    <!-- Content -->
  </div>
</template>

<script setup lang="ts">
// Use this pattern for computed properties
const computedItems = computed(() => {
  const items = props.rawItems || [];
  // Always sort for consistent ordering
  return items.sort((a, b) => {
    // Sort logic based on your data structure
    return (a.sortField || 0) - (b.sortField || 0);
  });
});
</script>
```

### 🚨 Real-World Example: Alert Settings Bug Fix

**Original Issue**: Response time alerts (5000ms and 10000ms) would swap positions when toggled.

**Root Cause**:
- Used `:key="alert.id"` which wasn't stable enough
- No sorting in computed property
- Vue reactivity system reordered DOM elements on data updates

**Solution Applied**:
```vue
<!-- Before (BROKEN) -->
<div v-for="alert in responseTimeAlerts" :key="alert.id">

<!-- After (FIXED) -->
<div v-for="alert in responseTimeAlerts"
     :key="`response-time-${alert.threshold_response_time}-${alert.id}`">
```

```typescript
// Added sorting to computed property
const responseTimeAlerts = computed(() => {
  const alerts = props.globalAlerts?.responseTimeAlerts || [];
  // Sort by threshold_response_time to ensure consistent ordering
  return alerts.sort((a, b) => (a.threshold_response_time || 0) - (b.threshold_response_time || 0));
});
```

### ✅ Vue Reactivity Checklist

When working with v-for loops and reactive data:

- [ ] **Always use stable key bindings** that combine multiple identifying properties
- [ ] **Always sort data in computed properties** to ensure consistent ordering
- [ ] **Never use single ID as key** if the data could be reordered
- [ ] **Test toggle operations** to verify items don't swap positions
- [ ] **Use descriptive key patterns** like `type-identifier-id`
- [ ] **Add comments explaining sorting logic** for future maintainers

---

This guide provides everything needed to efficiently work with the SSL Monitor v4 application. Follow these patterns for consistency and maintainability.