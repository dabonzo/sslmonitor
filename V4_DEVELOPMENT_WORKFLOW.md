# SSL Monitor v4 - Development Workflow

## 📊 Workflow Overview

This document outlines the comprehensive development workflow for SSL Monitor v4, focusing on **Test-Driven Development (TDD)** with **Pest v4 browser tests** and **VRISTO template integration**. The workflow is designed to ensure high-quality code while efficiently reusing proven components from `old_docs/`.

### **Core Workflow Principles**
1. **Test-First Development**: Write tests before implementation
2. **Proven Component Reuse**: Copy working code from old_docs
3. **VRISTO Visual Reference**: Use template for design inspiration only
4. **Continuous Integration**: Maintain test coverage throughout development

---

## 🧪 Test-Driven Development Process

### **TDD Cycle: Red → Green → Refactor**

#### **Phase 1: Red (Write Failing Test)**
```bash
# 1. Create test file
./vendor/bin/sail artisan make:test --pest WebsiteManagementTest

# 2. Write failing test first
./vendor/bin/sail artisan test --filter=WebsiteManagementTest
# Should fail initially
```

#### **Phase 2: Green (Make Test Pass)**
```bash
# 3. Implement minimal code to pass test
# Copy proven code from old_docs when possible

# 4. Run test to confirm it passes
./vendor/bin/sail artisan test --filter=WebsiteManagementTest
# Should pass now
```

#### **Phase 3: Refactor (Improve Code)**
```bash
# 5. Refactor code while maintaining tests
# Apply VRISTO styling, optimize performance

# 6. Run all tests to ensure no regression
./vendor/bin/sail artisan test
# All tests should still pass
```

---

## 📋 Pest v4 Testing Strategy

### **Test Categories & Structure**

#### **Model Tests** - Direct copy from old_docs
```php
// tests/Feature/Models/WebsiteTest.php
// Source: old_docs/tests/Feature/Models/WebsiteTest.php

test('website url is sanitized on save', function () {
    $user = User::factory()->create();
    $website = Website::create([
        'name' => 'Example Site',
        'url' => 'HTTP://EXAMPLE.COM/PATH/../',
        'user_id' => $user->id,
    ]);

    expect($website->url)->toBe('https://example.com');
});

test('website belongs to a user', function () {
    $user = User::factory()->create();
    $website = Website::factory()->for($user)->create();

    expect($website->user)->toBeInstanceOf(User::class)
        ->and($website->user->id)->toBe($user->id);
});
```

#### **Service Tests** - Direct copy from old_docs
```php
// tests/Feature/Services/SslCertificateCheckerTest.php
// Source: old_docs/tests/Feature/Services/SslCertificateCheckerTest.php

test('ssl certificate checker can fetch certificate from valid URL', function () {
    $website = Website::factory()->create(['url' => 'https://github.com']);
    $checker = new SslCertificateChecker();

    $result = $checker->checkCertificate($website);

    expect($result)->toBeArray()
        ->and($result['status'])->toBeIn(['valid', 'expiring_soon', 'expired', 'invalid', 'error'])
        ->and($result['issuer'])->not->toBeEmpty()
        ->and($result['expires_at'])->toBeInstanceOf(Carbon::class);
});
```

#### **Browser Tests** - Adapted for Vue.js + Inertia.js
```php
// tests/Feature/Browser/DashboardTest.php
// Adapted from: old_docs/tests/Feature/Livewire/SslDashboardComponentTest.php

test('user can view dashboard with ssl status overview', function () {
    $user = User::factory()->create();

    // Create test websites with different SSL statuses
    $validWebsite = Website::factory()->for($user)->create();
    SslCheck::factory()->for($validWebsite)->create(['status' => 'valid']);

    $expiringWebsite = Website::factory()->for($user)->create();
    SslCheck::factory()->for($expiringWebsite)->create(['status' => 'expiring_soon']);

    $this->actingAs($user)
        ->visit('/dashboard')
        ->assertSee('SSL Certificate Status')
        ->assertSee('Valid')
        ->assertSee('Expiring Soon')
        ->assertSee('1') // Count of valid certificates
        ->assertSee('1') // Count of expiring certificates
        ->assertNoJavascriptErrors();
});

test('user can add website with ssl preview', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->visit('/websites')
        ->assertSee('Add Website')
        ->fill('name', 'Example Website')
        ->fill('url', 'https://github.com')
        ->click('Check SSL Certificate')
        ->waitForText('SSL Certificate Preview')
        ->assertSee('Valid') // SSL status should appear
        ->click('Add Website')
        ->assertSee('Website added successfully')
        ->assertDatabaseHas('websites', [
            'user_id' => $user->id,
            'name' => 'Example Website',
            'url' => 'https://github.com',
        ]);
});

test('user can manage websites with bulk operations', function () {
    $user = User::factory()->create();
    $websites = Website::factory()->for($user)->count(3)->create();

    $this->actingAs($user)
        ->visit('/websites')
        ->assertSee($websites[0]->name)
        ->assertSee($websites[1]->name)
        ->assertSee($websites[2]->name)
        ->check("website_ids[{$websites[0]->id}]")
        ->check("website_ids[{$websites[1]->id}]")
        ->select('bulk_action', 'check')
        ->click('Apply Action')
        ->assertSee('SSL checks queued for 2 websites');

    // Verify jobs were dispatched
    expect(Queue::size('ssl-monitoring'))->toBe(2);
});
```

