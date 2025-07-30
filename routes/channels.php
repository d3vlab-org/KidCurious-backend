<?php

use Illuminate\Support\Facades\Broadcast;
use KidsQaAi\AuthService\Infrastructure\Services\SupabaseJwtService;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Public chat channel - all authenticated users can join
Broadcast::channel('chat', function ($user) {
    return $user !== null;
});

// Private user channels - only the specific user can join their own channel
Broadcast::channel('user.{userId}', function ($user, $userId) {
    // Extract user ID from Supabase JWT token
    $jwtService = app(SupabaseJwtService::class);

    // Get the token from the request
    $token = request()->bearerToken();

    if (!$token) {
        return false;
    }

    // Validate token and get user ID
    if (!$jwtService->validateToken($token)) {
        return false;
    }

    $tokenUserId = $jwtService->getUserIdFromToken($token);

    // User can only access their own private channel
    return $tokenUserId === $userId;
});

// Presence channel for active users (optional for future features)
Broadcast::channel('presence-online', function ($user) {
    if ($user) {
        return [
            'id' => $user->id ?? 'anonymous',
            'name' => $user->name ?? 'User',
            'joined_at' => now()->toISOString()
        ];
    }

    return false;
});
