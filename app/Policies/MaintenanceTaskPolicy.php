<?php

namespace App\Policies;

use App\Models\MaintenanceTask;
use App\Models\User;

class MaintenanceTaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isApprovedDepartmentHead($user) || $this->isApprovedItStaff($user);
    }

    public function view(User $user, MaintenanceTask $maintenanceTask): bool
    {
        if ($this->isApprovedDepartmentHead($user)) {
            return true;
        }

        return $this->isApprovedItStaff($user)
            && $maintenanceTask->assigned_to_user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->isApprovedDepartmentHead($user);
    }

    public function update(User $user, MaintenanceTask $maintenanceTask): bool
    {
        return $this->isApprovedDepartmentHead($user);
    }

    public function assign(User $user, MaintenanceTask $maintenanceTask): bool
    {
        return $this->isApprovedDepartmentHead($user);
    }

    public function schedule(User $user, MaintenanceTask $maintenanceTask): bool
    {
        return $this->isApprovedDepartmentHead($user);
    }

    public function updateStatus(User $user, MaintenanceTask $maintenanceTask): bool
    {
        if ($this->isApprovedDepartmentHead($user)) {
            return true;
        }

        return $this->isApprovedItStaff($user)
            && $maintenanceTask->assigned_to_user_id === $user->id;
    }

    protected function isApprovedDepartmentHead(User $user): bool
    {
        return $user->isDepartmentHead() && $user->hasApprovedStatus();
    }

    protected function isApprovedItStaff(User $user): bool
    {
        return $user->isStaff() && $user->hasApprovedStatus();
    }
}
