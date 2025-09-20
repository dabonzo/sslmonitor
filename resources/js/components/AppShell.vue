<script setup lang="ts">
import { SidebarProvider } from '@/components/ui/sidebar';
import { useThemeStore } from '@/stores/theme';

interface Props {
    variant?: 'header' | 'sidebar';
}

defineProps<Props>();

const themeStore = useThemeStore();
</script>

<template>
    <div v-if="variant === 'header'" class="flex min-h-screen w-full flex-col">
        <slot />
    </div>
    <SidebarProvider v-else :default-open="themeStore.sidebarOpen">
        <slot />
    </SidebarProvider>
</template>
