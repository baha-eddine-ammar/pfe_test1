<?php

namespace App\Http\Requests;

use App\Models\DepartmentHeadInvite;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreDepartmentHeadInviteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'invited_email' => strtolower(trim((string) $this->input('invited_email'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'invited_email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                'unique:'.User::class.',email',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $alreadyInvited = DepartmentHeadInvite::query()
                        ->where('invited_email', $value)
                        ->whereNull('used_at')
                        ->whereNull('revoked_at')
                        ->where('expires_at', '>', now())
                        ->exists();

                    if ($alreadyInvited) {
                        $fail('An active Department Head invite already exists for this email address.');
                    }
                },
            ],
        ];
    }
}
