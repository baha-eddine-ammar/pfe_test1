<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Server Room Supervision</p>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Dashboard
                </h2>
            </div>
            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-emerald-700">
                Verified Access
            </span>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
            <div class="grid gap-6 md:grid-cols-3">
                <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm text-gray-500">User</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $user->name }}</p>
                    <p class="mt-1 text-sm text-gray-600">{{ $user->email }}</p>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm text-gray-500">Department</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $user->department }}</p>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm text-gray-500">Role</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900">
                        {{ $user->role === 'department_head' ? 'Department Head' : 'IT Staff' }}
                    </p>
                </div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Access Status</h3>
                <p class="mt-2 text-sm text-gray-600">
                    Your account is authenticated and email-verified. This dashboard will become the main supervision control center.
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
