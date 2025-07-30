<?php

namespace App\Providers;

use App\Broadcasting\AuthenticatedWebSocketHandler;
use App\Broadcasting\SupabaseWebSocketGuard;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use Illuminate\Support\ServiceProvider;
use KidsQaAi\AuthService\Infrastructure\Services\SupabaseJwtService;

class WebSocketServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the Supabase WebSocket Guard
        $this->app->singleton(SupabaseWebSocketGuard::class, function ($app) {
            return new SupabaseWebSocketGuard(
                $app->make(SupabaseJwtService::class)
            );
        });

        // Register the Authenticated WebSocket Handler
        $this->app->singleton(AuthenticatedWebSocketHandler::class, function ($app) {
            return new AuthenticatedWebSocketHandler(
                $app->make(SupabaseWebSocketGuard::class),
                $app->make(ChannelManager::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Configure broadcasting authentication
        $this->configureBroadcastAuth();

        // Register WebSocket routes
        $this->registerWebSocketRoutes();
    }

    /**
     * Configure broadcast authentication
     */
    protected function configureBroadcastAuth(): void
    {
        // Custom authentication for WebSocket connections
        $this->app['router']->group([
            'prefix' => 'broadcasting',
            'middleware' => ['api']
        ], function ($router) {
            $router->post('/auth', function () {
                $jwtService = app(SupabaseJwtService::class);
                $token = request()->bearerToken();

                if (!$token || !$jwtService->validateToken($token)) {
                    return response()->json(['error' => 'Unauthorized'], 401);
                }

                $userId = $jwtService->getUserIdFromToken($token);
                $channelName = request()->input('channel_name');

                // Validate channel access
                if (str_starts_with($channelName, 'private-user.')) {
                    $channelUserId = str_replace('private-user.', '', $channelName);
                    if ($userId !== $channelUserId) {
                        return response()->json(['error' => 'Forbidden'], 403);
                    }
                }

                return response()->json([
                    'auth' => hash_hmac('sha256', request()->input('socket_id') . ':' . $channelName, config('broadcasting.connections.pusher.secret')),
                    'user_id' => $userId
                ]);
            });
        });
    }

    /**
     * Register WebSocket routes
     */
    protected function registerWebSocketRoutes(): void
    {
        // WebSocket server will be handled by Laravel WebSockets package
        // Additional custom routes can be added here if needed
    }
}
