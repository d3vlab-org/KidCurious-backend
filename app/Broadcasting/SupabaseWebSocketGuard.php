<?php

namespace App\Broadcasting;

use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use BeyondCode\LaravelWebSockets\WebSockets\Exceptions\UnknownAppKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use KidsQaAi\AuthService\Infrastructure\Services\SupabaseJwtService;
use Pusher\Pusher;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class SupabaseWebSocketGuard
{
    protected SupabaseJwtService $jwtService;

    public function __construct(SupabaseJwtService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    /**
     * Authenticate WebSocket connection using Supabase JWT token
     */
    public function authenticate(ConnectionInterface $connection, MessageInterface $message): bool
    {
        try {
            $payload = json_decode($message->getPayload(), true);

            // Extract auth data from the connection request
            $authData = $payload['auth'] ?? null;

            if (!$authData) {
                Log::warning('WebSocket authentication failed: No auth data provided');
                return false;
            }

            // Extract token from auth data
            $token = $this->extractTokenFromAuth($authData);

            if (!$token) {
                Log::warning('WebSocket authentication failed: No token provided');
                return false;
            }

            // Validate the JWT token
            if (!$this->jwtService->validateToken($token)) {
                Log::warning('WebSocket authentication failed: Invalid token');
                return false;
            }

            // Get user ID from token
            $userId = $this->jwtService->getUserIdFromToken($token);

            if (!$userId) {
                Log::warning('WebSocket authentication failed: Could not extract user ID from token');
                return false;
            }

            // Store user information in connection
            $connection->userId = $userId;
            $connection->authenticated = true;

            Log::info("WebSocket authentication successful for user: {$userId}");

            return true;

        } catch (\Exception $e) {
            Log::error('WebSocket authentication error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Extract token from various auth formats
     */
    protected function extractTokenFromAuth($authData): ?string
    {
        // Handle different auth formats
        if (is_string($authData)) {
            // Direct token string
            return $this->cleanToken($authData);
        }

        if (is_array($authData)) {
            // Auth object with token field
            $token = $authData['token'] ?? $authData['access_token'] ?? $authData['jwt'] ?? null;
            return $token ? $this->cleanToken($token) : null;
        }

        return null;
    }

    /**
     * Clean token by removing Bearer prefix if present
     */
    protected function cleanToken(string $token): string
    {
        return str_replace('Bearer ', '', $token);
    }

    /**
     * Check if connection is authenticated
     */
    public function isAuthenticated(ConnectionInterface $connection): bool
    {
        return isset($connection->authenticated) && $connection->authenticated === true;
    }

    /**
     * Get user ID from authenticated connection
     */
    public function getUserId(ConnectionInterface $connection): ?string
    {
        return $connection->userId ?? null;
    }

    /**
     * Authenticate channel subscription
     */
    public function authenticateChannelAccess(ConnectionInterface $connection, string $channelName): bool
    {
        if (!$this->isAuthenticated($connection)) {
            return false;
        }

        $userId = $this->getUserId($connection);

        // For private channels, ensure user can only access their own channels
        if (str_starts_with($channelName, 'private-user.')) {
            $channelUserId = str_replace('private-user.', '', $channelName);
            return $userId === $channelUserId;
        }

        // For presence channels, allow authenticated users
        if (str_starts_with($channelName, 'presence-')) {
            return true;
        }

        // For public channels, allow all authenticated users
        return true;
    }
}
