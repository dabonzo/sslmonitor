<?php

namespace App\Services;

use App\Mail\TeamInvitationMail;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\TeamMember;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class TeamInvitationService
{
    /**
     * Invite a user to join a team
     */
    public function inviteUser(Team $team, string $email, string $role, User $invitedBy): TeamInvitation
    {
        // Check if user is already a team member
        if ($team->members()->where('email', $email)->exists()) {
            throw new Exception('User is already a member of this team.');
        }

        // Check if there's already a pending invitation
        $existingInvitation = $team->invitations()
            ->where('email', $email)
            ->where('status', TeamInvitation::STATUS_PENDING)
            ->first();

        if ($existingInvitation && ! $existingInvitation->isExpired()) {
            throw new Exception('An invitation has already been sent to this email address.');
        }

        // Cancel any existing pending invitation
        if ($existingInvitation) {
            $existingInvitation->cancel();
        }

        $invitation = DB::transaction(function () use ($team, $email, $role, $invitedBy) {
            // Create invitation
            $invitation = TeamInvitation::create([
                'team_id' => $team->id,
                'email' => $email,
                'role' => $role,
                'token' => TeamInvitation::generateToken(),
                'invited_by' => $invitedBy->id,
                'status' => TeamInvitation::STATUS_PENDING,
                'expires_at' => now()->addHours(48), // 48-hour expiry
            ]);

            // Send invitation email
            $this->sendInvitationEmail($invitation);

            return $invitation;
        });

        return $invitation;
    }

    /**
     * Send invitation email
     */
    public function sendInvitationEmail(TeamInvitation $invitation): void
    {
        Mail::to($invitation->email)->send(new TeamInvitationMail($invitation));
    }

    /**
     * Accept an invitation and create/update user account
     */
    public function acceptInvitation(string $token, array $userData): User
    {
        $invitation = TeamInvitation::where('token', $token)->firstOrFail();

        if (! $invitation->isPending()) {
            throw new Exception('This invitation is no longer valid.');
        }

        return DB::transaction(function () use ($invitation, $userData) {
            // Create or find user
            $user = User::where('email', $invitation->email)->first();

            if (! $user) {
                // Create new user
                $user = User::create([
                    'name' => $userData['name'],
                    'email' => $invitation->email,
                    'password' => bcrypt($userData['password']),
                    'email_verified_at' => now(),
                ]);
            } else {
                // Update existing user if they set up their account
                if (isset($userData['password'])) {
                    $user->update([
                        'password' => bcrypt($userData['password']),
                        'email_verified_at' => now(),
                    ]);
                }
            }

            // Add user to team
            TeamMember::updateOrCreate(
                [
                    'team_id' => $invitation->team_id,
                    'user_id' => $user->id,
                ],
                [
                    'role' => $invitation->role,
                ]
            );

            // Mark invitation as accepted
            $invitation->markAsAccepted();

            return $user;
        });
    }

    /**
     * Cancel an invitation
     */
    public function cancelInvitation(TeamInvitation $invitation): void
    {
        $invitation->cancel();
    }

    /**
     * Resend an invitation email
     */
    public function resendInvitation(TeamInvitation $invitation): void
    {
        if (! $invitation->isPending()) {
            throw new Exception('Cannot resend invitation - invitation is not pending.');
        }

        // Update expiry time
        $invitation->update([
            'expires_at' => now()->addHours(48),
        ]);

        // Send email
        $this->sendInvitationEmail($invitation);
    }

    /**
     * Clean up expired invitations
     */
    public function cleanupExpiredInvitations(): int
    {
        $expiredCount = TeamInvitation::where('status', TeamInvitation::STATUS_PENDING)
            ->where('expires_at', '<', now())
            ->count();

        TeamInvitation::where('status', TeamInvitation::STATUS_PENDING)
            ->where('expires_at', '<', now())
            ->update(['status' => TeamInvitation::STATUS_EXPIRED]);

        return $expiredCount;
    }

    /**
     * Get invitation by token
     */
    public function getInvitationByToken(string $token): ?TeamInvitation
    {
        return TeamInvitation::where('token', $token)
            ->with(['team', 'invitedBy'])
            ->first();
    }
}
