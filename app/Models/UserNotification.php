<?php

/*
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| This model represents one in-app notification received by a user.
|
| Why this file exists:
| The project stores notifications in the database so they can appear in the
| topbar dropdown and the full notifications page.
|
| When this file is used:
| Whenever NotificationService creates alerts for maintenance, chat, or reports.
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'body',
        'url',
        'data',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            // Extra notification metadata is stored as JSON and exposed as an array.
            'data' => 'array',
            'read_at' => 'datetime',
        ];
    }

    // Relationship:
    // many notifications belong to one user.
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
