<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" :class="className" v-bind="$attrs">
        <!-- SSL Monitor Logo: Shield with Certificate and Monitoring Elements -->
        <defs>
            <linearGradient id="shieldGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" style="stop-color:#4F46E5" />
                <stop offset="100%" style="stop-color:#7C3AED" />
            </linearGradient>
        </defs>

        <!-- Main Shield -->
        <path
            d="M16 2.5c-3.5 1.5-7 2.5-12 2.5v8c0 8 4.5 14.5 12 19 7.5-4.5 12-11 12-19v-8c-5 0-8.5-1-12-2.5z"
            fill="url(#shieldGradient)"
            stroke="currentColor"
            stroke-width="0.5"
        />

        <!-- Certificate Icon -->
        <rect
            x="10" y="8" width="12" height="10"
            rx="1" ry="1"
            fill="white"
            opacity="0.9"
        />

        <!-- Certificate Lines -->
        <line x1="12" y1="10.5" x2="20" y2="10.5" stroke="currentColor" stroke-width="0.8" opacity="0.7" />
        <line x1="12" y1="12.5" x2="18" y2="12.5" stroke="currentColor" stroke-width="0.8" opacity="0.7" />
        <line x1="12" y1="14.5" x2="19" y2="14.5" stroke="currentColor" stroke-width="0.8" opacity="0.7" />

        <!-- SSL Lock Symbol -->
        <rect
            x="14.5" y="12" width="3" height="2.5"
            fill="none"
            stroke="currentColor"
            stroke-width="0.8"
            rx="0.5"
        />
        <path
            d="M15.2 12v-0.8c0-0.4 0.4-0.8 0.8-0.8s0.8 0.4 0.8 0.8V12"
            fill="none"
            stroke="currentColor"
            stroke-width="0.8"
        />

        <!-- Monitoring Pulse -->
        <path
            d="M6 20 L8 20 L9 16 L11 24 L13 12 L14 20 L16 20"
            fill="none"
            stroke="white"
            stroke-width="1.5"
            opacity="0.8"
        />

        <!-- Status Indicator -->
        <circle cx="25" cy="8" r="2.5" fill="#10B981" opacity="0.9" />
        <path d="M23.8 8 L24.8 9 L26.2 7" fill="none" stroke="white" stroke-width="1.2" stroke-linecap="round" />
    </svg>
</template>
