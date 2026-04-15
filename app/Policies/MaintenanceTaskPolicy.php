<?php

/*
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| This policy defines who may interact with maintenance tasks.
| Policies are Laravel's built-in way to keep authorization rules organized.
|
| Why this file exists:
| The maintenance module has role-based rules:
| - department heads manage everything
| - staff can only see/update tasks assigned to them
|
| When this file is used:
| Whenever a controller calls $this->authorize(...) for a maintenance task action.
|
| FILES TO READ (IN ORDER):
| 1. app/Models/User.php
| 2. app/Models/MaintenanceTask.php
| 3. app/Policies/MaintenanceTaskPolicy.php
| 4. app/Http/Controllers/MaintenanceTaskController.php
|
| HOW TO UNDERSTAND THIS FEATURE:
| 1. Read the user role helpers.
| 2. Read this policy to see allowed actions.
| 3. Read the controller to see where these rules are enforced.
*/

namespace App\Policies;

use App\Models\MaintenanceTask;
use App\Models\User;

class MaintenanceTaskPolicy
{
    // Both approved department heads and approved staff may open the maintenance module.
    public function viewAny(User $user): bool
    {
        return $this->isApprovedDepartmentHead($user) || $this->isApprovedItStaff($user);
    }

    // Staff visibility is limited to their own assigned tasks.
    public function view(User $user, MaintenanceTask $maintenanceTask): bool
    {
        if ($this->isApprovedDepartmentHead($user)) {
            return true;
        }

        return $this->isApprovedItStaff($user)
            && $maintenanceTask->assigned_to_user_id === $user->id;
    }

    // Only department heads may create tasks.
    public function create(User $user): bool
    {
        return $this->isApprovedDepartmentHead($user);
    }

    // Only department heads may edit full task details.
    public function update(User $user, MaintenanceTask $maintenanceTask): bool
    {
        return $this->isApprovedDepartmentHead($user);
    }

    // Explicit policy methods like assign/schedule make the domain intent clearer,
    // even if they currently follow the same rule as update/create.
    public function assign(User $user, MaintenanceTask $maintenanceTask): bool
    {
        return $this->isApprovedDepartmentHead($user);
    }

    public function schedule(User $user, MaintenanceTask $maintenanceTask): bool
    {
        return $this->isApprovedDepartmentHead($user);
    }

    // Staff may update the status of their own tasks; department heads may update any.
    public function updateStatus(User $user, MaintenanceTask $maintenanceTask): bool
    {
        if ($this->isApprovedDepartmentHead($user)) {
            return true;
        }

        return $this->isApprovedItStaff($user)
            && $maintenanceTask->assigned_to_user_id === $user->id;
    }

    // Delete remains restricted to department heads.
    public function delete(User $user, MaintenanceTask $maintenanceTask): bool
    {
        return $this->isApprovedDepartmentHead($user);
    }

    // Shared role helper used by the policy methods above.
    protected function isApprovedDepartmentHead(User $user): bool
    {
        return $user->isDepartmentHead() && $user->hasApprovedStatus();
    }

    // Shared role helper used by the policy methods above.
    protected function isApprovedItStaff(User $user): bool
    {
        return $user->isStaff() && $user->hasApprovedStatus();
    }
}
