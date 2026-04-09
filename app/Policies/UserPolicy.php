<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isDepartmentHead() && $user->hasApprovedStatus();
    }

    /**
     * Determine whether the user can approve a pending account.
     */
    public function approve(User $user, User $targetUser): bool
    {
        return $user->isDepartmentHead()
            && $user->hasApprovedStatus()
            && $targetUser->statusLabel() === 'pending';
    }

    public function reject(User $user, User $targetUser): bool
    {
        return $user->isDepartmentHead()
            && $user->hasApprovedStatus()
            && $targetUser->statusLabel() === 'pending';
    }

    public function promote(User $user, User $targetUser): bool
    {
        return $user->isDepartmentHead()
            && $user->hasApprovedStatus()
            && $user->id !== $targetUser->id
            && ! $targetUser->isDepartmentHead();
    }

    public function demote(User $user, User $targetUser): bool
    {
        return $user->isDepartmentHead()
            && $user->hasApprovedStatus()
            && $user->id !== $targetUser->id
            && $targetUser->isDepartmentHead();
    }
}
