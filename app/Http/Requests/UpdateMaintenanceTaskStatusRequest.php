<?php

namespace App\Http\Requests;

use App\Models\MaintenanceTask;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMaintenanceTaskStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $maintenanceTask = $this->route('maintenanceTask');

        return $maintenanceTask
            ? ($this->user()?->can('updateStatus', $maintenanceTask) ?? false)
            : false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(MaintenanceTask::statusOptions())],
        ];
    }
}
