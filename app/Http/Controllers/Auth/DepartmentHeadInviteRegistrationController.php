<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterDepartmentHeadFromInviteRequest;
use App\Models\DepartmentHeadInvite;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\DepartmentHeadInviteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DepartmentHeadInviteRegistrationController extends Controller
{
    public function landing(): View
    {
        return view('auth.department-head-invite-entry', [
            'bootstrapAvailable' => User::query()->departmentHeads()->doesntExist(),
        ]);
    }

    public function reveal(
        DepartmentHeadInvite $departmentHeadInvite,
        string $token,
        DepartmentHeadInviteService $departmentHeadInviteService,
        AuditLogService $auditLogService,
    ): View {
        if (! $departmentHeadInviteService->revealTokenMatches($departmentHeadInvite, $token)) {
            $auditLogService->record('auth.department_head.invite.reveal.denied', $departmentHeadInvite, [
                'reason' => 'invalid_token',
            ]);

            return $this->statusView('This Department Head invite is invalid.');
        }

        if ($departmentHeadInvite->isRevoked()) {
            return $this->statusView('This Department Head invite has been revoked.');
        }

        if ($departmentHeadInvite->isExpired()) {
            return $this->statusView('This Department Head invite has expired.');
        }

        if ($departmentHeadInvite->isUsed()) {
            return $this->statusView('This Department Head invite has already been used.');
        }

        if ($departmentHeadInvite->hasBeenRevealed()) {
            return $this->statusView('This code has already been revealed.');
        }

        $code = $departmentHeadInviteService->revealCode($departmentHeadInvite, $token);

        return view('auth.department-head-invite-revealed', [
            'departmentHeadInvite' => $departmentHeadInvite->fresh(),
            'authorizationCode' => $code,
        ]);
    }

    public function create(DepartmentHeadInvite $departmentHeadInvite): View
    {
        if ($departmentHeadInvite->isRevoked()) {
            return $this->statusView('This Department Head invite has been revoked.');
        }

        if ($departmentHeadInvite->isExpired()) {
            return $this->statusView('This Department Head invite has expired.');
        }

        if ($departmentHeadInvite->isUsed()) {
            return $this->statusView('This Department Head invite has already been used.');
        }

        if (! $departmentHeadInvite->hasBeenRevealed()) {
            return $this->statusView('Open the one-time reveal link from your email before continuing.');
        }

        return view('auth.department-head-invite-register', [
            'departmentHeadInvite' => $departmentHeadInvite,
        ]);
    }

    public function store(
        RegisterDepartmentHeadFromInviteRequest $request,
        DepartmentHeadInvite $departmentHeadInvite,
        DepartmentHeadInviteService $departmentHeadInviteService,
        AuditLogService $auditLogService,
    ): RedirectResponse {
        if (! $departmentHeadInvite->canBeRegistered()) {
            throw ValidationException::withMessages([
                'authorization_code' => 'This Department Head invite is no longer available.',
            ]);
        }

        if (! $departmentHeadInviteService->authorizationCodeMatches($departmentHeadInvite, $request->validated('authorization_code'))) {
            $departmentHeadInviteService->recordFailedCodeAttempt($departmentHeadInvite);

            throw ValidationException::withMessages([
                'authorization_code' => 'The authorization code is invalid or unavailable.',
            ]);
        }

        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'department' => $data['department'],
            'phone_number' => $data['phone_number'],
            'role' => 'department_head',
            'status' => 'approved',
            'is_approved' => true,
            'password' => Hash::make($data['password']),
            'email_verified_at' => null,
        ]);

        $departmentHeadInviteService->consumeInvite($departmentHeadInvite, $user);
        $user->sendEmailVerificationNotification();

        $auditLogService->record('auth.department_head.registration.completed', $user, [
            'invite_uuid' => $departmentHeadInvite->uuid,
        ], $user);

        return redirect()->route('login')->with(
            'status',
            'Department Head account created. Sign in and complete email verification to continue.'
        );
    }

    private function statusView(string $message): View
    {
        return view('auth.department-head-invite-status', [
            'message' => $message,
        ]);
    }
}
