<?php

namespace App\Events;

use App\Models\Report;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReportGenerated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Report $report,
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('ops.admin'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'report.generated';
    }

    public function broadcastWith(): array
    {
        return [
            'report' => [
                'id' => $this->report->id,
                'title' => $this->report->title,
                'type' => $this->report->type,
                'status' => $this->report->status,
                'generatedAt' => optional($this->report->generated_at)->toIso8601String(),
            ],
        ];
    }
}
