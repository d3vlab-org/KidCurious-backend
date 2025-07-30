<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Service API Routes
|--------------------------------------------------------------------------
|
| Here are the API routes for the auth-service package. These routes
| handle authentication, user management, and authorization.
|
*/

Route::prefix('auth')->group(function () {

    // Public routes (no authentication required)
    Route::post('/validate-token', function () {
        return response()->json(['message' => 'Token validation endpoint']);
    });

    Route::get('/user-types', function () {
        return response()->json([
            'user_types' => config('auth-service.user_types'),
            'scopes' => config('auth-service.scopes')
        ]);
    });

    // Protected routes (require authentication)
    Route::middleware(['supabase.auth'])->group(function () {

        Route::get('/me', function () {
            return response()->json([
                'user' => request()->get('auth_user'),
                'context' => request()->get('auth_context')
            ]);
        });

        Route::post('/refresh-context', function () {
            return response()->json([
                'context' => request()->get('auth_context')
            ]);
        });

        // Parent-only routes
        Route::middleware(['oauth.scopes:parent'])->group(function () {

            Route::get('/children', function () {
                return response()->json(['message' => 'Get children endpoint']);
            });

            Route::post('/children', function () {
                return response()->json(['message' => 'Create child endpoint']);
            });

        });

        // Admin-only routes
        Route::middleware(['oauth.scopes:admin'])->group(function () {

            Route::get('/users', function () {
                return response()->json(['message' => 'List users endpoint']);
            });

            Route::post('/users/{userId}/rotate-keys', function ($userId) {
                return response()->json(['message' => "Rotate keys for user {$userId}"]);
            });

        });

    });

});

// Health check endpoint for auth service
Route::get('/auth/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'auth-service',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0'
    ]);
});
