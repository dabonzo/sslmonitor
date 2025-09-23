<script setup lang="ts">
import { Head, Form, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import ModernSettingsLayout from '@/layouts/ModernSettingsLayout.vue';
import { Users, UserPlus, Crown, Shield, Eye, Plus } from 'lucide-vue-next';

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

    <ModernSettingsLayout title="Team Settings">
        <div class="space-y-8">
            <!-- Team Management Section -->
            <div class="rounded-xl bg-gradient-to-r from-gray-50 to-slate-50 dark:from-gray-900 dark:to-slate-900 p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-3">
                        <div class="rounded-lg bg-gray-100 dark:bg-gray-800 p-2">
                            <Users class="h-5 w-5 text-gray-600 dark:text-gray-400" />
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Team Management</h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Manage your team memberships and collaboration</p>
                        </div>
                    </div>
                    <Dialog v-model:open="showCreateTeamDialog">
                        <DialogTrigger as-child>
                            <Button class="h-11 px-6">
                                <Plus class="h-4 w-4 mr-2" />
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
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-8 mx-auto max-w-md">
                        <div class="rounded-full bg-gray-100 dark:bg-gray-700 p-3 w-12 h-12 mx-auto mb-4">
                            <Users class="h-6 w-6 text-gray-600 dark:text-gray-400" />
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">No teams yet</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                            Create your first team to collaborate with others on SSL monitoring.
                        </p>
                        <Button @click="showCreateTeamDialog = true" class="h-11 px-6">
                            <Plus class="h-4 w-4 mr-2" />
                            Create Your First Team
                        </Button>
                    </div>
                </div>

                <!-- Teams List -->
                <div v-else class="space-y-4">
                    <div v-for="team in userTeams" :key="team.id" class="rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-all duration-200">
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
                            <div class="flex gap-3">
                                <Button
                                    variant="outline"
                                    class="h-10 px-4"
                                    @click="viewTeamDetails(team.id)"
                                >
                                    <Eye class="h-4 w-4 mr-2" />
                                    View Details
                                </Button>
                                <Button
                                    v-if="team.is_owner"
                                    class="h-10 px-4"
                                    @click="selectedTeam = team; showInviteMemberDialog = true"
                                >
                                    <UserPlus class="h-4 w-4 mr-2" />
                                    Invite Member
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Role Permissions Info -->
            <div v-if="userTeams.length > 0" class="rounded-xl bg-gradient-to-r from-gray-50 to-slate-50 dark:from-gray-900 dark:to-slate-900 p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="rounded-lg bg-gray-100 dark:bg-gray-800 p-2">
                        <Shield class="h-5 w-5 text-gray-600 dark:text-gray-400" />
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Team Role Permissions</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Understanding team member capabilities</p>
                    </div>
                </div>
                <div class="grid gap-6 md:grid-cols-2">
                    <div v-for="(description, role) in roleDescriptions" :key="role" class="rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4">
                        <div class="flex items-center gap-3 mb-3">
                            <Crown v-if="role === 'OWNER'" class="h-4 w-4 text-red-600 dark:text-red-400" />
                            <Shield v-else-if="role === 'ADMIN'" class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                            <Users v-else-if="role === 'MANAGER'" class="h-4 w-4 text-green-600 dark:text-green-400" />
                            <Eye v-else class="h-4 w-4 text-gray-600 dark:text-gray-400" />
                            <Badge :class="getRoleColor(role)">{{ role }}</Badge>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ description }}</p>
                    </div>
                </div>
            </div>
        </div>

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
    </ModernSettingsLayout>
</template>