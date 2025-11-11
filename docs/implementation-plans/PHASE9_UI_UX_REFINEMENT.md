# Phase 9: UI/UX Refinement

**Status**: 📋 Planned
**Estimated Duration**: 12-16 hours
**Complexity**: Medium-High
**Dependencies**: Phase 5 (Production Optimization), Phase 8 (Performance Audit)

## Overview

Comprehensive UI/UX review and refinement focusing on user experience, accessibility, responsive design, and visual consistency. This phase ensures the application provides an excellent user experience across all devices and use cases.

## Objectives

- Audit and improve user experience flows
- Enhance accessibility (WCAG 2.1 AA compliance)
- Optimize responsive design for mobile/tablet
- Improve visual consistency and design system adherence
- Optimize performance-perceived user experience
- Document UI patterns and component library

## Part 1: UX Flow Audit & Optimization (4-6 hours)

### Agent Assignment
- **Primary**: `vue-component-builder` (frontend focus)
- **Support**: `browser-tester` (testing flows), `styling-expert` (visual consistency)

### 1.1 Critical User Journey Analysis (2 hours)

#### Journey 1: New User Onboarding
**Goal**: Minimize time to first monitor setup

**Current Flow**:
1. Registration → Email verification → Dashboard
2. "Add Website" → Fill form → Save
3. "Add Monitor" → Fill form → Configure checks → Save

**Audit Checklist**:
- [ ] Is the path to first monitor clear and intuitive?
- [ ] Are error messages helpful and actionable?
- [ ] Is there adequate guidance for first-time users?
- [ ] Can users complete setup in < 5 minutes?

**Optimization Opportunities**:
```vue
<!-- Onboarding wizard component -->
<template>
  <div class="space-y-6">
    <OnboardingProgress :step="currentStep" :total="3" />

    <TransitionGroup name="fade">
      <WelcomeStep v-if="currentStep === 1" @next="nextStep" />
      <WebsiteSetupStep v-else-if="currentStep === 2" @next="nextStep" />
      <MonitorConfigStep v-else-if="currentStep === 3" @complete="finishOnboarding" />
    </TransitionGroup>

    <div class="flex justify-between">
      <button @click="previousStep" v-if="currentStep > 1">
        Previous
      </button>
      <button @click="skipOnboarding">
        Skip wizard
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const currentStep = ref(1);

function nextStep() {
  currentStep.value++;
}

function previousStep() {
  currentStep.value--;
}

function skipOnboarding() {
  router.visit('/dashboard');
}

function finishOnboarding() {
  // Mark onboarding as complete
  router.post('/api/user/complete-onboarding', {}, {
    onSuccess: () => router.visit('/dashboard'),
  });
}
</script>
```

**Testing with browser-tester**:
```typescript
// Test onboarding flow completion time
test('new user can complete onboarding in under 5 minutes', async ({ page }) => {
  await page.goto('/register');

  const startTime = Date.now();

  // Registration
  await page.fill('[name="name"]', 'Test User');
  await page.fill('[name="email"]', 'test@example.com');
  await page.fill('[name="password"]', 'SecurePass123!');
  await page.click('button[type="submit"]');

  // Onboarding wizard
  await page.waitForSelector('[data-testid="onboarding-wizard"]');
  await page.click('button:text("Next")');

  // Add website
  await page.fill('[name="url"]', 'https://example.com');
  await page.click('button:text("Next")');

  // Configure monitor
  await page.check('[name="uptime_check_enabled"]');
  await page.check('[name="certificate_check_enabled"]');
  await page.click('button:text("Finish")');

  const endTime = Date.now();
  const duration = (endTime - startTime) / 1000;

  expect(duration).toBeLessThan(300); // 5 minutes
  expect(page.url()).toContain('/dashboard');
});
```

#### Journey 2: Monitor Management
**Goal**: Quick monitor creation and configuration

**Current Flow**:
1. Dashboard → "Add Monitor" button
2. Fill form with URL, check types
3. Optional: Configure advanced settings
4. Save and return to dashboard

**Audit Checklist**:
- [ ] Is the "Add Monitor" button prominently visible?
- [ ] Are default settings sensible (SSL + uptime enabled)?
- [ ] Is validation clear and immediate?
- [ ] Can users preview monitor before saving?

