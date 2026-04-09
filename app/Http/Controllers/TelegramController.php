<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TelegramController extends Controller
{
    public function connect(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user && $user->isDepartmentHead(), 403);

        $user->forceFill([
            'telegram_link_token' => Str::random(32),
        ])->save();

        $botUsername = config('services.telegram.bot_username');
        $startValue = 'connect_'.$user->telegram_link_token;

        return redirect()->away("https://t.me/{$botUsername}?start={$startValue}");
    }

    public function webhook(Request $request, TelegramService $telegramService)
    {
        $text = trim((string) data_get($request->all(), 'message.text', ''));
        $chatId = (string) data_get($request->all(), 'message.chat.id', '');

        if ($text === '' || $chatId === '') {
            return response('ok', 200);
        }

        if (! str_starts_with($text, '/start')) {
            return response('ok', 200);
        }

        $payload = trim(str_replace('/start', '', $text));

        if (! str_starts_with($payload, 'connect_')) {
            $telegramService->sendMessage($chatId, 'Please start from the Connect Telegram button in your profile.');
            return response('ok', 200);
        }

        $token = substr($payload, strlen('connect_'));

        $user = User::query()
            ->where('telegram_link_token', $token)
            ->where('role', 'department_head')
            ->first();

        if (! $user) {
            $telegramService->sendMessage($chatId, 'This Telegram connection link is invalid or expired.');
            return response('ok', 200);
        }

        $user->forceFill([
            'telegram_chat_id' => $chatId,
            'telegram_link_token' => null,
        ])->save();

        $telegramService->sendMessage(
            $chatId,
            'Your Telegram account is now connected. You will receive system alerts here.'
        );

        return response('ok', 200);
    }
}
