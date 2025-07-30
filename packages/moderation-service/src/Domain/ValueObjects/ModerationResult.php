<?php

namespace KidsQaAi\ModerationService\Domain\ValueObjects;

use KidsQaAi\ModerationService\Domain\Contracts\ModerationResult as ModerationResultInterface;

class ModerationResult implements ModerationResultInterface
{
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_NEEDS_REVIEW = 'needs_review';

    private string $status;
    private float $safetyScore;
    private array $issues;
    private string $filteredContent;
    private string $originalContent;
    private ?string $reason;

    public function __construct(
        string $status,
        float $safetyScore,
        array $issues,
        string $filteredContent,
        string $originalContent,
        ?string $reason = null
    ) {
        $this->status = $status;
        $this->safetyScore = max(0.0, min(1.0, $safetyScore)); // Clamp between 0 and 1
        $this->issues = $issues;
        $this->filteredContent = $filteredContent;
        $this->originalContent = $originalContent;
        $this->reason = $reason;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function needsReview(): bool
    {
        return $this->status === self::STATUS_NEEDS_REVIEW;
    }

    public function getSafetyScore(): float
    {
        return $this->safetyScore;
    }

    public function getIssues(): array
    {
        return $this->issues;
    }

    public function getFilteredContent(): string
    {
        return $this->filteredContent;
    }

    public function getOriginalContent(): string
    {
        return $this->originalContent;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'safety_score' => $this->safetyScore,
            'issues' => $this->issues,
            'filtered_content' => $this->filteredContent,
            'original_content' => $this->originalContent,
            'reason' => $this->reason,
            'is_approved' => $this->isApproved(),
            'is_rejected' => $this->isRejected(),
            'needs_review' => $this->needsReview(),
        ];
    }

    public static function approved(string $content, float $safetyScore = 1.0): self
    {
        return new self(
            self::STATUS_APPROVED,
            $safetyScore,
            [],
            $content,
            $content
        );
    }

    public static function rejected(string $content, array $issues, string $reason): self
    {
        return new self(
            self::STATUS_REJECTED,
            0.0,
            $issues,
            '',
            $content,
            $reason
        );
    }

    public static function flaggedForReview(string $content, string $filteredContent, array $issues, float $safetyScore): self
    {
        return new self(
            self::STATUS_NEEDS_REVIEW,
            $safetyScore,
            $issues,
            $filteredContent,
            $content,
            'Content flagged for manual review'
        );
    }
}
