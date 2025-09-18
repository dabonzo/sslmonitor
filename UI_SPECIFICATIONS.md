# SSL Monitor v3 - UI Specifications

## 🎨 User Interface Design Overview

SSL Monitor v3 features a modern, professional interface built with the **VRISTO admin template**, Vue 3, and Inertia.js. The UI prioritizes usability, data visualization, and responsive design across all devices.

**Design Philosophy**: Clean, professional interface that makes SSL monitoring and uptime tracking accessible to technical and non-technical users alike.

---

## 🏗️ Layout Architecture

### Master Layout Structure
```
┌─────────────────────────────────────────────┐
│ Top Navigation Bar (VRISTO NavBar)         │
├─────────────────────────────────────────────┤
│ Sidebar │ Main Content Area                 │
│ Menu    │                                   │
│         │ ┌─────────────────────────────┐   │
│ • Dash  │ │ Page Header                 │   │
│ • Sites │ │ (Breadcrumbs + Actions)     │   │
│ • Team  │ ├─────────────────────────────┤   │
│ • Alert │ │                             │   │
│ • Sett  │ │ Dynamic Page Content        │   │
│         │ │ (Vue Components)            │   │
│         │ │                             │   │
│         │ └─────────────────────────────┘   │
└─────────────────────────────────────────────┘
```

### Responsive Behavior
- **Desktop (≥1200px)**: Full sidebar + main content
- **Tablet (768px-1199px)**: Collapsible sidebar overlay
- **Mobile (≤767px)**: Hidden sidebar with hamburger menu
- **Dark/Light Theme**: Toggle in top navigation

---

## 📱 Page Specifications

### 1. Authentication Pages

#### Login Page (`/login`)
**Layout**: Centered authentication layout
**VRISTO Components**: Auth card, form elements
**Features**:
- Email/password login form
- "Remember me" checkbox
- Password reset link
- Social login buttons (future)
- Real-time validation with Vue 3 Composition API

**Vue Component Structure**:
```vue
<template>
  <AuthLayout>
    <VristoCard class="w-full max-w-md">
      <VristoForm @submit="handleLogin">
        <VristoInput
          v-model="form.email"
          type="email"
          label="Email"
          :error="form.errors.email"
        />
        <VristoInput
          v-model="form.password"
          type="password"
          label="Password"
          :error="form.errors.password"
        />
        <VristoCheckbox v-model="form.remember">
          Remember me
        </VristoCheckbox>
        <VristoButton
          type="submit"
          variant="primary"
          :loading="form.processing"
          class="w-full"
        >
          Sign In
        </VristoButton>
      </VristoForm>
    </VristoCard>
  </AuthLayout>
</template>
```

#### Registration Page (`/register`)
**Layout**: Centered authentication layout
**Features**:
- Name, email, password, company fields
- Password confirmation
- Terms acceptance checkbox
- Email verification flow

#### Password Reset (`/forgot-password`, `/reset-password`)
**Layout**: Centered authentication layout
**Features**:
- Email input for reset request
- Token-based password reset form
- Success/error messaging

### 2. Dashboard (`/dashboard`)

#### Dashboard Overview
**Layout**: Main app layout with sidebar
**Features**:
- Website status overview cards
- SSL expiry warnings
- Uptime statistics charts
- Recent activity feed
- Quick actions toolbar

**Key Components**:
```vue
<template>
  <AppLayout>
    <PageHeader title="Dashboard" />

    <!-- Stats Cards Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <StatsCard
        title="Total Websites"
        :value="dashboardData.total_websites"
        icon="globe"
        color="primary"
      />
      <StatsCard
        title="SSL Expiring Soon"
        :value="dashboardData.ssl_expiring_soon"
        icon="shield-exclamation"
        color="warning"
      />
      <StatsCard
        title="Uptime Issues"
        :value="dashboardData.uptime_issues"
        icon="exclamation-triangle"
        color="danger"
      />
      <StatsCard
        title="Average Uptime"
        :value="dashboardData.average_uptime + '%'"
        icon="chart-line"
        color="success"
      />
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
      <VristoCard>
        <CardHeader title="Uptime Trends" />
        <UptimeChart :data="dashboardData.uptime_trends" />
      </VristoCard>

      <VristoCard>
        <CardHeader title="SSL Certificate Status" />
        <SSLStatusChart :data="dashboardData.ssl_status" />
      </VristoCard>
    </div>

    <!-- Activity Feed -->
    <VristoCard class="mt-6">
      <CardHeader title="Recent Activity" />
      <ActivityFeed :activities="dashboardData.recent_activity" />
    </VristoCard>
  </AppLayout>
</template>
```

