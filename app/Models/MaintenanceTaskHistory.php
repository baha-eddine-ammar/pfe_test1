<?php

/*
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| This model represents one history entry attached to a maintenance task.
|
| Why this file exists:
| The maintenance module keeps an audit trail so users can understand what
| happened to a task over time: creation, assignment, status changes, and notes.
|
| When this file is used:
| Whenever the maintenance workflow service records a task activity event.
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceTaskHistory extends Model
{
    // The table manages created_at manually and does not use updated_at.
    public $timestamps = false;

    protected $fillable = [
        'maintenance_task_id',
        'actor_id',
        'action',
        'description',
        'old_status',
        'new_status',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            // Converts the stored timestamp into a Carbon instance.
            'created_at' => 'datetime',
        ];
    }

    // Relationship:
    // many history rows belong to one maintenance task.
    public function maintenanceTask(): BelongsTo
    {
        return $this->belongsTo(MaintenanceTask::class);
    }

    // Relationship:
    // many history rows can reference one acting user.
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
