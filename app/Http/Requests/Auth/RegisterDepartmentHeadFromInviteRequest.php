<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class RegisterDepartmentHeadFromInviteRequest extends FormRequest
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
            'authorization_code' => strtoupper(trim((string) $this->input('authorization_code'))),
        ]);
    }

    public function rules(): array
    {
        $invite = $this->route('departmentHeadInvite');
        $invitedEmail = $invite?->invited_email;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::in(array_filter([$invitedEmail])),
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
            'authorization_code' => ['required', 'string', 'min:8', 'max:20'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];
    }
}