#### **API Tests** - Inertia.js response validation
```php
// tests/Feature/Api/WebsiteControllerTest.php

test('dashboard returns correct inertia response', function () {
    $user = User::factory()->create();
    Website::factory()->for($user)->count(5)->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk()
        ->assertInertia(fn ($page) =>
            $page->component('Dashboard/Index')
                ->has('statusCounts', fn ($counts) =>
                    $counts->has('total')
                          ->has('valid')
                          ->has('expiring_soon')
                          ->has('expired')
                          ->has('error')
                          ->has('pending')
                )
                ->has('recentChecks')
                ->has('criticalIssues')
        );
});

test('website store creates website and queues ssl check', function () {
    Queue::fake();
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post('/websites', [
            'name' => 'Test Website',
            'url' => 'https://example.com',
        ]);

    $response->assertRedirect('/websites')
        ->assertSessionHas('success');

    $this->assertDatabaseHas('websites', [
        'user_id' => $user->id,
        'name' => 'Test Website',
        'url' => 'https://example.com',
    ]);

    Queue::assertPushed(CheckSslCertificateJob::class);
});
```

---

## 🎨 VRISTO Integration Workflow

### **Design System Application Strategy**

#### **Step 1: Extract Design Patterns** (Visual Reference Only)
```bash
# Study VRISTO HTML for visual patterns
# DO NOT copy HTML directly - use as visual reference for Vue components

# Example: Dashboard cards pattern analysis
# vristo-html-main/dashboard.html → Visual inspiration
# Create Vue components with similar visual design
```

#### **Step 2: Create Vue Components with VRISTO Styling**
```vue
<!-- resources/js/Components/StatusCard.vue -->
<!-- Inspired by VRISTO dashboard cards -->

<template>
  <div class="p-4 bg-white dark:bg-zinc-900 border rounded-lg shadow-sm"
       :class="[
         `border-${color}-200 dark:border-${color}-700`
       ]">
    <div class="flex items-center">
      <div class="p-2 rounded-lg"
           :class="[
             `bg-${color}-100 dark:bg-${color}-900`
           ]">
        <Component :is="iconComponent"
                   class="h-6 w-6"
                   :class="[
                     `text-${color}-600 dark:text-${color}-400`
                   ]" />
      </div>
      <div class="ml-3">
        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">
          {{ label }}
        </p>
        <p class="text-2xl font-semibold"
           :class="[
             `text-${color}-600 dark:text-${color}-400`
           ]">
          {{ count }}
        </p>
      </div>
    </div>
    <div class="mt-2" v-if="percentage !== undefined">
      <span class="text-xs text-zinc-500 dark:text-zinc-400">
        {{ percentage }}% of total
      </span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
// Import Lucide icons for consistency
import {
  ShieldCheck,
  ExclamationTriangle,
  ShieldExclamation,
  XCircle,
  Clock
} from 'lucide-vue-next'

const props = defineProps({
  count: {
    type: Number,
    required: true
  },
  label: {
    type: String,
    required: true
  },
  color: {
    type: String,
    default: 'gray',
    validator: (value) => ['green', 'yellow', 'red', 'gray'].includes(value)
  },
  icon: {
    type: String,
    required: true
  },
  percentage: {
    type: Number,
    default: undefined
  }
})

const iconComponent = computed(() => {
  const icons = {
    'shield-check': ShieldCheck,
    'exclamation-triangle': ExclamationTriangle,
    'shield-exclamation': ShieldExclamation,
    'x-circle': XCircle,
    'clock': Clock,
  }
  return icons[props.icon] || Clock
})
</script>
```

