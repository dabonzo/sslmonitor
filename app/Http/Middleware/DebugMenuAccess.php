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
        // TEMPORARY BYPASS: Always allow debug menu for testing
        // if (!config('debug_menu.menu_enabled')) {
        //     abort(403, 'Debug menu is disabled');
        // }

        $user = $request->user();

        // User must be authenticated
        if (! $user) {
            abort(403, 'Authentication required for debug access');
        }

        // TEMPORARY BYPASS: Hardcode allowed users for testing
        $allowedUsers = ['bonzo@konjscina.com'];
        $allowedRoles = ['OWNER', 'ADMIN'];

        $hasEmailAccess = in_array($user->email, $allowedUsers);
        $hasRoleAccess = in_array($user->primary_role, $allowedRoles);

        if (! $hasEmailAccess && ! $hasRoleAccess) {
            abort(403, 'Access denied - insufficient debug privileges');
        }

        // TEMPORARY BYPASS: Always log for testing
        if (true) {
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
