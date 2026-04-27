<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * A valid bcrypt hash used to keep password verification work consistent
     * even when the email address does not exist.
     */
    private const DUMMY_PASSWORD_HASH = '$2y$10$4wuSbJ.l0tuDivgsSgP3/eSPsnTAwugL9IKt5CINg2jTFrt4JIRkW';

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $email = Str::lower(trim($this->string('email')->toString()));
        $password = $this->string('password')->toString();

        $user = User::query()
            ->where('email', $email)
            ->first();

        $passwordMatches = Hash::check(
            $password,
            $user?->password ?? self::DUMMY_PASSWORD_HASH,
        );

        if (! $user || ! $passwordMatches) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        if (Hash::needsRehash($user->password)) {
            $user->forceFill([
                'password' => Hash::make($password),
            ])->save();
        }

        if (! $user->hasApprovedStatus()) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => $user->statusLabel() === 'pending'
                    ? 'Your account is pending approval.'
                    : 'Your account does not currently have access.',
            ]);
        }

        Auth::login($user, $this->boolean('remember'));

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower(trim($this->string('email')->toString())).'|'.$this->ip());
    }
}
