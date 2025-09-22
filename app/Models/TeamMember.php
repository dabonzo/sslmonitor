<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamMember extends Model
{
    public const ROLE_OWNER = 'OWNER';
    public const ROLE_ADMIN = 'ADMIN';
    public const ROLE_MANAGER = 'MANAGER';
    public const ROLE_VIEWER = 'VIEWER';

    protected $fillable = [
        'team_id',
        'user_id',
        'role',
        'joined_at',
        'invited_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    /**
     * Get all available roles
     */
    public static function getRoles(): array
    {
        return [
            self::ROLE_OWNER,
            self::ROLE_ADMIN,
            self::ROLE_MANAGER,
            self::ROLE_VIEWER,
        ];
    }

    /**
     * Get role descriptions
     */
    public static function getRoleDescriptions(): array
    {
        return [
            self::ROLE_OWNER => 'Full access - manage team, websites, and settings',
            self::ROLE_ADMIN => 'Manage websites and email settings (cannot manage team)',
            self::ROLE_MANAGER => 'Add/edit websites and view settings',
            self::ROLE_VIEWER => 'View-only access to websites and settings',
        ];
    }

    /**
     * Permission checking methods
     */
    public function canManageTeam(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    public function canManageWebsites(): bool
    {
        return in_array($this->role, [self::ROLE_OWNER, self::ROLE_ADMIN, self::ROLE_MANAGER]);
    }

    public function canManageEmailSettings(): bool
    {
        return in_array($this->role, [self::ROLE_OWNER, self::ROLE_ADMIN]);
    }

    public function canViewWebsites(): bool
    {
        return true; // All roles can view websites
    }

    public function canViewSettings(): bool
    {
        return true; // All roles can view settings
    }

    public function canInviteMembers(): bool
    {
        return in_array($this->role, [self::ROLE_OWNER, self::ROLE_ADMIN]);
    }

    public function canRemoveMembers(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isManager(): bool
    {
        return $this->role === self::ROLE_MANAGER;
    }

    public function isViewer(): bool
    {
        return $this->role === self::ROLE_VIEWER;
    }
}
