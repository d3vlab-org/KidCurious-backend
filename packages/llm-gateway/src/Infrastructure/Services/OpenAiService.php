<?php

namespace KidsQaAi\LlmGateway\Infrastructure\Services;

use OpenAI\Client;
use KidsQaAi\LlmGateway\Domain\Contracts\LlmServiceInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class OpenAiService implements LlmServiceInterface
{
    private Client $client;
    private array $config;
    private int $requestCount = 0;

    public function __construct()
    {
        $this->client = \OpenAI::client(config('llm-gateway.openai.api_key'));
        $this->config = config('llm-gateway.openai', []);
    }

    public function generateAnswer(string $question, array $context = []): string
    {
        try {
            $this->requestCount++;

            $systemPrompt = $this->buildSystemPrompt($context);
            $userPrompt = $this->buildUserPrompt($question, $context);

            $response = $this->client->chat()->create([
                'model' => $this->config['model'] ?? 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'max_tokens' => $this->config['max_tokens'] ?? 500,
                'temperature' => $this->config['temperature'] ?? 0.7,
                'top_p' => $this->config['top_p'] ?? 1.0,
                'frequency_penalty' => $this->config['frequency_penalty'] ?? 0.0,
                'presence_penalty' => $this->config['presence_penalty'] ?? 0.0,
            ]);

            $answer = $response->choices[0]->message->content ?? '';

            if (empty($answer)) {
                throw new \Exception('Empty response from OpenAI');
            }

            Log::info('OpenAI answer generated', [
                'question_length' => strlen($question),
                'answer_length' => strlen($answer),
                'model' => $this->config['model'] ?? 'gpt-3.5-turbo',
            ]);

            return trim($answer);

        } catch (\Exception $e) {
            Log::error('OpenAI answer generation failed', [
                'question' => $question,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \Exception('Failed to generate answer: ' . $e->getMessage());
        }
    }

    public function generateChildFriendlyAnswer(string $question, int $childAge = null): string
    {
        $context = [
            'child_age' => $childAge,
            'child_friendly' => true,
        ];

        return $this->generateAnswer($question, $context);
    }

    public function isAvailable(): bool
    {
        try {
            // Check if we can make a simple request to OpenAI
            $cacheKey = 'openai_availability_check';

            return Cache::remember($cacheKey, 300, function () {
                try {
                    $response = $this->client->chat()->create([
                        'model' => 'gpt-3.5-turbo',
                        'messages' => [
                            ['role' => 'user', 'content' => 'Hello'],
                        ],
                        'max_tokens' => 5,
                    ]);

                    return !empty($response->choices[0]->message->content);
                } catch (\Exception $e) {
                    Log::warning('OpenAI availability check failed', [
                        'error' => $e->getMessage(),
                    ]);
                    return false;
                }
            });

        } catch (\Exception $e) {
            return false;
        }
    }

    public function getProviderName(): string
    {
        return 'OpenAI';
    }

    public function getUsageStats(): array
    {
        return [
            'provider' => $this->getProviderName(),
            'requests_made' => $this->requestCount,
            'model' => $this->config['model'] ?? 'gpt-3.5-turbo',
            'is_available' => $this->isAvailable(),
        ];
    }

    private function buildSystemPrompt(array $context = []): string
    {
        $basePrompt = "You are KidCurious, a helpful AI assistant designed specifically for children. Your role is to provide safe, educational, and age-appropriate answers to children's questions.

IMPORTANT GUIDELINES:
- Always use simple, clear language that children can understand
- Be encouraging and positive in your responses
- Avoid scary, violent, or inappropriate content
- If a question is inappropriate, gently redirect to a more suitable topic
- Keep answers concise but informative (2-3 sentences for young children, up to 5 sentences for older children)
- Use examples and analogies that children can relate to
- Encourage curiosity and learning
- Never provide personal information or ask for personal details
- If you don't know something, it's okay to say so and suggest asking a parent or teacher";

        if (isset($context['child_age']) && $context['child_age']) {
            $age = $context['child_age'];
            if ($age <= 6) {
                $basePrompt .= "\n\nThe child asking this question is around {$age} years old, so use very simple words and short sentences. Think of how you would explain things to a preschooler.";
            } elseif ($age <= 10) {
                $basePrompt .= "\n\nThe child asking this question is around {$age} years old, so use elementary school level language and concepts they would understand.";
            } else {
                $basePrompt .= "\n\nThe child asking this question is around {$age} years old, so you can use slightly more advanced concepts while still keeping it age-appropriate.";
            }
        }

        return $basePrompt;
    }

    private function buildUserPrompt(string $question, array $context = []): string
    {
        $prompt = "Please answer this question from a curious child: \"{$question}\"";

        if (isset($context['child_age']) && $context['child_age']) {
            $prompt .= "\n\nRemember, this is from a {$context['child_age']}-year-old child.";
        }

        return $prompt;
    }
}
