<?php

/*
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| This policy defines who may use the Team Chat feature.
|
| Why this file exists:
| Authorization rules should not be hidden inside controllers. Policies keep
| permission logic explicit and reusable.
|
| When this file is used:
| Whenever ChatController authorizes viewing, posting, or deleting messages.
*/

namespace App\Policies;

use App\Models\Message;
use App\Models\User;

class MessagePolicy
{
    // Any authenticated user who reaches the chat route may view the shared chat room.
    public function viewAny(User $user): bool
    {
        return true;
    }

    // Any authenticated user may send a new chat message.
    public function create(User $user): bool
    {
        return true;
    }

    // Users may only delete their own messages.
    public function delete(User $user, Message $message): bool
    {
        return $message->user_id === $user->id;
    }
}
