<?php

/*
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| This controller manages Telegram account linking for the project.
|
| Why this file exists:
| The app can send Telegram notifications, so users need a safe way to connect
| their Telegram chat ID to their account.
|
| When this file is used:
| - when a user starts the Telegram connection flow from the profile page
| - when Telegram calls the webhook endpoint after /start
|
| FILES TO READ (IN ORDER):
| 1. routes/web.php
| 2. config/services.php
| 3. app/Http/Controllers/TelegramController.php
| 4. app/Services/TelegramService.php
| 5. app/Models/User.php
*/

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TelegramController extends Controller
{
    // Starts the Telegram linking flow by generating a one-time token and
    // redirecting the user to the configured bot with that token in /start.
    public function connect(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user && $user->hasApprovedStatus(), 403);

        $botUsername = trim((string) config('services.telegram.bot_username', ''));

        if ($botUsername === '') {
            return back()->withErrors([
                'telegram' => 'Telegram is not configured yet. Ask the administrator to set the bot username first.',
            ]);
        }

        $user->forceFill([
            'telegram_link_token' => Str::random(32),
        ])->save();

        $startValue = 'connect_'.$user->telegram_link_token;

        return redirect()->away("https://t.me/{$botUsername}?start={$startValue}");
    }

    /*
    |----------------------------------------------------------------------
    | Telegram webhook
    |----------------------------------------------------------------------
    | Flow:
    | Telegram update -> parse /start payload -> find matching user ->
    | save telegram_chat_id -> confirm connection back to Telegram
    */
    public function webhook(Request $request, TelegramService $telegramService)
    {
        $configuredSecret = trim((string) config('services.telegram.webhook_secret', ''));
        $providedSecret = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');

        if ($configuredSecret !== '' && ! hash_equals($configuredSecret, $providedSecret)) {
            return response('forbidden', 403);
        }

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
            ->first();

        if (! $user || ! $user->hasApprovedStatus()) {
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
