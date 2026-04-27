<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageCreated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Message $message,
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('ops.chat'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat.message.created';
    }

    public function broadcastWith(): array
    {
        return [
            'messageId' => $this->message->id,
            'userId' => $this->message->user_id,
            'createdAt' => optional($this->message->created_at)->toIso8601String(),
        ];
    }
}
