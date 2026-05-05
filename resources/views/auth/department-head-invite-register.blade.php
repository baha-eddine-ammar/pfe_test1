<x-guest-layout>
    <div class="mb-8">
        <p class="app-section-title">Department Head Invite</p>
        <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
            Complete Department Head registration
        </h1>
        <p class="mt-3 text-sm leading-7 text-gray-500 dark:text-gray-400">
            This registration is locked to {{ $departmentHeadInvite->invited_email }}. Enter the one-time authorization code you revealed earlier and finish the account setup.
        </p>
    </div>

    <form method="POST" action="{{ route('department-head.invites.store', $departmentHeadInvite) }}">
        @csrf

        <div>
            <x-input-label for="email_display" value="Invite email" />
            <x-text-input id="email_display" class="mt-1 block w-full" type="email" :value="$departmentHeadInvite->invited_email" disabled />
            <input type="hidden" name="email" value="{{ old('email', $departmentHeadInvite->invited_email) }}">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="mt-1 block w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="department" :value="__('Department')" />
            <select id="department" name="department" class="app-select mt-1 block w-full" required>
                <option value="">Select a department</option>
                <option value="Network" @selected(old('department') === 'Network')>Network</option>
                <option value="Security" @selected(old('department') === 'Security')>Security</option>
                <option value="Systems" @selected(old('department') === 'Systems')>Systems</option>
                <option value="Infrastructure" @selected(old('department') === 'Infrastructure')>Infrastructure</option>
            </select>
            <x-input-error :messages="$errors->get('department')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="phone_number" :value="__('Phone Number')" />
            <x-text-input id="phone_number" class="mt-1 block w-full" type="text" name="phone_number" :value="old('phone_number')" required autocomplete="tel" />
            <x-input-error :messages="$errors->get('phone_number')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="authorization_code" value="Authorization code" />
            <x-text-input id="authorization_code" class="mt-1 block w-full uppercase tracking-[0.25em]" type="text" name="authorization_code" :value="old('authorization_code')" required autocomplete="one-time-code" />
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                The code can be revealed only once and can be used only for this invite email.
            </p>
            <x-input-error :messages="$errors->get('authorization_code')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="mt-1 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-6 flex items-center justify-between gap-4">
            <a href="{{ route('login') }}" class="app-link">Back to sign in</a>

            <x-primary-button>
                Create Department Head account
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
