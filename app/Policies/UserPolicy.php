<?php

/*
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| This policy defines who may manage user accounts in the admin area.
|
| Why this file exists:
| The project has a role-based admin workflow where department heads can
| approve, reject, promote, or demote other users.
|
| When this file is used:
| Whenever the admin user-management controller authorizes an action on User.
*/

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    // Only approved department heads may open the user-management area.
    public function viewAny(User $user): bool
    {
        return $user->isDepartmentHead() && $user->hasApprovedStatus();
    }

    // Pending accounts may be approved only by approved department heads.
    public function approve(User $user, User $targetUser): bool
    {
        return $user->isDepartmentHead()
            && $user->hasApprovedStatus()
            && $targetUser->statusLabel() === 'pending';
    }

    // Reject follows the same rule as approve.
    public function reject(User $user, User $targetUser): bool
    {
        return $user->isDepartmentHead()
            && $user->hasApprovedStatus()
            && $targetUser->statusLabel() === 'pending';
    }

    // Promotion prevents department heads from promoting themselves and from
    // promoting someone who is already a department head.
    public function promote(User $user, User $targetUser): bool
    {
        return $user->isDepartmentHead()
            && $user->hasApprovedStatus()
            && $user->id !== $targetUser->id
            && ! $targetUser->isDepartmentHead();
    }

    // Demotion also blocks self-demotion and only applies to current department heads.
    public function demote(User $user, User $targetUser): bool
    {
        return $user->isDepartmentHead()
            && $user->hasApprovedStatus()
            && $user->id !== $targetUser->id
            && $targetUser->isDepartmentHead();
    }
}