### 3. Website Management

#### Websites List (`/websites`)
**Layout**: Main app layout
**Features**:
- Searchable/filterable data table
- Status indicators (green/yellow/red)
- Bulk actions (delete, enable/disable notifications)
- Quick add website button
- Export functionality

**Data Table Columns**:
- Domain/Name with status indicator
- SSL Status (expiry date, issuer)
- Uptime Status (percentage, last check)
- Response Time
- Notifications (enabled/disabled toggle)
- Actions (view, edit, delete)

**Vue Component Structure**:
```vue
<template>
  <AppLayout>
    <PageHeader title="Websites">
      <template #actions>
        <VristoButton @click="showAddModal = true">
          <PlusIcon class="w-4 h-4 mr-2" />
          Add Website
        </VristoButton>
      </template>
    </PageHeader>

    <!-- Filters Bar -->
    <FilterBar
      v-model:search="filters.search"
      v-model:status="filters.status"
      @reset="resetFilters"
    />

    <!-- Data Table -->
    <VristoDataTable
      :data="websites.data"
      :columns="tableColumns"
      :loading="loading"
      @sort="handleSort"
      @page-change="handlePageChange"
    />

    <!-- Pagination -->
    <VristoPagination
      :pagination="websites.pagination"
      @page-change="handlePageChange"
    />

    <!-- Add Website Modal -->
    <AddWebsiteModal
      v-model:show="showAddModal"
      @website-added="refreshWebsites"
    />
  </AppLayout>
</template>
```

#### Website Details (`/websites/{id}`)
**Layout**: Main app layout
**Features**:
- Website overview card
- SSL certificate details
- Uptime statistics and history
- Check history timeline
- Notification settings
- Manual check triggers

**Tab Navigation**:
- Overview (summary stats)
- SSL Certificate (detailed cert info)
- Uptime (uptime charts and history)
- History (check timeline)
- Settings (notification preferences)

#### Add/Edit Website (`/websites/create`, `/websites/{id}/edit`)
**Layout**: Main app layout
**Features**:
- Multi-step form with validation
- SSL check preview
- Advanced options (JavaScript checking, custom headers)
- Notification configuration
- Test connection button

### 4. Team Management

#### Team Overview (`/team`)
**Layout**: Main app layout
**Features**:
- Team member list with roles
- Invite new members
- Role management (Owner/Admin/Viewer)
- Member activity logs
- Team settings

#### Team Invitations (`/team/invitations`)
**Layout**: Main app layout
**Features**:
- Pending invitations list
- Resend invitation options
- Invitation management

### 5. Settings

#### User Settings (`/settings/profile`)
**Layout**: Main app layout with settings tabs
**Features**:
- Profile information editing
- Password change
- Timezone settings
- Email preferences

#### Notification Settings (`/settings/notifications`)
**Layout**: Main app layout with settings tabs
**Features**:
- Global notification toggle
- SSL expiry notification preferences
- Uptime alert settings
- Email frequency settings
- Test notification button

#### Billing & Subscription (`/settings/billing`)
**Layout**: Main app layout with settings tabs
**Features**:
- Current plan information
- Usage statistics
- Billing history
- Upgrade/downgrade options

---

## 🧩 Reusable Components

### Core UI Components (VRISTO-based)

#### VristoCard
```vue
<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <div v-if="$slots.header" class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
      <slot name="header" />
    </div>
    <div class="p-6">
      <slot />
    </div>
    <div v-if="$slots.footer" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
      <slot name="footer" />
    </div>
  </div>
</template>
```

#### StatusIndicator
```vue
<template>
  <div class="flex items-center">
    <div
      :class="[
        'w-3 h-3 rounded-full mr-2',
        statusColors[status]
      ]"
    />
    <span class="text-sm font-medium">{{ statusText }}</span>
  </div>
</template>

<script setup>
const props = defineProps(['status'])

const statusColors = {
  active: 'bg-green-500',
  warning: 'bg-yellow-500',
  error: 'bg-red-500',
  inactive: 'bg-gray-400'
}

const statusText = computed(() => {
  return {
    active: 'Active',
    warning: 'Warning',
    error: 'Error',
    inactive: 'Inactive'
  }[props.status]
})
</script>
```

