<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServerMetricStored implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int $serverId,
        public readonly int $metricId,
        public readonly array $serverCard,
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('servers.overview'),
            new PrivateChannel('servers.'.$this->serverId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'server.metric.stored';
    }

    public function broadcastWith(): array
    {
        return [
            'serverId' => $this->serverId,
            'metricId' => $this->metricId,
            'server' => $this->serverCard,
        ];
    }
}
