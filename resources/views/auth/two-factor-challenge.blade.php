<x-guest-layout>
    <div class="mb-8">
        <p class="app-section-title">Two-Factor Verification</p>
        <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
            Confirm your sign-in
        </h1>
        <p class="mt-3 text-sm leading-7 text-gray-500 dark:text-gray-400">
            A one-time verification code was sent to {{ $pendingUser?->email }}. Approved accounts must complete this step before access is granted.
        </p>
    </div>

    <x-auth-session-status class="mb-6" :status="session('status')" />

    <form method="POST" action="{{ route('two-factor.verify') }}">
        @csrf

        <div>
            <x-input-label for="code" value="Email verification code" />
            <x-text-input id="code" class="mt-1 block w-full text-center tracking-[0.4em]" type="text" name="code" :value="old('code')" required autofocus autocomplete="one-time-code" inputmode="numeric" />
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Codes expire after 5 minutes, can only be used once, and lock after 3 invalid attempts.
            </p>
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="mt-6 flex items-center justify-between gap-4">
            <button type="submit" form="two-factor-cancel-form" class="app-link">Cancel sign in</button>

            <x-primary-button>
                Verify and continue
            </x-primary-button>
        </div>
    </form>

    <form id="two-factor-cancel-form" method="POST" action="{{ route('two-factor.cancel') }}" class="hidden">
        @csrf
    </form>

    <div
        class="mt-6"
        x-data="{ seconds: {{ $secondsUntilResend }} }"
        x-init="if (seconds > 0) { const timer = setInterval(() => { seconds -= 1; if (seconds <= 0) { clearInterval(timer); } }, 1000); }"
    >
        <form method="POST" action="{{ route('two-factor.resend') }}">
            @csrf
            <button
                type="submit"
                class="app-link disabled:opacity-50 disabled:cursor-not-allowed"
                :disabled="seconds > 0"
            >
                <span x-show="seconds <= 0">Resend verification code</span>
                <span x-show="seconds > 0" x-text="`Resend available in ${seconds}s`"></span>
            </button>
        </form>
    </div>
</x-guest-layout>
