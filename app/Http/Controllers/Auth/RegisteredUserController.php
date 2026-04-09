<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    private const DEPARTMENT_HEAD_KEY = '123456789';

    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
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

        $departmentHeadKey = trim((string) $request->input('department_head_key', ''));
        $isDepartmentHead = $departmentHeadKey !== ''
            && hash_equals(self::DEPARTMENT_HEAD_KEY, $departmentHeadKey);

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

        event(new Registered($user));

        if ($user->hasApprovedStatus()) {
            Auth::login($user);

            return redirect(route('dashboard', absolute: false));
        }

        return redirect()->route('login')->with('status', 'Your account is pending approval.');
    }
}