**Optimization: Inline Quick Add**:
```vue
<!-- QuickAddMonitor.vue -->
<template>
  <div class="bg-card p-4 rounded-lg border border-border">
    <h3 class="text-lg font-semibold mb-4">Quick Add Monitor</h3>

    <form @submit.prevent="handleSubmit" class="space-y-4">
      <div>
        <label for="url" class="block text-sm font-medium mb-2">
          Website URL
        </label>
        <input
          id="url"
          v-model="form.url"
          type="url"
          placeholder="https://example.com"
          class="w-full px-3 py-2 border border-border rounded-md"
          @blur="validateUrl"
        />
        <p v-if="errors.url" class="mt-1 text-sm text-destructive">
          {{ errors.url }}
        </p>
      </div>

      <div class="flex gap-4">
        <label class="flex items-center gap-2">
          <input type="checkbox" v-model="form.uptime_check" checked />
          <span class="text-sm">Uptime Monitoring</span>
        </label>
        <label class="flex items-center gap-2">
          <input type="checkbox" v-model="form.ssl_check" checked />
          <span class="text-sm">SSL Certificate Check</span>
        </label>
      </div>

      <div class="flex gap-2">
        <button
          type="submit"
          :disabled="isSubmitting"
          class="bg-primary text-primary-foreground px-4 py-2 rounded-md hover:bg-primary/90"
        >
          {{ isSubmitting ? 'Adding...' : 'Add Monitor' }}
        </button>
        <button
          type="button"
          @click="showAdvanced = true"
          class="text-muted-foreground hover:text-foreground"
        >
          Advanced Options
        </button>
      </div>
    </form>

    <!-- Success message -->
    <Transition name="slide-up">
      <div v-if="showSuccess" class="mt-4 p-3 bg-green-50 text-green-800 rounded-md">
        ✓ Monitor added successfully! Checking now...
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';

const form = reactive({
  url: '',
  uptime_check: true,
  ssl_check: true,
});

const errors = reactive({});
const isSubmitting = ref(false);
const showSuccess = ref(false);
const showAdvanced = ref(false);

function validateUrl() {
  try {
    new URL(form.url);
    errors.url = null;
  } catch {
    errors.url = 'Please enter a valid URL (e.g., https://example.com)';
  }
}

function handleSubmit() {
  validateUrl();
  if (errors.url) return;

  isSubmitting.value = true;

  router.post('/monitors', form, {
    onSuccess: () => {
      showSuccess.value = true;
      form.url = '';
      setTimeout(() => showSuccess.value = false, 3000);
    },
    onError: (err) => {
      Object.assign(errors, err);
    },
    onFinish: () => {
      isSubmitting.value = false;
    },
  });
}
</script>
```

#### Journey 3: Alert Response
**Goal**: Quick understanding and resolution of issues

**Current Flow**:
1. Email alert received → Click link
2. View monitor details → See error
3. (Optional) Check history → Investigate
4. (Optional) Silence alerts temporarily

**Audit Checklist**:
- [ ] Are alert emails clear and actionable?
- [ ] Does the link go directly to relevant information?
- [ ] Is error context immediately visible?
- [ ] Can users quickly silence false positives?

**Optimization: Alert Context Panel**:
```vue
<!-- AlertContext.vue -->
<template>
  <div class="bg-destructive/10 border border-destructive rounded-lg p-4">
    <div class="flex items-start gap-3">
      <AlertIcon class="w-5 h-5 text-destructive flex-shrink-0" />

      <div class="flex-1 space-y-2">
        <h4 class="font-semibold text-destructive">
          {{ alert.title }}
        </h4>

        <p class="text-sm text-foreground">
          {{ alert.description }}
        </p>

        <div class="flex gap-2 text-xs text-muted-foreground">
          <span>First detected: {{ formatTime(alert.first_occurrence) }}</span>
          <span>•</span>
          <span>Occurrences: {{ alert.occurrence_count }}</span>
        </div>

        <!-- Quick actions -->
        <div class="flex gap-2 mt-3">
          <button
            @click="investigateIssue"
            class="text-sm bg-background border border-border px-3 py-1 rounded hover:bg-accent"
          >
            View Details
          </button>
          <button
            @click="showSnoozeDialog = true"
            class="text-sm text-muted-foreground hover:text-foreground"
          >
            Snooze Alerts
          </button>
          <button
            v-if="alert.can_acknowledge"
            @click="acknowledgeAlert"
            class="text-sm text-muted-foreground hover:text-foreground"
          >
            Mark as Resolved
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Snooze dialog -->
  <Dialog v-model="showSnoozeDialog">
    <DialogContent>
      <h3 class="text-lg font-semibold mb-4">Snooze Alerts</h3>
      <p class="text-sm text-muted-foreground mb-4">
        Temporarily disable alerts for this monitor
      </p>

      <div class="space-y-2">
        <button @click="snoozeFor(1)" class="w-full text-left p-2 hover:bg-accent rounded">
          1 hour
        </button>
        <button @click="snoozeFor(4)" class="w-full text-left p-2 hover:bg-accent rounded">
          4 hours
        </button>
        <button @click="snoozeFor(24)" class="w-full text-left p-2 hover:bg-accent rounded">
          24 hours
        </button>
        <button @click="snoozeFor(168)" class="w-full text-left p-2 hover:bg-accent rounded">
          1 week
        </button>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

interface Alert {
  id: number;
  title: string;
  description: string;
  first_occurrence: string;
  occurrence_count: number;
  can_acknowledge: boolean;
  monitor_id: number;
}

const props = defineProps<{
  alert: Alert;
}>();

const showSnoozeDialog = ref(false);

function investigateIssue() {
  router.visit(`/monitors/${props.alert.monitor_id}/history`);
}

function snoozeFor(hours: number) {
  router.post(`/alerts/${props.alert.id}/snooze`, {
    duration_hours: hours,
  }, {
    onSuccess: () => {
      showSnoozeDialog.value = false;
    },
  });
}

function acknowledgeAlert() {
  router.post(`/alerts/${props.alert.id}/acknowledge`);
}

function formatTime(timestamp: string) {
  return new Date(timestamp).toLocaleString();
}
</script>
```

