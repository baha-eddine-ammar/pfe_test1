<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="app-section-title">Settings</p>
                <h2 class="mt-2 app-heading">{{ __('Profile') }}</h2>
                <p class="mt-3 app-subtle">Manage your account details, password, and session-sensitive actions.</p>
            </div>
        </div>
    </x-slot>

    <section class="mx-auto max-w-7xl space-y-6">
        <div class="app-form-section">
            <div class="max-w-2xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="app-form-section">
            <div class="max-w-2xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="app-form-section">
            <div class="max-w-2xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </section>
</x-app-layout>
