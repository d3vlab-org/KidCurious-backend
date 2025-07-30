<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnswerGenerated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $questionId;
    public string $userId;
    public string $question;
    public string $answer;
    public array $metadata;

    /**
     * Create a new event instance.
     */
    public function __construct(
        string $questionId,
        string $userId,
        string $question,
        string $answer,
        array $metadata = []
    ) {
        $this->questionId = $questionId;
        $this->userId = $userId;
        $this->question = $question;
        $this->answer = $answer;
        $this->metadata = $metadata;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->userId),
            new Channel('chat')
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'question_id' => $this->questionId,
            'user_id' => $this->userId,
            'question' => $this->question,
            'answer' => $this->answer,
            'metadata' => $this->metadata,
            'timestamp' => now()->toISOString(),
            'type' => 'answer_generated'
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'answer.generated';
    }
}
