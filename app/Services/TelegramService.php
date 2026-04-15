<?php

/*
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| This service sends optional Telegram notifications using the Telegram Bot API.
| It is a secondary notification channel that complements in-app notifications.
|
| Why this file exists:
| Some project actions should also reach users outside the web UI, especially
| maintenance assignment and chat mention alerts.
|
| When this file is used:
| After a feature decides a Telegram message should be sent and the user has
| linked a Telegram chat ID.
|
| FILES TO READ (IN ORDER):
| 1. app/Services/TelegramService.php
| 2. config/services.php
| 3. app/Http/Controllers/TelegramController.php
| 4. app/Services/MaintenanceTaskWorkflowService.php
| 5. app/Services/ChatWorkspaceService.php
|
| HOW TO UNDERSTAND THIS FEATURE:
| 1. Another service calls a Telegram helper method here.
| 2. This service checks whether Telegram is configured and linked.
| 3. It sends the final message text to the Telegram HTTP API.
*/

namespace App\Services;

use App\Models\MaintenanceTask;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TelegramService
{
    // Generic low-level Telegram sender used by more specific helper methods.
    public function sendMessage(string $chatId, string $message): void
    {
        $token = config('services.telegram.bot_token');

        if (! $token || $chatId === '') {
            return;
        }

        try {
            $response = Http::connectTimeout(3)->timeout(5)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
            ]);
        } catch (ConnectionException $exception) {
            Log::warning('Telegram API was unreachable while sending a bot message.', [
                'chat_id' => $chatId,
                'error' => $exception->getMessage(),
            ]);

            return;
        }

        if ($response->failed()) {
            Log::warning('Telegram API rejected a bot message.', [
                'chat_id' => $chatId,
                'status' => $response->status(),
                'response' => Str::limit($response->body(), 500),
            ]);
        }
    }

    // Maintenance assignment notification format required by the project.
    public function sendMaintenanceTaskAssigned(User $recipient, MaintenanceTask $maintenanceTask, User $sender): void
    {
        if (($recipient->telegram_chat_id ?? '') === '') {
            return;
        }

        $this->sendMessage(
            $recipient->telegram_chat_id,
            implode("\n", [
                'You have a new maintenance task:',
                'Server Room: '.$maintenanceTask->server_room,
                'Priority: '.Strtoupper($maintenanceTask->priority),
                'Assigned by: '.$sender->name,
            ])
        );
    }

    // Telegram version of a chat mention alert.
    public function sendChatMention(User $recipient, Message $message, User $sender): void
    {
        if (($recipient->telegram_chat_id ?? '') === '') {
            return;
        }

        $this->sendMessage(
            $recipient->telegram_chat_id,
            implode("\n", [
                'You were mentioned in team chat.',
                'By: '.$sender->name,
                'Preview: '.Str::limit(preg_replace('/\s+/', ' ', trim($message->body)) ?: '', 180),
            ])
        );
    }
}
