<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamMember extends Model
{
    protected $fillable = [
        'team_id',
        'user_id',
        'role',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    // Role constants
    const ROLE_OWNER = 'owner';

    const ROLE_ADMIN = 'admin';

    const ROLE_MANAGER = 'manager';

    const ROLE_VIEWER = 'viewer';

    /**
     * Get the team
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if member has specific permission
     */
    public function hasPermission(string $permission): bool
    {
        return $this->team->userHasPermission($this->user, $permission);
    }

    /**
     * Get all available roles
     */
    public static function getAvailableRoles(): array
    {
        return [
            self::ROLE_OWNER => 'Owner',
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_MANAGER => 'Manager',
            self::ROLE_VIEWER => 'Viewer',
        ];
    }
}
