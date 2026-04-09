<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="app-section-title">Administration</p>
                <h2 class="mt-2 app-heading">Users</h2>
                <p class="mt-3 app-subtle">
                    Review pending accounts, approve or reject them, and manage user roles.
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
                    <h3 class="font-display text-xl font-semibold text-gray-900 dark:text-white">Pending Approvals</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        New accounts stay pending until a department head reviews them.
                    </p>
                </div>

                <span class="app-pill bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                    {{ $pendingUsers->count() }} pending
                </span>
            </div>

            <div class="overflow-x-auto">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Department</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pendingUsers as $listedUser)
                            <tr class="table-row-muted">
                                <td class="font-semibold text-gray-900 dark:text-white">{{ $listedUser->name }}</td>
                                <td>{{ $listedUser->email }}</td>
                                <td>{{ $listedUser->phone_number ?: 'Not provided' }}</td>
                                <td>{{ $listedUser->department }}</td>
                                <td>{{ $listedUser->isDepartmentHead() ? 'Department Head' : 'Staff' }}</td>
                                <td>
                                    <div class="flex flex-wrap gap-2">
                                        <form method="POST" action="{{ route('admin.users.approve', $listedUser) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="app-button-primary !px-4 !py-2 !text-xs">
                                                Approve
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.users.reject', $listedUser) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="app-button-secondary !px-4 !py-2 !text-xs">
                                                Reject
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                    No pending users found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="table-shell">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-5 dark:border-gray-800">
                <div>
                    <h3 class="font-display text-xl font-semibold text-gray-900 dark:text-white">All Users</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Full user list with status and role controls.
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Department</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Verified</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $listedUser)
                            <tr class="table-row-muted">
                                <td class="font-semibold text-gray-900 dark:text-white">{{ $listedUser->name }}</td>
                                <td>{{ $listedUser->email }}</td>
                                <td>{{ $listedUser->phone_number ?: 'Not provided' }}</td>
                                <td>{{ $listedUser->department }}</td>
                                <td>{{ $listedUser->isDepartmentHead() ? 'Department Head' : 'Staff' }}</td>
                                <td>
                                    @php
                                        $statusClass = match ($listedUser->statusLabel()) {
                                            'approved' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
                                            'rejected' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300',
                                            default => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
                                        };
                                    @endphp

                                    <span class="app-pill {{ $statusClass }}">
                                        {{ ucfirst($listedUser->statusLabel()) }}
                                    </span>
                                </td>
                                <td>
                                    @if ($listedUser->email_verified_at)
                                        <span class="app-pill bg-brand-100 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">Verified</span>
                                    @else
                                        <span class="app-pill bg-slate-100 text-slate-600 dark:bg-white/[0.03] dark:text-slate-300">Unverified</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex flex-wrap gap-2">
                                        @can('promote', $listedUser)
                                            <form method="POST" action="{{ route('admin.users.promote', $listedUser) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="app-button-primary !px-4 !py-2 !text-xs">
                                                    Promote
                                                </button>
                                            </form>
                                        @endcan

                                        @can('demote', $listedUser)
                                            <form method="POST" action="{{ route('admin.users.demote', $listedUser) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="app-button-secondary !px-4 !py-2 !text-xs">
                                                    Demote
                                                </button>
                                            </form>
                                        @endcan

                                        @if (! auth()->user()->is($listedUser) && ! auth()->user()->can('promote', $listedUser) && ! auth()->user()->can('demote', $listedUser))
                                            <span class="text-xs font-medium uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">
                                                No action
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
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
