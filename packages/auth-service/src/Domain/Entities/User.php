<?php

namespace KidsQaAi\AuthService\Domain\Entities;

use DateTime;

class User
{
    public const TYPE_PARENT = 'parent';
    public const TYPE_CHILD = 'child';

    public const SCOPE_CHILD_READ = 'child.read';
    public const SCOPE_PARENT_WRITE = 'parent.write';

    private string $id;
    private string $email;
    private string $name;
    private string $type;
    private array $scopes;
    private ?string $parentId;
    private array $children;
    private DateTime $createdAt;
    private DateTime $updatedAt;
    private ?DateTime $lastLoginAt;
    private array $metadata;

    public function __construct(
        string $id,
        string $email,
        string $name,
        string $type,
        array $scopes = [],
        ?string $parentId = null,
        array $children = [],
        ?DateTime $createdAt = null,
        ?DateTime $updatedAt = null,
        ?DateTime $lastLoginAt = null,
        array $metadata = []
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->name = $name;
        $this->type = $type;
        $this->scopes = $scopes;
        $this->parentId = $parentId;
        $this->children = $children;
        $this->createdAt = $createdAt ?? new DateTime();
        $this->updatedAt = $updatedAt ?? new DateTime();
        $this->lastLoginAt = $lastLoginAt;
        $this->metadata = $metadata;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes);
    }

    public function hasScopes(array $requiredScopes): bool
    {
        return empty(array_diff($requiredScopes, $this->scopes));
    }

    public function getParentId(): ?string
    {
        return $this->parentId;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function isParent(): bool
    {
        return $this->type === self::TYPE_PARENT;
    }

    public function isChild(): bool
    {
        return $this->type === self::TYPE_CHILD;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function getLastLoginAt(): ?DateTime
    {
        return $this->lastLoginAt;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function updateLastLogin(): void
    {
        $this->lastLoginAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    public function addScope(string $scope): void
    {
        if (!$this->hasScope($scope)) {
            $this->scopes[] = $scope;
            $this->updatedAt = new DateTime();
        }
    }

    public function removeScope(string $scope): void
    {
        $this->scopes = array_values(array_filter($this->scopes, fn($s) => $s !== $scope));
        $this->updatedAt = new DateTime();
    }

    public function addChild(string $childId): void
    {
        if (!in_array($childId, $this->children)) {
            $this->children[] = $childId;
            $this->updatedAt = new DateTime();
        }
    }

    public function removeChild(string $childId): void
    {
        $this->children = array_values(array_filter($this->children, fn($id) => $id !== $childId));
        $this->updatedAt = new DateTime();
    }

    public function updateMetadata(array $metadata): void
    {
        $this->metadata = array_merge($this->metadata, $metadata);
        $this->updatedAt = new DateTime();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
            'type' => $this->type,
            'scopes' => $this->scopes,
            'parent_id' => $this->parentId,
            'children' => $this->children,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
            'last_login_at' => $this->lastLoginAt?->format('Y-m-d H:i:s'),
            'metadata' => $this->metadata,
        ];
    }
}
