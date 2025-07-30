<?php

namespace KidsQaAi\QuestionService\Presentation\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use KidsQaAi\QuestionService\Domain\Contracts\QuestionRepositoryInterface;
use KidsQaAi\QuestionService\Domain\Contracts\AnswerRepositoryInterface;
use KidsQaAi\QuestionService\Domain\Entities\Question;
use KidsQaAi\QuestionService\Domain\Entities\Answer;
use KidsQaAi\LlmGateway\Domain\Contracts\LlmServiceInterface;
use KidsQaAi\ModerationService\Domain\Contracts\ModerationServiceInterface;

class QuestionController extends Controller
{
    public function __construct(
        private QuestionRepositoryInterface $questionRepository,
        private AnswerRepositoryInterface $answerRepository,
        private LlmServiceInterface $llmService,
        private ModerationServiceInterface $moderationService
    ) {}

    /**
     * Submit a new question
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'question' => 'required|string|min:5|max:1000',
            ]);

            // Get authenticated user ID from the request
            $userId = $request->user()->getId();

            // Check rate limiting
            $recentQuestions = $this->questionRepository->getRecentQuestionsForUser($userId, 1);
            if (count($recentQuestions) >= config('question-service.rate_limiting.questions_per_hour', 10)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Rate limit exceeded. Please wait before asking another question.',
                ], 429);
            }

            // Create the question entity
            $question = new Question(
                id: Str::uuid()->toString(),
                userId: $userId,
                questionText: $validated['question']
            );

            // Store the question
            $this->questionRepository->create($question);

            // Process the question asynchronously (this would typically be a job)
            $this->processQuestionAsync($question);

            return response()->json([
                'success' => true,
                'data' => [
                    'question_id' => $question->getId(),
                    'status' => $question->getStatus(),
                    'message' => 'Question submitted successfully. We\'ll have an answer for you soon!',
                ],
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid question format.',
                'details' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Question submission failed', [
                'user_id' => $request->user()?->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to submit question. Please try again.',
            ], 500);
        }
    }

    /**
     * Get questions for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->getId();
            $limit = min($request->get('limit', 20), 50);
            $offset = $request->get('offset', 0);

            $questions = $this->questionRepository->getQuestionsForUser($userId, $limit, $offset);

            // Get answers for questions that have them
            $questionsWithAnswers = [];
            foreach ($questions as $question) {
                $questionData = $question->toArray();

                if ($question->getAnswerId()) {
                    $answer = $this->answerRepository->findById($question->getAnswerId());
                    if ($answer && $answer->isApproved()) {
                        $questionData['answer'] = [
                            'id' => $answer->getId(),
                            'text' => $answer->getAnswerText(),
                            'created_at' => $answer->getCreatedAt()->format('Y-m-d H:i:s'),
                        ];
                    }
                }

                $questionsWithAnswers[] = $questionData;
            }

            return response()->json([
                'success' => true,
                'data' => $questionsWithAnswers,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to fetch questions', [
                'user_id' => $request->user()?->getId(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch questions.',
            ], 500);
        }
    }

    /**
     * Get a specific question with its answer
     */
    public function show(Request $request, string $questionId): JsonResponse
    {
        try {
            $question = $this->questionRepository->findById($questionId);

            if (!$question) {
                return response()->json([
                    'success' => false,
                    'error' => 'Question not found.',
                ], 404);
            }

            // Check if the question belongs to the authenticated user
            if ($question->getUserId() !== $request->user()->getId()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized access to question.',
                ], 403);
            }

            $questionData = $question->toArray();

            // Get answer if available
            if ($question->getAnswerId()) {
                $answer = $this->answerRepository->findById($question->getAnswerId());
                if ($answer && $answer->isApproved()) {
                    $questionData['answer'] = [
                        'id' => $answer->getId(),
                        'text' => $answer->getAnswerText(),
                        'created_at' => $answer->getCreatedAt()->format('Y-m-d H:i:s'),
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $questionData,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to fetch question', [
                'question_id' => $questionId,
                'user_id' => $request->user()?->getId(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch question.',
            ], 500);
        }
    }

    /**
     * Process question asynchronously (placeholder for job dispatch)
     */
    private function processQuestionAsync(Question $question): void
    {
        // This would typically dispatch a job to process the question
        // For now, we'll call the processing directly
        // In production, this should be: ProcessQuestionJob::dispatch($question);

        try {
            // Mark question as processing
            $question->markAsProcessing();
            $this->questionRepository->update($question);

            // TODO: Integrate with LLM Gateway and Moderation Service
            // This will be implemented in the next steps

        } catch (\Exception $e) {
            \Log::error('Failed to process question', [
                'question_id' => $question->getId(),
                'error' => $e->getMessage(),
            ]);

            $question->markAsFailed();
            $this->questionRepository->update($question);
        }
    }
}
