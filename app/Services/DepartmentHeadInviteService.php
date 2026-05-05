<?php

namespace App\Services;

use App\Models\DepartmentHeadInvite;
use App\Models\User;
use App\Notifications\DepartmentHeadInviteNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class DepartmentHeadInviteService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {
    }

    public function createInvite(User $actor, string $email): DepartmentHeadInvite
    {
        $normalizedEmail = Str::lower(trim($email));
        $rawRevealToken = Str::random(64);
        $authorizationCode = $this->deriveAuthorizationCode($rawRevealToken, $normalizedEmail);

        $invite = DepartmentHeadInvite::query()->create([
            'uuid' => (string) Str::uuid(),
            'invited_email' => $normalizedEmail,
            'invited_by_user_id' => $actor->id,
            'code_hash' => Hash::make($authorizationCode),
            'reveal_token_hash' => Hash::make($rawRevealToken),
            'expires_at' => now()->addDay(),
        ]);

        Notification::route('mail', $normalizedEmail)->notify(
            new DepartmentHeadInviteNotification(
                $invite,
                route('department-head.invites.reveal', [
                    'departmentHeadInvite' => $invite,
                    'token' => $rawRevealToken,
                ])
            )
        );

        $this->auditLogService->record('auth.department_head.invite.created', $invite, [
            'invited_email' => $invite->invited_email,
            'expires_at' => $invite->expires_at?->toIso8601String(),
        ], $actor);

        return $invite;
    }

    public function deriveAuthorizationCode(string $rawRevealToken, string $email): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $hash = hash_hmac('sha256', Str::lower(trim($email)).'|'.$rawRevealToken, (string) config('app.key'));
        $code = '';

        for ($index = 0; $index < 10; $index++) {
            $segment = substr($hash, $index * 2, 2);
            $characterIndex = hexdec($segment) % strlen($alphabet);
            $code .= $alphabet[$characterIndex];
        }

        return $code;
    }

    public function revealCode(DepartmentHeadInvite $invite, string $rawRevealToken): string
    {
        $code = $this->deriveAuthorizationCode($rawRevealToken, $invite->invited_email);

        $invite->forceFill([
            'reveal_used_at' => now(),
        ])->save();

        $this->auditLogService->record('auth.department_head.invite.revealed', $invite, [
            'invited_email' => $invite->invited_email,
        ]);

        return $code;
    }

    public function revealTokenMatches(DepartmentHeadInvite $invite, string $rawRevealToken): bool
    {
        return Hash::check($rawRevealToken, $invite->reveal_token_hash);
    }

    public function authorizationCodeMatches(DepartmentHeadInvite $invite, string $authorizationCode): bool
    {
        return Hash::check(Str::upper(trim($authorizationCode)), $invite->code_hash);
    }

    public function recordFailedCodeAttempt(DepartmentHeadInvite $invite): void
    {
        $invite->increment('failed_attempts');
        $invite->forceFill([
            'last_attempt_at' => now(),
        ])->save();

        $this->auditLogService->record('auth.department_head.invite.code_failed', $invite, [
            'failed_attempts' => $invite->fresh()->failed_attempts,
        ]);
    }

    public function consumeInvite(DepartmentHeadInvite $invite, User $user): void
    {
        $invite->forceFill([
            'used_at' => now(),
            'used_by_user_id' => $user->id,
        ])->save();

        $this->auditLogService->record('auth.department_head.invite.used', $invite, [
            'used_by_user_id' => $user->id,
        ], $user);
    }

    public function revokeInvite(DepartmentHeadInvite $invite, User $actor): void
    {
        $invite->forceFill([
            'revoked_at' => now(),
        ])->save();

        $this->auditLogService->record('auth.department_head.invite.revoked', $invite, [
            'invited_email' => $invite->invited_email,
        ], $actor);
    }
}
