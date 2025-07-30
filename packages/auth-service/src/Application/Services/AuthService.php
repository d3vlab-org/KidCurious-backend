<?php

namespace KidsQaAi\AuthService\Application\Services;

use KidsQaAi\AuthService\Domain\Contracts\AuthRepositoryInterface;
use KidsQaAi\AuthService\Domain\Contracts\JwtServiceInterface;
use KidsQaAi\AuthService\Domain\Entities\User;

class AuthService
{
    private AuthRepositoryInterface $authRepository;
    private JwtServiceInterface $jwtService;

    public function __construct(
        AuthRepositoryInterface $authRepository,
        JwtServiceInterface $jwtService
    ) {
        $this->authRepository = $authRepository;
        $this->jwtService = $jwtService;
    }

    /**
     * Authenticate user by validating JWT token
     */
    public function authenticateByToken(string $token): ?User
    {
        return $this->authRepository->validateToken($token);
    }

    /**
     * Register a new user with email and password
     */
    public function register(string $email, string $password, array $metadata = []): array
    {
        return $this->authRepository->register($email, $password, $metadata);
    }

    /**
     * Login user with email and password
     */
    public function login(string $email, string $password): array
    {
        return $this->authRepository->login($email, $password);
    }

    /**
     * Logout user by invalidating the token
     */
    public function logout(string $token): array
    {
        return $this->authRepository->logout($token);
    }

    /**
     * Get user by ID
     */
    public function getUserById(string $userId): ?User
    {
        return $this->authRepository->getUserById($userId);
    }

    /**
     * Check if user has required scopes
     */
    public function userHasScopes(User $user, array $requiredScopes): bool
    {
        return $this->authRepository->hasScopes($user, $requiredScopes);
    }

    /**
     * Check if token has required scopes
     */
    public function tokenHasScopes(string $token, array $requiredScopes): bool
    {
        return $this->jwtService->hasRequiredScopes($token, $requiredScopes);
    }

    /**
     * Create a new parent user
     */
    public function createParent(array $parentData): User
    {
        return $this->authRepository->createParent($parentData);
    }

    /**
     * Create a new child user
     */
    public function createChild(array $childData, string $parentId): User
    {
        // Verify parent exists and is actually a parent
        $parent = $this->authRepository->getUserById($parentId);
        if (!$parent || !$this->authRepository->isParent($parent)) {
            throw new \InvalidArgumentException('Invalid parent ID provided');
        }

        return $this->authRepository->createChild($childData, $parentId);
    }

    /**
     * Update user information
     */
    public function updateUser(string $userId, array $userData): User
    {
        return $this->authRepository->updateUser($userId, $userData);
    }

    /**
     * Delete user
     */
    public function deleteUser(string $userId): bool
    {
        return $this->authRepository->deleteUser($userId);
    }

    /**
     * Get children for a parent
     */
    public function getChildrenForParent(string $parentId): array
    {
        // Verify parent exists and is actually a parent
        $parent = $this->authRepository->getUserById($parentId);
        if (!$parent || !$this->authRepository->isParent($parent)) {
            return [];
        }

        return $this->authRepository->getChildrenForParent($parentId);
    }

    /**
     * Get parent for a child
     */
    public function getParentForChild(string $childId): ?User
    {
        return $this->authRepository->getParentForChild($childId);
    }

    /**
     * Check if user is a child
     */
    public function isChild(User $user): bool
    {
        return $this->authRepository->isChild($user);
    }

    /**
     * Check if user is a parent
     */
    public function isParent(User $user): bool
    {
        return $this->authRepository->isParent($user);
    }

    /**
     * Rotate API keys for user
     */
    public function rotateApiKeys(string $userId): array
    {
        return $this->authRepository->rotateApiKeys($userId);
    }

    /**
     * Validate JWT token
     */
    public function validateToken(string $token): bool
    {
        return $this->jwtService->validateToken($token);
    }

    /**
     * Get user ID from JWT token
     */
    public function getUserIdFromToken(string $token): ?string
    {
        return $this->jwtService->getUserIdFromToken($token);
    }

    /**
     * Get scopes from JWT token
     */
    public function getScopesFromToken(string $token): array
    {
        return $this->jwtService->getScopesFromToken($token);
    }

    /**
     * Check if JWT token is expired
     */
    public function isTokenExpired(string $token): bool
    {
        return $this->jwtService->isTokenExpired($token);
    }

    /**
     * Get token expiration time
     */
    public function getTokenExpiration(string $token): ?int
    {
        return $this->jwtService->getTokenExpiration($token);
    }

    /**
     * Verify token issuer
     */
    public function verifyTokenIssuer(string $token, string $expectedIssuer): bool
    {
        return $this->jwtService->verifyIssuer($token, $expectedIssuer);
    }

    /**
     * Authorize user action based on user type and scopes
     */
    public function authorizeUserAction(User $user, string $action, array $requiredScopes = []): bool
    {
        // Check if user has required scopes
        if (!empty($requiredScopes) && !$this->userHasScopes($user, $requiredScopes)) {
            return false;
        }

        // Additional authorization logic based on user type
        switch ($action) {
            case 'manage_children':
                return $this->isParent($user);

            case 'ask_questions':
                return $this->isChild($user) || $this->isParent($user);

            case 'view_child_activity':
                return $this->isParent($user);

            case 'moderate_content':
                return $this->userHasScopes($user, ['moderator', 'admin']);

            case 'admin_access':
                return $this->userHasScopes($user, ['admin']);

            default:
                return true; // Allow by default for unknown actions
        }
    }

    /**
     * Get user context for authorization decisions
     */
    public function getUserContext(string $token): ?array
    {
        $user = $this->authenticateByToken($token);
        if (!$user) {
            return null;
        }

        $context = [
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
            'user_type' => $this->isParent($user) ? 'parent' : ($this->isChild($user) ? 'child' : 'unknown'),
            'scopes' => $this->getScopesFromToken($token),
            'is_parent' => $this->isParent($user),
            'is_child' => $this->isChild($user),
        ];

        // Add parent/child relationships
        if ($this->isChild($user)) {
            $parent = $this->getParentForChild($user->getId());
            $context['parent_id'] = $parent?->getId();
        } elseif ($this->isParent($user)) {
            $children = $this->getChildrenForParent($user->getId());
            $context['children_ids'] = array_map(fn($child) => $child->getId(), $children);
        }

        return $context;
    }
}
