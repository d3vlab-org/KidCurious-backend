<?php

namespace KidsQaAi\ModerationService\Domain\Contracts;

interface ModerationServiceInterface
{
    /**
     * Moderate content and return moderation result
     */
    public function moderateContent(string $content): ModerationResult;

    /**
     * Check if content is safe for children
     */
    public function isSafeForChildren(string $content): bool;

    /**
     * Get content safety score (0-1, where 1 is completely safe)
     */
    public function getSafetyScore(string $content): float;

    /**
     * Filter and clean content, removing inappropriate parts
     */
    public function filterContent(string $content): string;

    /**
     * Get list of detected issues in content
     */
    public function getContentIssues(string $content): array;

    /**
     * Check if content contains profanity
     */
    public function containsProfanity(string $content): bool;

    /**
     * Get the service name/provider
     */
    public function getProviderName(): string;
}

interface ModerationResult
{
    /**
     * Whether the content is approved for children
     */
    public function isApproved(): bool;

    /**
     * Whether the content is rejected
     */
    public function isRejected(): bool;

    /**
     * Whether the content needs human review
     */
    public function needsReview(): bool;

    /**
     * Get the safety score (0-1)
     */
    public function getSafetyScore(): float;

    /**
     * Get list of detected issues
     */
    public function getIssues(): array;

    /**
     * Get the filtered/cleaned content
     */
    public function getFilteredContent(): string;

    /**
     * Get the original content
     */
    public function getOriginalContent(): string;

    /**
     * Get moderation reason if rejected
     */
    public function getReason(): ?string;
}
