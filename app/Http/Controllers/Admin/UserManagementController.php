<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\NotificationService;
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
            'pendingUsers' => User::query()
                ->where('status', 'pending')
                ->latest()
                ->paginate(10, ['*'], 'pending_page')
                ->withQueryString(),
            'users' => User::query()
                ->latest()
                ->paginate(15, ['*'], 'users_page')
                ->withQueryString(),
        ]);
    }

    public function approve(User $user, NotificationService $notificationService, AuditLogService $auditLogService): RedirectResponse
    {
        $this->authorize('approve', $user);

        $user->forceFill([
            'status' => 'approved',
            'is_approved' => true,
        ])->save();

        if (! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        $notificationService->notifyUser(
            $user,
            'user.approved',
            'Account approved',
            'Your workspace access has been approved.',
            route('dashboard', [], false)
        );

        $auditLogService->record('user.approved', $user, [
            'new_status' => $user->status,
            'role' => $user->role,
        ], request()->user());

        return back()->with('status', 'User approved successfully.');
    }

    public function reject(User $user, NotificationService $notificationService, AuditLogService $auditLogService): RedirectResponse
    {
        $this->authorize('reject', $user);

        $user->forceFill([
            'status' => 'rejected',
            'is_approved' => false,
        ])->save();

        $notificationService->notifyUser(
            $user,
            'user.rejected',
            'Account rejected',
            'Your account request was rejected by administration.',
            route('profile.edit', [], false)
        );

        $auditLogService->record('user.rejected', $user, [
            'new_status' => $user->status,
            'role' => $user->role,
        ], request()->user());

        return back()->with('status', 'User rejected successfully.');
    }

    public function promote(User $user, AuditLogService $auditLogService): RedirectResponse
    {
        $this->authorize('promote', $user);

        $user->forceFill([
            'role' => 'department_head',
        ])->save();

        $auditLogService->record('user.promoted', $user, [
            'role' => $user->role,
        ], request()->user());

        return back()->with('status', 'User promoted to Department Head.');
    }

    public function demote(User $user, AuditLogService $auditLogService): RedirectResponse
    {
        $this->authorize('demote', $user);

        $user->forceFill([
            'role' => 'staff',
        ])->save();

        $auditLogService->record('user.demoted', $user, [
            'role' => $user->role,
        ], request()->user());

        return back()->with('status', 'User demoted to Staff.');
    }
}