### 1.2 Form Usability Improvements (2 hours)

#### Inline Validation
```vue
<!-- Example: MonitorForm.vue -->
<template>
  <form @submit.prevent="handleSubmit" class="space-y-6">
    <FormField
      v-model="form.url"
      label="Monitor URL"
      type="url"
      :error="errors.url"
      :success="validation.url.isValid"
      :validating="validation.url.isValidating"
      @blur="validateField('url')"
    >
      <template #hint>
        Enter the full URL including https://
      </template>
      <template #validation-success>
        ✓ Valid URL format
      </template>
    </FormField>

    <FormField
      v-model="form.check_interval"
      label="Check Interval"
      type="select"
      :options="intervalOptions"
      :error="errors.check_interval"
    >
      <template #hint>
        How often should we check this monitor?
      </template>
    </FormField>

    <!-- Show estimated cost -->
    <div class="bg-muted p-3 rounded-md text-sm">
      <p class="text-muted-foreground">
        Estimated checks per day: <strong>{{ estimatedChecksPerDay }}</strong>
      </p>
    </div>
  </form>
</template>

<script setup lang="ts">
import { computed, reactive } from 'vue';

const form = reactive({
  url: '',
  check_interval: 5,
});

const validation = reactive({
  url: {
    isValid: false,
    isValidating: false,
  },
});

const estimatedChecksPerDay = computed(() => {
  return Math.floor(1440 / form.check_interval); // 1440 minutes per day
});

async function validateField(field: string) {
  if (field === 'url') {
    validation.url.isValidating = true;

    try {
      const response = await fetch('/api/validate-url', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ url: form.url }),
      });

      const result = await response.json();
      validation.url.isValid = result.is_valid;

      if (!result.is_valid) {
        errors.url = result.message;
      } else {
        errors.url = null;
      }
    } finally {
      validation.url.isValidating = false;
    }
  }
}
</script>
```

#### Error Message Improvements
```typescript
// utils/errorMessages.ts
export const errorMessages = {
  url: {
    required: 'Please enter a website URL',
    invalid: 'Please enter a valid URL (e.g., https://example.com)',
    unreachable: 'This URL could not be reached. Please check the address.',
    dns_failed: 'Domain name could not be resolved. Please check the domain.',
  },

  email: {
    required: 'Email address is required',
    invalid: 'Please enter a valid email address',
    taken: 'This email address is already registered',
  },

  password: {
    required: 'Password is required',
    too_short: 'Password must be at least 8 characters',
    no_match: 'Passwords do not match',
  },

  two_factor: {
    required: '2FA code is required',
    invalid: 'Invalid 2FA code. Please check and try again.',
    expired: '2FA code has expired. Please generate a new one.',
  },
};

// Helper function for contextual errors
export function getErrorMessage(field: string, errorType: string): string {
  return errorMessages[field]?.[errorType] || 'An error occurred. Please try again.';
}
```

### 1.3 Loading States & Feedback (1-2 hours)

#### Skeleton Screens
```vue
<!-- MonitorCardSkeleton.vue -->
<template>
  <div class="bg-card p-6 rounded-lg border border-border animate-pulse">
    <div class="flex items-start justify-between mb-4">
      <div class="space-y-2 flex-1">
        <div class="h-4 bg-muted rounded w-3/4"></div>
        <div class="h-3 bg-muted rounded w-1/2"></div>
      </div>
      <div class="h-8 w-8 bg-muted rounded-full"></div>
    </div>

    <div class="grid grid-cols-3 gap-4">
      <div class="space-y-1">
        <div class="h-3 bg-muted rounded w-16"></div>
        <div class="h-6 bg-muted rounded w-12"></div>
      </div>
      <div class="space-y-1">
        <div class="h-3 bg-muted rounded w-16"></div>
        <div class="h-6 bg-muted rounded w-12"></div>
      </div>
      <div class="space-y-1">
        <div class="h-3 bg-muted rounded w-16"></div>
        <div class="h-6 bg-muted rounded w-12"></div>
      </div>
    </div>
  </div>
</template>
```

