<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm text-gray-500">Department Head Area</p>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Users
            </h2>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Registered Users</h3>
                <p class="mt-2 text-sm text-gray-600">
                    Review registered accounts, their role, and whether they are approved.
                </p>
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Department</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Approval</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Verified</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($users as $listedUser)
                                <tr>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $listedUser->name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $listedUser->email }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $listedUser->department }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $listedUser->role === 'department_head' ? 'Department Head' : 'IT Staff' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        @if ($listedUser->is_approved)
                                            <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                                Approved
                                            </span>
                                        @else
                                            <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                                                Pending
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $listedUser->email_verified_at ? 'Yes' : 'No' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-sm text-gray-500">No users found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
