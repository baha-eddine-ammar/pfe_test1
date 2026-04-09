<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user?->isDepartmentHead() && $user->hasApprovedStatus();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'identifier' => [
                'required',
                'string',
                'min:3',
                'max:255',
                'alpha_dash',
                Rule::unique('servers', 'identifier'),
            ],
        ];
    }
}
