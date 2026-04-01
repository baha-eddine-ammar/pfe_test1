<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="app-section-title">Administration</p>
                <h2 class="mt-2 app-heading">Users</h2>
                <p class="mt-3 app-subtle">
                    Review registered accounts, approvals, and verification state without leaving the admin workspace.
                </p>
            </div>

            <div class="rounded-full border border-gray-200 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.28em] text-gray-500 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400">
                {{ $users->count() }} accounts
            </div>
        </div>
    </x-slot>

    <section class="mx-auto max-w-7xl space-y-6">
        @if (session('status'))
            <div class="app-status-success">
                {{ session('status') }}
            </div>
        @endif

        <div class="table-shell">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-5 dark:border-gray-800">
                <div>
                    <h3 class="font-display text-xl font-semibold text-gray-900 dark:text-white">Registered Users</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Department, role, approval, and verification state in one table.
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Role</th>
                            <th>Approval</th>
                            <th>Verified</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $listedUser)
                            <tr class="table-row-muted">
                                <td>
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ $listedUser->name }}</div>
                                </td>
                                <td>{{ $listedUser->email }}</td>
                                <td>{{ $listedUser->department }}</td>
                                <td>
                                    {{ $listedUser->role === 'department_head' ? 'Department Head' : 'IT Staff' }}
                                </td>
                                <td>
                                    @if ($listedUser->is_approved)
                                        <span class="app-pill bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                                            Approved
                                        </span>
                                    @else
                                        <span class="app-pill bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                                            Pending
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if ($listedUser->email_verified_at)
                                        <span class="app-pill bg-brand-100 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">Verified</span>
                                    @else
                                        <span class="app-pill bg-slate-100 text-slate-600 dark:bg-white/[0.03] dark:text-slate-300">Unverified</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($listedUser->role === 'department_head' && ! $listedUser->is_approved)
                                        <form method="POST" action="{{ route('admin.users.approve', $listedUser) }}">
                                            @csrf
                                            @method('PATCH')

                                            <button type="submit" class="app-button-primary !px-4 !py-2 !text-xs">
                                                Approve
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs font-medium uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">No action</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                    No users found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</x-app-layout>
