<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="app-section-title">Administration</p>
                <h2 class="mt-2 app-heading">Admin Workspace</h2>
                <p class="mt-3 app-subtle">High-privilege controls for account approvals, audits, and protected actions.</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('admin.users.index') }}" class="app-button-primary">
                    Open Users
                </a>
            </div>
        </div>
    </x-slot>

    <section class="mx-auto max-w-7xl">
        <div class="grid gap-5 lg:grid-cols-3">
            <div class="app-card px-6 py-6">
                <p class="app-section-title">Access</p>
                <h3 class="mt-3 font-display text-2xl font-semibold text-gray-900 dark:text-white">Restricted</h3>
                <p class="mt-3 app-subtle">
                    Only approved Department Head accounts can access this workspace.
                </p>
            </div>

            <div class="app-card px-6 py-6">
                <p class="app-section-title">User Management</p>
                <h3 class="mt-3 font-display text-2xl font-semibold text-gray-900 dark:text-white">Approvals</h3>
                <p class="mt-3 app-subtle">
                    Review pending Department Head registrations and confirm access manually.
                </p>
            </div>

            <div class="app-card px-6 py-6">
                <p class="app-section-title">Audit Trail</p>
                <h3 class="mt-3 font-display text-2xl font-semibold text-gray-900 dark:text-white">Ready</h3>
                <p class="mt-3 app-subtle">
                    Audit logging tables are in place and can now back future admin actions.
                </p>
            </div>
        </div>
    </section>
</x-app-layout>
