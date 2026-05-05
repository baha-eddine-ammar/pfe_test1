<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\SuspiciousLoginAlertNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuthenticationSecurityService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {
    }

    public function finalizeSuccessfulLogin(User $user, Request $request, bool $usedTwoFactor = false): void
    {
        $currentLoginAt = now();
        $currentIp = $request->ip();
        $currentUserAgent = Str::limit((string) $request->userAgent(), 1000, '');
        $previousIp = $user->last_login_ip;
        $previousLoginAt = $user->last_login_at;

        $user->forceFill([
            'last_login_at' => $currentLoginAt,
            'last_login_ip' => $currentIp,
            'last_login_user_agent' => $currentUserAgent,
        ])->save();

        if ($previousIp && $currentIp && $previousIp !== $currentIp) {
            $user->notify(new SuspiciousLoginAlertNotification(
                $currentIp,
                $previousIp,
                $previousLoginAt,
                $currentLoginAt,
                $currentUserAgent,
            ));
        }

        $this->auditLogService->record('auth.login.success', $user, [
            'ip' => $currentIp,
            'user_agent' => $currentUserAgent,
            'used_two_factor' => $usedTwoFactor,
        ], $user);
    }
}
