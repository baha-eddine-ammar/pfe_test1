<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogService
{
    public function record(
        string $action,
        Model|string $target,
        array $metadata = [],
        ?User $actor = null,
    ): AuditLog {
        [$targetType, $targetId] = $this->normalizeTarget($target);

        return AuditLog::query()->create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }

    private function normalizeTarget(Model|string $target): array
    {
        if ($target instanceof Model) {
            return [$target::class, $target->getKey()];
        }

        return [$target, null];
    }
}
