<?php

namespace App\Services;

use App\Models\LoginTwoFactorChallenge;
use App\Models\User;
use App\Notifications\LoginTwoFactorCodeNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginTwoFactorService
{
    public const SESSION_USER_ID = 'auth.two_factor.user_id';
    public const SESSION_REMEMBER = 'auth.two_factor.remember';

    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {
    }

    public function begin(Request $request, User $user, bool $remember): void
    {
        $request->session()->put([
            self::SESSION_USER_ID => $user->id,
            self::SESSION_REMEMBER => $remember,
        ]);

        $this->issueCode($user);

        $this->auditLogService->record('auth.two_factor.challenge.started', $user, [
            'email' => $user->email,
        ], $user);
    }

    public function pendingUser(Request $request): ?User
    {
        $pendingUserId = $request->session()->get(self::SESSION_USER_ID);

        if (! $pendingUserId) {
            return null;
        }

        $user = User::query()->find($pendingUserId);

        if (! $user || ! $user->hasApprovedStatus()) {
            return null;
        }

        return $user;
    }

    public function pendingRemember(Request $request): bool
    {
        return (bool) $request->session()->get(self::SESSION_REMEMBER, false);
    }

    public function clearPendingState(Request $request, bool $deleteChallenge = true): void
    {
        $pendingUserId = $request->session()->get(self::SESSION_USER_ID);

        if ($deleteChallenge && $pendingUserId) {
            LoginTwoFactorChallenge::query()
                ->where('user_id', $pendingUserId)
                ->delete();
        }

        $request->session()->forget([
            self::SESSION_USER_ID,
            self::SESSION_REMEMBER,
        ]);
    }

    public function issueCode(User $user): LoginTwoFactorChallenge
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $challenge = LoginTwoFactorChallenge::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'code_hash' => Hash::make($code),
                'attempts' => 0,
                'last_attempt_at' => null,
                'last_sent_at' => now(),
                'expires_at' => now()->addMinutes(5),
            ]
        );

        $user->notify(new LoginTwoFactorCodeNotification($code));

        $this->auditLogService->record('auth.two_factor.code.sent', $user, [
            'expires_at' => $challenge->expires_at?->toIso8601String(),
        ], $user);

        return $challenge;
    }

    public function resend(Request $request): void
    {
        $user = $this->pendingUser($request);

        if (! $user) {
            throw ValidationException::withMessages([
                'code' => 'Your sign-in session has expired. Please sign in again.',
            ]);
        }

        $challenge = LoginTwoFactorChallenge::query()
            ->where('user_id', $user->id)
            ->first();

        if ($challenge && $challenge->last_sent_at && $challenge->last_sent_at->gt(now()->subSeconds(60))) {
            throw ValidationException::withMessages([
                'code' => 'Please wait before requesting another verification code.',
            ]);
        }

        $this->issueCode($user);
    }

    public function verify(Request $request, string $enteredCode): User
    {
        $user = $this->pendingUser($request);

        if (! $user) {
            throw ValidationException::withMessages([
                'code' => 'Your sign-in session has expired. Please sign in again.',
            ]);
        }

        $challenge = LoginTwoFactorChallenge::query()
            ->where('user_id', $user->id)
            ->first();

        if (! $challenge) {
            $this->clearPendingState($request, false);

            throw ValidationException::withMessages([
                'code' => 'A new verification code is required. Please sign in again.',
            ]);
        }

        if ($challenge->isExpired()) {
            $challenge->delete();
            $this->clearPendingState($request, false);

            $this->auditLogService->record('auth.two_factor.code.expired', $user, [], $user);

            throw ValidationException::withMessages([
                'code' => 'This verification code has expired. Please sign in again.',
            ]);
        }

        if (! Hash::check($enteredCode, $challenge->code_hash)) {
            $challenge->increment('attempts');
            $challenge->forceFill([
                'last_attempt_at' => now(),
            ])->save();

            $attempts = (int) $challenge->fresh()->attempts;

            $this->auditLogService->record('auth.two_factor.code.failed', $user, [
                'attempts' => $attempts,
            ], $user);

            if ($attempts >= 3) {
                $challenge->delete();
                $this->clearPendingState($request, false);

                throw ValidationException::withMessages([
                    'code' => 'Too many invalid verification attempts. Please sign in again.',
                ]);
            }

            throw ValidationException::withMessages([
                'code' => 'The verification code is invalid.',
            ]);
        }

        $challenge->delete();

        $this->auditLogService->record('auth.two_factor.code.verified', $user, [], $user);

        return $user;
    }
}
