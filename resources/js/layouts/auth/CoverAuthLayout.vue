<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="relative min-h-screen overflow-x-hidden font-nunito text-sm font-normal antialiased text-black dark:text-white-dark">
        <!-- Background gradient -->
        <div class="absolute inset-0">
            <div class="h-full w-full bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 dark:from-gray-900 dark:via-indigo-900 dark:to-purple-900"></div>
        </div>

        <!-- Main container -->
        <div class="relative flex min-h-screen items-center justify-center bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMSIgZmlsbD0iIzAwMDAwMCIgZmlsbC1vcGFjaXR5PSIwLjA1Ii8+Cjwvc3ZnPgo=')] bg-cover bg-center bg-no-repeat px-6 py-10">

            <!-- Decorative elements -->
            <div class="absolute left-0 top-1/2 h-full max-h-[600px] w-32 -translate-y-1/2 bg-gradient-to-r from-indigo-500/10 to-transparent"></div>
            <div class="absolute right-0 top-0 h-[200px] w-[200px] bg-gradient-to-l from-purple-500/10 to-transparent"></div>
            <div class="absolute bottom-0 right-[28%] h-16 w-16 bg-gradient-to-t from-blue-500/10 to-transparent"></div>

            <!-- Main auth container -->
            <div class="relative flex w-full max-w-[1200px] flex-col justify-between overflow-hidden rounded-xl bg-white/80 backdrop-blur-lg dark:bg-black/50 lg:min-h-[600px] lg:flex-row lg:gap-0 shadow-2xl">

                <!-- Left panel - Branding -->
                <div class="relative hidden w-full items-center justify-center bg-gradient-to-br from-[#ef1262] to-[#4361ee] p-8 lg:flex lg:max-w-[500px]">
                    <div class="absolute inset-y-0 w-8 from-primary/10 via-transparent to-transparent ltr:-right-10 ltr:bg-gradient-to-r rtl:-left-10 rtl:bg-gradient-to-l xl:w-16 ltr:xl:-right-20 rtl:xl:-left-20"></div>
                    <div class="text-center text-white">
                        <div class="mb-8 flex justify-center">
                            <AppLogoIcon class="h-16 w-16 fill-current text-white" />
                        </div>
                        <h1 class="mb-4 text-3xl font-bold">SSL Monitor</h1>
                        <p class="text-lg opacity-90">Professional SSL certificate and uptime monitoring platform</p>

                        <!-- Decorative illustration -->
                        <div class="mt-12 hidden lg:block">
                            <div class="mx-auto h-48 w-48 rounded-full bg-white/10 backdrop-blur-sm flex items-center justify-center">
                                <svg class="h-24 w-24 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right panel - Form -->
                <div class="relative flex w-full flex-col items-center justify-center gap-6 px-6 pb-16 pt-8 lg:max-w-[700px]">

                    <!-- Mobile logo -->
                    <div class="flex w-full max-w-[440px] items-center gap-2 lg:hidden">
                        <div class="flex items-center gap-3">
                            <AppLogoIcon class="h-8 w-8 fill-current text-primary" />
                            <span class="text-xl font-bold text-primary">SSL Monitor</span>
                        </div>
                    </div>

                    <!-- Form content -->
                    <div class="w-full max-w-[440px] lg:mt-8">
                        <div class="mb-10">
                            <h1 class="text-3xl font-extrabold uppercase !leading-snug text-primary md:text-4xl">{{ title || 'Sign in' }}</h1>
                            <p class="text-base font-medium leading-normal text-slate-600 dark:text-slate-400">{{ description || 'Enter your email and password to login' }}</p>
                        </div>

                        <!-- Form slot -->
                        <slot />

                        <!-- Footer -->
                        <div class="mt-8 text-center text-sm text-slate-500 dark:text-slate-400">
                            © {{ new Date().getFullYear() }} SSL Monitor. All Rights Reserved.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Custom form styling inspired by design template */
:deep(.form-input) {
    width: 100%;
    border: 1px solid #cbd5e1;
    background-color: white;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    border-radius: 0.5rem;
    outline: none;
    transition: all 0.2s;
}

:deep(.form-input::placeholder) {
    color: #94a3b8;
}

:deep(.form-input:focus) {
    border-color: #4361ee;
    box-shadow: 0 0 0 2px rgba(67, 97, 238, 0.2);
}

:deep(.form-input.ps-10) {
    padding-left: 3rem;
}

:deep(.btn-gradient) {
    background: linear-gradient(to right, #ef1262, #4361ee);
    color: white;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    transition: all 0.2s;
}

:deep(.btn-gradient:hover) {
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

/* Dark mode */
:deep(.dark .form-input) {
    border-color: #475569;
    background-color: #1e293b;
    color: white;
}

:deep(.dark .form-input::placeholder) {
    color: #64748b;
}
</style>