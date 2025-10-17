<script setup lang="ts">
import RegisteredUserController from '@/actions/App/Http/Controllers/Auth/RegisteredUserController';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import CoverAuthLayout from '@/layouts/auth/CoverAuthLayout.vue';
import { login } from '@/routes';
import { Form, Head } from '@inertiajs/vue3';
import { LoaderCircle, User, Mail, Lock } from 'lucide-vue-next';
</script>

<template>
    <CoverAuthLayout title="Sign up" description="Enter your details below to create your account">
        <Head title="Register" />

        <Form
            v-bind="RegisteredUserController.store.form()"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
            class="space-y-5 dark:text-white"
        >
            <!-- Name field -->
            <div>
                <Label for="name" class="mb-2 block text-sm font-medium text-foreground dark:text-muted-foreground">Name</Label>
                <div class="relative text-muted-foreground dark:text-muted-foreground">
                    <Input
                        id="name"
                        type="text"
                        name="name"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="name"
                        placeholder="Enter Full Name"
                        class="form-input ps-10 placeholder:text-muted-foreground dark:placeholder:text-muted-foreground"
                    />
                    <span class="absolute left-4 top-1/2 -translate-y-1/2">
                        <User class="h-[18px] w-[18px]" />
                    </span>
                </div>
                <InputError :message="errors.name" class="mt-1" />
            </div>

            <!-- Email field -->
            <div>
                <Label for="email" class="mb-2 block text-sm font-medium text-foreground dark:text-muted-foreground">Email</Label>
                <div class="relative text-muted-foreground dark:text-muted-foreground">
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        :tabindex="2"
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

            <!-- Password field -->
            <div>
                <Label for="password" class="mb-2 block text-sm font-medium text-foreground dark:text-muted-foreground">Password</Label>
                <div class="relative text-muted-foreground dark:text-muted-foreground">
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        required
                        :tabindex="3"
                        autocomplete="new-password"
                        placeholder="Enter Password"
                        class="form-input ps-10 placeholder:text-muted-foreground dark:placeholder:text-muted-foreground"
                    />
                    <span class="absolute left-4 top-1/2 -translate-y-1/2">
                        <Lock class="h-[18px] w-[18px]" />
                    </span>
                </div>
                <InputError :message="errors.password" class="mt-1" />
            </div>

            <!-- Confirm Password field -->
            <div>
                <Label for="password_confirmation" class="mb-2 block text-sm font-medium text-foreground dark:text-muted-foreground">Confirm Password</Label>
                <div class="relative text-muted-foreground dark:text-muted-foreground">
                    <Input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        required
                        :tabindex="4"
                        autocomplete="new-password"
                        placeholder="Confirm Password"
                        class="form-input ps-10 placeholder:text-muted-foreground dark:placeholder:text-muted-foreground"
                    />
                    <span class="absolute left-4 top-1/2 -translate-y-1/2">
                        <Lock class="h-[18px] w-[18px]" />
                    </span>
                </div>
                <InputError :message="errors.password_confirmation" class="mt-1" />
            </div>

            <!-- Register button -->
            <Button
                type="submit"
                class="btn-gradient !mt-6 w-full border-0 py-3 text-sm font-semibold uppercase tracking-wide shadow-blue-600/30"
                :tabindex="5"
                :disabled="processing"
                data-test="register-user-button"
            >
                <LoaderCircle v-if="processing" class="mr-2 h-4 w-4 animate-spin" />
                Create Account
            </Button>

            <!-- Sign in link -->
            <div class="text-center text-foreground dark:text-muted-foreground">
                Already have an account?
                <TextLink :href="login()" class="font-semibold uppercase text-primary underline transition hover:text-black dark:hover:text-white" :tabindex="6">
                    SIGN IN
                </TextLink>
            </div>
        </Form>
    </CoverAuthLayout>
</template>
