<x-guest-layout>
    <div class="mb-8">
        <p class="app-section-title">Initial Setup</p>
        <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
            Create the first Department Head
        </h1>
        <p class="mt-3 text-sm leading-7 text-gray-500 dark:text-gray-400">
            This setup path is only available while the system has no Department Head accounts. Email verification is still required after registration.
        </p>
    </div>

    <form method="POST" action="{{ route('department-head.setup.store') }}">
        @csrf

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="mt-1 block w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
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
            <x-input-label for="setup_key" :value="__('Initial Setup Key')" />
            <x-text-input id="setup_key" class="mt-1 block w-full" type="password" name="setup_key" required autocomplete="off" />
            <x-input-error :messages="$errors->get('setup_key')" class="mt-2" />
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
            <a href="{{ route('register') }}" class="app-link">Back to staff registration</a>

            <x-primary-button>
                Create Department Head
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
