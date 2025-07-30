<?php

namespace KidsQaAi\LlmGateway\Domain\Contracts;

interface LlmServiceInterface
{
    /**
     * Generate an answer for a given question
     */
    public function generateAnswer(string $question, array $context = []): string;

    /**
     * Generate a child-friendly answer for a given question
     */
    public function generateChildFriendlyAnswer(string $question, int $childAge = null): string;

    /**
     * Check if the service is available
     */
    public function isAvailable(): bool;

    /**
     * Get the service name/provider
     */
    public function getProviderName(): string;

    /**
     * Get usage statistics
     */
    public function getUsageStats(): array;
}
