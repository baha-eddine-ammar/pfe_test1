<?php

/*
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| This controller handles user registration for the project.
|
| Why this file exists:
| The application has custom registration rules beyond default Laravel auth:
| department selection, phone number, role assignment, and department head key logic.
|
| When this file is used:
| - GET /register
| - POST /register
|
| FILES TO READ (IN ORDER):
| 1. routes/auth.php
| 2. app/Http/Controllers/Auth/RegisteredUserController.php
| 3. app/Models/User.php
| 4. resources/views/auth/register.blade.php
| 5. app/Http/Middleware/EnsureDepartmentHead.php
|
| HOW TO UNDERSTAND THIS FEATURE:
| 1. The user submits the register form.
| 2. This controller validates the input.
| 3. It decides whether the account is staff or department_head.
| 4. It creates the user row.
| 5. Approved users are logged in immediately.
*/

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\NewStaffRegistrationNotification;
use App\Notifications\PendingApprovalNotification;
use App\Services\AuditLogService;
use App\Http\Requests\Auth\RegisterStaffRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    // Displays the registration page.
    public function create(): View
    {
        return view('auth.register', [
            'firstDepartmentHeadSetupAvailable' => User::query()->departmentHeads()->doesntExist(),
        ]);
    }

    /*
    |----------------------------------------------------------------------
    | Handle registration
    |----------------------------------------------------------------------
    | Important variables:
    | - $departmentHeadKey: optional secret key entered in the form.
    | - $isDepartmentHead: determines role and approval status.
    | - $role / $status: values saved into the users table.
    */
    public function store(
        RegisterStaffRequest $request,
        AuditLogService $auditLogService,
    ): RedirectResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'department' => $data['department'],
            'phone_number' => $data['phone_number'],
            'role' => 'staff',
            'status' => 'pending',
            'is_approved' => false,
            'password' => Hash::make($data['password']),
        ]);

        $user->notify(new PendingApprovalNotification());

        $approvedDepartmentHeads = User::query()
            ->departmentHeads()
            ->approved()
            ->orderBy('id')
            ->get();

        Notification::send($approvedDepartmentHeads, new NewStaffRegistrationNotification($user));

        $auditLogService->record('auth.registration.staff.created', $user, [
            'email' => $user->email,
            'department' => $user->department,
        ]);

        return redirect()->route('register.pending')->with([
            'status' => 'Your account is pending approval.',
            'registered_email' => $user->email,
        ]);
    }

    public function pending(Request $request): View
    {
        return view('auth.pending-approval', [
            'registeredEmail' => $request->session()->get('registered_email'),
        ]);
    }
}
