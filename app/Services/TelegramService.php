<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TelegramService
{
    public function sendMessage(string $chatId, string $message): void
    {
        $token = config('services.telegram.bot_token');

        if (! $token || $chatId === '') {
            return;
        }

        Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $message,
        ]);
    }
}
