# SSL Monitor v4 - Styling Guide

This guide establishes consistent styling patterns for the SSL Monitor v4 application to ensure visual cohesion across all pages and components.

## Table of Contents
- [Core Principles](#core-principles)
- [Layout Structure](#layout-structure)
- [Semantic Color System](#semantic-color-system)
- [Typography](#typography)
- [Component Patterns](#component-patterns)
- [Page Layout Standards](#page-layout-standards)
- [Utility Classes](#utility-classes)
- [Dark Mode Consistency](#dark-mode-consistency)
- [Debug Page Styling](#debug-page-styling)

## Core Principles

### 1. Semantic First
- Use semantic color classes (`text-foreground`, `bg-card`) instead of hardcoded colors
- Leverage the semantic utility classes defined in `resources/css/app.css`
- Avoid manual dark mode prefixes (`dark:text-white`)

### 2. Consistency Over Customization
- Follow established patterns rather than creating new ones
- Use existing components and utility classes
- Maintain visual hierarchy throughout the application

### 3. Accessibility First
- Ensure proper contrast ratios in both light and dark modes
- Use semantic HTML elements
- Provide clear visual feedback for interactive elements

## Layout Structure

### Standard Page Layout Pattern

All pages should follow this basic structure:

```vue
<template>
  <Head title="Page Title" />
  <DashboardLayout title="Page Title">
    <div class="page-class space-y-6">
      <!-- Page Header -->
      <div class="flex items-center justify-between mb-4">
        <div>
          <h1 class="text-2xl font-semibold text-foreground">Page Title</h1>
          <p class="text-muted-foreground">Page description with optional badge</p>
        </div>

        <!-- Optional: Quick Actions -->
        <div class="flex items-center space-x-2">
          <!-- Action buttons or links -->
        </div>
      </div>

      <!-- Main Content -->
      <div class="space-y-6">
        <!-- Page content sections -->
      </div>
    </div>
  </DashboardLayout>
</template>
```

### Layout Container Classes

- `space-y-6` - Main vertical spacing between sections
- `flex items-center justify-between` - Header layout with title and actions
- `flex items-center space-x-2` - Horizontal button/link groups

## Semantic Color System

### Core Semantic Classes

| Class | Purpose | Example Usage |
|-------|---------|---------------|
| `text-foreground` | Primary text color | Headings, body text |
| `text-muted-foreground` | Secondary text color | Descriptions, metadata |
| `bg-card` | Card backgrounds | Content panels |
| `text-card-foreground` | Card text color | Text on cards |
| `bg-background` | Main background | Page background |
| `border-border` | Border color | Dividers, card borders |
| `bg-primary` | Primary background | Selected states |
| `text-primary-foreground` | Primary text | Text on primary bg |
| `hover:bg-accent` | Hover states | Interactive elements |
| `hover:text-accent-foreground` | Hover text | Interactive text |

### Status Badge Classes

```css
.status-badge-info    /* Blue informational badges */
.status-badge-success  /* Green success badges */
.status-badge-warning  /* Orange warning badges */
.status-badge-destructive /* Red error badges */
```

### Glass Morphism Effects

```css
.glass-card        /* Light glass effect for cards */
.glass-card-strong /* Stronger glass effect with more blur */
```

## Typography

### Headings
- **Page Titles**: `text-2xl font-semibold text-foreground`
- **Section Headers**: `text-xl font-bold text-foreground`
- **Card Titles**: `text-lg font-semibold text-foreground`
- **Subheadings**: `text-base font-medium text-foreground`

### Body Text
- **Primary**: `text-foreground`
- **Secondary/Descriptive**: `text-muted-foreground`
- **Small Text**: `text-sm text-muted-foreground`

### Typography Hierarchy

```html
<!-- Page Title -->
<h1 class="text-2xl font-semibold text-foreground">Page Title</h1>

<!-- Section Header -->
<h2 class="text-xl font-bold text-foreground mb-4">Section Title</h2>

<!-- Card Title -->
<h3 class="text-lg font-semibold text-foreground">Card Title</h3>

<!-- Description -->
<p class="text-muted-foreground">Descriptive text</p>

<!-- Small/Meta -->
<p class="text-sm text-muted-foreground">Meta information</p>
```

## Component Patterns

### Standard Card Pattern

```vue
<div class="glass-card-strong rounded-2xl p-6">
  <div class="flex items-center justify-between">
    <div>
      <p class="text-sm font-medium text-muted-foreground">Label</p>
      <p class="text-2xl font-bold text-foreground">Value</p>
    </div>
    <div class="status-badge-info p-3">
      <Icon class="h-6 w-6" />
    </div>
  </div>
</div>
```

### Interactive Button Pattern

```vue
<!-- Primary Action -->
<button class="flex items-center space-x-2 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white px-6 py-3 rounded-xl transition-all duration-300 shadow-lg disabled:opacity-50 disabled:cursor-not-allowed">
  <Icon class="h-5 w-5" />
  <span>Action</span>
</button>

<!-- Secondary Action -->
<button class="flex items-center space-x-2 button-soft px-4 py-2 rounded-lg transition-all duration-300">
  <Icon class="h-4 w-4" />
  <span>Secondary Action</span>
</button>

<!-- Ghost Action -->
<button class="flex items-center space-x-2 button-ghost px-4 py-2 rounded-lg transition-all duration-300">
  <Icon class="h-4 w-4" />
  <span>Ghost Action</span>
</button>
```

### Form Input Pattern

```vue
<input class="input-styled" placeholder="Enter value..." />
```

### Selection Pattern

```vue
<div :class="[
  'p-4 rounded-xl border-2 cursor-pointer transition-all duration-300',
  isSelected
    ? 'border-primary bg-primary/10 selected-state'
    : 'border-border hover:border-border hover-lift'
]">
  <!-- Content -->
</div>
```

## Page Layout Standards

### Standard Page Sections

1. **Page Header**
   - Title (`text-2xl font-semibold text-foreground`)
   - Description (`text-muted-foreground`)
   - Optional action buttons/links on the right

2. **Stats Cards** (if applicable)
   - 3-column grid on desktop (`grid-cols-1 md:grid-cols-3`)
   - Use glass-card-strong styling
   - Include status badges with icons

3. **Main Content Sections**
   - Each section in a glass-card-strong container
   - Clear section headers
   - Consistent spacing (space-y-6)

4. **Empty States**
   - Centered content with status-badge-destructive icon
   - Clear messaging with text-foreground and text-muted-foreground

### Page-Specific Patterns

#### Dashboard Pages
- Use overview stats and quick actions
- Focus on data visualization and navigation

#### Management Pages (e.g., SSL Websites)
- Include search and filter controls
- Use data tables for listing items
- Add primary action buttons (e.g., "Add Website")

#### Configuration Pages (e.g., Settings, Alerts)
- Use navigation cards for sub-sections
- Include descriptive text for each section
- Focus on form controls and settings

#### Debug Pages
- Include DEBUG badge in page description
- Maintain standard layout patterns
- Focus on testing and development tools

## Utility Classes

### Interactive States

```css
.hover-lift          /* Adds subtle lift effect on hover */
.button-ghost        /* Ghost button styling */
.button-soft         /* Soft background button styling */
.selected-state      /* Selected item styling */
```

### Animation Classes

```css
.transition-all duration-300  /* Standard transition timing */
.animate-spin       /* Loading spinner animation */
```

### Spacing Classes

- `space-y-6` - Standard section spacing
- `space-x-2` - Button/icon spacing
- `mb-4` - Standard margin bottom for headers

## Dark Mode Consistency

### Rules for Dark Mode

1. **Always use semantic classes** - They automatically adapt to dark mode
2. **Never use manual dark mode prefixes** - Avoid `dark:text-white`, `dark:bg-gray-800`
3. **Test in both themes** - Ensure all components work in light and dark modes

### Common Mistakes to Avoid

❌ **Wrong:**
```css
class="bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
```

✅ **Correct:**
```css
class="bg-card text-foreground"
```

## Debug Page Styling

### Debug Page Standards

Debug pages should follow the same styling standards as production pages with these specific requirements:

1. **Page Title Pattern**
   ```html
   <h1 class="text-2xl font-semibold text-foreground">Debug Feature Name</h1>
   <p class="text-muted-foreground">DEBUG • Feature description</p>
   ```

2. **No Custom Icons in Headers**
   - Avoid decorative icons (like Bug icons) in page headers
   - Use standard layout patterns

3. **Consistent Navigation**
   - Include navigation links to related debug tools
   - Use glass-card styling for navigation elements

4. **Status Indicators**
   - Use status-badge-* classes for debug states
   - Maintain consistent color coding

### Debug Page Examples

#### Alert Testing Page
- Standard page header with DEBUG badge
- Stats cards showing monitoring status
- Interactive selection cards for websites
- Glass-card containers for all sections

#### SSL Overrides Page
- Clean page header without decorative icons
- Standard stats display
- Form controls using input-styled class
- Table-based layout for website listings

## Code Examples

### Complete Page Template

```vue
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

        <div class="flex items-center space-x-2">
          <Link href="/related-page" class="glass-card hover:bg-card px-4 py-2 rounded-xl button-ghost">
            <Icon class="h-4 w-4 text-primary" />
            <span class="text-sm font-medium text-primary">Related Tool</span>
          </Link>
        </div>
      </div>

      <!-- Stats Section -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="glass-card-strong rounded-2xl p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-muted-foreground">Metric Name</p>
              <p class="text-2xl font-bold text-foreground">{{ value }}</p>
            </div>
            <div class="status-badge-info p-3">
              <Icon class="h-6 w-6" />
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <div class="glass-card-strong rounded-2xl p-6">
        <h2 class="text-xl font-bold text-foreground mb-4">Section Title</h2>
        <!-- Section content -->
      </div>
    </div>
  </DashboardLayout>
</template>
```

## Best Practices

### Do's
✅ Use semantic color classes (`text-foreground`, `bg-card`)
✅ Follow the standard page layout pattern
✅ Use consistent spacing (`space-y-6`, `space-x-2`)
✅ Include proper hover states and transitions
✅ Test in both light and dark modes
✅ Use glass-card-* classes for modern styling
✅ Include descriptive text with `text-muted-foreground`

### Don'ts
❌ Use hardcoded colors (`text-gray-900`, `bg-white`)
❌ Use manual dark mode prefixes (`dark:text-white`)
❌ Create custom layout patterns
❌ Use decorative icons in page headers
❌ Skip accessibility considerations
❌ Mix different styling approaches

## Review Checklist

Before submitting a page or component, verify:

- [ ] Uses semantic color classes only
- [ ] Follows standard page layout pattern
- [ ] Works in both light and dark modes
- [ ] Has proper hover states and transitions
- [ ] Includes descriptive text for accessibility
- [ ] Uses consistent spacing and typography
- [ ] Follows established component patterns
- [ ] Maintains visual hierarchy
- [ ] Has no hardcoded colors or manual dark mode classes

---

This guide should be updated as new patterns emerge or existing patterns are refined. Always prioritize consistency and user experience in design decisions.