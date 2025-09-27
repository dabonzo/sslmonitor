<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm } from '@inertiajs/vue3';
import { Smartphone, Shield, Key, RefreshCw } from 'lucide-vue-next';
import { ref } from 'vue';

const showRecoveryForm = ref<boolean>(false);

const form = useForm({
    code: '',
    recovery_code: '',
});

const recoveryForm = useForm({
    recovery_code: '',
});

const submitAuthentication = () => {
    form.post(route('two-factor.challenge.store'), {
        onFinish: () => {
            form.reset('code');
        },
    });
};

const submitRecovery = () => {
    recoveryForm.post(route('two-factor.challenge.store'), {
        onFinish: () => {
            recoveryForm.reset('recovery_code');
        },
    });
};

const toggleRecoveryForm = () => {
    showRecoveryForm.value = !showRecoveryForm.value;
    form.reset();
    recoveryForm.reset();
};
</script>

<template>
    <Head title="Two-Factor Challenge" />

    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-gray-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800 flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 mb-4">
                    <Shield class="w-8 h-8 text-white" />
                </div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Two-Factor Authentication
                </h1>
                <p class="text-gray-600 dark:text-gray-400">
                    Please confirm access to your account by entering one of your emergency recovery codes or the authentication code provided by your authenticator application.
                </p>
            </div>

            <Card class="shadow-xl border-0 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm">
                <CardHeader class="text-center pb-4">
                    <CardTitle class="flex items-center justify-center space-x-2">
                        <component :is="showRecoveryForm ? Key : Smartphone" class="w-5 h-5 text-blue-600" />
                        <span>{{ showRecoveryForm ? 'Recovery Code' : 'Authentication Code' }}</span>
                    </CardTitle>
                    <CardDescription>
                        {{ showRecoveryForm
                            ? 'Enter one of your eight-digit recovery codes'
                            : 'Enter the six-digit code from your authenticator app'
                        }}
                    </CardDescription>
                </CardHeader>

                <CardContent class="space-y-6">
                    <!-- Authentication Code Form -->
                    <form v-if="!showRecoveryForm" @submit.prevent="submitAuthentication" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="code">Authentication Code</Label>
                            <div class="relative">
                                <Smartphone class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                                <Input
                                    id="code"
                                    v-model="form.code"
                                    type="text"
                                    placeholder="123456"
                                    maxlength="6"
                                    pattern="[0-9]{6}"
                                    class="pl-10 text-center text-lg tracking-widest font-mono"
                                    :class="{ 'border-red-500 focus:ring-red-500': form.errors.code }"
                                    autofocus
                                    autocomplete="one-time-code"
                                />
                            </div>
                            <p v-if="form.errors.code" class="text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.code }}
                            </p>
                        </div>

                        <Button
                            type="submit"
                            :disabled="form.processing || form.code.length !== 6"
                            class="w-full h-12 text-lg"
                        >
                            <RefreshCw v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
                            <Shield v-else class="w-4 h-4 mr-2" />
                            {{ form.processing ? 'Verifying...' : 'Verify & Continue' }}
                        </Button>
                    </form>

                    <!-- Recovery Code Form -->
                    <form v-else @submit.prevent="submitRecovery" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="recovery_code">Recovery Code</Label>
                            <div class="relative">
                                <Key class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                                <Input
                                    id="recovery_code"
                                    v-model="recoveryForm.recovery_code"
                                    type="text"
                                    placeholder="ABCD1234"
                                    maxlength="8"
                                    class="pl-10 text-center text-lg tracking-widest font-mono uppercase"
                                    :class="{ 'border-red-500 focus:ring-red-500': recoveryForm.errors.recovery_code }"
                                    autofocus
                                    autocomplete="off"
                                />
                            </div>
                            <p v-if="recoveryForm.errors.recovery_code" class="text-sm text-red-600 dark:text-red-400">
                                {{ recoveryForm.errors.recovery_code }}
                            </p>
                        </div>

                        <Button
                            type="submit"
                            :disabled="recoveryForm.processing || recoveryForm.recovery_code.length !== 8"
                            class="w-full h-12 text-lg"
                        >
                            <RefreshCw v-if="recoveryForm.processing" class="w-4 h-4 mr-2 animate-spin" />
                            <Key v-else class="w-4 w-4 mr-2" />
                            {{ recoveryForm.processing ? 'Verifying...' : 'Use Recovery Code' }}
                        </Button>
                    </form>

                    <!-- Toggle between forms -->
                    <div class="text-center border-t border-gray-200 dark:border-gray-700 pt-4">
                        <button
                            type="button"
                            @click="toggleRecoveryForm"
                            class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors"
                        >
                            {{ showRecoveryForm
                                ? 'Use authenticator app instead'
                                : "Can't access your authenticator? Use a recovery code"
                            }}
                        </button>
                    </div>
                </CardContent>
            </Card>

            <!-- Help Section -->
            <div class="mt-8 text-center">
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                    <div class="flex items-start space-x-3">
                        <Shield class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5" />
                        <div class="text-left">
                            <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-1">
                                Need help?
                            </h3>
                            <p class="text-sm text-blue-700 dark:text-blue-300">
                                If you've lost access to your authenticator app and recovery codes,
                                please contact support for assistance.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>