#### Progress Indicators
```vue
<!-- ProgressIndicator.vue -->
<template>
  <div class="space-y-2">
    <div class="flex justify-between text-sm">
      <span class="text-foreground">{{ label }}</span>
      <span class="text-muted-foreground">{{ percentage }}%</span>
    </div>

    <div class="h-2 bg-muted rounded-full overflow-hidden">
      <div
        class="h-full bg-primary transition-all duration-300 ease-out"
        :style="{ width: `${percentage}%` }"
      ></div>
    </div>

    <p v-if="subtitle" class="text-xs text-muted-foreground">
      {{ subtitle }}
    </p>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  label: string;
  percentage: number;
  subtitle?: string;
}>();
</script>

<!-- Usage example -->
<ProgressIndicator
  label="Checking monitors"
  :percentage="(completedChecks / totalChecks) * 100"
  :subtitle="`${completedChecks} of ${totalChecks} complete`"
/>
```

#### Optimistic Updates
```typescript
// Example: Delete monitor with optimistic update
function deleteMonitor(monitorId: number) {
  // Optimistically remove from UI
  monitors.value = monitors.value.filter(m => m.id !== monitorId);

  router.delete(`/monitors/${monitorId}`, {
    preserveScroll: true,
    onError: (errors) => {
      // Revert optimistic update on error
      loadMonitors();

      // Show error notification
      toast.error('Failed to delete monitor', {
        description: 'Please try again or contact support.',
      });
    },
    onSuccess: () => {
      toast.success('Monitor deleted successfully');
    },
  });
}
```

## Part 2: Accessibility Improvements (3-4 hours)

### Agent Assignment
- **Primary**: `vue-component-builder`
- **Testing**: `browser-tester` (with accessibility testing)

### 2.1 WCAG 2.1 AA Compliance (2 hours)

#### Keyboard Navigation
```vue
<!-- Example: Accessible dropdown menu -->
<template>
  <div class="relative" ref="menuRef">
    <button
      ref="triggerRef"
      @click="toggleMenu"
      @keydown="handleTriggerKeydown"
      :aria-expanded="isOpen"
      aria-haspopup="true"
      :aria-controls="menuId"
      class="px-4 py-2 bg-background border border-border rounded hover:bg-accent"
    >
      {{ label }}
      <ChevronDown class="inline w-4 h-4 ml-2" />
    </button>

    <Transition name="fade">
      <ul
        v-if="isOpen"
        :id="menuId"
        role="menu"
        class="absolute z-10 mt-2 w-56 bg-background border border-border rounded-md shadow-lg"
        @keydown="handleMenuKeydown"
      >
        <li
          v-for="(item, index) in items"
          :key="item.id"
          role="menuitem"
          :tabindex="index === focusedIndex ? 0 : -1"
          @click="selectItem(item)"
          @keydown.enter="selectItem(item)"
          @keydown.space.prevent="selectItem(item)"
          class="px-4 py-2 hover:bg-accent cursor-pointer focus:bg-accent focus:outline-none"
        >
          {{ item.label }}
        </li>
      </ul>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps<{
  label: string;
  items: Array<{ id: string; label: string }>;
}>();

const emit = defineEmits<{
  select: [item: { id: string; label: string }];
}>();

const menuId = `menu-${Math.random().toString(36).substr(2, 9)}`;
const isOpen = ref(false);
const focusedIndex = ref(0);
const menuRef = ref<HTMLElement | null>(null);
const triggerRef = ref<HTMLButtonElement | null>(null);

function toggleMenu() {
  isOpen.value = !isOpen.value;
  if (isOpen.value) {
    focusedIndex.value = 0;
  }
}

function handleTriggerKeydown(event: KeyboardEvent) {
  if (event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ') {
    event.preventDefault();
    isOpen.value = true;
    focusedIndex.value = 0;
  }
}

function handleMenuKeydown(event: KeyboardEvent) {
  switch (event.key) {
    case 'ArrowDown':
      event.preventDefault();
      focusedIndex.value = Math.min(focusedIndex.value + 1, props.items.length - 1);
      break;
    case 'ArrowUp':
      event.preventDefault();
      focusedIndex.value = Math.max(focusedIndex.value - 1, 0);
      break;
    case 'Escape':
      isOpen.value = false;
      triggerRef.value?.focus();
      break;
    case 'Tab':
      isOpen.value = false;
      break;
  }
}

function selectItem(item: { id: string; label: string }) {
  emit('select', item);
  isOpen.value = false;
  triggerRef.value?.focus();
}

// Close menu when clicking outside
function handleClickOutside(event: MouseEvent) {
  if (menuRef.value && !menuRef.value.contains(event.target as Node)) {
    isOpen.value = false;
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>
```