#### **Step 3: VRISTO Color System Integration**
```css
/* resources/css/app.css */
/* VRISTO color variables for consistency */

:root {
  /* VRISTO Primary Colors */
  --vristo-primary: #4361ee;
  --vristo-secondary: #805dca;
  --vristo-success: #00ab55;
  --vristo-warning: #e7515a;
  --vristo-info: #2196f3;

  /* VRISTO Semantic Colors */
  --vristo-valid: #00ab55;
  --vristo-expiring: #fbbf24;
  --vristo-expired: #ef4444;
  --vristo-error: #ef4444;
  --vristo-pending: #6b7280;
}

/* TailwindCSS configuration integration */
@tailwind base;
@tailwind components;
@tailwind utilities;

/* Custom VRISTO-inspired components */
@layer components {
  .vristo-card {
    @apply bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-sm;
  }

  .vristo-button-primary {
    @apply bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors;
  }

  .vristo-input {
    @apply w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg
           bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100
           focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
  }
}
```

#### **Step 4: Responsive Layout Implementation**
```vue
<!-- resources/js/Layouts/AppLayout.vue -->
<!-- VRISTO-inspired responsive layout -->

<template>
  <div class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
    <!-- Sidebar -->
    <AppSidebar
      :is-open="sidebarOpen"
      @close="sidebarOpen = false"
    />

    <!-- Main Content -->
    <div class="lg:pl-64">
      <!-- Header -->
      <AppHeader
        :title="title"
        @toggle-sidebar="sidebarOpen = !sidebarOpen"
      />

      <!-- Page Content -->
      <main class="p-6">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import AppSidebar from '@/Components/AppSidebar.vue'
import AppHeader from '@/Components/AppHeader.vue'

defineProps({
  title: {
    type: String,
    default: 'SSL Monitor'
  }
})

const sidebarOpen = ref(false)
</script>
```

---

## 🔄 Development Workflow Steps

### **Feature Development Process**

#### **Step 1: Planning & Test Design**
```bash
# 1. Read requirements from implementation plan
# 2. Check if old_docs has similar feature to reuse
# 3. Write comprehensive test scenarios
# 4. Create test file with failing tests

./vendor/bin/sail artisan make:test --pest FeatureNameTest
```

#### **Step 2: Backend Implementation** (TDD)
```bash
# 1. Write failing model test
./vendor/bin/sail artisan test --filter=ModelTest
# Should fail

# 2. Copy/adapt model from old_docs
# 3. Run test again
./vendor/bin/sail artisan test --filter=ModelTest
# Should pass

# 4. Write failing service test
./vendor/bin/sail artisan test --filter=ServiceTest
# Should fail

# 5. Copy/adapt service from old_docs
# 6. Run test again
./vendor/bin/sail artisan test --filter=ServiceTest
# Should pass
```

#### **Step 3: API Layer Implementation** (TDD)
```bash
# 1. Write failing controller test
./vendor/bin/sail artisan test --filter=ControllerTest
# Should fail

# 2. Create Inertia.js controller
# 3. Run test again
./vendor/bin/sail artisan test --filter=ControllerTest
# Should pass
```

#### **Step 4: Frontend Implementation** (VRISTO Integration)
```bash
# 1. Study VRISTO template for visual reference
# 2. Create Vue.js page/component with VRISTO styling
# 3. Implement Inertia.js data binding
# 4. Test in browser manually
```

#### **Step 5: Browser Testing** (Pest v4)
```bash
# 1. Write comprehensive browser test
./vendor/bin/sail artisan test --filter=BrowserTest
# Should fail initially

# 2. Fix any issues in Vue components
# 3. Run browser test again
./vendor/bin/sail artisan test --filter=BrowserTest
# Should pass

# 4. Test responsive design and accessibility
```

#### **Step 6: Integration & Quality Assurance**
```bash
# 1. Run all tests to ensure no regression
./vendor/bin/sail artisan test

# 2. Check code formatting
./vendor/bin/sail exec laravel.test ./vendor/bin/pint

# 3. Manual testing in different browsers
# 4. Mobile responsiveness testing
```

---

## 📱 VRISTO Responsive Design Workflow

### **Mobile-First Approach**

