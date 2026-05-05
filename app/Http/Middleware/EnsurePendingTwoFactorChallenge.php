<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\LoginTwoFactorService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePendingTwoFactorChallenge
{
    public function handle(Request $request, Closure $next): Response
    {
        $pendingUserId = $request->session()->get(LoginTwoFactorService::SESSION_USER_ID);

        if (! $pendingUserId) {
            return redirect()->route('login');
        }

        $user = User::query()->find($pendingUserId);

        if (! $user || ! $user->hasApprovedStatus()) {
            $request->session()->forget([
                LoginTwoFactorService::SESSION_USER_ID,
                LoginTwoFactorService::SESSION_REMEMBER,
            ]);

            return redirect()->route('login');
        }

        return $next($request);
    }
}