#### ARIA Labels and Descriptions
```vue
<!-- Example: Accessible form with ARIA -->
<template>
  <form @submit.prevent="handleSubmit" aria-labelledby="form-title">
    <h2 id="form-title" class="text-2xl font-bold mb-6">
      Add New Monitor
    </h2>

    <div class="space-y-4">
      <div>
        <label for="url-input" class="block text-sm font-medium mb-2">
          Website URL
          <span class="text-destructive" aria-label="required">*</span>
        </label>
        <input
          id="url-input"
          v-model="form.url"
          type="url"
          required
          aria-required="true"
          :aria-invalid="!!errors.url"
          :aria-describedby="errors.url ? 'url-error' : 'url-hint'"
          class="w-full px-3 py-2 border border-border rounded"
        />
        <p id="url-hint" class="mt-1 text-sm text-muted-foreground">
          Enter the full URL including https://
        </p>
        <p
          v-if="errors.url"
          id="url-error"
          role="alert"
          class="mt-1 text-sm text-destructive"
        >
          {{ errors.url }}
        </p>
      </div>

      <fieldset>
        <legend class="text-sm font-medium mb-2">
          Monitoring Options
        </legend>
        <div class="space-y-2">
          <label class="flex items-center gap-2">
            <input
              type="checkbox"
              v-model="form.uptime_check"
              aria-describedby="uptime-description"
            />
            <span>Uptime Monitoring</span>
          </label>
          <p id="uptime-description" class="text-xs text-muted-foreground ml-6">
            Monitor website availability and response time
          </p>

          <label class="flex items-center gap-2">
            <input
              type="checkbox"
              v-model="form.ssl_check"
              aria-describedby="ssl-description"
            />
            <span>SSL Certificate Check</span>
          </label>
          <p id="ssl-description" class="text-xs text-muted-foreground ml-6">
            Check SSL certificate validity and expiration
          </p>
        </div>
      </fieldset>

      <button
        type="submit"
        :disabled="isSubmitting"
        :aria-busy="isSubmitting"
        class="bg-primary text-primary-foreground px-4 py-2 rounded hover:bg-primary/90 disabled:opacity-50"
      >
        {{ isSubmitting ? 'Adding Monitor...' : 'Add Monitor' }}
      </button>
    </div>
  </form>
</template>
```

#### Color Contrast Verification
```bash
# Use browser-tester with accessibility checks
test('all interactive elements meet WCAG AA color contrast', async ({ page }) => {
  await page.goto('/dashboard');

  // Run axe accessibility audit
  const results = await page.evaluate(() => {
    return new Promise((resolve) => {
      // @ts-ignore
      axe.run(document, {
        rules: {
          'color-contrast': { enabled: true }
        }
      }, (err, results) => {
        resolve(results);
      });
    });
  });

  const violations = results.violations.filter(v => v.id === 'color-contrast');

  expect(violations).toHaveLength(0);
});
```

### 2.2 Screen Reader Support (1-2 hours)

#### Semantic HTML Structure
```vue
<!-- Before: Non-semantic -->
<div class="header">
  <div class="logo">SSL Monitor</div>
  <div class="nav">
    <div class="nav-item">Dashboard</div>
    <div class="nav-item">Monitors</div>
  </div>
</div>

<!-- After: Semantic HTML -->
<header role="banner">
  <h1 class="logo">
    <Link href="/">SSL Monitor</Link>
  </h1>

  <nav role="navigation" aria-label="Main navigation">
    <ul>
      <li><Link href="/dashboard">Dashboard</Link></li>
      <li><Link href="/monitors">Monitors</Link></li>
    </ul>
  </nav>
</header>

<main role="main">
  <!-- Page content -->
</main>

<footer role="contentinfo">
  <!-- Footer content -->
</footer>
```

#### Live Regions for Dynamic Updates
```vue
<!-- NotificationManager.vue -->
<template>
  <div
    role="status"
    aria-live="polite"
    aria-atomic="true"
    class="sr-only"
  >
    {{ announceMessage }}
  </div>

  <div class="fixed bottom-4 right-4 space-y-2">
    <TransitionGroup name="slide-up">
      <div
        v-for="notification in notifications"
        :key="notification.id"
        role="alert"
        class="bg-card border border-border rounded-lg p-4 shadow-lg"
      >
        <p class="font-semibold">{{ notification.title }}</p>
        <p class="text-sm text-muted-foreground">{{ notification.message }}</p>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';

const notifications = ref([]);
const announceMessage = computed(() => {
  const latest = notifications.value[0];
  return latest ? `${latest.title}: ${latest.message}` : '';
});
</script>

<style scoped>
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border-width: 0;
}
</style>
```

## Part 3: Responsive Design Optimization (3-4 hours)

### Agent Assignment
- **Primary**: `styling-expert`
- **Testing**: `browser-tester` (with device emulation)

### 3.1 Mobile-First Improvements (2 hours)

#### Responsive Navigation
```vue
<!-- MobileNavigation.vue -->
<template>
  <nav class="lg:hidden">
    <!-- Mobile menu button -->
    <button
      @click="isOpen = !isOpen"
      aria-expanded="isOpen"
      aria-controls="mobile-menu"
      class="p-2 rounded-md hover:bg-accent"
    >
      <MenuIcon v-if="!isOpen" class="w-6 h-6" />
      <XIcon v-else class="w-6 h-6" />
      <span class="sr-only">{{ isOpen ? 'Close menu' : 'Open menu' }}</span>
    </button>

    <!-- Mobile menu panel -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div
        v-if="isOpen"
        id="mobile-menu"
        class="absolute top-16 left-0 right-0 bg-background border-b border-border shadow-lg"
      >
        <div class="px-4 py-2 space-y-1">
          <Link
            v-for="item in navigationItems"
            :key="item.href"
            :href="item.href"
            class="block px-3 py-2 rounded-md hover:bg-accent"
            @click="isOpen = false"
          >
            <component :is="item.icon" class="inline w-5 h-5 mr-3" />
            {{ item.label }}
          </Link>
        </div>
      </div>
    </Transition>
  </nav>
</template>
```