#### DataTable
- Sortable columns
- Responsive design
- Row selection
- Loading states
- Empty states
- Pagination integration

### Domain-Specific Components

#### SSLCertificateCard
```vue
<template>
  <VristoCard>
    <div class="flex items-start justify-between">
      <div>
        <h3 class="font-semibold text-lg">SSL Certificate</h3>
        <p class="text-gray-600 dark:text-gray-400">{{ certificate.issuer }}</p>
      </div>
      <StatusIndicator :status="certificate.status" />
    </div>

    <div class="mt-4 space-y-2">
      <div class="flex justify-between">
        <span class="text-gray-600">Expires</span>
        <span :class="expiryColor">{{ formatDate(certificate.expires_at) }}</span>
      </div>
      <div class="flex justify-between">
        <span class="text-gray-600">Days Remaining</span>
        <span :class="expiryColor">{{ certificate.days_until_expiry }}</span>
      </div>
      <div class="flex justify-between">
        <span class="text-gray-600">Last Checked</span>
        <span>{{ formatRelativeTime(certificate.last_checked_at) }}</span>
      </div>
    </div>

    <template #footer>
      <VristoButton
        variant="outline"
        size="sm"
        @click="$emit('check-ssl')"
        :loading="checking"
      >
        Check Now
      </VristoButton>
    </template>
  </VristoCard>
</template>
```

#### UptimeChart
```vue
<template>
  <div class="space-y-4">
    <!-- Chart Container -->
    <div class="h-64">
      <canvas ref="chartRef"></canvas>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-3 gap-4 text-center">
      <div>
        <div class="text-2xl font-bold text-green-600">{{ uptimeData.percentage }}%</div>
        <div class="text-sm text-gray-600">Uptime</div>
      </div>
      <div>
        <div class="text-2xl font-bold text-blue-600">{{ uptimeData.avgResponseTime }}ms</div>
        <div class="text-sm text-gray-600">Avg Response</div>
      </div>
      <div>
        <div class="text-2xl font-bold text-gray-600">{{ uptimeData.totalChecks }}</div>
        <div class="text-sm text-gray-600">Total Checks</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import Chart from 'chart.js/auto'

const props = defineProps(['data'])
const chartRef = ref(null)
let chart = null

onMounted(() => {
  initChart()
})

const initChart = () => {
  const ctx = chartRef.value.getContext('2d')
  chart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: props.data.labels,
      datasets: [{
        label: 'Response Time (ms)',
        data: props.data.responseTimes,
        borderColor: 'rgb(59, 130, 246)',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        tension: 0.4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: {
            color: 'rgba(156, 163, 175, 0.2)'
          }
        },
        x: {
          grid: {
            color: 'rgba(156, 163, 175, 0.2)'
          }
        }
      }
    }
  })
}
</script>
```

#### ActivityFeed
```vue
<template>
  <div class="space-y-4">
    <div
      v-for="activity in activities"
      :key="activity.id"
      class="flex items-start space-x-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-700"
    >
      <div
        :class="[
          'flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center',
          activityTypeColors[activity.type]
        ]"
      >
        <component :is="activityTypeIcons[activity.type]" class="w-4 h-4" />
      </div>

      <div class="flex-1 min-w-0">
        <p class="text-sm text-gray-900 dark:text-white">
          {{ activity.message }}
        </p>
        <p class="text-xs text-gray-500 dark:text-gray-400">
          {{ formatRelativeTime(activity.created_at) }}
        </p>
      </div>
    </div>
  </div>
</template>
```

---

## 🎨 Design System

### Color Palette (VRISTO Theme)
```css
/* Primary Colors */
--color-primary: #4f46e5;
--color-primary-dark: #3730a3;
--color-primary-light: #6366f1;

/* Status Colors */
--color-success: #10b981;
--color-warning: #f59e0b;
--color-danger: #ef4444;
--color-info: #3b82f6;

/* Neutral Colors */
--color-gray-50: #f9fafb;
--color-gray-100: #f3f4f6;
--color-gray-200: #e5e7eb;
--color-gray-300: #d1d5db;
--color-gray-400: #9ca3af;
--color-gray-500: #6b7280;
--color-gray-600: #4b5563;
--color-gray-700: #374151;
--color-gray-800: #1f2937;
--color-gray-900: #111827;
```

