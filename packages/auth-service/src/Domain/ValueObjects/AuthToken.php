<?php

namespace KidsQaAi\AuthService\Domain\ValueObjects;

use DateTime;

class AuthToken
{
    private string $token;
    private string $type;
    private DateTime $expiresAt;
    private array $scopes;
    private string $userId;
    private string $issuer;

    public function __construct(
        string $token,
        string $type = 'Bearer',
        ?DateTime $expiresAt = null,
        array $scopes = [],
        string $userId = '',
        string $issuer = ''
    ) {
        $this->token = $token;
        $this->type = $type;
        $this->expiresAt = $expiresAt ?? new DateTime('+1 hour');
        $this->scopes = $scopes;
        $this->userId = $userId;
        $this->issuer = $issuer;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getExpiresAt(): DateTime
    {
        return $this->expiresAt;
    }

    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getIssuer(): string
    {
        return $this->issuer;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new DateTime();
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes);
    }

    public function hasScopes(array $requiredScopes): bool
    {
        return empty(array_diff($requiredScopes, $this->scopes));
    }

    public function getAuthorizationHeader(): string
    {
        return $this->type . ' ' . $this->token;
    }

    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'type' => $this->type,
            'expires_at' => $this->expiresAt->format('Y-m-d H:i:s'),
            'scopes' => $this->scopes,
            'user_id' => $this->userId,
            'issuer' => $this->issuer,
        ];
    }
}