#### Touch-Friendly Interactions
```vue
<!-- Touch-optimized button sizes (min 44x44px) -->
<button class="min-h-[44px] min-w-[44px] p-3 rounded-lg">
  <Icon class="w-5 h-5" />
</button>

<!-- Swipeable cards -->
<template>
  <div
    class="overflow-x-auto snap-x snap-mandatory"
    @touchstart="handleTouchStart"
    @touchend="handleTouchEnd"
  >
    <div class="flex gap-4 pb-4">
      <div
        v-for="monitor in monitors"
        :key="monitor.id"
        class="snap-center shrink-0 w-80"
      >
        <MonitorCard :monitor="monitor" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
let touchStartX = 0;
let touchEndX = 0;

function handleTouchStart(e: TouchEvent) {
  touchStartX = e.changedTouches[0].screenX;
}

function handleTouchEnd(e: TouchEvent) {
  touchEndX = e.changedTouches[0].screenX;
  handleSwipe();
}

function handleSwipe() {
  const swipeThreshold = 50;
  const diff = touchStartX - touchEndX;

  if (Math.abs(diff) > swipeThreshold) {
    if (diff > 0) {
      // Swiped left
      console.log('Next');
    } else {
      // Swiped right
      console.log('Previous');
    }
  }
}
</script>
```

### 3.2 Tablet Optimization (1 hour)

#### Adaptive Layouts
```vue
<template>
  <!-- Stack on mobile, 2 columns on tablet, 3 on desktop -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <MonitorCard v-for="monitor in monitors" :key="monitor.id" :monitor="monitor" />
  </div>

  <!-- Sidebar navigation: bottom on mobile, side on tablet/desktop -->
  <div class="flex flex-col md:flex-row gap-4">
    <aside class="w-full md:w-64 order-2 md:order-1">
      <Navigation />
    </aside>
    <main class="flex-1 order-1 md:order-2">
      <slot />
    </main>
  </div>

  <!-- Adaptive form layout -->
  <form class="space-y-4">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <FormField label="First Name" />
      <FormField label="Last Name" />
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <FormField label="Email" />
      <FormField label="Phone" />
    </div>
  </form>
</template>
```

### 3.3 Device Testing (1 hour)

```typescript
// Browser tests with device emulation
test('dashboard is usable on mobile devices', async ({ page }) => {
  // Test on iPhone 12
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto('/dashboard');

  // Navigation should be collapsed
  await expect(page.locator('[aria-label="Open menu"]')).toBeVisible();

  // Cards should stack vertically
  const cards = page.locator('[data-testid="monitor-card"]');
  const firstCard = cards.first();
  const secondCard = cards.nth(1);

  const firstBox = await firstCard.boundingBox();
  const secondBox = await secondCard.boundingBox();

  // Second card should be below first (not beside)
  expect(secondBox!.y).toBeGreaterThan(firstBox!.y + firstBox!.height);
});

test('touch interactions work on tablets', async ({ page }) => {
  // Test on iPad
  await page.setViewportSize({ width: 768, height: 1024 });
  await page.goto('/monitors');

  // Test swipe gesture
  const card = page.locator('[data-testid="monitor-card"]').first();
  const box = await card.boundingBox();

  await page.touchscreen.tap(box!.x + 50, box!.y + 50);
  await page.touchscreen.swipe(
    { x: box!.x + box!.width - 50, y: box!.y + 50 },
    { x: box!.x + 50, y: box!.y + 50 }
  );

  // Verify swipe action triggered
  await expect(page.locator('[data-testid="action-menu"]')).toBeVisible();
});
```

## Part 4: Visual Consistency & Design System (2-3 hours)

### Agent Assignment
- **Primary**: `styling-expert`
- **Documentation**: `documentation-writer`

### 4.1 Component Audit (1 hour)

#### Style Consistency Check
```bash
# Find all button variants
grep -r "bg-primary\|bg-secondary\|bg-destructive" resources/js/

# Find inconsistent spacing
grep -r "p-[0-9]\|m-[0-9]" resources/js/ | grep -v "p-4\|p-6\|m-4"

# Find color usage outside semantic tokens
grep -r "bg-blue\|text-red\|border-gray" resources/js/
```

