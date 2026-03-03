<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm text-gray-500">Department Head Area</p>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Admin
            </h2>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Restricted Access Confirmed</h3>
                <p class="mt-2 text-sm text-gray-600">
                    Only Department Head accounts can open this page. We will use this area for user management, audit review, and high-privilege actions.
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
