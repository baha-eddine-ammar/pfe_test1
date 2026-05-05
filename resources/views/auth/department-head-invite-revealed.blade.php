<x-guest-layout>
    <div class="mb-8">
        <p class="app-section-title">Department Head Invite</p>
        <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
            Authorization code revealed
        </h1>
        <p class="mt-3 text-sm leading-7 text-gray-500 dark:text-gray-400">
            This code is shown only once. Save it now, then continue to the registration form for {{ $departmentHeadInvite->invited_email }}.
        </p>
    </div>

    <div class="rounded-3xl border border-brand-200 bg-brand-50 px-6 py-5 dark:border-brand-500/30 dark:bg-brand-500/10">
        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-brand-500 dark:text-brand-300">One-time code</p>
        <p class="mt-3 font-display text-3xl font-semibold tracking-[0.2em] text-gray-900 dark:text-white">
            {{ $authorizationCode }}
        </p>
    </div>

    <div class="mt-8 flex items-center justify-between gap-4">
        <a href="{{ route('login') }}" class="app-link">Back to sign in</a>
        <a href="{{ route('department-head.invites.register', $departmentHeadInvite) }}" class="app-link">Continue to registration</a>
    </div>
</x-guest-layout>
