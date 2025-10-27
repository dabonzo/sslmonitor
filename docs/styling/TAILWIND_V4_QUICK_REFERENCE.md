# Tailwind CSS v4 Quick Reference

A one-page reference for the SSL Monitor v4 Tailwind v4 semantic color system.

## The Golden Rules

### ✅ DO THIS

```vue
<!-- In templates: Use semantic classes -->
<template>
  <div class="bg-background text-foreground">
    <button class="bg-primary text-primary-foreground hover:bg-primary/90">
      Click me
    </button>
  </div>
</template>

<!-- In scoped styles: Use CSS variables -->
<style scoped>
.custom-class {
  background-color: hsl(var(--primary));
  color: hsl(var(--primary-foreground));
}
</style>
```

### ❌ DON'T DO THIS

```vue
<!-- ❌ NO numeric scales -->
<div class="bg-gray-300 text-blue-600">

<!-- ❌ NO @apply with semantic tokens in scoped styles -->
<style scoped>
.custom-class {
  @apply bg-muted text-primary;
}
</style>

<!-- ❌ NO semantic tokens in gradients -->
<div class="bg-gradient-to-r from-primary to-secondary">
```

## Semantic Token Reference

### Layout & Structure

| Token | Usage | Example |
|-------|-------|---------|
| `background` | Page background | `bg-background` |
| `foreground` | Primary text | `text-foreground` |
| `card` | Card background | `bg-card` |
| `card-foreground` | Card text | `text-card-foreground` |
| `border` | Borders | `border-border` |

### Interactive Elements

| Token | Usage | Example |
|-------|-------|---------|
| `primary` | Primary buttons | `bg-primary` |
| `primary-foreground` | Primary text | `text-primary-foreground` |
| `secondary` | Secondary buttons | `bg-secondary` |
| `secondary-foreground` | Secondary text | `text-secondary-foreground` |

### Status & Feedback

| Token | Usage | Example |
|-------|-------|---------|
| `destructive` | Delete/error | `bg-destructive` |
| `destructive-foreground` | Error text | `text-destructive-foreground` |
| `muted` | Subtle bg | `bg-muted` |
| `muted-foreground` | Secondary text | `text-muted-foreground` |
| `accent` | Highlights | `bg-accent` |
| `accent-foreground` | Accent text | `text-accent-foreground` |

## Common Patterns

### Buttons

```vue
<!-- Primary -->
<button class="bg-primary text-primary-foreground hover:bg-primary/90">
  Primary
</button>

<!-- Secondary -->
<button class="bg-secondary text-secondary-foreground hover:bg-secondary/80">
  Secondary
</button>

<!-- Destructive -->
<button class="bg-destructive text-destructive-foreground hover:bg-destructive/90">
  Delete
</button>

<!-- Ghost -->
<button class="hover:bg-accent hover:text-accent-foreground">
  Ghost
</button>
```

### Cards

```vue
<!-- Simple card -->
<div class="bg-card text-card-foreground border border-border rounded-lg p-6">
  <h2 class="text-foreground font-semibold">Title</h2>
  <p class="text-muted-foreground">Description</p>
</div>
```

### Custom Styles (Scoped)

```vue
<style scoped>
.custom-element {
  background-color: hsl(var(--card));
  color: hsl(var(--card-foreground));
  border: 1px solid hsl(var(--border));
}

/* Hover with opacity */
.custom-element:hover {
  background-color: hsl(var(--card) / 0.95);
}

/* Focus rings */
.custom-element:focus {
  outline: 2px solid hsl(var(--ring));
  outline-offset: 2px;
}
</style>
```

### Gradients

```vue
<!-- Brand gradient: Use hex values -->
<div class="bg-gradient-to-r from-[#ef1262] to-[#4361ee]">
  Brand gradient
</div>

<!-- Or use solid color instead -->
<div class="bg-primary">
  Solid color
</div>

<!-- Or CSS gradient with variables -->
<template>
  <div class="custom-gradient">Custom gradient</div>
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

### Badges

```vue
<!-- Success -->
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-accent/10 text-accent border border-accent/20">
  Active
</span>

<!-- Error -->
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-destructive/10 text-destructive border border-destructive/20">
  Error
</span>

<!-- Muted -->
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-muted text-muted-foreground border border-border">
  Inactive
</span>
```

### Alerts

```vue
<!-- Info -->
<div class="bg-muted text-foreground border border-border rounded-lg p-4">
  Info message
</div>

<!-- Warning (custom styling) -->
<template>
  <div class="alert-warning">Warning message</div>
</template>

<style scoped>
.alert-warning {
  background-color: hsl(38 92% 50% / 0.1);
  color: hsl(38 92% 50%);
  border: 1px solid hsl(38 92% 50% / 0.3);
  border-radius: 0.5rem;
  padding: 1rem;
}
</style>
```

## Opacity Modifiers

```vue
<!-- Template opacity -->
<div class="bg-primary/90">90% opacity</div>
<div class="bg-muted/50">50% opacity</div>

<!-- CSS variable opacity -->
<style scoped>
.semi-transparent {
  background-color: hsl(var(--card) / 0.8);
}
</style>
```

## Troubleshooting

### Build Error: "Cannot resolve semantic token"

**Problem:**
```vue
<style scoped>
.my-class {
  @apply bg-muted;  /* ❌ ERROR */
}
</style>
```

**Solution:**
```vue
<style scoped>
.my-class {
  background-color: hsl(var(--muted));  /* ✅ WORKS */
}
</style>
```

### Colors Don't Change with Theme

**Check:**
1. Using semantic tokens? (not `bg-gray-300`)
2. Dark class on `<html>` element?
3. Colors defined in `resources/css/app.css`?

### Cache Issues After Changes

```bash
# Clear everything
./vendor/bin/sail artisan cache:clear && \
./vendor/bin/sail artisan config:clear && \
./vendor/bin/sail artisan view:clear && \
./vendor/bin/sail npm run dev
```

## Migration Cheat Sheet

| Tailwind v3 (Old) | Tailwind v4 (New) |
|-------------------|-------------------|
| `bg-white` | `bg-background` |
| `bg-gray-50` | `bg-muted` |
| `bg-gray-100` | `bg-card` |
| `bg-blue-600` | `bg-primary` |
| `bg-gray-200` | `bg-secondary` |
| `bg-red-600` | `bg-destructive` |
| `text-gray-900` | `text-foreground` |
| `text-gray-600` | `text-muted-foreground` |
| `border-gray-200` | `border-border` |

## Where to Find More

- **Full Guide**: `docs/TAILWIND_V4_STYLING_GUIDE.md`
- **Color Definitions**: `resources/css/app.css`
- **Example Components**: `resources/js/Components/`
- **Project Overview**: `CLAUDE.md`

## Quick Memory Aid

```
┌─────────────────────────────────────────┐
│ TAILWIND V4 RULES                       │
├─────────────────────────────────────────┤
│ ✅ Templates: class="bg-primary"        │
│ ✅ Styles: hsl(var(--primary))          │
│ ❌ Don't: bg-gray-300, @apply bg-muted  │
│ 🔄 After changes: cache:clear + npm dev │
└─────────────────────────────────────────┘
```
