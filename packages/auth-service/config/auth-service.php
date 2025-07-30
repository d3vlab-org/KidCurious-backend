<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Supabase Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Supabase Auth integration
    |
    */
    'supabase' => [
        'url' => env('SUPABASE_URL'),
        'anon_key' => env('SUPABASE_ANON_KEY'),
        'service_role_key' => env('SUPABASE_SERVICE_ROLE_KEY'),
        'jwt_secret' => env('SUPABASE_JWT_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | JWT Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for JWT token validation
    |
    */
    'jwt' => [
        'algorithm' => 'HS256',
        'leeway' => 60, // seconds
        'verify_issuer' => true,
        'verify_audience' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | User Types and Scopes
    |--------------------------------------------------------------------------
    |
    | Define available user types and their default scopes
    |
    */
    'user_types' => [
        'parent' => [
            'scopes' => ['parent', 'child_management', 'ask_questions', 'view_child_activity'],
            'description' => 'Parent user with child management capabilities',
        ],
        'child' => [
            'scopes' => ['child', 'ask_questions'],
            'description' => 'Child user with limited capabilities',
        ],
        'moderator' => [
            'scopes' => ['moderator', 'moderate_content', 'view_reports'],
            'description' => 'Content moderator with moderation capabilities',
        ],
        'admin' => [
            'scopes' => ['admin', 'moderate_content', 'admin_access', 'user_management'],
            'description' => 'Administrator with full access',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Available Scopes
    |--------------------------------------------------------------------------
    |
    | Define all available OAuth2 scopes in the system
    |
    */
    'scopes' => [
        'parent' => 'Parent user access',
        'child' => 'Child user access',
        'child_management' => 'Manage child users',
        'ask_questions' => 'Ask questions to AI',
        'view_child_activity' => 'View child activity and history',
        'moderate_content' => 'Moderate user-generated content',
        'view_reports' => 'View moderation reports',
        'admin_access' => 'Administrative access',
        'user_management' => 'Manage users',
        'api_access' => 'API access',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limiting configuration for different user types
    |
    */
    'rate_limits' => [
        'parent' => [
            'questions_per_hour' => 100,
            'api_requests_per_minute' => 60,
        ],
        'child' => [
            'questions_per_hour' => 20,
            'api_requests_per_minute' => 30,
        ],
        'guest' => [
            'api_requests_per_minute' => 10,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Security-related configuration
    |
    */
    'security' => [
        'token_expiry_buffer' => 300, // 5 minutes buffer before token expiry
        'max_failed_attempts' => 5,
        'lockout_duration' => 900, // 15 minutes
        'require_email_verification' => true,
        'password_reset_expiry' => 3600, // 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Authentication and authorization logging configuration
    |
    */
    'logging' => [
        'log_successful_auth' => env('AUTH_LOG_SUCCESS', false),
        'log_failed_auth' => env('AUTH_LOG_FAILURES', true),
        'log_scope_violations' => env('AUTH_LOG_SCOPE_VIOLATIONS', true),
        'log_channel' => env('AUTH_LOG_CHANNEL', 'stack'),
    ],
];
