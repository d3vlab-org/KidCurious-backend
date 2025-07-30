<?php

namespace KidsQaAi\AuthService\Domain\Contracts;

use KidsQaAi\AuthService\Domain\Entities\User;
use KidsQaAi\AuthService\Domain\ValueObjects\AuthToken;

interface AuthRepositoryInterface
{
    /**
     * Register a new user with email and password
     */
    public function register(string $email, string $password, array $metadata = []): array;

    /**
     * Login user with email and password
     */
    public function login(string $email, string $password): array;

    /**
     * Logout user by invalidating the token
     */
    public function logout(string $token): array;

    /**
     * Validate JWT token and return user information
     */
    public function validateToken(string $token): ?User;

    /**
     * Get user by ID
     */
    public function getUserById(string $userId): ?User;

    /**
     * Check if user has required OAuth2 scopes
     */
    public function hasScopes(User $user, array $requiredScopes): bool;

    /**
     * Create a new child user
     */
    public function createChild(array $childData, string $parentId): User;

    /**
     * Create a new parent user
     */
    public function createParent(array $parentData): User;

    /**
     * Update user information
     */
    public function updateUser(string $userId, array $userData): User;

    /**
     * Delete user
     */
    public function deleteUser(string $userId): bool;

    /**
     * Get children for a parent
     */
    public function getChildrenForParent(string $parentId): array;

    /**
     * Check if user is a child
     */
    public function isChild(User $user): bool;

    /**
     * Check if user is a parent
     */
    public function isParent(User $user): bool;

    /**
     * Get parent for a child
     */
    public function getParentForChild(string $childId): ?User;

    /**
     * Rotate API keys for user
     */
    public function rotateApiKeys(string $userId): array;
}
