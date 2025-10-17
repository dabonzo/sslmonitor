<script setup lang="ts">
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import { Form, Head, Link, usePage } from '@inertiajs/vue3';

import DeleteUser from '@/components/DeleteUser.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import ModernSettingsLayout from '@/layouts/ModernSettingsLayout.vue';
import { User, Mail, Save, Trash2 } from 'lucide-vue-next';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const page = usePage();
const user = page.props.auth.user;
</script>

<template>
    <Head title="Profile Settings" />

    <ModernSettingsLayout title="Profile Settings">
        <div class="space-y-8">
            <!-- Profile Information Section -->
            <div class="rounded-xl bg-muted dark:bg-card p-6 border border-border dark:border-border">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="rounded-lg bg-muted dark:bg-card p-2">
                        <User class="h-5 w-5 text-foreground dark:text-muted-foreground" />
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-foreground dark:text-foreground">Profile Information</h2>
                        <p class="text-sm text-foreground dark:text-muted-foreground">Update your personal details and contact information</p>
                    </div>
                </div>

                <Form v-bind="ProfileController.update.form()" class="space-y-6" v-slot="{ errors, processing, recentlySuccessful }">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <Label for="name" class="text-sm font-semibold text-foreground dark:text-muted-foreground">
                                Full Name
                            </Label>
                            <div class="relative">
                                <User class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <Input
                                    id="name"
                                    class="pl-10 h-11"
                                    name="name"
                                    :default-value="user.name"
                                    required
                                    autocomplete="name"
                                    placeholder="Enter your full name"
                                />
                            </div>
                            <InputError class="mt-1" :message="errors.name" />
                        </div>

                        <div class="space-y-2">
                            <Label for="email" class="text-sm font-semibold text-foreground dark:text-muted-foreground">
                                Email Address
                            </Label>
                            <div class="relative">
                                <Mail class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <Input
                                    id="email"
                                    type="email"
                                    class="pl-10 h-11"
                                    name="email"
                                    :default-value="user.email"
                                    required
                                    autocomplete="username"
                                    placeholder="Enter your email address"
                                />
                            </div>
                            <InputError class="mt-1" :message="errors.email" />
                        </div>
                    </div>

                    <!-- Email Verification Notice -->
                    <div v-if="mustVerifyEmail && !user.email_verified_at" class="rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-4">
                        <div class="flex items-start space-x-3">
                            <div class="rounded-lg bg-amber-500/10 p-2">
                                <Mail class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                            </div>
                            <div class="flex-1">
                                <h3 class="text-sm font-semibold text-amber-800 dark:text-amber-200">Email Verification Required</h3>
                                <p class="text-sm text-amber-700 dark:text-amber-300 mt-1">
                                    Your email address is unverified.
                                    <Link
                                        :href="send()"
                                        as="button"
                                        class="font-semibold underline hover:no-underline"
                                    >
                                        Click here to resend the verification email.
                                    </Link>
                                </p>
                                <div v-if="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-green-600 dark:text-green-400">
                                    ✓ A new verification link has been sent to your email address.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-between pt-4 border-t border-border dark:border-border">
                        <div class="flex items-center space-x-4">
                            <Button
                                :disabled="processing"
                                data-test="update-profile-button"
                                class="h-11 px-6"
                            >
                                <Save class="h-4 w-4 mr-2" />
                                {{ processing ? 'Saving...' : 'Save Changes' }}
                            </Button>

                            <Transition
                                enter-active-class="transition ease-in-out duration-300"
                                enter-from-class="opacity-0 scale-95"
                                enter-to-class="opacity-100 scale-100"
                                leave-active-class="transition ease-in-out duration-300"
                                leave-from-class="opacity-100 scale-100"
                                leave-to-class="opacity-0 scale-95"
                            >
                                <div v-show="recentlySuccessful" class="flex items-center space-x-2 text-green-600 dark:text-green-400">
                                    <div class="h-2 w-2 bg-green-500 rounded-full"></div>
                                    <p class="text-sm font-medium">Profile updated successfully!</p>
                                </div>
                            </Transition>
                        </div>
                    </div>
                </Form>
            </div>

            <!-- Account Deletion Section -->
            <div class="rounded-xl bg-muted dark:bg-card p-6 border border-red-200 dark:border-red-700">
                <div class="flex items-center space-x-3 mb-4">
                    <div class="rounded-lg bg-red-50 dark:bg-red-900/20 p-2">
                        <Trash2 class="h-5 w-5 text-destructive dark:text-red-400" />
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-foreground dark:text-foreground">Danger Zone</h2>
                        <p class="text-sm text-foreground dark:text-muted-foreground">Permanently delete your account and all associated data</p>
                    </div>
                </div>

                <DeleteUser />
            </div>
        </div>
    </ModernSettingsLayout>
</template>
