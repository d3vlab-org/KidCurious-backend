<?php

namespace App\Broadcasting;

use BeyondCode\LaravelWebSockets\Apps\App;
use BeyondCode\LaravelWebSockets\QueryParameters;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use BeyondCode\LaravelWebSockets\WebSockets\Exceptions\UnknownAppKey;
use BeyondCode\LaravelWebSockets\WebSockets\Messages\PusherMessageFactory;
use Illuminate\Support\Facades\Log;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Ratchet\WebSocket\MessageComponentInterface;

class AuthenticatedWebSocketHandler implements MessageComponentInterface
{
    protected SupabaseWebSocketGuard $guard;
    protected ChannelManager $channelManager;

    public function __construct(SupabaseWebSocketGuard $guard, ChannelManager $channelManager)
    {
        $this->guard = $guard;
        $this->channelManager = $channelManager;
    }

    public function onOpen(ConnectionInterface $connection)
    {
        $this->verifyAppKey($connection);

        Log::info('WebSocket connection opened', [
            'connection_id' => $connection->resourceId,
            'remote_address' => $connection->remoteAddress
        ]);
    }

    public function onMessage(ConnectionInterface $connection, MessageInterface $message)
    {
        $payload = json_decode($message->getPayload(), true);

        if (!$payload) {
            $this->closeConnection($connection, 'Invalid JSON payload');
            return;
        }

        $event = $payload['event'] ?? null;

        switch ($event) {
            case 'pusher:connection_established':
                $this->handleConnectionEstablished($connection);
                break;

            case 'pusher:subscribe':
                $this->handleChannelSubscription($connection, $payload);
                break;

            case 'pusher:unsubscribe':
                $this->handleChannelUnsubscription($connection, $payload);
                break;

            case 'pusher:ping':
                $this->handlePing($connection);
                break;

            case 'client-auth':
                $this->handleAuthentication($connection, $message);
                break;

            default:
                $this->handleClientMessage($connection, $payload);
                break;
        }
    }

    public function onClose(ConnectionInterface $connection)
    {
        $userId = $this->guard->getUserId($connection);

        Log::info('WebSocket connection closed', [
            'connection_id' => $connection->resourceId,
            'user_id' => $userId
        ]);

        // Remove connection from all channels
        $this->channelManager->removeFromAllChannels($connection);
    }

    public function onError(ConnectionInterface $connection, \Exception $e)
    {
        Log::error('WebSocket connection error', [
            'connection_id' => $connection->resourceId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        $connection->close();
    }

    protected function handleConnectionEstablished(ConnectionInterface $connection)
    {
        $response = [
            'event' => 'pusher:connection_established',
            'data' => json_encode([
                'socket_id' => $connection->socketId ?? $connection->resourceId,
                'activity_timeout' => 30
            ])
        ];

        $connection->send(json_encode($response));
    }

    protected function handleAuthentication(ConnectionInterface $connection, MessageInterface $message)
    {
        if ($this->guard->authenticate($connection, $message)) {
            $userId = $this->guard->getUserId($connection);

            $response = [
                'event' => 'pusher:auth_success',
                'data' => json_encode([
                    'user_id' => $userId,
                    'authenticated' => true
                ])
            ];

            $connection->send(json_encode($response));

            Log::info('WebSocket authentication successful', [
                'connection_id' => $connection->resourceId,
                'user_id' => $userId
            ]);
        } else {
            $response = [
                'event' => 'pusher:auth_failed',
                'data' => json_encode([
                    'message' => 'Authentication failed'
                ])
            ];

            $connection->send(json_encode($response));

            Log::warning('WebSocket authentication failed', [
                'connection_id' => $connection->resourceId
            ]);
        }
    }

    protected function handleChannelSubscription(ConnectionInterface $connection, array $payload)
    {
        $channelName = $payload['data']['channel'] ?? null;

        if (!$channelName) {
            $this->sendError($connection, 'Channel name is required');
            return;
        }

        // Check if user can access this channel
        if (!$this->guard->authenticateChannelAccess($connection, $channelName)) {
            $this->sendError($connection, 'Access denied to channel: ' . $channelName);
            return;
        }

        // Add connection to channel
        $this->channelManager->subscribeToChannel($connection, $channelName);

        $response = [
            'event' => 'pusher_internal:subscription_succeeded',
            'channel' => $channelName,
            'data' => '{}'
        ];

        $connection->send(json_encode($response));

        Log::info('Channel subscription successful', [
            'connection_id' => $connection->resourceId,
            'channel' => $channelName,
            'user_id' => $this->guard->getUserId($connection)
        ]);
    }

    protected function handleChannelUnsubscription(ConnectionInterface $connection, array $payload)
    {
        $channelName = $payload['data']['channel'] ?? null;

        if (!$channelName) {
            return;
        }

        $this->channelManager->unsubscribeFromChannel($connection, $channelName);

        Log::info('Channel unsubscription', [
            'connection_id' => $connection->resourceId,
            'channel' => $channelName,
            'user_id' => $this->guard->getUserId($connection)
        ]);
    }

    protected function handlePing(ConnectionInterface $connection)
    {
        $response = [
            'event' => 'pusher:pong',
            'data' => '{}'
        ];

        $connection->send(json_encode($response));
    }

    protected function handleClientMessage(ConnectionInterface $connection, array $payload)
    {
        // Handle client-to-client messages if needed
        // For now, we'll just log them
        Log::info('Client message received', [
            'connection_id' => $connection->resourceId,
            'event' => $payload['event'] ?? 'unknown',
            'user_id' => $this->guard->getUserId($connection)
        ]);
    }

    protected function verifyAppKey(ConnectionInterface $connection)
    {
        $queryParameters = QueryParameters::create($connection->httpRequest);

        try {
            $app = App::findByKey($queryParameters->get('appKey'));
            $connection->app = $app;
            $connection->socketId = $this->generateSocketId();
        } catch (UnknownAppKey $e) {
            $this->closeConnection($connection, 'Unknown app key');
        }
    }

    protected function generateSocketId(): string
    {
        return sprintf('%d.%d', random_int(1, 1000000), random_int(1, 1000000));
    }

    protected function sendError(ConnectionInterface $connection, string $message)
    {
        $response = [
            'event' => 'pusher:error',
            'data' => json_encode([
                'message' => $message,
                'code' => 4001
            ])
        ];

        $connection->send(json_encode($response));
    }

    protected function closeConnection(ConnectionInterface $connection, string $reason)
    {
        Log::warning('Closing WebSocket connection', [
            'connection_id' => $connection->resourceId,
            'reason' => $reason
        ]);

        $connection->close();
    }
}
