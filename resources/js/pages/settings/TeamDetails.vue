<script setup lang="ts">
import { Head, usePage, Form, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import HeadingSmall from '@/components/HeadingSmall.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';

interface TeamMember {
    id: number;
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
}

interface Website {
    id: number;
    url: string;
    assigned_at: string;
    assigned_by: string;
}

interface Team {
    id: number;
    name: string;
    description: string;
    created_by: string;
    user_role: string;
    is_owner: boolean;
    created_at: string;
}

interface Props {
    team: Team;
    members: TeamMember[];
    pendingInvitations: TeamInvitation[];
    websites: Website[];
    userRole: string;
    roleDescriptions: Record<string, string>;
    availableRoles: string[];
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Team settings',
        href: '/settings/team',
    },
    {
        title: props.team.name,
        href: `/settings/team/${props.team.id}`,
    },
];

// Component state
const showDeleteConfirm = ref(false);
const memberToRemove = ref<TeamMember | null>(null);
const invitationToCancel = ref<TeamInvitation | null>(null);
const showRoleChangeDialog = ref(false);
const selectedMember = ref<TeamMember | null>(null);
const newRole = ref('');
const showInviteDialog = ref(false);
const showEditTeamDialog = ref(false);

// Role utilities
const getRoleColor = (role: string) => {
    const colors = {
        'OWNER': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        'ADMIN': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        'MANAGER': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        'VIEWER': 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
    };
    return colors[role] || colors['VIEWER'];
};

const canManageMembers = computed(() => props.team.is_owner);
const canEditRole = (member: TeamMember) => {
    return canManageMembers.value && member.role !== 'OWNER' && props.userRole === 'OWNER';
};

// Actions
const changeRole = (member: TeamMember, role: string) => {
    router.patch(`/settings/team/${props.team.id}/members/${member.user_id}/role`, {
        role: role
    });
};

const removeMember = (member: TeamMember) => {
    router.delete(`/settings/team/${props.team.id}/members/${member.user_id}`);
    memberToRemove.value = null;
};

const cancelInvitation = (invitation: TeamInvitation) => {
    router.delete(`/settings/team/${props.team.id}/invitations/${invitation.id}`);
    invitationToCancel.value = null;
};

const viewWebsiteDetails = (websiteId: number) => {
    router.visit(`/ssl/${websiteId}`);
};

const resendInvitation = (invitation: TeamInvitation) => {
    router.post(`/settings/team/${props.team.id}/invitations/${invitation.id}/resend`);
};

const transferWebsite = (website: Website) => {
    // Transfer website back to personal ownership
    router.delete(`/settings/team/${props.team.id}/websites/${website.id}`);
};
</script>

