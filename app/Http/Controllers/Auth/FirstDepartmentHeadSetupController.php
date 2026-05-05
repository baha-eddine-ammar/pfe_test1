<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreFirstDepartmentHeadSetupRequest;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class FirstDepartmentHeadSetupController extends Controller
{
    public function create(): View
    {
        abort_unless($this->bootstrapAvailable(), 404);

        return view('auth.department-head-setup');
    }

    public function store(StoreFirstDepartmentHeadSetupRequest $request, AuditLogService $auditLogService): RedirectResponse
    {
        abort_unless($this->bootstrapAvailable(), 404);

        $configuredKey = trim((string) config('services.registration.department_head_key', ''));
        $providedKey = $request->validated('setup_key');

        if ($configuredKey === '' || ! hash_equals($configuredKey, $providedKey)) {
            return back()
                ->withInput($request->except('password', 'password_confirmation', 'setup_key'))
                ->withErrors([
                    'setup_key' => 'The initial setup key is invalid.',
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

        $user->sendEmailVerificationNotification();

        $auditLogService->record('auth.department_head.bootstrap.created', $user, [
            'email' => $user->email,
            'department' => $user->department,
        ]);

        return redirect()->route('login')->with(
            'status',
            'Department Head account created. Sign in and complete email verification to continue.'
        );
    }

    private function bootstrapAvailable(): bool
    {
        return User::query()->departmentHeads()->doesntExist();
    }
}
