<?php

/*
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| This service creates in-app notifications inside the user_notifications table.
| It is the project's central helper for platform-level notification delivery.
|
| Why this file exists:
| Multiple features need to notify users (maintenance, chat mentions, reports).
| Keeping notification creation here prevents duplication in controllers/services.
|
| When this file is used:
| Whenever the app needs to store a notification for one or more users.
|
| FILES TO READ (IN ORDER):
| 1. app/Services/NotificationService.php
| 2. app/Models/UserNotification.php
| 3. app/Http/Controllers/NotificationController.php
| 4. resources/views/layouts/topbar.blade.php
| 5. resources/views/notifications/index.blade.php
|
| HOW TO UNDERSTAND THIS FEATURE:
| 1. A service/controller decides a user should be notified.
| 2. This service writes the notification row into the database.
| 3. The notification pages/dropdowns read that table and display it.
*/

namespace App\Services;

use App\Models\MaintenanceTask;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Str;

class NotificationService
{
    // Lowest-level helper used by the more specific notification methods below.
    public function notifyUser(
        User $user,
        string $type,
        string $title,
        ?string $body = null,
        ?string $url = null,
        array $data = []
    ): void {
        $user->userNotifications()->create([
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'data' => $data,
        ]);
    }

    // Sends the same notification to many users while avoiding duplicates.
    public function notifyUsers(
        iterable $users,
        string $type,
        string $title,
        ?string $body = null,
        ?string $url = null,
        array $data = []
    ): void {
        $seen = [];

        foreach ($users as $user) {
            if (! $user instanceof User || isset($seen[$user->id])) {
                continue;
            }

            $seen[$user->id] = true;

            $this->notifyUser($user, $type, $title, $body, $url, $data);
        }
    }

    // Used for admin-focused alerts, such as reporting events.
    public function notifyApprovedDepartmentHeads(
        string $type,
        string $title,
        ?string $body = null,
        ?string $url = null,
        array $data = []
    ): void {
        $users = User::query()
            ->departmentHeads()
            ->approved()
            ->orderBy('id')
            ->get();

        $this->notifyUsers($users, $type, $title, $body, $url, $data);
    }

    // Platform notification sent when a maintenance task is assigned.
    public function notifyMaintenanceTaskAssigned(User $recipient, MaintenanceTask $maintenanceTask, User $sender): void
    {
        $message = sprintf(
            'You have a new maintenance task for %s scheduled on %s.',
            $maintenanceTask->server_room,
            $maintenanceTask->maintenance_date->format('d M Y H:i')
        );

        $this->notifyUser(
            $recipient,
            'maintenance.assigned',
            'New maintenance task assigned',
            $message,
            route('maintenance.show', $maintenanceTask, false),
            [
                'message' => $message,
                'task_id' => $maintenanceTask->id,
                'maintenance_task_id' => $maintenanceTask->id,
                'sender_id' => $sender->id,
                'sender_name' => $sender->name,
                'server_room' => $maintenanceTask->server_room,
                'priority' => $maintenanceTask->priority,
                'assigned_at' => now()->toIso8601String(),
            ]
        );
    }

    // Platform notification sent when a user is mentioned in chat.
    public function notifyChatMention(User $recipient, Message $message, User $sender): void
    {
        $body = sprintf(
            '%s mentioned you in Team Chat: %s',
            $sender->name,
            Str::limit(preg_replace('/\s+/', ' ', trim($message->body)) ?: '', 120)
        );

        $this->notifyUser(
            $recipient,
            'chat.mentioned',
            'You were mentioned in team chat',
            $body,
            route('chat.index', [
                'mentions' => 'me',
                'highlight' => $message->id,
            ], false),
            [
                'message_id' => $message->id,
                'sender_id' => $sender->id,
                'sender_name' => $sender->name,
                'sender_handle' => $sender->chatHandle(),
                'body_preview' => Str::limit(preg_replace('/\s+/', ' ', trim($message->body)) ?: '', 160),
                'mentioned_at' => now()->toIso8601String(),
            ]
        );
    }
}
