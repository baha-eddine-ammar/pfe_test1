<x-guest-layout>
    <div class="mb-8">
        <p class="app-section-title">Department Head Invite</p>
        <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
            Invite status
        </h1>
        <p class="mt-3 text-sm leading-7 text-gray-500 dark:text-gray-400">
            {{ $message }}
        </p>
    </div>

    <div class="mt-8 flex items-center justify-between gap-4">
        <a href="{{ route('login') }}" class="app-link">Back to sign in</a>
        <a href="{{ route('department-head.invites.landing') }}" class="app-link">Invite help</a>
    </div>
</x-guest-layout>
