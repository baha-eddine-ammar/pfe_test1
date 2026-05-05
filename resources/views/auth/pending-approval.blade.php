<x-guest-layout>
    <div class="mb-8">
        <p class="app-section-title">Registration received</p>
        <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
            Account pending approval
        </h1>
        <p class="mt-3 text-sm leading-7 text-gray-500 dark:text-gray-400">
            Your staff registration has been created. A Department Head must approve your access before you can enter the InfraGuard workspace.
        </p>
    </div>

    @if (session('status'))
        <div class="app-status-success mb-6">
            {{ session('status') }}
        </div>
    @endif

    <div class="space-y-4 text-sm leading-7 text-gray-500 dark:text-gray-400">
        @if ($registeredEmail)
            <p>
                Registered email:
                <span class="font-medium text-gray-900 dark:text-white">{{ $registeredEmail }}</span>
            </p>
        @endif

        <p>We will notify you by email when your account is approved and when email verification is ready.</p>
    </div>

    <div class="mt-8 flex flex-wrap items-center justify-between gap-4">
        <a href="{{ route('login') }}" class="app-link">Back to sign in</a>
        <a href="{{ route('register') }}" class="app-link">Register another staff account</a>
    </div>
</x-guest-layout>