#### **Breakpoint Strategy**
```css
/* VRISTO responsive breakpoints */
/* Mobile: 0-640px (default) */
/* Tablet: 641-1024px (md:) */
/* Desktop: 1025px+ (lg:) */

.sidebar {
  /* Mobile: Hidden by default */
  @apply fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-zinc-900 transform -translate-x-full transition-transform;

  /* Tablet: Slide-over */
  @apply md:translate-x-0 md:static md:inset-0;

  /* Desktop: Always visible */
  @apply lg:translate-x-0 lg:static lg:inset-0;
}

.main-content {
  /* Mobile: Full width */
  @apply w-full;

  /* Desktop: Account for sidebar */
  @apply lg:pl-64;
}
```

#### **Component Responsiveness**
```vue
<!-- Responsive status cards grid -->
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
  <StatusCard
    v-for="(count, status) in statusCounts"
    :key="status"
    :count="count"
    :label="status"
    :color="getStatusColor(status)"
    :icon="getStatusIcon(status)"
  />
</div>

<!-- Responsive table -->
<div class="overflow-x-auto">
  <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
    <!-- Table content -->
  </table>
</div>
```

---

## 🧹 Code Quality Workflow

### **Pre-commit Checklist**
```bash
# 1. Run all tests
./vendor/bin/sail artisan test
# All tests must pass

# 2. Format code
./vendor/bin/sail exec laravel.test ./vendor/bin/pint
# Code must be properly formatted

# 3. Check for console errors in browser
# No JavaScript errors in development console

# 4. Verify responsive design
# Test on mobile, tablet, and desktop viewports

# 5. Accessibility check
# Basic keyboard navigation and screen reader compatibility
```

### **Performance Optimization**
```bash
# 1. Optimize database queries
# Check for N+1 query problems
# Use eager loading: with(['relationship'])

# 2. Cache frequently accessed data
# Use Laravel cache for dashboard statistics

# 3. Optimize asset loading
# Lazy load Vue components where appropriate
# Minimize JavaScript bundle size

# 4. SSL check performance
# Ensure certificate checks complete within timeout
# Monitor queue processing performance
```

---

## 📊 Workflow Integration with MCP Servers

### **Development Support Tools**

#### **Laravel Boost Integration**
```bash
# 1. Application context discovery
application-info
database-schema
list-routes

# 2. Development and debugging
tinker
# Test SSL certificate logic interactively

last-error
# Check for application errors

read-log-entries --entries=20
# Monitor SSL check job execution

# 3. Documentation research
search-docs ["ssl certificate", "pest testing", "inertia forms"]
```

#### **Context7 Integration**
```bash
# 1. VRISTO template research
use context7: "VRISTO dashboard layout responsive design"
use context7: "VRISTO sidebar navigation mobile patterns"

# 2. Vue.js implementation guidance
use context7: "Vue 3 composition API testing patterns"
use context7: "Inertia.js form handling best practices"

# 3. Browser testing optimization
use context7: "Playwright Vue component testing strategies"
```

---

## 🎯 Success Metrics

### **Development Quality Standards**
- **Test Coverage**: Maintain 85%+ overall, 95%+ for SSL monitoring core
- **Performance**: Dashboard loads in <2 seconds
- **Mobile Experience**: Lighthouse score >90
- **Browser Compatibility**: Chrome, Firefox, Safari latest 2 versions

### **Workflow Efficiency Metrics**
- **TDD Cycle Time**: <30 minutes per feature cycle
- **Component Reuse**: 90%+ backend components from old_docs
- **Test Reliability**: 99%+ test pass rate
- **Code Quality**: 100% Laravel Pint compliance

### **VRISTO Integration Success**
- **Design Consistency**: All components follow VRISTO patterns
- **Responsive Design**: Perfect mobile experience
- **Accessibility**: WCAG 2.1 AA compliance
- **Dark Mode**: Complete theme switching support

---

## 🚀 Next Steps

1. **Start with Model Tests**: Copy proven tests from old_docs
2. **Implement Service Layer**: Reuse SSL monitoring logic
3. **Create API Controllers**: Build Inertia.js endpoints
4. **Design Vue Components**: Apply VRISTO styling patterns
5. **Write Browser Tests**: Ensure complete user workflows

This workflow ensures SSL Monitor v4 combines the reliability of proven architecture with the superior user experience of modern development practices.

---

## 📚 Related Documentation

- **[SSL_MONITOR_V4_IMPLEMENTATION_PLAN.md](SSL_MONITOR_V4_IMPLEMENTATION_PLAN.md)** - Complete 8-week development plan
- **[MIGRATION_FROM_V3.md](MIGRATION_FROM_V3.md)** - Component reuse and migration strategy
- **[V4_TECHNICAL_SPECIFICATIONS.md](V4_TECHNICAL_SPECIFICATIONS.md)** - Detailed technical architecture