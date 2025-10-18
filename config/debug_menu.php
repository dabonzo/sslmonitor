<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Debug Menu Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration controls the visibility and access permissions for the
    | debug menu in the SSL Monitor application. The debug menu provides
    | development and testing tools for authorized users.
    |
    */

    /**
     * Enable or disable the debug menu functionality.
     * When set to false, the debug menu will be hidden from all users.
     */
    'menu_enabled' => env('DEBUG_MENU_ENABLED', true),

    /**
     * Comma-separated list of email addresses that are allowed to access
     * the debug menu. These users will have access regardless of their role.
     * Example: 'admin@example.com,dev@example.com'
     */
    'menu_users' => env('DEBUG_MENU_USERS', 'bonzo@konjscina.com'),

    /**
     * Comma-separated list of user roles that are allowed to access the debug menu.
     * Users with these roles will have access to debug functionality.
     * Example: 'OWNER,ADMIN,MANAGER'
     */
    'menu_roles' => env('DEBUG_MENU_ROLES', 'OWNER,ADMIN'),

    /**
     * Enable or disable audit logging for debug menu access.
     * When enabled, all debug menu access will be logged for security purposes.
     */
    'menu_audit' => env('DEBUG_MENU_AUDIT', true),

    /**
     * Default expiration time for debug overrides in hours.
     * This controls how long SSL certificate overrides remain active.
     */
    'overrides_expire_hours' => env('DEBUG_OVERRIDES_EXPIRE_HOURS', 24),

    /**
     * Maximum number of overrides that can be active at once per user.
     * This prevents abuse of the debug override functionality.
     */
    'max_overrides_per_user' => env('DEBUG_MAX_OVERRIDES_PER_USER', 50),

    /**
     * Enable or disable the SSL certificate override functionality.
     * When disabled, users cannot modify SSL expiry dates for testing.
     */
    'ssl_overrides_enabled' => env('DEBUG_SSL_OVERRIDES_ENABLED', true),

    /**
     * Enable or disable real-time monitoring for debug operations.
     * When enabled, debug operations will trigger immediate monitoring checks.
     */
    'realtime_monitoring' => env('DEBUG_REALTIME_MONITORING', false),
];
