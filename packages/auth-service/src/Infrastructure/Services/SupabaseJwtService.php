<?php

namespace KidsQaAi\AuthService\Infrastructure\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use KidsQaAi\AuthService\Domain\Contracts\JwtServiceInterface;

class SupabaseJwtService implements JwtServiceInterface
{
    private string $jwtSecret;
    private string $expectedIssuer;

    public function __construct()
    {
        $this->jwtSecret = config('auth-service.supabase.jwt_secret');
        $this->expectedIssuer = config('auth-service.supabase.url');
    }

    /**
     * Decode and validate JWT token
     */
    public function decodeToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            return (array) $decoded;
        } catch (ExpiredException | SignatureInvalidException | \Exception $e) {
            return null;
        }
    }

    /**
     * Validate token signature and expiration
     */
    public function validateToken(string $token): bool
    {
        $decoded = $this->decodeToken($token);

        if (!$decoded) {
            return false;
        }

        // Check if token is expired
        if ($this->isTokenExpired($token)) {
            return false;
        }

        // Verify issuer if configured
        if ($this->expectedIssuer && !$this->verifyIssuer($token, $this->expectedIssuer)) {
            return false;
        }

        return true;
    }

    /**
     * Extract user ID from token
     */
    public function getUserIdFromToken(string $token): ?string
    {
        $decoded = $this->decodeToken($token);

        if (!$decoded) {
            return null;
        }

        return $decoded['sub'] ?? null;
    }

    /**
     * Extract scopes from token
     */
    public function getScopesFromToken(string $token): array
    {
        $decoded = $this->decodeToken($token);

        if (!$decoded) {
            return [];
        }

        // Supabase uses 'role' claim for user roles/scopes
        $role = $decoded['role'] ?? null;

        if ($role) {
            return [$role];
        }

        // Also check for custom scopes if present
        return $decoded['scopes'] ?? [];
    }

    /**
     * Check if token has required scopes
     */
    public function hasRequiredScopes(string $token, array $requiredScopes): bool
    {
        $tokenScopes = $this->getScopesFromToken($token);

        foreach ($requiredScopes as $requiredScope) {
            if (!in_array($requiredScope, $tokenScopes)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get token expiration time
     */
    public function getTokenExpiration(string $token): ?int
    {
        $decoded = $this->decodeToken($token);

        if (!$decoded) {
            return null;
        }

        return $decoded['exp'] ?? null;
    }

    /**
     * Check if token is expired
     */
    public function isTokenExpired(string $token): bool
    {
        $expiration = $this->getTokenExpiration($token);

        if (!$expiration) {
            return true;
        }

        return time() >= $expiration;
    }

    /**
     * Get token issuer
     */
    public function getTokenIssuer(string $token): ?string
    {
        $decoded = $this->decodeToken($token);

        if (!$decoded) {
            return null;
        }

        return $decoded['iss'] ?? null;
    }

    /**
     * Verify token issuer matches expected
     */
    public function verifyIssuer(string $token, string $expectedIssuer): bool
    {
        $issuer = $this->getTokenIssuer($token);

        if (!$issuer) {
            return false;
        }

        return $issuer === $expectedIssuer;
    }
}
