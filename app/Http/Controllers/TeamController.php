<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\TeamInvitationMail;

class TeamController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Get user's teams and owned teams
        $userTeams = $user->teams()->with(['createdBy', 'members'])->get();
        $ownedTeams = $user->ownedTeams()->with(['members', 'pendingInvitations'])->get();

        // Get all teams for display (combine owned and member teams)
        $allTeams = $userTeams->merge($ownedTeams)->unique('id');

        return Inertia::render('Settings/Team', [
            'teams' => $allTeams->map(function ($team) use ($user) {
                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'description' => $team->description,
                    'created_by' => $team->createdBy->name,
                    'user_role' => $user->getRoleInTeam($team),
                    'is_owner' => $user->canManageTeam($team),
                    'members_count' => $team->members->count(),
                    'pending_invitations_count' => $team->pendingInvitations->count(),
                    'created_at' => $team->created_at,
                ];
            }),
            'roleDescriptions' => TeamMember::getRoleDescriptions(),
            'availableRoles' => TeamMember::getRoles(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $team = Team::create([
            'name' => $request->name,
            'description' => $request->description,
            'created_by_user_id' => $request->user()->id,
        ]);

        // Add the creator as an owner
        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $request->user()->id,
            'role' => TeamMember::ROLE_OWNER,
            'joined_at' => now(),
            'invited_by_user_id' => $request->user()->id,
        ]);

        return redirect()->route('settings.team')->with('success', 'Team created successfully!');
    }

    public function show(Team $team): Response
    {
        $user = Auth::user();

        // Check if user is a member of this team
        if (!$user->isMemberOf($team)) {
            abort(403, 'You are not a member of this team.');
        }

        $team->load(['teamMembers.user', 'pendingInvitations.invitedBy', 'websites']);

        return Inertia::render('Settings/Team', [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'description' => $team->description,
                'created_by' => $team->createdBy->name,
                'user_role' => $user->getRoleInTeam($team),
                'is_owner' => $user->canManageTeam($team),
                'created_at' => $team->created_at,
            ],
            'userRole' => $user->getRoleInTeam($team),
            'members' => $team->teamMembers->map(function ($teamMember) {
                return [
                    'id' => $teamMember->id,
                    'user_id' => $teamMember->user_id,
                    'name' => $teamMember->user->name,
                    'email' => $teamMember->user->email,
                    'role' => $teamMember->role,
                    'joined_at' => $teamMember->joined_at,
                    'invited_by' => User::find($teamMember->invited_by_user_id)?->name,
                ];
            }),
            'pendingInvitations' => $team->pendingInvitations->map(function ($invitation) {
                return [
                    'id' => $invitation->id,
                    'email' => $invitation->email,
                    'role' => $invitation->role,
                    'expires_at' => $invitation->expires_at,
                    'invited_by' => $invitation->invitedBy->name,
                    'created_at' => $invitation->created_at,
                ];
            }),
            'websites' => $team->websites->map(function ($website) {
                return [
                    'id' => $website->id,
                    'name' => $website->name,
                    'url' => $website->url,
                    'assigned_at' => $website->assigned_at,
                    'assigned_by' => $website->assignedBy?->name,
                ];
            }),
            'roleDescriptions' => TeamMember::getRoleDescriptions(),
            'availableRoles' => TeamMember::getRoles(),
        ]);
    }

    public function update(Request $request, Team $team): RedirectResponse
    {
        $user = $request->user();

        if (!$user->canManageTeam($team)) {
            abort(403, 'You do not have permission to update this team.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $team->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->back()->with('success', 'Team updated successfully!');
    }

    public function destroy(Team $team): RedirectResponse
    {
        $user = Auth::user();

        if (!$user->canManageTeam($team)) {
            abort(403, 'You do not have permission to delete this team.');
        }

        // Transfer all team websites back to personal ownership
        $team->websites()->update([
            'team_id' => null,
            'assigned_by_user_id' => null,
            'assigned_at' => null,
        ]);

        $team->delete();

        return redirect()->route('settings.team')->with('success', 'Team deleted successfully!');
    }

    public function inviteMember(Request $request, Team $team): RedirectResponse
    {
        $user = $request->user();

        // Check permissions - only owners and admins can invite
        $userRole = $user->getRoleInTeam($team);
        if (!in_array($userRole, [TeamMember::ROLE_OWNER, TeamMember::ROLE_ADMIN])) {
            abort(403, 'You do not have permission to invite members to this team.');
        }

        $request->validate([
            'email' => 'required|email',
            'role' => 'required|in:' . implode(',', TeamMember::getRoles()),
        ]);

        // Check if user is already a member
        $existingUser = User::where('email', $request->email)->first();
        if ($existingUser && $existingUser->isMemberOf($team)) {
            return redirect()->back()->withErrors(['email' => 'This user is already a member of the team.']);
        }

        // Check if there's already a pending invitation
        if (TeamInvitation::hasPendingInvitation($team, $request->email)) {
            return redirect()->back()->withErrors(['email' => 'There is already a pending invitation for this email.']);
        }

        // Create invitation
        $invitation = TeamInvitation::createInvitation(
            $team,
            $request->email,
            $request->role,
            $user
        );

        // Send invitation email
        try {
            Mail::to($request->email)->send(new TeamInvitationMail($invitation));
        } catch (\Exception $e) {
            // Log error but don't fail the invitation creation
            \Log::error('Failed to send team invitation email: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Invitation sent successfully!');
    }

    public function removeMember(Request $request, Team $team, User $user): RedirectResponse
    {
        $currentUser = $request->user();

        // Only owners can remove members
        if (!$currentUser->canManageTeam($team)) {
            abort(403, 'You do not have permission to remove members from this team.');
        }

        // Can't remove yourself if you're the only owner
        if ($user->id === $currentUser->id) {
            $ownerCount = TeamMember::where('team_id', $team->id)->where('role', TeamMember::ROLE_OWNER)->count();
            if ($ownerCount <= 1) {
                return redirect()->back()->with('error', 'You cannot remove yourself as the last owner. Transfer ownership first or delete the team.');
            }
        }

        // Remove member
        TeamMember::where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->delete();

        // Transfer any websites assigned by this member back to personal ownership
        $team->websites()->where('assigned_by_user_id', $user->id)
            ->update([
                'team_id' => null,
                'assigned_by_user_id' => null,
                'assigned_at' => null,
            ]);

        return redirect()->back()->with('success', 'Member removed successfully!');
    }

    public function updateMemberRole(Request $request, Team $team, User $user): RedirectResponse
    {
        $currentUser = $request->user();

        // Only owners can update member roles
        if (!$currentUser->canManageTeam($team)) {
            abort(403, 'You do not have permission to update member roles.');
        }

        $request->validate([
            'role' => 'required|in:' . implode(',', TeamMember::getRoles()),
        ]);

        // Can't change your own role if you're the only owner
        if ($user->id === $currentUser->id && $request->role !== TeamMember::ROLE_OWNER) {
            $ownerCount = TeamMember::where('team_id', $team->id)->where('role', TeamMember::ROLE_OWNER)->count();
            if ($ownerCount <= 1) {
                return redirect()->back()->with('error', 'You cannot change your role as the last owner. Assign another owner first.');
            }
        }

        // Update member role
        $updated = TeamMember::where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->update(['role' => $request->role]);

        if ($updated === 0) {
            return redirect()->back()->with('error', 'Member not found or could not be updated.');
        }

        return redirect()->back()->with('success', 'Member role updated successfully!');
    }

    public function cancelInvitation(Request $request, Team $team, TeamInvitation $invitation): RedirectResponse
    {
        $user = $request->user();

        // Check permissions and team ownership
        if (!$user->canManageTeam($team) || $invitation->team_id !== $team->id) {
            abort(403, 'You do not have permission to cancel this invitation.');
        }

        $invitation->delete();

        return redirect()->back()->with('success', 'Invitation cancelled successfully!');
    }

    public function resendInvitation(Request $request, Team $team, TeamInvitation $invitation): RedirectResponse
    {
        $user = $request->user();

        // Check permissions and team ownership
        if (!$user->canManageTeam($team) || $invitation->team_id !== $team->id) {
            abort(403, 'You do not have permission to resend this invitation.');
        }

        $invitation->resend();

        // Send invitation email
        try {
            Mail::to($invitation->email)->send(new TeamInvitationMail($invitation));
        } catch (\Exception $e) {
            \Log::error('Failed to resend team invitation email: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Invitation resent successfully!');
    }
}