<template>
    <Head :title="`${team.name} - Team Details`" />

    <AppLayout>
        <SettingsLayout>
            <template #header>
                <HeadingSmall :breadcrumb-items="breadcrumbItems">
                    {{ team.name }}
                </HeadingSmall>
            </template>

            <div class="space-y-6">
                <!-- Team Information -->
                <Card class="p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-xl font-semibold">{{ team.name }}</h3>
                                <Badge :class="getRoleColor(team.user_role)">
                                    {{ team.user_role }}
                                </Badge>
                                <Badge v-if="team.is_owner" variant="outline">
                                    Team Owner
                                </Badge>
                            </div>
                            <p v-if="team.description" class="text-muted-foreground mb-3">
                                {{ team.description }}
                            </p>
                            <div class="flex items-center gap-6 text-sm text-muted-foreground">
                                <span>Created by {{ team.created_by }}</span>
                                <span>{{ members.length }} {{ members.length === 1 ? 'member' : 'members' }}</span>
                                <span v-if="pendingInvitations.length > 0">
                                    {{ pendingInvitations.length }} pending invitations
                                </span>
                            </div>
                        </div>
                        <div v-if="canManageMembers" class="flex gap-2">
                            <Button @click="showInviteDialog = true">
                                Invite Member
                            </Button>
                            <Button variant="outline" @click="showEditTeamDialog = true">
                                Edit Team
                            </Button>
                        </div>
                    </div>
                </Card>

                <!-- Team Members -->
                <Card class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h4 class="text-lg font-medium">Team Members ({{ members.length }})</h4>
                    </div>
                    <div class="space-y-4">
                        <div
                            v-for="member in members"
                            :key="member.id"
                            class="flex items-center justify-between p-4 border rounded-lg"
                        >
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center">
                                    <span class="text-sm font-medium">{{ member.name.charAt(0).toUpperCase() }}</span>
                                </div>
                                <div>
                                    <div class="font-medium">{{ member.name }}</div>
                                    <div class="text-sm text-muted-foreground">{{ member.email }}</div>
                                    <div class="flex items-center gap-2 mt-1">
                                        <Badge :class="getRoleColor(member.role)" class="text-xs">
                                            {{ member.role }}
                                        </Badge>
                                        <span class="text-xs text-muted-foreground">
                                            Joined {{ new Date(member.joined_at).toLocaleDateString() }}
                                        </span>
                                        <span v-if="member.invited_by" class="text-xs text-muted-foreground">
                                            • Invited by {{ member.invited_by }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div v-if="canEditRole(member)" class="flex gap-2">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="outline" size="sm">
                                            Change Role
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent>
                                        <DropdownMenuItem
                                            v-for="role in availableRoles.filter(r => r !== 'OWNER' && r !== member.role)"
                                            :key="role"
                                            @click="changeRole(member, role)"
                                        >
                                            {{ role }}
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>

                                <Button
                                    variant="destructive"
                                    size="sm"
                                    @click="memberToRemove = member; showDeleteConfirm = true"
                                >
                                    Remove
                                </Button>
                            </div>
                        </div>
                    </div>
                </Card>

                <!-- Pending Invitations -->
                <Card v-if="pendingInvitations.length > 0" class="p-6">
                    <h4 class="text-lg font-medium mb-6">Pending Invitations ({{ pendingInvitations.length }})</h4>
                    <div class="space-y-4">
                        <div
                            v-for="invitation in pendingInvitations"
                            :key="invitation.id"
                            class="flex items-center justify-between p-4 border rounded-lg bg-muted/30"
                        >
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-muted rounded-full flex items-center justify-center">
                                    <span class="text-sm text-muted-foreground">?</span>
                                </div>
                                <div>
                                    <div class="font-medium">{{ invitation.email }}</div>
                                    <div class="flex items-center gap-2 mt-1">
                                        <Badge :class="getRoleColor(invitation.role)" class="text-xs">
                                            {{ invitation.role }}
                                        </Badge>
                                        <span class="text-xs text-muted-foreground">
                                            Expires {{ new Date(invitation.expires_at).toLocaleDateString() }}
                                        </span>
                                        <span class="text-xs text-muted-foreground">
                                            • Invited by {{ invitation.invited_by }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div v-if="canManageMembers" class="flex gap-2">
                                <Button variant="outline" size="sm" @click="resendInvitation(invitation)">
                                    Resend
                                </Button>
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    @click="cancelInvitation(invitation)"
                                >
                                    Cancel
                                </Button>
                            </div>
                        </div>
                    </div>
                </Card>

                <!-- Team Websites -->
                <Card v-if="websites.length > 0" class="p-6">
                    <h4 class="text-lg font-medium mb-6">Team Websites ({{ websites.length }})</h4>
                    <div class="space-y-4">
                        <div
                            v-for="website in websites"
                            :key="website.id"
                            class="flex items-center justify-between p-4 border rounded-lg"
                        >
                            <div>
                                <div class="font-medium text-blue-600 hover:text-blue-800">
                                    {{ website.url }}
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">
                                    Assigned {{ new Date(website.assigned_at).toLocaleDateString() }} by {{ website.assigned_by }}
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <Button variant="outline" size="sm" @click="viewWebsiteDetails(website.id)">
                                    View Details
                                </Button>
                                <Button
                                    v-if="canManageMembers"
                                    variant="outline"
                                    size="sm"
                                    @click="transferWebsite(website)"
                                >
                                    Transfer
                                </Button>
                            </div>
                        </div>
                    </div>
                </Card>

                <!-- Role Permissions -->
                <Card class="p-6">
                    <h4 class="text-lg font-medium mb-6">Role Permissions</h4>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div v-for="(description, role) in roleDescriptions" :key="role" class="space-y-2">
                            <div class="flex items-center gap-2">
                                <Badge :class="getRoleColor(role)">{{ role }}</Badge>
                            </div>
                            <p class="text-sm text-muted-foreground">{{ description }}</p>
                        </div>
                    </div>
                </Card>

                <!-- Danger Zone -->
                <Card v-if="canManageMembers" class="p-6 border-red-200 dark:border-red-800">
                    <h4 class="text-lg font-medium text-red-700 dark:text-red-400 mb-4">Danger Zone</h4>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-4 border border-red-200 dark:border-red-800 rounded-lg">
                            <div>
                                <div class="font-medium">Delete Team</div>
                                <div class="text-sm text-muted-foreground">
                                    Permanently delete this team and all its data. This cannot be undone.
                                </div>
                            </div>
                            <Button variant="destructive" @click="showDeleteConfirm = true">
                                Delete Team
                            </Button>
                        </div>
                    </div>
                </Card>

                <!-- Confirmation Dialogs -->
                <Dialog v-model:open="showDeleteConfirm">
                    <DialogContent class="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle>Confirm Action</DialogTitle>
                        </DialogHeader>
                        <div class="space-y-4">
                            <p v-if="memberToRemove" class="text-sm">
                                Are you sure you want to remove <strong>{{ memberToRemove.name }}</strong> from this team?
                            </p>
                            <p v-else class="text-sm">
                                Are you sure you want to delete the <strong>{{ team.name }}</strong> team? This action cannot be undone.
                            </p>
                            <div class="flex justify-end gap-2">
                                <Button variant="outline" @click="showDeleteConfirm = false; memberToRemove = null">
                                    Cancel
                                </Button>
                                <Button
                                    variant="destructive"
                                    @click="memberToRemove ? removeMember(memberToRemove) : router.delete(`/settings/team/${team.id}`)"
                                >
                                    {{ memberToRemove ? 'Remove Member' : 'Delete Team' }}
                                </Button>
                            </div>
                        </div>
                    </DialogContent>
                </Dialog>

                <!-- Invite Member Dialog -->
                <Dialog v-model:open="showInviteDialog">
                    <DialogContent class="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle>Invite Team Member</DialogTitle>
                        </DialogHeader>
                        <Form
                            :action="`/settings/team/${team.id}/invite`"
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
                                <Button type="button" variant="outline" @click="showInviteDialog = false">
                                    Cancel
                                </Button>
                                <Button type="submit" :disabled="processing">
                                    {{ processing ? 'Sending...' : 'Send Invitation' }}
                                </Button>
                            </div>
                        </Form>
                    </DialogContent>
                </Dialog>

                <!-- Edit Team Dialog -->
                <Dialog v-model:open="showEditTeamDialog">
                    <DialogContent class="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle>Edit Team</DialogTitle>
                        </DialogHeader>
                        <Form
                            :action="`/settings/team/${team.id}`"
                            method="put"
                            class="space-y-4"
                            #default="{ errors, processing }"
                        >
                            <div class="space-y-2">
                                <Label for="name">Team Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    :value="team.name"
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
                                    :value="team.description"
                                    placeholder="Brief description of your team"
                                    class="w-full"
                                />
                                <p v-if="errors.description" class="text-sm text-red-600">{{ errors.description }}</p>
                            </div>
                            <div class="flex justify-end gap-2">
                                <Button type="button" variant="outline" @click="showEditTeamDialog = false">
                                    Cancel
                                </Button>
                                <Button type="submit" :disabled="processing">
                                    {{ processing ? 'Updating...' : 'Update Team' }}
                                </Button>
                            </div>
                        </Form>
                    </DialogContent>
                </Dialog>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>