<?php

/*
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| This model represents one chat message sent in the Team Chat module.
|
| Why this file exists:
| The app needs a model to read/write rows in the messages table and define the
| relationship back to the sender.
|
| When this file is used:
| Whenever the chat module loads, stores, filters, or displays messages.
|
| FILES TO READ (IN ORDER):
| 1. database/migrations/*messages*.php
| 2. app/Models/Message.php
| 3. app/Http/Controllers/ChatController.php
| 4. app/Services/ChatWorkspaceService.php
| 5. resources/views/chat/*
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    // These fields are allowed to be inserted from user input.
    // This protects other columns from mass-assignment.
    protected $fillable = [
        'user_id',
        'body',
    ];

    // Relationship:
    // many messages belong to one user (the sender).
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
