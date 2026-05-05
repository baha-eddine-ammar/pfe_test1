<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyTwoFactorCodeRequest;
use App\Services\AuthenticationSecurityService;
use App\Services\LoginTwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EmailTwoFactorChallengeController extends Controller
{
    public function create(Request $request, LoginTwoFactorService $loginTwoFactorService): View
    {
        $user = $loginTwoFactorService->pendingUser($request);
        $challenge = $user?->loginTwoFactorChallenge;

        $secondsUntilResend = 0;

        if ($challenge?->last_sent_at) {
            $resendAt = $challenge->last_sent_at->copy()->addSeconds(60);
            $secondsUntilResend = now()->lt($resendAt)
                ? now()->diffInSeconds($resendAt)
                : 0;
        }

        return view('auth.two-factor-challenge', [
            'pendingUser' => $user,
            'secondsUntilResend' => $secondsUntilResend,
        ]);
    }

    public function store(
        VerifyTwoFactorCodeRequest $request,
        LoginTwoFactorService $loginTwoFactorService,
        AuthenticationSecurityService $authenticationSecurityService,
    ): RedirectResponse {
        $remember = $loginTwoFactorService->pendingRemember($request);
        $user = $loginTwoFactorService->verify($request, $request->validated('code'));

        Auth::login($user, $remember);
        $request->session()->regenerate();
        $loginTwoFactorService->clearPendingState($request, false);
        $authenticationSecurityService->finalizeSuccessfulLogin($user, $request, true);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function resend(Request $request, LoginTwoFactorService $loginTwoFactorService): RedirectResponse
    {
        $loginTwoFactorService->resend($request);

        return back()->with('status', 'A new verification code has been sent to your email address.');
    }

    public function destroy(Request $request, LoginTwoFactorService $loginTwoFactorService): RedirectResponse
    {
        $loginTwoFactorService->clearPendingState($request);

        return redirect()->route('login')->with('status', 'Two-factor sign-in was canceled.');
    }
}
