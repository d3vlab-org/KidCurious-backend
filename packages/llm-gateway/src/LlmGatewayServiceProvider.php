<?php

namespace KidsQaAi\LlmGateway;

use Illuminate\Support\ServiceProvider;
use KidsQaAi\LlmGateway\Domain\Contracts\LlmServiceInterface;
use KidsQaAi\LlmGateway\Infrastructure\Services\OpenAiService;

class LlmGatewayServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind interface to implementation
        $this->app->bind(LlmServiceInterface::class, OpenAiService::class);

        // Register as singleton for better performance
        $this->app->singleton(OpenAiService::class, function ($app) {
            return new OpenAiService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/llm-gateway.php' => config_path('llm-gateway.php'),
        ], 'llm-gateway-config');
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            LlmServiceInterface::class,
            OpenAiService::class,
        ];
    }
}
