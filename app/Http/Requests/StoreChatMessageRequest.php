<?php

/*
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| This FormRequest validates a new team chat message before it is stored.
|
| Why this file exists:
| It keeps the controller clean and centralizes chat message validation rules.
|
| When this file is used:
| Before ChatController@store creates a new message row.
|
| FILES TO READ (IN ORDER):
| 1. app/Policies/MessagePolicy.php
| 2. app/Http/Requests/StoreChatMessageRequest.php
| 3. app/Http/Controllers/ChatController.php
| 4. app/Services/ChatWorkspaceService.php
*/

namespace App\Http\Requests;

use App\Models\Message;
use Illuminate\Foundation\Http\FormRequest;

class StoreChatMessageRequest extends FormRequest
{
    // Only authorized authenticated users may post messages.
    public function authorize(): bool
    {
        return $this->user()?->can('create', Message::class) ?? false;
    }

    // Trim the message before validation so a message made only of spaces
    // becomes empty and correctly fails the "required" rule.
    protected function prepareForValidation(): void
    {
        $this->merge([
            'body' => trim((string) $this->input('body', '')),
        ]);
    }

    // Team chat messages must stay reasonably short and non-empty.
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:1000'],
        ];
    }
}
