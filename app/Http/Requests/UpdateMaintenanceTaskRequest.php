<?php

/*
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| This FormRequest validates data when an existing maintenance task is edited.
|
| Why this file exists:
| Update rules are similar to create rules, but authorization depends on the
| specific maintenance task taken from the route.
|
| When this file is used:
| Before MaintenanceTaskController@update saves edited task data.
|
| FILES TO READ (IN ORDER):
| 1. app/Policies/MaintenanceTaskPolicy.php
| 2. app/Http/Requests/UpdateMaintenanceTaskRequest.php
| 3. app/Http/Controllers/MaintenanceTaskController.php
| 4. app/Services/MaintenanceTaskWorkflowService.php
*/

namespace App\Http\Requests;

use App\Models\MaintenanceTask;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMaintenanceTaskRequest extends FormRequest
{
    // The policy checks whether the current user may edit this exact task.
    public function authorize(): bool
    {
        $maintenanceTask = $this->route('maintenanceTask');

        return $maintenanceTask
            ? ($this->user()?->can('update', $maintenanceTask) ?? false)
            : false;
    }

    // The update form can also change status, so that field is validated here too.
    public function rules(): array
    {
        return [
            'server_room' => ['required', 'string', 'max:255'],
            'maintenance_date' => ['required', 'date'],
            'fix_description' => ['required', 'string', 'max:10000'],
            'priority' => ['required', Rule::in(MaintenanceTask::priorityOptions())],
            'status' => ['required', Rule::in(MaintenanceTask::statusOptions())],
            'assigned_to_user_id' => [
                'required',
                Rule::exists((new User)->getTable(), 'id')->where(function ($query): void {
                    $query
                        ->whereIn('role', ['staff', 'it_staff'])
                        ->where(function ($approvalQuery): void {
                            $approvalQuery
                                ->where('status', 'approved')
                                ->orWhere(function ($legacyQuery): void {
                                    $legacyQuery
                                        ->whereNull('status')
                                        ->where('is_approved', true);
                                });
                        });
                }),
            ],
        ];
    }

    // Trim text fields before validation and storage.
    protected function prepareForValidation(): void
    {
        $this->merge([
            'server_room' => is_string($this->server_room) ? trim($this->server_room) : $this->server_room,
            'fix_description' => is_string($this->fix_description) ? trim($this->fix_description) : $this->fix_description,
        ]);
    }
}
