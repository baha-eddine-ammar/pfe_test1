<?php

/*
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| This FormRequest validates status-only updates for maintenance tasks.
|
| Why this file exists:
| Staff do not edit the full task. They usually only change the task status
| and optionally leave a note, so this request handles that smaller workflow.
|
| When this file is used:
| Before MaintenanceTaskController@updateStatus is allowed to run.
|
| FILES TO READ (IN ORDER):
| 1. app/Models/MaintenanceTask.php
| 2. app/Policies/MaintenanceTaskPolicy.php
| 3. app/Http/Requests/UpdateMaintenanceTaskStatusRequest.php
| 4. app/Http/Controllers/MaintenanceTaskController.php
*/

namespace App\Http\Requests;

use App\Models\MaintenanceTask;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMaintenanceTaskStatusRequest extends FormRequest
{
    // Authorization depends on the task loaded from the route parameter.
    public function authorize(): bool
    {
        $maintenanceTask = $this->route('maintenanceTask');

        return $maintenanceTask
            ? ($this->user()?->can('updateStatus', $maintenanceTask) ?? false)
            : false;
    }

    // Only status and note are allowed in this lightweight workflow.
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(MaintenanceTask::statusOptions())],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    // Extra validation step:
    // even if the status value exists globally, the current user may not be
    // allowed to move this specific task into that specific next state.
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $maintenanceTask = $this->route('maintenanceTask');
            $user = $this->user();

            if (! $maintenanceTask || ! $user) {
                return;
            }

            if (! in_array($this->input('status'), $maintenanceTask->allowedStatusTransitionsFor($user), true)) {
                $validator->errors()->add('status', 'The selected status transition is not allowed for this task.');
            }
        });
    }
}
