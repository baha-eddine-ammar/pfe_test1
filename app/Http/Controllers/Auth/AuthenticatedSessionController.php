<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\LoginTwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(Request $request, LoginTwoFactorService $twoFactorService): View|RedirectResponse
    {
        if ($twoFactorService->pendingUser($request)) {
            return redirect()->route('two-factor.challenge');
        }

        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(
        LoginRequest $request,
        LoginTwoFactorService $twoFactorService,
    ): RedirectResponse
    {
        $twoFactorService->clearPendingState($request);

        $user = $request->authenticate();

        $request->session()->regenerate();
        $twoFactorService->begin($request, $user, $request->boolean('remember'));

        return redirect()->route('two-factor.challenge')
            ->with('status', 'A verification code has been sent to your email address.');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request, LoginTwoFactorService $twoFactorService): RedirectResponse
    {
        $twoFactorService->clearPendingState($request);

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
