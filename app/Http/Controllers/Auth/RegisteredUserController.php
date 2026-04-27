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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    // Displays the registration page.
    public function create(): View
    {
        return view('auth.register');
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
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                'ends_with:@draxmailer',
                'unique:'.User::class,
            ],
            'department' => [
                'required',
                'string',
                Rule::in(['Network', 'Security', 'Systems', 'Infrastructure']),
            ],
            'phone_number' => ['required', 'string', 'max:30'],
            'department_head_key' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'email.ends_with' => 'Only @draxmailer email addresses are allowed.',
        ]);

        // Role decision logic:
        // if the configured special key matches, the user becomes an approved department head.
        // otherwise, the user becomes pending staff.
        $departmentHeadKey = trim((string) $request->input('department_head_key', ''));
        $configuredDepartmentHeadKey = trim((string) config('services.registration.department_head_key', ''));
        $isDepartmentHead = $departmentHeadKey !== ''
            && $configuredDepartmentHeadKey !== ''
            && hash_equals($configuredDepartmentHeadKey, $departmentHeadKey);

        $role = $isDepartmentHead ? 'department_head' : 'staff';
        $status = $isDepartmentHead ? 'approved' : 'pending';

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'department' => $request->department,
            'phone_number' => $request->phone_number,
            'role' => $role,
            'status' => $status,
            'is_approved' => $status === 'approved',
            'password' => Hash::make($request->password),
        ]);

        // Approved users can enter the system immediately.
        // Department heads are trusted through the secret registration key,
        // so they are allowed to access the workspace immediately.
        // Pending staff users are redirected to login with a waiting message.
        if ($user->hasApprovedStatus()) {
            if ($user->isDepartmentHead()) {
                $user->forceFill([
                    'email_verified_at' => now(),
                ])->save();
            } else {
                $user->sendEmailVerificationNotification();
            }

            Auth::login($user);

            return redirect(route('dashboard', absolute: false));
        }

        return redirect()->route('login')->with('status', 'Your account is pending approval.');
    }
}
