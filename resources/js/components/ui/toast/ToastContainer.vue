<script setup lang="ts">
import { useToast } from '@/composables/useToast';
import { X, CheckCircle2, XCircle, AlertTriangle, Info } from 'lucide-vue-next';

const { toasts, removeToast } = useToast();

const getVariantClass = (variant: string) => {
    const baseClasses = 'pointer-events-auto relative flex w-full items-start gap-3 overflow-hidden rounded-xl border px-4 py-4 shadow-2xl backdrop-blur-sm transition-all duration-300 hover:scale-[1.02]';

    const variantClasses = {
        default: 'border-gray-200 bg-white/95 dark:border-gray-700 dark:bg-gray-800/95',
        success: 'border-green-300 bg-gradient-to-r from-green-50 to-emerald-50 dark:border-green-700 dark:from-green-950/95 dark:to-emerald-950/95',
        error: 'border-red-300 bg-gradient-to-r from-red-50 to-rose-50 dark:border-red-700 dark:from-red-950/95 dark:to-rose-950/95',
        warning: 'border-yellow-300 bg-gradient-to-r from-yellow-50 to-amber-50 dark:border-yellow-700 dark:from-yellow-950/95 dark:to-amber-950/95',
        info: 'border-blue-300 bg-gradient-to-r from-blue-50 to-cyan-50 dark:border-blue-700 dark:from-blue-950/95 dark:to-cyan-950/95',
    };

    return `${baseClasses} ${variantClasses[variant as keyof typeof variantClasses]}`;
};

const getIconComponent = (variant: string) => {
    const icons = {
        success: CheckCircle2,
        error: XCircle,
        warning: AlertTriangle,
        info: Info,
    };

    return icons[variant as keyof typeof icons];
};

const getIconClass = (variant: string) => {
    const classes = {
        success: 'text-green-600 dark:text-green-400 animate-bounce-subtle',
        error: 'text-red-600 dark:text-red-400 animate-shake',
        warning: 'text-yellow-600 dark:text-yellow-400 animate-pulse',
        info: 'text-blue-600 dark:text-blue-400 animate-bounce-subtle',
        default: 'text-gray-600 dark:text-gray-400',
    };

    return classes[variant as keyof typeof classes];
};
</script>

<template>
    <div class="fixed top-4 right-4 z-[100] flex max-h-screen w-full flex-col gap-3 sm:max-w-md md:max-w-lg pointer-events-none">
        <TransitionGroup
            enter-active-class="transition-all duration-500 ease-out"
            enter-from-class="translate-x-full opacity-0 scale-95"
            enter-to-class="translate-x-0 opacity-100 scale-100"
            leave-active-class="transition-all duration-300 ease-in"
            leave-from-class="translate-x-0 opacity-100 scale-100"
            leave-to-class="translate-x-full opacity-0 scale-95"
        >
            <div
                v-for="toast in toasts"
                :key="toast.id"
                :class="getVariantClass(toast.variant)"
            >
                <component
                    :is="getIconComponent(toast.variant)"
                    v-if="toast.variant !== 'default'"
                    :class="['h-5 w-5 flex-shrink-0', getIconClass(toast.variant)]"
                />

                <div class="flex-1 space-y-1">
                    <div class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">
                        {{ toast.title }}
                    </div>

                    <div
                        v-if="toast.description"
                        class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed"
                    >
                        {{ toast.description }}
                    </div>
                </div>

                <button
                    @click="removeToast(toast.id)"
                    class="flex-shrink-0 rounded-lg p-1.5 text-gray-500 transition-all hover:bg-white/50 hover:text-gray-700 hover:rotate-90 dark:text-gray-400 dark:hover:bg-gray-700/50 dark:hover:text-gray-200"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>

<style scoped>
@keyframes bounce-subtle {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-4px);
    }
}

@keyframes shake {
    0%, 100% {
        transform: translateX(0);
    }
    25% {
        transform: translateX(-4px);
    }
    75% {
        transform: translateX(4px);
    }
}

.animate-bounce-subtle {
    animation: bounce-subtle 1s ease-in-out;
}

.animate-shake {
    animation: shake 0.5s ease-in-out;
}
</style>
