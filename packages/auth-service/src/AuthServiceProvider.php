<?php

namespace KidsQaAi\AuthService;

use Illuminate\Support\ServiceProvider;
use KidsQaAi\AuthService\Application\Services\AuthService;
use KidsQaAi\AuthService\Application\Services\JwtService;
use KidsQaAi\AuthService\Domain\Contracts\AuthRepositoryInterface;
use KidsQaAi\AuthService\Domain\Contracts\JwtServiceInterface;
use KidsQaAi\AuthService\Infrastructure\Repositories\SupabaseAuthRepository;
use KidsQaAi\AuthService\Infrastructure\Services\SupabaseJwtService;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind interfaces to implementations
        $this->app->bind(AuthRepositoryInterface::class, SupabaseAuthRepository::class);
        $this->app->bind(JwtServiceInterface::class, SupabaseJwtService::class);

        // Register main auth service
        $this->app->singleton(AuthService::class, function ($app) {
            return new AuthService(
                $app->make(AuthRepositoryInterface::class),
                $app->make(JwtServiceInterface::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/auth-service.php' => config_path('auth-service.php'),
        ], 'auth-service-config');

        // Load routes
        $this->loadRoutesFrom(__DIR__.'/Presentation/routes/api.php');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Register middleware
        $this->app['router']->aliasMiddleware('supabase.auth', \KidsQaAi\AuthService\Presentation\Middleware\SupabaseAuthMiddleware::class);
        $this->app['router']->aliasMiddleware('oauth.scopes', \KidsQaAi\AuthService\Presentation\Middleware\OAuthScopesMiddleware::class);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            AuthRepositoryInterface::class,
            JwtServiceInterface::class,
            AuthService::class,
        ];
    }
}
