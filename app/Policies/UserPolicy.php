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
        return $user->role === 'department_head' && $user->is_approved;
    }

    /**
     * Determine whether the user can approve a pending Department Head.
     */
    public function approve(User $user, User $targetUser): bool
    {
        return $user->role === 'department_head'
            && $user->is_approved
            && $targetUser->role === 'department_head'
            && ! $targetUser->is_approved;
    }
}
