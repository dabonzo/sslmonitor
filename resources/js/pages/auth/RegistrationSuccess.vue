<script setup lang="ts">
import TextLink from '@/components/TextLink.vue';
import CoverAuthLayout from '@/layouts/auth/CoverAuthLayout.vue';
import { login } from '@/routes';
import { Head } from '@inertiajs/vue3';
import { Mail } from 'lucide-vue-next';

defineProps<{
    email: string;
}>();
</script>

<template>
    <CoverAuthLayout title="Check your email" description="We've sent you a verification link">
        <Head title="Registration Successful" />

        <div class="space-y-6 text-center">
            <!-- Success icon -->
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                <Mail class="h-8 w-8 text-green-600 dark:text-green-400" />
            </div>

            <!-- Message -->
            <div class="space-y-3">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                    Account created successfully!
                </h3>
                <p class="text-sm text-foreground dark:text-muted-foreground">
                    We've sent a verification email to <strong class="text-slate-900 dark:text-white">{{ email }}</strong>
                </p>
                <p class="text-sm text-foreground dark:text-muted-foreground">
                    Please check your inbox and click the verification link to activate your account.
                </p>
            </div>

            <!-- Instructions -->
            <div class="rounded-lg bg-blue-50 p-4 text-sm text-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                <p class="font-medium">Didn't receive the email?</p>
                <p class="mt-1">Check your spam folder or sign in to resend the verification link.</p>
            </div>

            <!-- Sign in link -->
            <div class="pt-4">
                <TextLink
                    :href="login()"
                    class="font-semibold uppercase text-primary underline transition hover:text-black dark:hover:text-white"
                >
                    Go to Sign In
                </TextLink>
            </div>
        </div>
    </CoverAuthLayout>
</template>
