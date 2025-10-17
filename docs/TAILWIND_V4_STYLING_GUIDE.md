# Tailwind CSS v4 Styling Guide

This guide documents the Tailwind v4 semantic color system implementation in SSL Monitor v4, including critical limitations, best practices, and troubleshooting.

## Table of Contents

1. [Overview](#overview)
2. [Tailwind v4 Color System](#tailwind-v4-color-system)
3. [Critical Limitations](#critical-limitations)
4. [Available Semantic Tokens](#available-semantic-tokens)
5. [Best Practices](#best-practices)
6. [Common Scenarios](#common-scenarios)
7. [Troubleshooting](#troubleshooting)
8. [Migration Guide](#migration-guide)

## Overview

SSL Monitor v4 uses **Tailwind CSS v4's semantic token system** instead of traditional numeric color scales. This approach provides:

- **Theme-aware colors**: Automatic light/dark mode support
- **Semantic naming**: Colors reflect their purpose, not their appearance
- **Centralized configuration**: All colors defined in `resources/css/app.css`
- **CSS variables**: Direct access to color values when needed

## Tailwind v4 Color System

### What Changed from Tailwind v3

#### ❌ Tailwind v3 (Numeric Scales - REMOVED)
```vue
<!-- These DO NOT work in Tailwind v4 -->
<div class="bg-gray-300 text-blue-600">
<button class="bg-slate-500 hover:bg-slate-600">
<div class="border-gray-200 text-red-500">
```

#### ✅ Tailwind v4 (Semantic Tokens)
```vue
<!-- Use semantic tokens instead -->
<div class="bg-muted text-primary">
<button class="bg-secondary hover:bg-secondary/90">
<div class="border-border text-destructive">
```

### How It Works

1. **CSS Variables**: Defined in `resources/css/app.css`
2. **Semantic Tokens**: Utility classes like `bg-primary`, `text-muted`
3. **Theme Switching**: Variables change based on `.dark` class
4. **HSL Format**: Colors use HSL for easy manipulation

```css
/* Example from app.css */
:root {
  --primary: 262.1 83.3% 57.8%;
  --background: 0 0% 100%;
}

.dark {
  --primary: 263.4 70% 50.4%;
  --background: 224 71.4% 4.1%;
}
```

## Critical Limitations

### 🚨 @apply with Semantic Tokens in Scoped Styles

**This is the most important limitation to understand.**

#### ❌ DOES NOT WORK (Causes Vite Build Errors)

```vue
<template>
  <div class="custom-element">Content</div>
</template>

<style scoped>
.custom-element {
  @apply bg-muted text-primary border-border;
  /* ❌ ERROR: Cannot resolve semantic tokens in scoped styles */
}
</style>
```

**Error Message:**
```
[postcss] Cannot resolve semantic token 'muted' in @apply directive
```

#### ✅ CORRECT SOLUTION (Use CSS Variables)

```vue
<template>
  <div class="custom-element">Content</div>
</template>

<style scoped>
.custom-element {
  background-color: hsl(var(--muted));
  color: hsl(var(--primary));
  border: 1px solid hsl(var(--border));
}
</style>
```

### Why This Happens

Tailwind v4 has a known issue where semantic tokens cannot be resolved within `@apply` directives inside Vue `<style scoped>` blocks. The scoped style transformation interferes with Tailwind's token resolution.

## Available Semantic Tokens

All tokens are defined in `resources/css/app.css`. Here's the complete reference:

### Layout & Structure

| Token | Use Case | Example |
|-------|----------|---------|
| `background` | Main page background | `bg-background` |
| `foreground` | Primary text color | `text-foreground` |
| `card` | Card backgrounds | `bg-card` |
| `card-foreground` | Text on cards | `text-card-foreground` |

### Interactive Elements

| Token | Use Case | Example |
|-------|----------|---------|
| `primary` | Primary actions | `bg-primary` |
| `primary-foreground` | Text on primary | `text-primary-foreground` |
| `secondary` | Secondary actions | `bg-secondary` |
| `secondary-foreground` | Text on secondary | `text-secondary-foreground` |

### Feedback & Status

| Token | Use Case | Example |
|-------|----------|---------|
| `destructive` | Errors, delete actions | `bg-destructive` |
| `destructive-foreground` | Text on destructive | `text-destructive-foreground` |
| `muted` | Subtle backgrounds | `bg-muted` |
| `muted-foreground` | Secondary text | `text-muted-foreground` |
| `accent` | Highlights | `bg-accent` |
| `accent-foreground` | Text on accents | `text-accent-foreground` |

### UI Components

| Token | Use Case | Example |
|-------|----------|---------|
| `border` | Borders and dividers | `border-border` |
| `input` | Input backgrounds | `bg-input` |
| `ring` | Focus rings | `ring-ring` |
| `popover` | Popover backgrounds | `bg-popover` |
| `popover-foreground` | Text in popovers | `text-popover-foreground` |

### Sidebar Components

| Token | Use Case | Example |
|-------|----------|---------|
| `sidebar` | Sidebar background | `bg-sidebar` |
| `sidebar-foreground` | Sidebar text | `text-sidebar-foreground` |
| `sidebar-primary` | Active sidebar items | `bg-sidebar-primary` |
| `sidebar-primary-foreground` | Active sidebar text | `text-sidebar-primary-foreground` |
| `sidebar-accent` | Hover states | `bg-sidebar-accent` |
| `sidebar-accent-foreground` | Hover text | `text-sidebar-accent-foreground` |
| `sidebar-border` | Sidebar borders | `border-sidebar-border` |
| `sidebar-ring` | Sidebar focus rings | `ring-sidebar-ring` |

### Chart Colors

| Token | Use Case | Example |
|-------|----------|---------|
| `chart-1` through `chart-5` | Data visualization | `fill-chart-1` |

## Best Practices

### 1. Template Styling (Preferred Approach)

**✅ Use semantic classes directly in templates:**

```vue
<template>
  <div class="bg-background text-foreground">
    <!-- Cards -->
    <div class="bg-card text-card-foreground border border-border rounded-lg p-6">
      <h2 class="text-foreground font-semibold">Card Title</h2>
      <p class="text-muted-foreground">Card description</p>
    </div>

    <!-- Buttons -->
    <button class="bg-primary text-primary-foreground hover:bg-primary/90">
      Primary Action
    </button>

    <button class="bg-secondary text-secondary-foreground hover:bg-secondary/80">
      Secondary Action
    </button>

    <!-- Destructive actions -->
    <button class="bg-destructive text-destructive-foreground hover:bg-destructive/90">
      Delete
    </button>

    <!-- Muted elements -->
    <div class="bg-muted text-muted-foreground p-4 rounded">
      Muted background content
    </div>
  </div>
</template>
```

### 2. Scoped Styles (Use CSS Variables)

**✅ When you need custom styles, use CSS variables:**

```vue
<template>
  <div class="custom-component">
    <div class="custom-header">Header</div>
    <div class="custom-body">Body content</div>
  </div>
</template>

<style scoped>
.custom-component {
  background-color: hsl(var(--card));
  border: 1px solid hsl(var(--border));
  border-radius: 0.5rem;
}

.custom-header {
  background-color: hsl(var(--muted));
  color: hsl(var(--foreground));
  padding: 1rem;
  border-bottom: 1px solid hsl(var(--border));
}

.custom-body {
  color: hsl(var(--card-foreground));
  padding: 1rem;
}

/* Hover states with opacity */
.custom-component:hover {
  background-color: hsl(var(--card) / 0.95);
}
</style>
```

### 3. Opacity Modifiers

**✅ Use opacity modifiers for hover/focus states:**

```vue
<template>
  <!-- Opacity with slash notation -->
  <button class="bg-primary/90 hover:bg-primary text-primary-foreground">
    Button with 90% opacity
  </button>

  <!-- Focus rings with opacity -->
  <input
    class="border-border focus:ring-2 focus:ring-primary/20"
    type="text"
  />
</template>

<style scoped>
/* Opacity in CSS variables */
.custom-overlay {
  background-color: hsl(var(--background) / 0.8);
  backdrop-filter: blur(4px);
}

.custom-shadow {
  box-shadow: 0 4px 6px hsl(var(--foreground) / 0.1);
}
</style>
```

### 4. Gradients

Gradients require special handling in Tailwind v4:

#### ❌ Semantic Tokens in Gradients (DOES NOT WORK)

```vue
<template>
  <!-- ❌ These cause errors -->
  <div class="bg-gradient-to-r from-background to-card">
  <div class="bg-gradient-to-br from-primary to-secondary">
</template>
```

#### ✅ Brand Gradients (Use Hex Values)

```vue
<template>
  <!-- ✅ Use exact hex values for brand gradients -->
  <div class="bg-gradient-to-r from-[#ef1262] to-[#4361ee]">
    Brand gradient
  </div>
</template>
```

#### ✅ Simple Backgrounds (Use Solid Colors)

```vue
<template>
  <!-- ✅ Use solid semantic colors instead -->
  <div class="bg-muted dark:bg-card">
    Adaptive background
  </div>
</template>
```

#### ✅ Custom CSS Gradients

```vue
<template>
  <div class="custom-gradient">
    Custom gradient background
  </div>
</template>

<style scoped>
.custom-gradient {
  background: linear-gradient(
    to right,
    hsl(var(--primary)),
    hsl(var(--accent))
  );
}
</style>
```

### 5. Theme Customization

To change the color scheme, edit `resources/css/app.css`:

```css
/* Light mode */
:root {
  --background: 0 0% 100%;
  --foreground: 222.2 84% 4.9%;
  --primary: 262.1 83.3% 57.8%;
  /* ... other tokens ... */
}

/* Dark mode */
.dark {
  --background: 224 71.4% 4.1%;
  --foreground: 210 40% 98%;
  --primary: 263.4 70% 50.4%;
  /* ... other tokens ... */
}
```

**After changing colors, clear caches:**

```bash
./vendor/bin/sail artisan cache:clear && \
./vendor/bin/sail artisan config:clear && \
./vendor/bin/sail artisan view:clear && \
./vendor/bin/sail npm run dev
```

## Common Scenarios

### Scenario 1: Creating a Custom Card Component

```vue
<template>
  <div class="custom-card">
    <div class="custom-card-header">
      <slot name="header" />
    </div>
    <div class="custom-card-body">
      <slot />
    </div>
    <div class="custom-card-footer" v-if="$slots.footer">
      <slot name="footer" />
    </div>
  </div>
</template>

<style scoped>
.custom-card {
  background-color: hsl(var(--card));
  color: hsl(var(--card-foreground));
  border: 1px solid hsl(var(--border));
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px hsl(var(--foreground) / 0.1);
}

.custom-card-header {
  padding: 1.5rem;
  border-bottom: 1px solid hsl(var(--border));
  font-weight: 600;
}

.custom-card-body {
  padding: 1.5rem;
}

.custom-card-footer {
  padding: 1rem 1.5rem;
  background-color: hsl(var(--muted));
  border-top: 1px solid hsl(var(--border));
  border-bottom-left-radius: 0.5rem;
  border-bottom-right-radius: 0.5rem;
}
</style>
```

### Scenario 2: Custom Alert Component

```vue
<template>
  <div :class="alertClasses">
    <div class="alert-icon">
      <slot name="icon" />
    </div>
    <div class="alert-content">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  variant?: 'info' | 'success' | 'warning' | 'danger';
}>();

const alertClasses = computed(() => {
  const base = 'alert-base';
  return `${base} alert-${props.variant || 'info'}`;
});
</script>

<style scoped>
.alert-base {
  display: flex;
  gap: 1rem;
  padding: 1rem;
  border-radius: 0.5rem;
  border: 1px solid hsl(var(--border));
}

.alert-info {
  background-color: hsl(var(--muted));
  color: hsl(var(--foreground));
}

.alert-success {
  background-color: hsl(142 76% 36% / 0.1);
  color: hsl(142 76% 36%);
  border-color: hsl(142 76% 36% / 0.3);
}

.alert-warning {
  background-color: hsl(38 92% 50% / 0.1);
  color: hsl(38 92% 50%);
  border-color: hsl(38 92% 50% / 0.3);
}

.alert-danger {
  background-color: hsl(var(--destructive) / 0.1);
  color: hsl(var(--destructive));
  border-color: hsl(var(--destructive) / 0.3);
}

.alert-icon {
  flex-shrink: 0;
}

.alert-content {
  flex: 1;
}
</style>
```

### Scenario 3: Status Badges

```vue
<template>
  <span :class="badgeClasses">
    <slot />
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  status: 'active' | 'inactive' | 'pending' | 'error';
}>();

const badgeClasses = computed(() => {
  const base = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium';

  const variants = {
    active: 'bg-accent/10 text-accent border border-accent/20',
    inactive: 'bg-muted text-muted-foreground border border-border',
    pending: 'bg-secondary/10 text-secondary-foreground border border-secondary/20',
    error: 'bg-destructive/10 text-destructive border border-destructive/20',
  };

  return `${base} ${variants[props.status]}`;
});
</script>
```

### Scenario 4: Interactive Table Rows

```vue
<template>
  <table class="custom-table">
    <thead class="custom-thead">
      <tr>
        <th class="custom-th">Name</th>
        <th class="custom-th">Status</th>
        <th class="custom-th">Actions</th>
      </tr>
    </thead>
    <tbody>
      <tr
        v-for="item in items"
        :key="item.id"
        class="custom-tr"
      >
        <td class="custom-td">{{ item.name }}</td>
        <td class="custom-td">{{ item.status }}</td>
        <td class="custom-td">
          <button class="text-primary hover:text-primary/80">Edit</button>
        </td>
      </tr>
    </tbody>
  </table>
</template>

<style scoped>
.custom-table {
  width: 100%;
  border-collapse: collapse;
  background-color: hsl(var(--card));
  border: 1px solid hsl(var(--border));
  border-radius: 0.5rem;
}

.custom-thead {
  background-color: hsl(var(--muted));
  border-bottom: 1px solid hsl(var(--border));
}

.custom-th {
  padding: 0.75rem 1rem;
  text-align: left;
  font-weight: 600;
  color: hsl(var(--foreground));
  font-size: 0.875rem;
}

.custom-tr {
  border-bottom: 1px solid hsl(var(--border));
  transition: background-color 0.2s;
}

.custom-tr:hover {
  background-color: hsl(var(--muted) / 0.5);
}

.custom-tr:last-child {
  border-bottom: none;
}

.custom-td {
  padding: 1rem;
  color: hsl(var(--card-foreground));
}
</style>
```

### Scenario 5: Modal/Dialog Overlay

```vue
<template>
  <Teleport to="body">
    <div v-if="isOpen" class="modal-overlay" @click.self="close">
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="text-foreground font-semibold text-lg">
            <slot name="title" />
          </h2>
          <button
            class="text-muted-foreground hover:text-foreground"
            @click="close"
          >
            ×
          </button>
        </div>
        <div class="modal-body">
          <slot />
        </div>
        <div class="modal-footer" v-if="$slots.footer">
          <slot name="footer" />
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
defineProps<{
  isOpen: boolean;
}>();

const emit = defineEmits<{
  close: [];
}>();

function close() {
  emit('close');
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background-color: hsl(var(--background) / 0.8);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 50;
}

.modal-content {
  background-color: hsl(var(--card));
  border: 1px solid hsl(var(--border));
  border-radius: 0.75rem;
  box-shadow: 0 10px 15px hsl(var(--foreground) / 0.1);
  max-width: 32rem;
  width: 100%;
  margin: 1rem;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.5rem;
  border-bottom: 1px solid hsl(var(--border));
}

.modal-body {
  padding: 1.5rem;
  color: hsl(var(--card-foreground));
}

.modal-footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid hsl(var(--border));
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
}
</style>
```

## Troubleshooting

### Error: "Cannot resolve semantic token in @apply directive"

**Problem:**
```vue
<style scoped>
.my-class {
  @apply bg-muted text-primary;
  /* ERROR: Cannot resolve semantic token 'muted' in @apply directive */
}
</style>
```

**Solution:**
```vue
<style scoped>
.my-class {
  background-color: hsl(var(--muted));
  color: hsl(var(--primary));
}
</style>
```

### Error: "Unknown utility class"

**Problem:**
```vue
<template>
  <!-- ERROR: Unknown utility class 'bg-gray-300' -->
  <div class="bg-gray-300 text-blue-600">
</template>
```

**Solution:**
```vue
<template>
  <!-- Use semantic tokens -->
  <div class="bg-muted text-primary">
</template>
```

### Colors Not Changing with Theme Switch

**Problem:**
Dark mode toggle doesn't change colors.

**Solution:**
1. Ensure `dark` class is on `<html>` or `<body>` element
2. Check that colors use semantic tokens, not hardcoded values
3. Verify `resources/css/app.css` has both `:root` and `.dark` definitions

```javascript
// Check theme toggle implementation
document.documentElement.classList.toggle('dark');
```

### Gradient Build Errors

**Problem:**
```vue
<template>
  <!-- ERROR: Cannot use semantic tokens in gradients -->
  <div class="bg-gradient-to-r from-primary to-secondary">
</template>
```

**Solution:**
```vue
<template>
  <!-- Option 1: Use solid color -->
  <div class="bg-primary">

  <!-- Option 2: Use hex values for brand gradients -->
  <div class="bg-gradient-to-r from-[#ef1262] to-[#4361ee]">
</template>

<style scoped>
/* Option 3: Use CSS gradients */
.custom-gradient {
  background: linear-gradient(
    to right,
    hsl(var(--primary)),
    hsl(var(--secondary))
  );
}
</style>
```

### Vite Build Fails After Adding Colors

**Problem:**
Vite build fails with CSS-related errors after adding new styles.

**Solution:**
```bash
# Clear all caches and restart Vite
./vendor/bin/sail artisan cache:clear && \
./vendor/bin/sail artisan config:clear && \
./vendor/bin/sail artisan view:clear && \
./vendor/bin/sail artisan route:clear && \
./vendor/bin/sail npm run dev
```

### Colors Look Wrong in Production

**Problem:**
Colors appear correct in development but wrong in production build.

**Solution:**
1. Ensure `resources/css/app.css` is imported in your main entry point
2. Check that CSS variables are defined before components load
3. Verify production build includes all necessary CSS:

```bash
# Build for production and check output
./vendor/bin/sail npm run build
./vendor/bin/sail npm run preview
```

### IntelliSense Not Showing Semantic Tokens

**Problem:**
VS Code doesn't autocomplete `bg-primary`, `text-foreground`, etc.

**Solution:**
1. Install the official Tailwind CSS IntelliSense extension
2. Ensure `tailwind.config.js` is at the project root
3. Restart VS Code after installing extension

### Custom Colors Not Working

**Problem:**
Added new color to `app.css` but it doesn't work.

**Example:**
```css
:root {
  --my-custom-color: 200 50% 50%;
}
```

```vue
<template>
  <!-- ❌ This won't work -->
  <div class="bg-my-custom-color">
</template>
```

**Solution:**
Tailwind v4 semantic tokens must be registered in `components.json` or Tailwind config. For custom colors, use CSS variables directly:

```vue
<style scoped>
.my-element {
  background-color: hsl(var(--my-custom-color));
}
</style>
```

## Migration Guide

### Migrating from Tailwind v3 to v4

#### Step 1: Identify All Color Usage

Search your codebase for numeric color scales:

```bash
# Find all numeric color classes
grep -r "bg-gray-[0-9]" resources/js/
grep -r "text-blue-[0-9]" resources/js/
grep -r "border-slate-[0-9]" resources/js/
```

#### Step 2: Map to Semantic Tokens

| Old (v3) | New (v4) | Use Case |
|----------|----------|----------|
| `bg-white` | `bg-background` | Main backgrounds |
| `bg-gray-50` | `bg-muted` | Subtle backgrounds |
| `bg-gray-100` | `bg-card` | Card backgrounds |
| `bg-blue-600` | `bg-primary` | Primary actions |
| `bg-gray-200` | `bg-secondary` | Secondary actions |
| `bg-red-600` | `bg-destructive` | Destructive actions |
| `text-gray-900` | `text-foreground` | Primary text |
| `text-gray-600` | `text-muted-foreground` | Secondary text |
| `border-gray-200` | `border-border` | Borders |

#### Step 3: Update Templates

```vue
<!-- Before (Tailwind v3) -->
<template>
  <div class="bg-white text-gray-900">
    <div class="bg-gray-100 border-gray-200">
      <button class="bg-blue-600 text-white hover:bg-blue-700">
        Click me
      </button>
    </div>
  </div>
</template>

<!-- After (Tailwind v4) -->
<template>
  <div class="bg-background text-foreground">
    <div class="bg-card border-border">
      <button class="bg-primary text-primary-foreground hover:bg-primary/90">
        Click me
      </button>
    </div>
  </div>
</template>
```

#### Step 4: Update Scoped Styles

```vue
<!-- Before (Tailwind v3) -->
<style scoped>
.custom-class {
  @apply bg-gray-100 text-gray-900 border-gray-200;
}
</style>

<!-- After (Tailwind v4) -->
<style scoped>
.custom-class {
  background-color: hsl(var(--muted));
  color: hsl(var(--foreground));
  border: 1px solid hsl(var(--border));
}
</style>
```

#### Step 5: Test Both Themes

After migration, test thoroughly in both light and dark modes:

```bash
# Run development server
./vendor/bin/sail npm run dev

# Open browser and toggle dark mode
# Check all pages for color issues
```

### Quick Reference Card

```plaintext
┌─────────────────────────────────────────────────────────────┐
│ TAILWIND V4 QUICK REFERENCE                                 │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ ✅ IN TEMPLATES:                                            │
│    <div class="bg-primary text-foreground">                 │
│                                                             │
│ ✅ IN SCOPED STYLES:                                        │
│    .class {                                                 │
│      background-color: hsl(var(--primary));                 │
│    }                                                        │
│                                                             │
│ ❌ DON'T USE:                                                │
│    - Numeric scales (bg-gray-300, text-blue-600)            │
│    - @apply with semantic tokens in scoped styles           │
│    - Semantic tokens in gradients                           │
│                                                             │
│ 🔄 AFTER CSS CHANGES:                                        │
│    ./vendor/bin/sail artisan cache:clear &&                │
│    ./vendor/bin/sail npm run dev                            │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

## Additional Resources

- **Tailwind v4 Documentation**: https://tailwindcss.com/docs
- **shadcn-vue Documentation**: https://www.shadcn-vue.com/
- **Project Configuration**: `components.json`, `tailwind.config.js`
- **Color Definitions**: `resources/css/app.css`
- **Example Components**: `resources/js/Components/`

## Conclusion

The Tailwind v4 semantic token system provides a robust, theme-aware styling approach. By following these guidelines and using the correct patterns, you'll create maintainable, accessible, and theme-consistent interfaces.

**Key Takeaways:**

1. Use semantic classes in templates
2. Use CSS variables in scoped styles
3. Avoid `@apply` with semantic tokens in scoped styles
4. Test in both light and dark modes
5. Clear caches after CSS changes

For questions or issues not covered here, check the Troubleshooting section or refer to the official Tailwind CSS v4 documentation.