### Typography Scale
```css
/* Headings */
.text-h1 { font-size: 2.5rem; font-weight: 700; }
.text-h2 { font-size: 2rem; font-weight: 600; }
.text-h3 { font-size: 1.5rem; font-weight: 600; }
.text-h4 { font-size: 1.25rem; font-weight: 600; }

/* Body Text */
.text-lg { font-size: 1.125rem; }
.text-base { font-size: 1rem; }
.text-sm { font-size: 0.875rem; }
.text-xs { font-size: 0.75rem; }
```

### Spacing System (Tailwind)
- `space-1` = 0.25rem (4px)
- `space-2` = 0.5rem (8px)
- `space-3` = 0.75rem (12px)
- `space-4` = 1rem (16px)
- `space-6` = 1.5rem (24px)
- `space-8` = 2rem (32px)

---

## 📱 Responsive Design

### Breakpoints
```css
/* Mobile First Approach */
sm: 640px   /* Small devices */
md: 768px   /* Tablets */
lg: 1024px  /* Laptops */
xl: 1280px  /* Desktops */
2xl: 1536px /* Large screens */
```

### Component Adaptations

#### Mobile Navigation
- Hamburger menu replaces sidebar
- Simplified top navigation
- Touch-friendly button sizes (min 44px)
- Swipe gestures for modals

#### Tablet Layout
- Collapsible sidebar overlay
- Adaptive grid layouts (2-column becomes 1-column)
- Touch-optimized form controls

#### Desktop Enhancements
- Full sidebar navigation
- Multi-column layouts
- Hover states and tooltips
- Keyboard shortcuts

---

## 🎯 User Experience Features

### Loading States
- Skeleton loaders for data tables
- Progress indicators for long operations
- Optimistic UI updates where appropriate
- Spinner components for buttons

### Error Handling
- Toast notifications for success/error messages
- Inline validation errors
- Graceful degradation for failed API calls
- Retry mechanisms for network errors

### Accessibility
- ARIA labels and roles
- Keyboard navigation support
- Screen reader compatibility
- Color contrast compliance (WCAG 2.1)
- Focus management in modals

### Performance Optimizations
- Lazy loading for routes and components
- Virtual scrolling for large datasets
- Image optimization and lazy loading
- Bundle splitting and code splitting

---

## 🔄 State Management

### Pinia Store Structure
```javascript
// stores/websites.js
export const useWebsitesStore = defineStore('websites', () => {
  const websites = ref([])
  const loading = ref(false)
  const filters = ref({
    search: '',
    status: 'all',
    sortBy: 'domain',
    sortDirection: 'asc'
  })

  const fetchWebsites = async () => {
    loading.value = true
    try {
      const response = await api.get('/websites', { params: filters.value })
      websites.value = response.data.data.websites
    } finally {
      loading.value = false
    }
  }

  const addWebsite = async (websiteData) => {
    const response = await api.post('/websites', websiteData)
    websites.value.push(response.data.data.website)
    return response.data.data.website
  }

  return {
    websites: readonly(websites),
    loading: readonly(loading),
    filters,
    fetchWebsites,
    addWebsite
  }
})
```

### Global State Elements
- User authentication status
- Theme preference (light/dark)
- Notification preferences
- Real-time connection status
- Active page breadcrumbs

---

## 🚀 Development Guidelines

### Component Organization
```
resources/js/
├── components/
│   ├── ui/           # Reusable UI components
│   ├── forms/        # Form-specific components
│   ├── charts/       # Data visualization
│   └── domain/       # Business logic components
├── layouts/          # Page layouts
├── pages/           # Page components (Inertia.js)
├── stores/          # Pinia stores
└── composables/     # Vue composables
```

### Naming Conventions
- **Components**: PascalCase (`WebsiteCard.vue`)
- **Props**: camelCase (`websiteData`)
- **Events**: kebab-case (`website-updated`)
- **CSS classes**: BEM methodology where needed

### Testing Strategy
- **Unit Tests**: Individual component functionality
- **Integration Tests**: Component interactions
- **E2E Tests**: Complete user workflows with Playwright
- **Visual Tests**: Screenshot comparisons for UI consistency

---

This UI specification provides a comprehensive blueprint for building SSL Monitor v3's frontend with Vue 3, Inertia.js, and the VRISTO admin template, ensuring a professional, responsive, and user-friendly interface.