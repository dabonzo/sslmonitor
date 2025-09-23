<script setup lang="ts">
import PasswordController from '@/actions/App/Http/Controllers/Settings/PasswordController';
import InputError from '@/components/InputError.vue';
import ModernSettingsLayout from '@/layouts/ModernSettingsLayout.vue';
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Key, Lock, Save, Eye, EyeOff } from 'lucide-vue-next';

const passwordInput = ref<HTMLInputElement | null>(null);
const currentPasswordInput = ref<HTMLInputElement | null>(null);

// Password visibility toggles
const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);
</script>

<template>
    <Head title="Password Settings" />

    <ModernSettingsLayout title="Password Settings">
        <div class="space-y-8">
            <!-- Password Update Section -->
            <div class="rounded-xl bg-gradient-to-r from-gray-50 to-slate-50 dark:from-gray-900 dark:to-slate-900 p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="rounded-lg bg-gray-100 dark:bg-gray-800 p-2">
                        <Key class="h-5 w-5 text-gray-600 dark:text-gray-400" />
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Update Password</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Ensure your account uses a strong, unique password to stay secure</p>
                    </div>
                </div>

                <Form
                    v-bind="PasswordController.update.form()"
                    :options="{
                        preserveScroll: true,
                    }"
                    reset-on-success
                    :reset-on-error="['password', 'password_confirmation', 'current_password']"
                    class="space-y-6"
                    v-slot="{ errors, processing, recentlySuccessful }"
                >
                    <div class="space-y-6">
                        <!-- Current Password -->
                        <div class="space-y-2">
                            <Label for="current_password" class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                Current Password
                            </Label>
                            <div class="relative">
                                <Lock class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                <Input
                                    id="current_password"
                                    ref="currentPasswordInput"
                                    name="current_password"
                                    :type="showCurrentPassword ? 'text' : 'password'"
                                    class="pl-10 pr-10 h-11"
                                    autocomplete="current-password"
                                    placeholder="Enter your current password"
                                />
                                <button
                                    type="button"
                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                    @click="showCurrentPassword = !showCurrentPassword"
                                >
                                    <Eye v-if="showCurrentPassword" class="h-4 w-4" />
                                    <EyeOff v-else class="h-4 w-4" />
                                </button>
                            </div>
                            <InputError :message="errors.current_password" />
                        </div>

                        <!-- New Password -->
                        <div class="space-y-2">
                            <Label for="password" class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                New Password
                            </Label>
                            <div class="relative">
                                <Lock class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                <Input
                                    id="password"
                                    ref="passwordInput"
                                    name="password"
                                    :type="showNewPassword ? 'text' : 'password'"
                                    class="pl-10 pr-10 h-11"
                                    autocomplete="new-password"
                                    placeholder="Choose a strong password"
                                />
                                <button
                                    type="button"
                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                    @click="showNewPassword = !showNewPassword"
                                >
                                    <Eye v-if="showNewPassword" class="h-4 w-4" />
                                    <EyeOff v-else class="h-4 w-4" />
                                </button>
                            </div>
                            <InputError :message="errors.password" />
                        </div>

                        <!-- Confirm Password -->
                        <div class="space-y-2">
                            <Label for="password_confirmation" class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                Confirm New Password
                            </Label>
                            <div class="relative">
                                <Lock class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                <Input
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    :type="showConfirmPassword ? 'text' : 'password'"
                                    class="pl-10 pr-10 h-11"
                                    autocomplete="new-password"
                                    placeholder="Confirm your new password"
                                />
                                <button
                                    type="button"
                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                    @click="showConfirmPassword = !showConfirmPassword"
                                >
                                    <Eye v-if="showConfirmPassword" class="h-4 w-4" />
                                    <EyeOff v-else class="h-4 w-4" />
                                </button>
                            </div>
                            <InputError :message="errors.password_confirmation" />
                        </div>
                    </div>

                    <!-- Password Tips -->
                    <div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 p-4">
                        <h3 class="text-sm font-semibold text-blue-800 dark:text-blue-200 mb-2">Password Requirements</h3>
                        <ul class="text-sm text-blue-700 dark:text-blue-300 space-y-1">
                            <li>• At least 8 characters long</li>
                            <li>• Include uppercase and lowercase letters</li>
                            <li>• Include at least one number</li>
                            <li>• Include at least one special character</li>
                        </ul>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center space-x-4">
                            <Button
                                :disabled="processing"
                                data-test="update-password-button"
                                class="h-11 px-6"
                            >
                                <Save class="h-4 w-4 mr-2" />
                                {{ processing ? 'Updating...' : 'Update Password' }}
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
                                    <p class="text-sm font-medium">Password updated successfully!</p>
                                </div>
                            </Transition>
                        </div>
                    </div>
                </Form>
            </div>
        </div>
    </ModernSettingsLayout>
</template>
