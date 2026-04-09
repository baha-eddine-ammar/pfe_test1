<x-guest-layout>
    <div class="mb-8">
        <p class="app-section-title">Onboarding</p>
        <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
            Create account
        </h1>
        <p class="mt-3 text-sm leading-7 text-gray-500 dark:text-gray-400">
            Register your department account with a valid <span class="font-semibold">@draxmailer</span> email address.
        </p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="department" :value="__('Department')" />
            <select
                id="department"
                name="department"
                class="app-select mt-1 block w-full"
                required
            >
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
            <x-text-input id="phone_number" class="block mt-1 w-full" type="text" name="phone_number" :value="old('phone_number')" required autocomplete="tel" />
            <x-input-error :messages="$errors->get('phone_number')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="department_head_key" :value="__('Department Head Key')" />
            <x-text-input
                id="department_head_key"
                class="block mt-1 w-full"
                type="password"
                name="department_head_key"
                :value="old('department_head_key')"
                autocomplete="off"
            />
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Optional. Leave this blank unless you were given the department head key.
            </p>
            <x-input-error :messages="$errors->get('department_head_key')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-6 flex items-center justify-between gap-4">
            <a class="app-link" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button>
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
