<?php

namespace KidsQaAi\ModerationService;

use Illuminate\Support\ServiceProvider;
use KidsQaAi\ModerationService\Domain\Contracts\ModerationServiceInterface;
use KidsQaAi\ModerationService\Infrastructure\Services\ProfanityFilterService;

class ModerationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind interface to implementation
        $this->app->bind(ModerationServiceInterface::class, ProfanityFilterService::class);

        // Register as singleton for better performance
        $this->app->singleton(ProfanityFilterService::class, function ($app) {
            return new ProfanityFilterService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/moderation-service.php' => config_path('moderation-service.php'),
        ], 'moderation-service-config');
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            ModerationServiceInterface::class,
            ProfanityFilterService::class,
        ];
    }
}