#### Component Consolidation
```vue
<!-- Before: Multiple button styles -->
<button class="px-4 py-2 bg-blue-600 text-white rounded">Primary</button>
<button class="px-3 py-1 bg-gray-200 text-gray-800 rounded">Secondary</button>
<button class="p-2 bg-red-500 text-white rounded-lg">Danger</button>

<!-- After: Unified Button component -->
<Button variant="primary">Primary</Button>
<Button variant="secondary">Secondary</Button>
<Button variant="destructive">Danger</Button>

<!-- Button.vue implementation -->
<template>
  <button
    :class="[
      'inline-flex items-center justify-center rounded-md text-sm font-medium',
      'transition-colors focus-visible:outline-none focus-visible:ring-2',
      'disabled:pointer-events-none disabled:opacity-50',
      variantClasses[variant],
      sizeClasses[size],
    ]"
    v-bind="$attrs"
  >
    <slot />
  </button>
</template>

<script setup lang="ts">
const props = withDefaults(defineProps<{
  variant?: 'primary' | 'secondary' | 'destructive' | 'outline' | 'ghost';
  size?: 'sm' | 'md' | 'lg';
}>(), {
  variant: 'primary',
  size: 'md',
});

const variantClasses = {
  primary: 'bg-primary text-primary-foreground hover:bg-primary/90',
  secondary: 'bg-secondary text-secondary-foreground hover:bg-secondary/80',
  destructive: 'bg-destructive text-destructive-foreground hover:bg-destructive/90',
  outline: 'border border-border bg-background hover:bg-accent hover:text-accent-foreground',
  ghost: 'hover:bg-accent hover:text-accent-foreground',
};

const sizeClasses = {
  sm: 'h-9 px-3',
  md: 'h-10 px-4 py-2',
  lg: 'h-11 px-8',
};
</script>
```

### 4.2 Design Tokens Documentation (1 hour)

Create `docs/ui/DESIGN_TOKENS.md`:
```markdown
# Design Tokens

## Colors

### Semantic Tokens
| Token | Usage | Light Mode | Dark Mode |
|-------|-------|------------|-----------|
| `--background` | Page background | hsl(0 0% 100%) | hsl(222.2 84% 4.9%) |
| `--foreground` | Primary text | hsl(222.2 84% 4.9%) | hsl(210 40% 98%) |
| `--primary` | Primary actions | hsl(221.2 83.2% 53.3%) | hsl(217.2 91.2% 59.8%) |
| `--destructive` | Danger/delete | hsl(0 84.2% 60.2%) | hsl(0 62.8% 30.6%) |

### Usage Guidelines
- Use semantic tokens for all UI elements
- Never use numeric scales (e.g., `bg-gray-300`)
- Dark mode handled automatically via CSS variables

## Typography

### Font Sizes
| Class | Size | Usage |
|-------|------|-------|
| `text-xs` | 0.75rem | Small labels, captions |
| `text-sm` | 0.875rem | Body text, form labels |
| `text-base` | 1rem | Default body text |
| `text-lg` | 1.125rem | Emphasized text |
| `text-xl` | 1.25rem | Section headings |
| `text-2xl` | 1.5rem | Page titles |

### Font Weights
- Regular: 400 (body text)
- Medium: 500 (emphasized text)
- Semibold: 600 (headings)
- Bold: 700 (strong emphasis)

## Spacing

### Scale
- 4px base unit
- Common values: 4, 8, 12, 16, 24, 32, 48, 64px

### Usage
| Space | Class | Usage |
|-------|-------|-------|
| 4px | `p-1`, `m-1` | Tight spacing |
| 8px | `p-2`, `m-2` | Compact layouts |
| 16px | `p-4`, `m-4` | Default spacing |
| 24px | `p-6`, `m-6` | Comfortable spacing |
| 32px | `p-8`, `m-8` | Section spacing |

## Components

### Shadows
- `shadow-sm`: Subtle elevation (cards)
- `shadow-md`: Standard elevation (dropdowns)
- `shadow-lg`: Strong elevation (modals)

### Border Radius
- `rounded-sm`: 2px (small elements)
- `rounded-md`: 6px (buttons, inputs)
- `rounded-lg`: 8px (cards)
- `rounded-full`: 9999px (avatars, pills)
```

### 4.3 Component Library Update (1 hour)

Update `resources/js/Components/README.md`:
```markdown
# Component Library

## Form Components

### Button
**Usage**: Primary interactive elements

**Props**:
- `variant`: primary | secondary | destructive | outline | ghost
- `size`: sm | md | lg
- `disabled`: boolean

**Examples**:
```vue
<Button variant="primary">Save Changes</Button>
<Button variant="destructive" size="sm">Delete</Button>
<Button variant="ghost" disabled>Loading...</Button>
```

### Input
**Usage**: Text input fields

**Props**:
- `type`: text | email | url | password
- `error`: string (error message)
- `disabled`: boolean

**Accessibility**:
- Always use with `<label>`
- Use `aria-describedby` for errors/hints

**Examples**:
```vue
<label for="email">Email</label>
<Input
  id="email"
  type="email"
  v-model="form.email"
  :error="errors.email"
  aria-describedby="email-hint"
