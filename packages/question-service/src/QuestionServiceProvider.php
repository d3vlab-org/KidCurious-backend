<?php

namespace KidsQaAi\QuestionService;

use Illuminate\Support\ServiceProvider;
use KidsQaAi\QuestionService\Domain\Contracts\QuestionRepositoryInterface;
use KidsQaAi\QuestionService\Domain\Contracts\AnswerRepositoryInterface;
use KidsQaAi\QuestionService\Infrastructure\Repositories\QuestionRepository;
use KidsQaAi\QuestionService\Infrastructure\Repositories\AnswerRepository;

class QuestionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind interfaces to implementations
        $this->app->bind(QuestionRepositoryInterface::class, QuestionRepository::class);
        $this->app->bind(AnswerRepositoryInterface::class, AnswerRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/question-service.php' => config_path('question-service.php'),
        ], 'question-service-config');

        // Load routes
        $this->loadRoutesFrom(__DIR__.'/Presentation/routes/api.php');
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            QuestionRepositoryInterface::class,
            AnswerRepositoryInterface::class,
        ];
    }
}
