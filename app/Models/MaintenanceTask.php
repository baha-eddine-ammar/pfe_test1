<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceTask extends Model
{
    public const PRIORITY_URGENT = 'urgent';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_LOW = 'low';

    public const STATUS_PENDING = 'pending';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'server_room',
        'maintenance_date',
        'fix_description',
        'priority',
        'status',
        'assigned_to_user_id',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'maintenance_date' => 'datetime',
        ];
    }

    public static function priorityOptions(): array
    {
        return [
            self::PRIORITY_URGENT,
            self::PRIORITY_HIGH,
            self::PRIORITY_MEDIUM,
            self::PRIORITY_LOW,
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_ASSIGNED,
            self::STATUS_IN_PROGRESS,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
        ];
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function assignedToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(MaintenanceTaskHistory::class)->latest('created_at');
    }
}
