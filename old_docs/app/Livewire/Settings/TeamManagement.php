<?php

namespace App\Livewire\Settings;

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\Website;
use App\Services\TeamInvitationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TeamManagement extends Component
{
    public string $teamName = '';

    public array $transferWebsites = [];

    public string $inviteEmail = '';

    public string $inviteRole = 'admin';

    public function mount(): void
    {
        // No specific authorization needed - just require authentication
    }

    public function createTeam(): void
    {
        $this->validate([
            'teamName' => 'required|string|max:255',
            'transferWebsites' => 'array',
        ]);

        $user = Auth::user();

        // Create team
        $team = Team::create([
            'name' => $this->teamName,
            'owner_id' => $user->id,
        ]);

        // Add owner as team member
        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'role' => TeamMember::ROLE_OWNER,
        ]);

        // Transfer selected websites to team
        if (! empty($this->transferWebsites)) {
            Website::whereIn('id', $this->transferWebsites)
                ->where('user_id', $user->id) // Security: only transfer user's own websites
                ->update([
                    'team_id' => $team->id,
                    'added_by' => $user->id,
                ]);
        }

        // Reset form
        $this->teamName = '';
        $this->transferWebsites = [];

        session()->flash('success', 'Team created successfully!');
    }

    public function inviteUser(): void
    {
        $user = Auth::user();
        $team = $user->primaryTeam();

        // Check permissions
        if (! $team || ! $team->userHasPermission($user, 'manage_team')) {
            abort(403, 'You do not have permission to invite users.');
        }

        $this->validate([
            'inviteEmail' => 'required|email|max:255',
            'inviteRole' => 'required|in:admin,manager,viewer',
        ]);

        try {
            $invitationService = app(TeamInvitationService::class);
            $invitationService->inviteUser($team, $this->inviteEmail, $this->inviteRole, $user);

            // Reset form
            $this->inviteEmail = '';
            $this->inviteRole = 'admin';

            session()->flash('success', 'Invitation sent successfully! The user will receive an email with setup instructions.');

        } catch (\Exception $e) {
            $this->addError('inviteEmail', $e->getMessage());
        }
    }

    public function removeMember(int $userId): void
    {
        $user = Auth::user();
        $team = $user->primaryTeam();

        // Check permissions
        if (! $team || ! $team->userHasPermission($user, 'manage_team')) {
            abort(403);
        }

        // Prevent owner from removing themselves
        if ($userId === $user->id) {
            $this->addError('removeMember', 'You cannot remove yourself from the team.');

            return;
        }

        // Remove team member
        TeamMember::where('team_id', $team->id)
            ->where('user_id', $userId)
            ->delete();

        session()->flash('success', 'Member removed successfully!');
    }

    public function changeMemberRole(int $userId, string $newRole): void
    {
        $user = Auth::user();
        $team = $user->primaryTeam();

        // Check permissions
        if (! $team || ! $team->userHasPermission($user, 'manage_team')) {
            abort(403);
        }

        // Update member role
        TeamMember::where('team_id', $team->id)
            ->where('user_id', $userId)
            ->update(['role' => $newRole]);

        session()->flash('success', 'Member role updated successfully!');
    }

    public function resendInvitation(int $invitationId): void
    {
        $user = Auth::user();
        $team = $user->primaryTeam();

        if (! $team || ! $team->userHasPermission($user, 'manage_team')) {
            abort(403);
        }

        try {
            $invitation = $team->invitations()->findOrFail($invitationId);
            $invitationService = app(TeamInvitationService::class);
            $invitationService->resendInvitation($invitation);

            session()->flash('success', 'Invitation resent successfully!');
        } catch (\Exception $e) {
            $this->addError('resend', $e->getMessage());
        }
    }

    public function cancelInvitation(int $invitationId): void
    {
        $user = Auth::user();
        $team = $user->primaryTeam();

        if (! $team || ! $team->userHasPermission($user, 'manage_team')) {
            abort(403);
        }

        try {
            $invitation = $team->invitations()->findOrFail($invitationId);
            $invitationService = app(TeamInvitationService::class);
            $invitationService->cancelInvitation($invitation);

            session()->flash('success', 'Invitation cancelled successfully!');
        } catch (\Exception $e) {
            $this->addError('cancel', $e->getMessage());
        }
    }

    public function render()
    {
        $user = Auth::user();
        $team = $user->primaryTeam();
        $personalWebsites = $user->websites()->whereNull('team_id')->get();

        return view('livewire.settings.team-management', [
            'user' => $user,
            'team' => $team,
            'personalWebsites' => $personalWebsites,
            'teamMembers' => $team ? $team->teamMembers()->with('user')->get() : collect(),
            'pendingInvitations' => $team ? $team->pendingInvitations()->with('invitedBy')->get() : collect(),
        ]);
    }
}
