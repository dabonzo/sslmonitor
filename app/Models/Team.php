<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_members')
            ->withPivot(['role', 'joined_at', 'invited_by_user_id'])
            ->withTimestamps();
    }

    public function websites(): HasMany
    {
        return $this->hasMany(Website::class);
    }

    public function alertConfigurations(): HasMany
    {
        return $this->hasMany(AlertConfiguration::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class);
    }

    public function pendingInvitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class)->whereNull('accepted_at');
    }

    /**
     * Check if user is a member of this team
     */
    public function hasMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Get user's role in this team
     */
    public function getUserRole(User $user): ?string
    {
        $membership = $this->members()->where('user_id', $user->id)->first();
        return $membership?->pivot->role;
    }

    /**
     * Transfer team ownership to another user
     */
    public function transferOwnership(User $newOwner): void
    {
        // Check if new owner is a team member
        if (!$this->hasMember($newOwner)) {
            throw new \Exception('New owner must be a team member');
        }

        // Update the team creator
        $this->update(['created_by_user_id' => $newOwner->id]);

        // Update team member role to OWNER
        TeamMember::where('team_id', $this->id)
            ->where('user_id', $newOwner->id)
            ->update(['role' => TeamMember::ROLE_OWNER]);

        // Optionally downgrade previous owner to ADMIN
        TeamMember::where('team_id', $this->id)
            ->where('user_id', '!=', $newOwner->id)
            ->where('role', TeamMember::ROLE_OWNER)
            ->update(['role' => TeamMember::ROLE_ADMIN]);
    }

    /**
     * Delete team and clean up resources
     */
    public function deleteTeam(): void
    {
        // Transfer all team websites back to personal ownership of team creator
        $this->websites()->update([
            'team_id' => null,
            'user_id' => $this->created_by_user_id,
            'assigned_by_user_id' => null,
            'assigned_at' => null,
        ]);

        // Delete team (cascade will handle team_members, invitations, etc.)
        $this->delete();
    }

    /**
     * Check if user can delete this team
     */
    public function canBeDeletedBy(User $user): bool
    {
        $role = $this->getUserRole($user);
        return $role === TeamMember::ROLE_OWNER;
    }

    /**
     * Check if user can transfer this team
     */
    public function canBeTransferredBy(User $user): bool
    {
        $role = $this->getUserRole($user);
        return $role === TeamMember::ROLE_OWNER;
    }
}