/>
<p id="email-hint">We'll never share your email</p>
```

## Layout Components

### Card
**Usage**: Content containers

**Props**:
- `padding`: sm | md | lg
- `hoverable`: boolean

**Examples**:
```vue
<Card padding="lg">
  <CardHeader>
    <CardTitle>Monitor Status</CardTitle>
  </CardHeader>
  <CardContent>
    <p>All systems operational</p>
  </CardContent>
</Card>
```
```

## Success Criteria

### UX Flow Improvements
- ✅ New user onboarding flow optimized (< 5 minutes)
- ✅ Monitor creation streamlined (inline quick-add)
- ✅ Alert response improved (context panel)
- ✅ Form validation provides immediate feedback
- ✅ Loading states implemented (skeletons, progress)
- ✅ Error messages are clear and actionable

### Accessibility (WCAG 2.1 AA)
- ✅ Keyboard navigation fully functional
- ✅ Screen reader support complete
- ✅ Color contrast meets WCAG AA (4.5:1)
- ✅ ARIA labels and descriptions present
- ✅ Focus indicators visible
- ✅ Forms fully accessible

### Responsive Design
- ✅ Mobile layout optimized (< 768px)
- ✅ Tablet layout functional (768px - 1024px)
- ✅ Touch interactions work properly
- ✅ Navigation adapts to screen size
- ✅ Content readable on all devices

### Visual Consistency
- ✅ Component variants standardized
- ✅ Spacing consistent throughout
- ✅ Color usage follows design tokens
- ✅ Typography hierarchy clear
- ✅ Design system documented

## Verification Commands

### Accessibility Testing
```bash
# Run accessibility tests with browser-tester
./vendor/bin/sail artisan test tests/Browser/AccessibilityTest.php

# Manual accessibility audit
# Use browser dev tools (Lighthouse, axe DevTools)
```

### Responsive Testing
```bash
# Test on multiple devices with browser-tester
./vendor/bin/sail artisan test tests/Browser/ResponsiveTest.php

# Manual testing devices:
# - Mobile: iPhone 12 (390x844), iPhone 12 Pro Max (428x926)
# - Tablet: iPad (768x1024), iPad Pro (1024x1366)
# - Desktop: 1366x768, 1920x1080
```

### Visual Regression Testing
```bash
# Capture baseline screenshots
./vendor/bin/sail artisan test:visual-baseline

# Compare against baseline
./vendor/bin/sail artisan test:visual-regression
```

## Dependencies

**Requires Completion**:
- Phase 5: Production Optimization (performance baselines)
- Phase 8: Security & Performance Audit (performance data)

**Enables**:
- Better user adoption and satisfaction
- Reduced support requests
- Improved accessibility compliance
- Professional, polished application

## Agent Workflow

### UX Flow Optimization (vue-component-builder + browser-tester)
1. Analyze current user journeys with browser-tester
2. Identify pain points and optimization opportunities
3. Implement improvements (onboarding, quick-add, etc.)
4. Test new flows with browser-tester
5. Measure completion times and success rates

### Accessibility Improvements (vue-component-builder)
1. Audit components against WCAG 2.1 AA checklist
2. Add keyboard navigation and ARIA labels
3. Implement screen reader support
4. Test with accessibility tools (axe, Lighthouse)
5. Document accessibility patterns

### Responsive Design (styling-expert + browser-tester)
1. Test current design on multiple devices
2. Identify layout issues and improvements
3. Implement mobile-first responsive styles
4. Test touch interactions
5. Verify on real devices

### Design System (styling-expert + documentation-writer)
1. Audit component usage and inconsistencies
2. Consolidate into reusable components
3. Document design tokens and patterns
4. Create component library documentation
5. Update existing components to use system

## Timeline

| Task | Duration | Agent |
|------|----------|-------|
| Critical UX Flows | 2 hours | vue-component-builder |
| Form Usability | 2 hours | vue-component-builder |
| Loading States | 2 hours | vue-component-builder |
| WCAG Compliance | 2 hours | vue-component-builder |
| Screen Reader Support | 2 hours | vue-component-builder |
| Mobile Optimization | 2 hours | styling-expert |
| Tablet Optimization | 1 hour | styling-expert |
| Device Testing | 1 hour | browser-tester |
| Component Audit | 1 hour | styling-expert |
| Design Tokens Documentation | 1 hour | documentation-writer |
| Component Library Update | 1 hour | documentation-writer |

**Total**: 12-16 hours

## Notes

- UX improvements are iterative - prioritize high-impact changes
- Accessibility is non-negotiable for production applications
- Mobile traffic should be monitored to prioritize mobile improvements
- Design system documentation enables consistent future development
- User testing is recommended before finalizing UX changes

## Related Documentation

- `docs/styling/TAILWIND_V4_STYLING_GUIDE.md` - Tailwind v4 semantic tokens
- `docs/CODING_GUIDE.md` - Vue 3 + TypeScript standards
- `WCAG 2.1 Guidelines`: https://www.w3.org/WAI/WCAG21/quickref/
- `MDN Accessibility`: https://developer.mozilla.org/en-US/docs/Web/Accessibility
