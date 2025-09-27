<script setup lang="ts">
import TwoFactorRecoveryCodes from '@/components/TwoFactorRecoveryCodes.vue';
import TwoFactorSetupModal from '@/components/TwoFactorSetupModal.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import ModernSettingsLayout from '@/layouts/ModernSettingsLayout.vue';
import { destroy as disable, store as enable } from '@/routes/two-factor';
import { Form, Head } from '@inertiajs/vue3';
import { Shield, ShieldBan, ShieldCheck, Smartphone, Lock } from 'lucide-vue-next';
import { onUnmounted, ref } from 'vue';

interface Props {
    twoFactorEnabled?: boolean;
    recoveryCodes?: number;
    qrCodeSvg?: string | null;
}

const props = withDefaults(defineProps<Props>(), {
    twoFactorEnabled: false,
    recoveryCodes: 0,
    qrCodeSvg: null,
});

const { hasSetupData, clearTwoFactorAuthData } = useTwoFactorAuth();
const showSetupModal = ref<boolean>(false);

onUnmounted(() => {
    clearTwoFactorAuthData();
});
</script>

<template>
    <Head title="Two-Factor Authentication" />

    <ModernSettingsLayout title="Two-Factor Authentication">
        <div class="space-y-8">
            <!-- Two-Factor Authentication Section -->
            <div class="rounded-xl bg-gradient-to-r from-gray-50 to-slate-50 dark:from-gray-900 dark:to-slate-900 p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="rounded-lg bg-gray-100 dark:bg-gray-800 p-2">
                        <Shield class="h-5 w-5 text-gray-600 dark:text-gray-400" />
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Two-Factor Authentication</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Add an extra layer of security to your account</p>
                    </div>
                </div>

                <!-- Disabled State -->
                <div v-if="!twoFactorEnabled" class="space-y-6">
                    <div class="flex items-center space-x-3">
                        <div class="rounded-lg bg-red-50 dark:bg-red-900/20 p-2">
                            <Lock class="h-5 w-5 text-red-600 dark:text-red-400" />
                        </div>
                        <div>
                            <Badge variant="destructive" class="mb-1">Disabled</Badge>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Your account is not protected by two-factor authentication</p>
                        </div>
                    </div>

                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 p-4">
                        <div class="flex items-start space-x-3">
                            <Smartphone class="h-5 w-5 text-gray-600 dark:text-gray-400 mt-0.5" />
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">How it works</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    When you enable two-factor authentication, you will be prompted for a secure pin during login.
                                    This pin can be retrieved from a TOTP-supported application on your phone.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center space-x-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <Button v-if="props.qrCodeSvg" @click="showSetupModal = true" class="h-11 px-6">
                            <ShieldCheck class="h-4 w-4 mr-2" />
                            Continue Setup
                        </Button>
                        <Form v-else v-bind="enable.form()" @success="showSetupModal = true" #default="{ processing }">
                            <Button type="submit" :disabled="processing" class="h-11 px-6">
                                <ShieldCheck class="h-4 w-4 mr-2" />
                                {{ processing ? 'Enabling...' : 'Enable 2FA' }}
                            </Button>
                        </Form>
                    </div>
                </div>

                <!-- Enabled State -->
                <div v-else class="space-y-6">
                    <div class="flex items-center space-x-3">
                        <div class="rounded-lg bg-green-50 dark:bg-green-900/20 p-2">
                            <ShieldCheck class="h-5 w-5 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <Badge variant="default" class="mb-1 bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">Enabled</Badge>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Your account is protected with two-factor authentication</p>
                        </div>
                    </div>

                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 p-4">
                        <div class="flex items-start space-x-3">
                            <ShieldCheck class="h-5 w-5 text-green-600 dark:text-green-400 mt-0.5" />
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">Protection Active</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    With two-factor authentication enabled, you will be prompted for a secure, random pin during login,
                                    which you can retrieve from the TOTP-supported application on your phone.
                                </p>
                            </div>
                        </div>
                    </div>

                    <TwoFactorRecoveryCodes />

                    <div class="flex items-center space-x-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <Form v-bind="disable.form()" #default="{ processing }">
                            <Button variant="destructive" type="submit" :disabled="processing" class="h-11 px-6">
                                <ShieldBan class="h-4 w-4 mr-2" />
                                {{ processing ? 'Disabling...' : 'Disable 2FA' }}
                            </Button>
                        </Form>
                    </div>
                </div>
            </div>
        </div>

        <TwoFactorSetupModal
            v-model:isOpen="showSetupModal"
            :requiresConfirmation="true"
            :twoFactorEnabled="twoFactorEnabled"
        />
    </ModernSettingsLayout>
</template>
