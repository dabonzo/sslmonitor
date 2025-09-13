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
        'owner_id',
    ];

    /**
     * Get the team owner
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get all team members (including owner)
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_members')
            ->withPivot(['role', 'permissions'])
            ->withTimestamps();
    }

    /**
     * Get team member records
     */
    public function teamMembers(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    /**
     * Get team websites
     */
    public function websites(): HasMany
    {
        return $this->hasMany(Website::class);
    }

    /**
     * Get team email settings
     */
    public function emailSettings(): HasMany
    {
        return $this->hasMany(EmailSettings::class);
    }

    /**
     * Get active email settings for this team
     */
    public function activeEmailSettings(): ?EmailSettings
    {
        return $this->emailSettings()->where('is_active', true)->first();
    }

    /**
     * Get team invitations
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class);
    }

    /**
     * Get pending invitations
     */
    public function pendingInvitations(): HasMany
    {
        return $this->invitations()->where('status', TeamInvitation::STATUS_PENDING);
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
        $member = $this->members()->where('user_id', $user->id)->first();

        return $member?->pivot->role;
    }

    /**
     * Check if user has permission for this team
     */
    public function userHasPermission(User $user, string $permission): bool
    {
        $role = $this->getUserRole($user);

        if (! $role) {
            return false;
        }

        return $this->roleHasPermission($role, $permission);
    }

    /**
     * Check if role has permission
     */
    private function roleHasPermission(string $role, string $permission): bool
    {
        $permissions = [
            'owner' => [
                'view_websites', 'add_websites', 'edit_websites', 'delete_websites',
                'view_settings', 'edit_settings', 'manage_team', 'delete_team',
            ],
            'admin' => [
                'view_websites', 'add_websites', 'edit_websites', 'delete_websites',
                'view_settings', 'edit_settings',
            ],
            'manager' => [
                'view_websites', 'add_websites', 'edit_websites', 'delete_websites',
                'view_settings',
            ],
            'viewer' => [
                'view_websites', 'view_settings',
            ],
        ];

        return in_array($permission, $permissions[$role] ?? []);
    }
}
