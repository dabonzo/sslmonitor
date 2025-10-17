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
import { Users, UserPlus, Crown, Shield, Eye, Plus, Trash2 } from 'lucide-vue-next';

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

interface TeamMember {
    id: number;
    user_id: number;
    name: string;
    email: string;
    role: string;
    joined_at: string;
    invited_by: string;
}

interface TeamInvitation {
    id: number;
    email: string;
    role: string;
    expires_at: string;
    invited_by: string;
    created_at: string;
}

interface TeamWebsite {
    id: number;
    name: string;
    url: string;
    assigned_at: string;
    assigned_by: string;
}

interface SingleTeam {
    id: number;
    name: string;
    description: string;
    created_by: string;
    user_role: string;
    is_owner: boolean;
    created_at: string;
}

interface Props {
    teams?: Team[];
    team?: SingleTeam;
    members?: TeamMember[];
    pendingInvitations?: TeamInvitation[];
    websites?: TeamWebsite[];
    userRole?: string;
    roleDescriptions: Record<string, string>;
    availableRoles: string[];
}

const props = defineProps<Props>();

// Component state
const showCreateTeamDialog = ref(false);
const showInviteMemberDialog = ref(false);
const showDeleteTeamDialog = ref(false);
const selectedTeam = ref<Team | null>(null);

// Role color mapping
const getRoleColor = (role: string) => {
    const colors = {
        'OWNER': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        'ADMIN': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        'MANAGER': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        'VIEWER': 'bg-muted text-gray-800 dark:bg-gray-900 dark:text-muted-foreground'
    };
    return colors[role] || colors['VIEWER'];
};

const userTeams = computed(() => props.teams || []);

// Determine if we're in team details view or team list view
const isTeamDetailsView = computed(() => !!props.team);
const isTeamListView = computed(() => !props.team);

// Navigation function
const viewTeamDetails = (teamId: number) => {
    router.visit(`/settings/team/${teamId}`);
};

const backToTeamList = () => {
    router.visit('/settings/team');
};

const deleteTeam = (teamId: number) => {
    router.delete(`/settings/team/${teamId}`, {
        onSuccess: () => {
            showDeleteTeamDialog.value = false;
        },
    });
};
</script>

