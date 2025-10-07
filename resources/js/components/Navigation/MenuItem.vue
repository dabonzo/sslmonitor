<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { ChevronDown } from 'lucide-vue-next'
import type { MenuItem } from '@/config/navigation'

interface Props {
  item: MenuItem
  isActive?: boolean
  isDropdownOpen?: boolean
  variant?: 'sidebar' | 'horizontal'
}

const props = withDefaults(defineProps<Props>(), {
  isActive: false,
  isDropdownOpen: false,
  variant: 'sidebar'
})

const emit = defineEmits<{
  toggle: []
}>()

function handleClick() {
  if (props.item.disabled) {
    return
  }
  emit('toggle')
}
</script>

<template>
  <!-- Direct link items -->
  <Link
    v-if="item.href"
    :href="item.disabled ? '#' : item.href"
    class="nav-link group w-full"
    :class="{
      'active': !item.disabled && isActive,
      'nav-link-disabled': item.disabled,
      'horizontal': variant === 'horizontal',
      'sidebar': variant === 'sidebar'
    }"
    @click.prevent="item.disabled ? null : null"
  >
    <div class="flex items-center">
      <component :is="item.icon" class="shrink-0 group-hover:!text-primary" />
      <div :class="variant === 'sidebar' ? 'ltr:pl-3 rtl:pr-3' : 'ltr:pl-2 rtl:pr-2'">
        <span class="text-sidebar-foreground group-hover:text-sidebar-primary">
          {{ item.title }}
        </span>
        <div v-if="item.description && variant === 'sidebar'" class="text-xs text-sidebar-muted">
          {{ item.description }}
        </div>
      </div>
    </div>
  </Link>

  <!-- Dropdown items -->
  <button
    v-else
    type="button"
    class="nav-link group w-full"
    :class="{
      'active': !item.disabled && isDropdownOpen,
      'nav-link-disabled': item.disabled,
      'horizontal': variant === 'horizontal',
      'sidebar': variant === 'sidebar'
    }"
    :disabled="item.disabled"
    @click="handleClick"
  >
    <div class="flex items-center">
      <component :is="item.icon" class="shrink-0 group-hover:!text-primary" />
      <span
        class="text-sidebar-foreground group-hover:text-sidebar-primary"
        :class="variant === 'sidebar' ? 'ltr:pl-3 rtl:pr-3' : 'ltr:pl-2 rtl:pr-2'"
      >
        {{ item.title }}
      </span>
    </div>

    <div
      class="rtl:rotate-180 transition-transform duration-200"
      :class="{ '!rotate-90': !item.disabled && isDropdownOpen && variant === 'sidebar', 'rotate-180': !item.disabled && isDropdownOpen && variant === 'horizontal' }"
    >
      <ChevronDown :class="variant === 'horizontal' ? 'h-3 w-3' : 'h-4 w-4'" />
    </div>
  </button>
</template>

<style scoped>
/* Navigation styles */
.nav-link {
  display: flex;
  cursor: pointer;
  align-items: center;
  justify-content: space-between;
  border-radius: 0.375rem;
  transition: all 0.2s ease;
}

/* Sidebar variant */
.nav-link.sidebar {
  padding: 0.5rem;
}

/* Horizontal variant */
.nav-link.horizontal {
  padding: 0.625rem 1rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: rgb(55 65 81);
}

.dark .nav-link.horizontal {
  color: rgb(209 213 219);
}

.nav-link.sidebar:hover {
  background-color: rgb(248 250 252);
  color: rgb(67 97 238);
}

.dark .nav-link.sidebar:hover {
  background-color: rgb(24 31 50);
  color: rgb(67 97 238);
}

.nav-link.horizontal:hover {
  color: rgb(37 99 235);
  background-color: rgba(255, 255, 255, 0.6);
}

.dark .nav-link.horizontal:hover {
  color: rgb(96 165 250);
  background-color: rgba(255, 255, 255, 0.1);
}

.nav-link.active.sidebar {
  background-color: rgb(243 244 246);
  color: rgb(67 97 238);
}

.dark .nav-link.active.sidebar {
  background-color: rgb(24 31 50);
  color: rgb(67 97 238);
}

/* Disabled state */
.nav-link-disabled {
  opacity: 0.5;
  cursor: not-allowed;
  pointer-events: none;
}

.nav-link-disabled:hover {
  background-color: transparent !important;
  color: inherit !important;
}
</style>
