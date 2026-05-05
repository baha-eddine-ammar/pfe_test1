<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class RegisterStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->input('email'))),
            'phone_number' => trim((string) $this->input('phone_number')),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                'unique:'.User::class,
            ],
            'department' => [
                'required',
                'string',
                Rule::in(['Network', 'Security', 'Systems', 'Infrastructure']),
            ],
            'phone_number' => [
                'required',
                'string',
                'min:8',
                'max:20',
                'regex:/^[0-9+\-\s()]+$/',
            ],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];
    }
}
