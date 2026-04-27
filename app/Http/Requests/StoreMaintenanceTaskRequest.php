<?php

/*
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| This FormRequest validates data when a new maintenance task is created.
|
| Why this file exists:
| Validation rules should live outside the controller so the controller stays
| focused on flow, not raw rule definitions.
|
| When this file is used:
| Before MaintenanceTaskController@store is allowed to create a task.
|
| FILES TO READ (IN ORDER):
| 1. app/Policies/MaintenanceTaskPolicy.php
| 2. app/Http/Requests/StoreMaintenanceTaskRequest.php
| 3. app/Http/Controllers/MaintenanceTaskController.php
| 4. app/Services/MaintenanceTaskWorkflowService.php
|
| HOW TO UNDERSTAND THIS FEATURE:
| 1. Authorization is checked here first.
| 2. Input is trimmed/prepared.
| 3. Validation rules are applied.
| 4. Only validated data reaches the controller/service.
*/

namespace App\Http\Requests;

use App\Models\MaintenanceTask;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaintenanceTaskRequest extends FormRequest
{
    // Authorization uses the maintenance policy.
    // If this returns false, the controller method will not run.
    public function authorize(): bool
    {
        return $this->user()?->can('create', MaintenanceTask::class) ?? false;
    }

    // These rules define exactly what a valid task creation payload looks like.
    public function rules(): array
    {
        return [
            'server_room' => ['required', 'string', 'max:255'],
            'maintenance_date' => ['required', 'date'],
            'fix_description' => ['required', 'string', 'max:10000'],
            'priority' => ['required', Rule::in(MaintenanceTask::priorityOptions())],
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

    // Trimming happens before validation so values like "   Room A   " are
    // stored and validated as clean input.
    protected function prepareForValidation(): void
    {
        $this->merge([
            'server_room' => is_string($this->server_room) ? trim($this->server_room) : $this->server_room,
            'fix_description' => is_string($this->fix_description) ? trim($this->fix_description) : $this->fix_description,
        ]);
    }
}
