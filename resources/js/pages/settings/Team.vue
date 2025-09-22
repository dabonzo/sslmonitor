<script setup lang="ts">
import { Head, usePage, Form, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';
import HeadingSmall from '@/components/HeadingSmall.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
// Note: using direct URL since team routes are auto-generated
import { type BreadcrumbItem } from '@/types';

interface Team {
    id: number;
    name: string;
    description: string;
    created_by: string;
    user_role: string;
    is_owner: boolean;
    members_count: number;
    pending_invitations_count: number;
    created_at: string;
}

interface Props {
    teams: Team[];
    roleDescriptions: Record<string, string>;
    availableRoles: string[];
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Team settings',
        href: '/settings/team',
    },
];

// Component state
const showCreateTeamDialog = ref(false);
const showInviteMemberDialog = ref(false);
const selectedTeam = ref<Team | null>(null);

// Role color mapping
const getRoleColor = (role: string) => {
    const colors = {
        'OWNER': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        'ADMIN': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        'MANAGER': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        'VIEWER': 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
    };
    return colors[role] || colors['VIEWER'];
};

const userTeams = computed(() => props.teams || []);

// Navigation function
const viewTeamDetails = (teamId: number) => {
    router.visit(`/settings/team/${teamId}`);
};
</script>

<template>
    <Head title="Team Settings" />

    <AppLayout>
        <SettingsLayout>
            <template #header>
                <HeadingSmall :breadcrumb-items="breadcrumbItems">
                    Team Settings
                </HeadingSmall>
            </template>

            <div class="space-y-6">
                <!-- Header Section -->
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-medium">Your Teams</h3>
                        <p class="text-sm text-muted-foreground">
                            Manage your team memberships and create new teams for collaborative monitoring.
                        </p>
                    </div>
                    <Dialog v-model:open="showCreateTeamDialog">
                        <DialogTrigger as-child>
                            <Button>
                                Create Team
                            </Button>
                        </DialogTrigger>
                        <DialogContent class="sm:max-w-md">
                            <DialogHeader>
                                <DialogTitle>Create New Team</DialogTitle>
                            </DialogHeader>
                            <Form action="/settings/team" method="post" class="space-y-4" #default="{ errors, processing }">
                                <div class="space-y-2">
                                    <Label for="name">Team Name</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        placeholder="Enter team name"
                                        required
                                        class="w-full"
                                    />
                                    <p v-if="errors.name" class="text-sm text-red-600">{{ errors.name }}</p>
                                </div>
                                <div class="space-y-2">
                                    <Label for="description">Description (Optional)</Label>
                                    <Input
                                        id="description"
                                        name="description"
                                        placeholder="Brief description of your team"
                                        class="w-full"
                                    />
                                    <p v-if="errors.description" class="text-sm text-red-600">{{ errors.description }}</p>
                                </div>
                                <div class="flex justify-end gap-2">
                                    <Button type="button" variant="outline" @click="showCreateTeamDialog = false">
                                        Cancel
                                    </Button>
                                    <Button type="submit" :disabled="processing">
                                        {{ processing ? 'Creating...' : 'Create Team' }}
                                    </Button>
                                </div>
                            </Form>
                        </DialogContent>
                    </Dialog>
                </div>

                <!-- Empty State -->
                <div v-if="userTeams.length === 0" class="text-center py-12">
                    <div class="mx-auto max-w-md">
                        <h3 class="text-lg font-medium text-muted-foreground mb-2">No teams yet</h3>
                        <p class="text-sm text-muted-foreground mb-6">
                            Create your first team to collaborate with others on SSL monitoring.
                        </p>
                        <Button @click="showCreateTeamDialog = true">
                            Create Your First Team
                        </Button>
                    </div>
                </div>

                <!-- Teams List -->
                <div v-else class="space-y-4">
                    <Card v-for="team in userTeams" :key="team.id" class="p-6">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <h4 class="text-lg font-medium">{{ team.name }}</h4>
                                    <Badge :class="getRoleColor(team.user_role)">
                                        {{ team.user_role }}
                                    </Badge>
                                    <Badge v-if="team.is_owner" variant="outline">
                                        Owner
                                    </Badge>
                                </div>
                                <p v-if="team.description" class="text-sm text-muted-foreground mb-3">
                                    {{ team.description }}
                                </p>
                                <div class="flex items-center gap-6 text-xs text-muted-foreground">
                                    <span class="flex items-center gap-1">
                                        <span class="font-medium">{{ team.members_count }}</span>
                                        {{ team.members_count === 1 ? 'member' : 'members' }}
                                    </span>
                                    <span v-if="team.pending_invitations_count > 0" class="flex items-center gap-1">
                                        <span class="font-medium">{{ team.pending_invitations_count }}</span>
                                        pending invitations
                                    </span>
                                    <span>Created by {{ team.created_by }}</span>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="viewTeamDetails(team.id)"
                                >
                                    View Details
                                </Button>
                                <Button
                                    v-if="team.is_owner"
                                    size="sm"
                                    @click="selectedTeam = team; showInviteMemberDialog = true"
                                >
                                    Invite Member
                                </Button>
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- Role Permissions Info -->
                <Card v-if="userTeams.length > 0" class="p-6">
                    <h4 class="text-lg font-medium mb-4">Team Role Permissions</h4>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div v-for="(description, role) in roleDescriptions" :key="role" class="space-y-2">
                            <div class="flex items-center gap-2">
                                <Badge :class="getRoleColor(role)">{{ role }}</Badge>
                            </div>
                            <p class="text-sm text-muted-foreground">{{ description }}</p>
                        </div>
                    </div>
                </Card>

                <!-- Invite Member Dialog -->
                <Dialog v-model:open="showInviteMemberDialog">
                    <DialogContent class="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle>Invite Team Member</DialogTitle>
                        </DialogHeader>
                        <Form
                            v-if="selectedTeam"
                            :action="`/settings/team/${selectedTeam.id}/invite`"
                            method="post"
                            class="space-y-4"
                            #default="{ errors, processing }"
                        >
                            <div class="space-y-2">
                                <Label for="email">Email Address</Label>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    placeholder="Enter email address"
                                    required
                                    class="w-full"
                                />
                                <p v-if="errors.email" class="text-sm text-red-600">{{ errors.email }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="role">Role</Label>
                                <select
                                    id="role"
                                    name="role"
                                    required
                                    class="w-full px-3 py-2 border border-input bg-background rounded-md text-sm"
                                >
                                    <option value="">Select a role...</option>
                                    <option v-for="role in availableRoles.filter(r => r !== 'OWNER')" :key="role" :value="role">
                                        {{ role }}
                                    </option>
                                </select>
                                <p v-if="errors.role" class="text-sm text-red-600">{{ errors.role }}</p>
                            </div>
                            <div class="flex justify-end gap-2">
                                <Button type="button" variant="outline" @click="showInviteMemberDialog = false">
                                    Cancel
                                </Button>
                                <Button type="submit" :disabled="processing">
                                    {{ processing ? 'Sending...' : 'Send Invitation' }}
                                </Button>
                            </div>
                        </Form>
                    </DialogContent>
                </Dialog>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>