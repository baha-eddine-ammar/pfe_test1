<x-guest-layout>
    <div class="mb-8">
        <p class="app-section-title">Department Head Access</p>
        <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
            Use your secure invite
        </h1>
        <p class="mt-3 text-sm leading-7 text-gray-500 dark:text-gray-400">
            Department Head registration now uses one-time email invites. Open the reveal link from your invitation email to view your authorization code once, then continue with registration.
        </p>
    </div>

    <div class="space-y-4 text-sm leading-7 text-gray-500 dark:text-gray-400">
        <p>The invite email does not contain the code itself. It contains a one-time reveal link for better security.</p>
        <p>If your organization has not created its first Department Head yet, the protected first-time setup flow is available below.</p>
    </div>

    <div class="mt-8 flex flex-wrap items-center justify-between gap-4">
        <a href="{{ route('register') }}" class="app-link">Back to staff registration</a>

        @if ($bootstrapAvailable)
            <a href="{{ route('department-head.setup.create') }}" class="app-link">
                First Department Head setup
            </a>
        @endif
    </div>
</x-guest-layout>
