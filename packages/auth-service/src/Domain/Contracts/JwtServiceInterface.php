<?php

namespace KidsQaAi\AuthService\Domain\Contracts;

use KidsQaAi\AuthService\Domain\ValueObjects\AuthToken;

interface JwtServiceInterface
{
    /**
     * Decode and validate JWT token
     */
    public function decodeToken(string $token): ?array;

    /**
     * Validate token signature and expiration
     */
    public function validateToken(string $token): bool;

    /**
     * Extract user ID from token
     */
    public function getUserIdFromToken(string $token): ?string;

    /**
     * Extract scopes from token
     */
    public function getScopesFromToken(string $token): array;

    /**
     * Check if token has required scopes
     */
    public function hasRequiredScopes(string $token, array $requiredScopes): bool;

    /**
     * Get token expiration time
     */
    public function getTokenExpiration(string $token): ?int;

    /**
     * Check if token is expired
     */
    public function isTokenExpired(string $token): bool;

    /**
     * Get token issuer
     */
    public function getTokenIssuer(string $token): ?string;

    /**
     * Verify token issuer matches expected
     */
    public function verifyIssuer(string $token, string $expectedIssuer): bool;
}
