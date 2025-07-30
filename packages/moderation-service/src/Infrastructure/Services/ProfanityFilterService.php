<?php

namespace KidsQaAi\ModerationService\Infrastructure\Services;

use KidsQaAi\ModerationService\Domain\Contracts\ModerationServiceInterface;
use KidsQaAi\ModerationService\Domain\Contracts\ModerationResult as ModerationResultInterface;
use KidsQaAi\ModerationService\Domain\ValueObjects\ModerationResult;
use Illuminate\Support\Facades\Log;

class ProfanityFilterService implements ModerationServiceInterface
{
    private array $profanityWords;
    private array $inappropriateTopics;
    private array $config;

    public function __construct()
    {
        $this->config = config('moderation-service', []);
        $this->loadProfanityWords();
        $this->loadInappropriateTopics();
    }

    public function moderateContent(string $content): ModerationResultInterface
    {
        $issues = [];
        $safetyScore = 1.0;
        $filteredContent = $content;

        // Check for profanity
        if ($this->containsProfanity($content)) {
            $issues[] = 'profanity';
            $safetyScore -= 0.5;
            $filteredContent = $this->filterProfanity($content);
        }

        // Check for inappropriate topics
        $topicIssues = $this->checkInappropriateTopics($content);
        if (!empty($topicIssues)) {
            $issues = array_merge($issues, $topicIssues);
            $safetyScore -= 0.3 * count($topicIssues);
        }

        // Check for personal information patterns
        if ($this->containsPersonalInfo($content)) {
            $issues[] = 'personal_information';
            $safetyScore -= 0.4;
        }

        // Check content length and complexity
        if ($this->isTooComplex($content)) {
            $issues[] = 'too_complex';
            $safetyScore -= 0.2;
        }

        $safetyScore = max(0.0, $safetyScore);

        // Determine moderation result
        if ($safetyScore >= ($this->config['auto_approve_threshold'] ?? 0.8)) {
            return ModerationResult::approved($filteredContent, $safetyScore);
        } elseif ($safetyScore <= ($this->config['auto_reject_threshold'] ?? 0.3)) {
            return ModerationResult::rejected($content, $issues, 'Content contains inappropriate material for children');
        } else {
            return ModerationResult::flaggedForReview($content, $filteredContent, $issues, $safetyScore);
        }
    }

    public function isSafeForChildren(string $content): bool
    {
        $result = $this->moderateContent($content);
        return $result->isApproved();
    }

    public function getSafetyScore(string $content): float
    {
        $result = $this->moderateContent($content);
        return $result->getSafetyScore();
    }

    public function filterContent(string $content): string
    {
        $result = $this->moderateContent($content);
        return $result->getFilteredContent();
    }

    public function getContentIssues(string $content): array
    {
        $result = $this->moderateContent($content);
        return $result->getIssues();
    }

    public function containsProfanity(string $content): bool
    {
        $words = $this->extractWords($content);

        foreach ($words as $word) {
            if (in_array(strtolower($word), $this->profanityWords)) {
                return true;
            }
        }

        return false;
    }

    public function getProviderName(): string
    {
        return 'ProfanityFilter';
    }

    private function loadProfanityWords(): void
    {
        // Basic profanity word list - in production, this should be more comprehensive
        // and loaded from a configuration file or database
        $this->profanityWords = [
            'damn', 'hell', 'crap', 'stupid', 'idiot', 'dumb', 'hate',
            'kill', 'die', 'death', 'blood', 'violence', 'weapon',
            'gun', 'knife', 'hurt', 'pain', 'scary', 'monster',
            // Add more words as needed - this is a basic starter list
        ];
    }

    private function loadInappropriateTopics(): void
    {
        // Topics that might be inappropriate for children
        $this->inappropriateTopics = [
            'violence' => ['fight', 'war', 'battle', 'attack', 'violence', 'violent'],
            'adult_content' => ['sex', 'sexual', 'adult', 'mature', 'inappropriate'],
            'scary_content' => ['ghost', 'demon', 'devil', 'evil', 'nightmare', 'horror'],
            'dangerous_activities' => ['dangerous', 'poison', 'toxic', 'harmful', 'unsafe'],
            'personal_info' => ['address', 'phone', 'email', 'password', 'location', 'home'],
        ];
    }

    private function filterProfanity(string $content): string
    {
        $words = explode(' ', $content);
        $filteredWords = [];

        foreach ($words as $word) {
            $cleanWord = preg_replace('/[^\w]/', '', strtolower($word));
            if (in_array($cleanWord, $this->profanityWords)) {
                $filteredWords[] = str_repeat('*', strlen($word));
            } else {
                $filteredWords[] = $word;
            }
        }

        return implode(' ', $filteredWords);
    }

    private function checkInappropriateTopics(string $content): array
    {
        $issues = [];
        $contentLower = strtolower($content);

        foreach ($this->inappropriateTopics as $topic => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($contentLower, $keyword) !== false) {
                    $issues[] = $topic;
                    break; // Only add the topic once
                }
            }
        }

        return array_unique($issues);
    }

    private function containsPersonalInfo(string $content): bool
    {
        // Check for patterns that might be personal information
        $patterns = [
            '/\b\d{3}-\d{3}-\d{4}\b/', // Phone numbers
            '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', // Email addresses
            '/\b\d{1,5}\s+\w+\s+(street|st|avenue|ave|road|rd|drive|dr|lane|ln)\b/i', // Addresses
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    private function isTooComplex(string $content): bool
    {
        // Check if content is too complex for children
        $wordCount = str_word_count($content);
        $avgWordLength = strlen(str_replace(' ', '', $content)) / max(1, $wordCount);

        // Flag as too complex if average word length is very high
        // or if it contains too many complex sentences
        return $avgWordLength > 8 || $wordCount > 200;
    }

    private function extractWords(string $content): array
    {
        // Extract words, removing punctuation
        $words = preg_split('/\s+/', strtolower($content));
        return array_map(function($word) {
            return preg_replace('/[^\w]/', '', $word);
        }, $words);
    }
}
