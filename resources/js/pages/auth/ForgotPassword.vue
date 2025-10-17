<script setup lang="ts">
import PasswordResetLinkController from '@/actions/App/Http/Controllers/Auth/PasswordResetLinkController';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import CoverAuthLayout from '@/layouts/auth/CoverAuthLayout.vue';
import { login } from '@/routes';
import { Form, Head } from '@inertiajs/vue3';
import { LoaderCircle, Mail } from 'lucide-vue-next';

defineProps<{
    status?: string;
}>();
</script>

<template>
    <CoverAuthLayout title="Forgot Password" description="Enter your email to receive a password reset link">
        <Head title="Forgot password" />

        <div v-if="status" class="mb-6 rounded-lg bg-green-100 p-3 text-center text-sm font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">
            {{ status }}
        </div>

        <Form v-bind="PasswordResetLinkController.store.form()" v-slot="{ errors, processing }" class="space-y-5 dark:text-white">
            <!-- Email field -->
            <div>
                <Label for="email" class="mb-2 block text-sm font-medium text-foreground dark:text-muted-foreground">Email</Label>
                <div class="relative text-muted-foreground dark:text-muted-foreground">
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        autocomplete="email"
                        placeholder="Enter Email"
                        class="form-input ps-10 placeholder:text-muted-foreground dark:placeholder:text-muted-foreground"
                    />
                    <span class="absolute left-4 top-1/2 -translate-y-1/2">
                        <Mail class="h-[18px] w-[18px]" />
                    </span>
                </div>
                <InputError :message="errors.email" class="mt-1" />
            </div>

            <!-- Reset button -->
            <Button
                type="submit"
                class="btn-gradient !mt-6 w-full border-0 py-3 text-sm font-semibold uppercase tracking-wide shadow-blue-600/30"
                :disabled="processing"
                data-test="email-password-reset-link-button"
            >
                <LoaderCircle v-if="processing" class="mr-2 h-4 w-4 animate-spin" />
                Send Reset Link
            </Button>

            <!-- Back to login link -->
            <div class="text-center text-foreground dark:text-muted-foreground">
                Remember your password?
                <TextLink :href="login()" class="font-semibold uppercase text-primary underline transition hover:text-black dark:hover:text-white">
                    SIGN IN
                </TextLink>
            </div>
        </Form>
    </CoverAuthLayout>
</template>
