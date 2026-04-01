<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    /**
     * Display the users list for Department Head accounts.
     */
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        return view('admin.users.index', [
            'users' => User::query()->latest()->get(),
        ]);
    }

    /**
     * Approve a pending Department Head account.
     */
    public function approve(User $user): RedirectResponse
    {
        $this->authorize('approve', $user);

        $user->forceFill([
            'is_approved' => true,
        ])->save();

        return back()->with('status', 'User approved successfully.');
    }
}
