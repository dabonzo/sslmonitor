<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DebugMenuAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if debug menu is enabled in configuration
        if (! config('debug_menu.menu_enabled')) {
            abort(403, 'Debug menu is disabled');
        }

        $user = $request->user();

        // User must be authenticated
        if (! $user) {
            abort(403, 'Authentication required for debug access');
        }

        // Get allowed users and roles from configuration
        $allowedUsersString = config('debug_menu.menu_users', '');
        $allowedUsers = array_filter(array_map('trim', explode(',', $allowedUsersString)));

        $allowedRolesString = config('debug_menu.menu_roles', 'OWNER,ADMIN');
        $allowedRoles = array_filter(array_map('trim', explode(',', $allowedRolesString)));

        $hasEmailAccess = in_array($user->email, $allowedUsers);
        $hasRoleAccess = in_array($user->primary_role, $allowedRoles);

        if (! $hasEmailAccess && ! $hasRoleAccess) {
            abort(403, 'Access denied - insufficient debug privileges');
        }

        // Log access if audit is enabled
        if (config('debug_menu.menu_audit', true)) {
            Log::info('Debug menu accessed', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_role' => $user->primary_role,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'path' => $request->path(),
                'method' => $request->method(),
                'access_type' => $hasEmailAccess ? 'email' : 'role',
                'timestamp' => now()->toDateTimeString(),
            ]);
        }

        return $next($request);
    }
}
