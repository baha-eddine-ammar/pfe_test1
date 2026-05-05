<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyTwoFactorCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => preg_replace('/\s+/', '', (string) $this->input('code')),
        ]);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'regex:/^\d{6}$/'],
        ];
    }
}
