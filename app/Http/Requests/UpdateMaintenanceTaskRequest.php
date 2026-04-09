<?php

namespace App\Http\Requests;

use App\Models\MaintenanceTask;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMaintenanceTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $maintenanceTask = $this->route('maintenanceTask');

        return $maintenanceTask
            ? ($this->user()?->can('update', $maintenanceTask) ?? false)
            : false;
    }

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
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->whereIn('role', ['staff', 'it_staff'])
                        ->where('status', 'approved');
                }),
            ],
        ];
    }
}
