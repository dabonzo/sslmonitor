<script setup lang="ts">
import { Head, Form, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';

interface Invitation {
    id: number;
    email: string;
    role: string;
    expires_at: string;
    team: {
        id: number;
        name: string;
        description?: string;
    };
    invited_by: string;
}

interface Props {
    invitation: Invitation;
    existing_user: boolean;
}

const props = defineProps<Props>();

const showRegistrationForm = ref(!props.existing_user);
const showLoginPrompt = ref(props.existing_user);

const getRoleColor = (role: string) => {
    const colors = {
        'OWNER': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        'ADMIN': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        'MANAGER': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        'VIEWER': 'bg-muted text-gray-800 dark:bg-gray-900 dark:text-muted-foreground'
    };
    return colors[role] || colors['VIEWER'];
};

const getRoleDescription = (role: string) => {
    const descriptions = {
        'ADMIN': 'Can manage websites, email settings, and invite team members',
        'MANAGER': 'Can add and edit websites and view team settings',
        'VIEWER': 'Has read-only access to websites and team settings'
    };
    return descriptions[role] || `Has ${role.toLowerCase()} access to the team`;
};

const isExpired = computed(() => {
    return new Date(props.invitation.expires_at) < new Date();
});

const timeUntilExpiry = computed(() => {
    const expiryDate = new Date(props.invitation.expires_at);
    const now = new Date();
    const diff = expiryDate.getTime() - now.getTime();

    if (diff < 0) return 'Expired';

    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));

    if (days > 0) return `${days} day${days > 1 ? 's' : ''}`;
    if (hours > 0) return `${hours} hour${hours > 1 ? 's' : ''}`;
    return 'Less than 1 hour';
});
</script>

<template>
    <Head title="Accept Team Invitation" />

    <div class="min-h-screen bg-muted dark:bg-gray-900 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="text-center">
                <h1 class="text-3xl font-bold text-foreground dark:text-white">🔒 SSL Monitor</h1>
                <h2 class="mt-4 text-xl text-foreground dark:text-muted-foreground">Team Invitation</h2>
            </div>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <Card class="px-8 py-8">
                <!-- Expired Invitation -->
                <div v-if="isExpired" class="text-center">
                    <div class="text-destructive mb-4">
                        <svg class="mx-auto h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 48 48">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-foreground dark:text-white mb-2">Invitation Expired</h3>
                    <p class="text-sm text-muted-foreground dark:text-muted-foreground mb-6">
                        This invitation to join <strong>{{ invitation.team.name }}</strong> has expired.
                    </p>
                    <Link href="/" class="text-primary hover:text-blue-500">
                        Return to Home
                    </Link>
                </div>

                <!-- Valid Invitation -->
                <div v-else>
                    <!-- Invitation Details -->
                    <div class="text-center mb-6">
                        <h3 class="text-lg font-medium text-foreground dark:text-white mb-2">
                            You're invited to join
                        </h3>
                        <h2 class="text-xl font-bold text-foreground dark:text-white">
                            {{ invitation.team.name }}
                        </h2>
                        <p v-if="invitation.team.description" class="text-sm text-muted-foreground dark:text-muted-foreground mt-2">
                            {{ invitation.team.description }}
                        </p>
                    </div>

                    <!-- Role and Details -->
                    <div class="bg-muted dark:bg-card rounded-lg p-4 mb-6">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-medium text-foreground dark:text-muted-foreground">Your Role:</span>
                            <Badge :class="getRoleColor(invitation.role)">
                                {{ invitation.role }}
                            </Badge>
                        </div>
                        <p class="text-xs text-foreground dark:text-muted-foreground mb-3">
                            {{ getRoleDescription(invitation.role) }}
                        </p>
                        <div class="text-xs text-muted-foreground dark:text-muted-foreground">
                            <div>Invited by: {{ invitation.invited_by }}</div>
                            <div>Email: {{ invitation.email }}</div>
                            <div>Expires in: {{ timeUntilExpiry }}</div>
                        </div>
                    </div>

                    <!-- Existing User - Login Prompt -->
                    <div v-if="showLoginPrompt" class="space-y-4">
                        <div class="text-center">
                            <p class="text-sm text-foreground dark:text-muted-foreground mb-4">
                                An account with this email already exists. Please log in to accept the invitation.
                            </p>
                            <div class="space-y-3">
                                <Link
                                    :href="`/login?email=${encodeURIComponent(invitation.email)}&redirect=${encodeURIComponent($page.url)}`"
                                    class="w-full inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                >
                                    Log In to Accept
                                </Link>
                                <Button
                                    variant="outline"
                                    class="w-full"
                                    @click="showLoginPrompt = false; showRegistrationForm = true"
                                >
                                    I don't have an account
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- New User Registration -->
                    <div v-if="showRegistrationForm" class="space-y-4">
                        <Form
                            :action="`/team/invitations/${$page.url.split('/').pop()}/register`"
                            method="post"
                            class="space-y-4"
                            #default="{ errors, processing }"
                        >
                            <div>
                                <Label for="name">Full Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    type="text"
                                    required
                                    class="mt-1 w-full"
                                    placeholder="Enter your full name"
                                />
                                <p v-if="errors.name" class="mt-1 text-sm text-destructive">{{ errors.name }}</p>
                            </div>

                            <div>
                                <Label for="email">Email Address</Label>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    :value="invitation.email"
                                    disabled
                                    class="mt-1 w-full bg-muted dark:bg-muted"
                                />
                            </div>

                            <div>
                                <Label for="password">Password</Label>
                                <Input
                                    id="password"
                                    name="password"
                                    type="password"
                                    required
                                    class="mt-1 w-full"
                                    placeholder="Create a secure password"
                                />
                                <p v-if="errors.password" class="mt-1 text-sm text-destructive">{{ errors.password }}</p>
                            </div>

                            <div>
                                <Label for="password_confirmation">Confirm Password</Label>
                                <Input
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    type="password"
                                    required
                                    class="mt-1 w-full"
                                    placeholder="Confirm your password"
                                />
                            </div>

                            <div class="flex gap-2">
                                <Button type="submit" class="flex-1" :disabled="processing">
                                    {{ processing ? 'Creating Account...' : 'Accept & Create Account' }}
                                </Button>
                            </div>
                        </Form>

                        <div v-if="existing_user" class="text-center">
                            <Button
                                variant="outline"
                                class="w-full"
                                @click="showRegistrationForm = false; showLoginPrompt = true"
                            >
                                I already have an account
                            </Button>
                        </div>
                    </div>

                    <Separator class="my-6" />

                    <!-- Decline Option -->
                    <div class="text-center">
                        <Form
                            :action="`/team/invitations/${$page.url.split('/').pop()}/decline`"
                            method="post"
                            class="inline"
                            #default="{ processing }"
                        >
                            <Button
                                type="submit"
                                variant="ghost"
                                size="sm"
                                :disabled="processing"
                                class="text-muted-foreground hover:text-foreground dark:text-muted-foreground dark:hover:text-muted-foreground"
                            >
                                Decline Invitation
                            </Button>
                        </Form>
                    </div>
                </div>
            </Card>

            <!-- Footer -->
            <div class="mt-8 text-center">
                <p class="text-xs text-muted-foreground dark:text-muted-foreground">
                    SSL Monitor - Keeping your certificates secure and up to date
                </p>
            </div>
        </div>
    </div>
</template>