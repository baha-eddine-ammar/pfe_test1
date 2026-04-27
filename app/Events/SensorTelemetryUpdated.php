<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SensorTelemetryUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly array $trend,
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('dashboard.telemetry'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'sensor.telemetry.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'trend' => $this->trend,
        ];
    }
}
