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
            'description' => ['nullable', 'string', 'max:2000'],
            'identifier' => [
                'required',
                'string',
                'min:3',
                'max:255',
                'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('servers', 'identifier'),
            ],
            'ip_address' => ['nullable', 'ip'],
            'server_type' => ['nullable', 'string', 'max:255'],
            'api_token' => ['nullable', 'string', 'min:16', 'max:255', Rule::unique('servers', 'api_token')],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->name) ? trim($this->name) : $this->name,
            'description' => is_string($this->description) ? trim($this->description) : $this->description,
            'identifier' => is_string($this->identifier)
                ? strtolower(trim($this->identifier))
                : $this->identifier,
            'ip_address' => is_string($this->ip_address) ? trim($this->ip_address) : $this->ip_address,
            'server_type' => is_string($this->server_type) ? trim($this->server_type) : $this->server_type,
            'api_token' => is_string($this->api_token) ? trim($this->api_token) : $this->api_token,
        ]);
    }
}
