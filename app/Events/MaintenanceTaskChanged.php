<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MaintenanceTaskChanged implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $action,
        public readonly array $task,
        public readonly array $userIds = [],
    ) {
    }

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('ops.admin'),
        ];

        foreach (array_unique(array_filter(array_map('intval', $this->userIds))) as $userId) {
            $channels[] = new PrivateChannel('users.'.$userId.'.maintenance');
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'maintenance.task.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'action' => $this->action,
            'task' => $this->task,
        ];
    }
}
