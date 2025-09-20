# /ssl-page - Vue.js SSL Page Creator

**Purpose**: Create Vue.js pages using existing component patterns for SSL monitoring.

**Usage**: `/ssl-page [page-name] [description]`

## Vue.js Page Development Workflow

Please create the SSL Vue.js page: **$ARGUMENTS**.

**Context**: Build pages using existing professional shadcn/ui components and TypeScript patterns.

Follow these steps:

### 1. Analyze Existing Patterns
```bash
# Study existing page structure
filesystem-mcp: read-file resources/js/pages/Dashboard.vue
filesystem-mcp: read-directory resources/js/pages/
filesystem-mcp: read-directory resources/js/components/ui/

# Check layout components
filesystem-mcp: read-file resources/js/layouts/DashboardLayout.vue
filesystem-mcp: read-file resources/js/components/AppHeader.vue

# Review TypeScript interfaces
filesystem-mcp: read-file resources/js/types/index.d.ts
```

### 2. Component Research
```bash
# Research Vue 3 + Inertia patterns
use context7: "Vue 3 composition API with TypeScript"
use context7: "Inertia.js page props and form handling"

# Check available UI components
filesystem-mcp: list-files resources/js/components/ui/
```

### 3. Page Structure Planning
```bash
# Plan page layout using existing components:
# - DashboardLayout for consistent structure
# - Card components for content sections
# - Table components for SSL data display
# - Button and Form components for actions
# - Loading and Error states using existing patterns

# Define TypeScript interfaces for SSL data
# Plan responsive layout for mobile/desktop
```

### 4. Create Vue Page File
```bash
# Create page in appropriate directory
# Follow existing naming conventions
# Use .vue extension with TypeScript support

# Standard page location: resources/js/pages/ssl/
# Example: resources/js/pages/ssl/Websites.vue
```

### 5. Implement Page Structure
```bash
# Use existing component patterns:

# <script setup lang="ts">
# - Import Head from @inertiajs/vue3
# - Import DashboardLayout
# - Import needed UI components (Card, Button, Table)
# - Define props interface for SSL data
# - Use composition API patterns from existing pages

# <template>
# - Use Head for page title
# - Wrap in DashboardLayout
# - Build UI using existing shadcn/ui components
# - Implement responsive grid layouts
# - Add loading states and error handling

# Follow existing styling patterns
```

### 6. Data Integration
```bash
# Connect to Inertia controller props
# Define TypeScript interfaces for SSL data
# Handle real-time updates where needed
# Implement proper error states

# Example data structure:
# interface WebsiteProps {
#   websites: Website[]
#   statistics: SslStatistics
#   pagination: PaginationData
# }
```

### 7. Interactive Features
```bash
# Add SSL-specific functionality:
# - Manual SSL certificate checks
# - Website add/edit/delete forms
# - Bulk operations (select multiple)
# - Search and filtering
# - Real-time status updates

# Use existing form patterns from settings pages
# Implement proper validation and error handling
```

### 8. Mobile Responsiveness
```bash
# Test responsive design:
# - Mobile-first approach using existing patterns
# - Proper table responsive behavior
# - Touch-friendly interactions
# - Consistent with existing responsive patterns

# Use existing TailwindCSS responsive classes
```

### 9. Browser Testing
```bash
# Create browser test for the page
./vendor/bin/sail artisan make:test --pest Browser/Ssl${ARGUMENTS}PageTest

# Test user workflows:
# - Page loads correctly
# - SSL data displays properly
# - Interactive features work
# - Mobile responsiveness
# - Error states handle gracefully
```

## Vue.js Component Pattern Standards

### Page Structure Template
```vue
<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'

interface Props {
  websites: Website[]
  statistics: SslStatistics
}

defineProps<Props>()
</script>

<template>
  <Head title="SSL Monitoring" />

  <DashboardLayout title="SSL Certificates">
    <!-- Content using existing components -->
  </DashboardLayout>
</template>
```

### TypeScript Interface Pattern
```typescript
interface Website {
  id: number
  name: string
  url: string
  ssl_status: 'valid' | 'expiring' | 'expired' | 'invalid'
  certificate_expires_at: string
  last_checked_at: string
}

interface SslStatistics {
  total_websites: number
  valid_certificates: number
  expiring_soon: number
  expired_certificates: number
}
```

### Existing Component Usage
```vue
<!-- Use existing UI components -->
<Card>
  <CardHeader>
    <CardTitle>SSL Certificate Status</CardTitle>
  </CardHeader>
  <CardContent>
    <Badge :variant="sslStatusVariant(website.ssl_status)">
      {{ website.ssl_status }}
    </Badge>
  </CardContent>
</Card>
```

## Integration Requirements

### Layout Integration
- Use **DashboardLayout** for consistent page structure
- Follow existing **navigation and breadcrumb** patterns
- Maintain **theme consistency** (dark/light mode support)
- Use existing **responsive grid** patterns

### Component Integration
- Leverage existing **shadcn/ui components** (Card, Button, Table, Badge)
- Follow established **TypeScript patterns** from other pages
- Use existing **form handling** patterns from settings pages
- Implement **loading states** using existing skeleton components

### Data Integration
- Connect to **Inertia.js props** from controllers
- Handle **pagination** using existing patterns
- Implement **real-time updates** where appropriate
- Use consistent **error handling** patterns

## Testing Requirements

### Browser Test Coverage
- Page renders correctly with SSL data
- Interactive features work as expected
- Mobile responsiveness functions properly
- Error states display correctly
- Form submissions work correctly

### Visual Consistency
- Matches existing page styling
- Responsive design works across devices
- Theme switching works properly
- Loading states are consistent

## Success Criteria
1. Page uses existing component patterns correctly
2. TypeScript interfaces are properly defined
3. Responsive design works on all devices
4. SSL data displays clearly and professionally
5. Interactive features work smoothly
6. Browser tests pass
7. Visual consistency with existing pages

**Ready to build professional SSL pages with existing component foundation!** 🎨