<template>
    <Head title="Team Settings" />

    <ModernSettingsLayout :title="isTeamDetailsView ? props.team?.name + ' Details' : 'Team Settings'">
        <!-- Team Details View -->
        <div v-if="isTeamDetailsView" class="space-y-8">
            <!-- Back Navigation -->
            <div class="flex items-center space-x-4 mb-6">
                <button
                    @click="backToTeamList"
                    class="flex items-center space-x-2 px-3 py-2 text-sm text-foreground dark:text-muted-foreground hover:text-foreground dark:hover:text-gray-100 transition-colors"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Back to Teams
                </button>
            </div>

            <!-- Team Header -->
            <div class="rounded-xl bg-muted dark:bg-card p-6 border border-border dark:border-border">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h1 class="text-2xl font-bold text-foreground dark:text-foreground">{{ props.team?.name }}</h1>
                        <p v-if="props.team?.description" class="text-foreground dark:text-muted-foreground mt-1">{{ props.team?.description }}</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <Badge :class="getRoleColor(props.team?.user_role || '')">
                            {{ props.team?.user_role }}
                        </Badge>
                        <Badge v-if="props.team?.is_owner" variant="outline">
                            Owner
                        </Badge>
                        <Button
                            v-if="props.team?.is_owner"
                            variant="destructive"
                            @click="selectedTeam = props.team; showDeleteTeamDialog = true"
                            class="h-10 px-4"
                        >
                            <Trash2 class="h-4 w-4 mr-2" />
                            Delete Team
                        </Button>
                    </div>
                </div>
                <div class="text-sm text-muted-foreground dark:text-muted-foreground">
                    Created by {{ props.team?.created_by }} • {{ members?.length || 0 }} members • {{ websites?.length || 0 }} websites
                </div>
            </div>

            <!-- Team Members -->
            <div class="rounded-xl bg-muted dark:bg-card p-6 border border-border dark:border-border">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-foreground dark:text-foreground">Team Members</h3>
                    <Button
                        v-if="props.team?.is_owner"
                        @click="selectedTeam = props.team; showInviteMemberDialog = true"
                        class="h-10 px-4"
                    >
                        <UserPlus class="h-4 w-4 mr-2" />
                        Invite Member
                    </Button>
                </div>

                <div class="space-y-3">
                    <div v-for="member in members" :key="member.id" class="flex items-center justify-between p-4 bg-background dark:bg-card rounded-lg border border-border dark:border-border">
                        <div class="flex items-center space-x-4">
                            <div class="rounded-full bg-muted dark:bg-muted p-2">
                                <Users class="h-4 w-4 text-foreground dark:text-muted-foreground" />
                            </div>
                            <div>
                                <h4 class="font-medium text-foreground dark:text-foreground">{{ member.name }}</h4>
                                <p class="text-sm text-foreground dark:text-muted-foreground">{{ member.email }}</p>
                                <p class="text-xs text-muted-foreground dark:text-muted-foreground">Joined {{ member.joined_at }}</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <Badge :class="getRoleColor(member.role)">{{ member.role }}</Badge>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Team Websites -->
            <div v-if="websites && websites.length > 0" class="rounded-xl bg-muted dark:bg-card p-6 border border-border dark:border-border">
                <h3 class="text-xl font-bold text-foreground dark:text-foreground mb-6">Team Websites</h3>
                <div class="space-y-3">
                    <div v-for="website in websites" :key="website.id" class="flex items-center justify-between p-4 bg-background dark:bg-card rounded-lg border border-border dark:border-border">
                        <div>
                            <h4 class="font-medium text-foreground dark:text-foreground">{{ website.name }}</h4>
                            <p class="text-sm text-foreground dark:text-muted-foreground">{{ website.url }}</p>
                            <p class="text-xs text-muted-foreground dark:text-muted-foreground">Assigned {{ website.assigned_at }} by {{ website.assigned_by }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Team List View -->
        <div v-else class="space-y-8">
            <!-- Team Management Section -->
            <div class="rounded-xl bg-muted dark:bg-card p-6 border border-border dark:border-border">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-3">
                        <div class="rounded-lg bg-muted dark:bg-card p-2">
                            <Users class="h-5 w-5 text-foreground dark:text-muted-foreground" />
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-foreground dark:text-foreground">Team Management</h2>
                            <p class="text-sm text-foreground dark:text-muted-foreground">Manage your team memberships and collaboration</p>
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
                            <Form
                                action="/settings/team"
                                method="post"
                                class="space-y-4"
                                @success="showCreateTeamDialog = false"
                                #default="{ errors, processing }"
                            >
                                <div class="space-y-2">
                                    <Label for="name">Team Name</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        placeholder="Enter team name"
                                        required
                                        class="w-full"
                                    />
                                    <p v-if="errors.name" class="text-sm text-destructive">{{ errors.name }}</p>
                                </div>
                                <div class="space-y-2">
                                    <Label for="description">Description (Optional)</Label>
                                    <Input
                                        id="description"
                                        name="description"
                                        placeholder="Brief description of your team"
                                        class="w-full"
                                    />
                                    <p v-if="errors.description" class="text-sm text-destructive">{{ errors.description }}</p>
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
                    <div class="rounded-lg bg-muted dark:bg-gray-800/50 p-8 mx-auto max-w-md">
                        <div class="rounded-full bg-muted dark:bg-muted p-3 w-12 h-12 mx-auto mb-4">
                            <Users class="h-6 w-6 text-foreground dark:text-muted-foreground" />
                        </div>
                        <h3 class="text-lg font-semibold text-foreground dark:text-foreground mb-2">No teams yet</h3>
                        <p class="text-sm text-foreground dark:text-muted-foreground mb-6">
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
                    <div v-for="team in userTeams" :key="team.id" class="rounded-lg bg-background dark:bg-card border border-border dark:border-border p-6 hover:shadow-md transition-all duration-200">
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
                                <Button
                                    v-if="team.is_owner"
                                    variant="destructive"
                                    class="h-10 px-4"
                                    @click="selectedTeam = team; showDeleteTeamDialog = true"
                                >
                                    <Trash2 class="h-4 w-4 mr-2" />
                                    Delete
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Role Permissions Info -->
            <div v-if="userTeams.length > 0" class="rounded-xl bg-muted dark:bg-card p-6 border border-border dark:border-border">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="rounded-lg bg-muted dark:bg-card p-2">
                        <Shield class="h-5 w-5 text-foreground dark:text-muted-foreground" />
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-foreground dark:text-foreground">Team Role Permissions</h2>
                        <p class="text-sm text-foreground dark:text-muted-foreground">Understanding team member capabilities</p>
                    </div>
                </div>
                <div class="grid gap-6 md:grid-cols-2">
                    <div v-for="(description, role) in roleDescriptions" :key="role" class="rounded-lg bg-background dark:bg-card border border-border dark:border-border p-4">
                        <div class="flex items-center gap-3 mb-3">
                            <Crown v-if="role === 'OWNER'" class="h-4 w-4 text-destructive dark:text-red-400" />
                            <Shield v-else-if="role === 'ADMIN'" class="h-4 w-4 text-primary dark:text-blue-400" />
                            <Users v-else-if="role === 'MANAGER'" class="h-4 w-4 text-green-600 dark:text-green-400" />
                            <Eye v-else class="h-4 w-4 text-foreground dark:text-muted-foreground" />
                            <Badge :class="getRoleColor(role)">{{ role }}</Badge>
                        </div>
                        <p class="text-sm text-foreground dark:text-muted-foreground">{{ description }}</p>
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
                    @success="showInviteMemberDialog = false"
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
                        <p v-if="errors.email" class="text-sm text-destructive">{{ errors.email }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="role">Role</Label>
                        <select
                            id="role"
                            name="role"
                            required
                            class="w-full px-3 py-2 border border-border bg-background rounded-md text-sm"
                        >
                            <option value="">Select a role...</option>
                            <option v-for="role in availableRoles.filter(r => r !== 'OWNER')" :key="role" :value="role">
                                {{ role }}
                            </option>
                        </select>
                        <p v-if="errors.role" class="text-sm text-destructive">{{ errors.role }}</p>
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

        <!-- Delete Team Dialog -->
        <Dialog v-model:open="showDeleteTeamDialog">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Delete Team</DialogTitle>
                </DialogHeader>
                <div class="space-y-4">
                    <div class="rounded-lg bg-red-50 dark:bg-red-900/20 p-4 border border-red-200 dark:border-red-800">
                        <p class="text-sm text-red-800 dark:text-red-200">
                            Are you sure you want to delete <strong>{{ selectedTeam?.name }}</strong>?
                        </p>
                        <p class="text-sm text-red-700 dark:text-red-300 mt-2">
                            This action cannot be undone. All team websites will be transferred back to their original owners.
                        </p>
                    </div>
                    <div class="flex justify-end gap-2">
                        <Button type="button" variant="outline" @click="showDeleteTeamDialog = false">
                            Cancel
                        </Button>
                        <Button
                            variant="destructive"
                            @click="selectedTeam && deleteTeam(selectedTeam.id)"
                        >
                            Delete Team
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </ModernSettingsLayout>
</template>