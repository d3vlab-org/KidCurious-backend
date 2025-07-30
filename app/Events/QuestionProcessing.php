<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuestionProcessing implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $questionId;
    public string $userId;
    public string $question;
    public string $status;
    public array $metadata;

    /**
     * Create a new event instance.
     */
    public function __construct(
        string $questionId,
        string $userId,
        string $question,
        string $status = 'processing',
        array $metadata = []
    ) {
        $this->questionId = $questionId;
        $this->userId = $userId;
        $this->question = $question;
        $this->status = $status;
        $this->metadata = $metadata;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->userId)
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
            'status' => $this->status,
            'metadata' => $this->metadata,
            'timestamp' => now()->toISOString(),
            'type' => 'question_processing'
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'question.processing';
    }
}
