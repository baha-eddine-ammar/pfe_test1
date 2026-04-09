<x-app-layout>
    <section class="mx-auto max-w-4xl">
        <div class="mb-6">
            <a href="{{ route('servers.index') }}" class="app-link">
                Back to Servers
            </a>

            <p class="app-section-title mt-4">Infrastructure</p>
            <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
                Add Server
            </h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Register a monitoring node. Laravel will generate the API token automatically after creation.
            </p>
        </div>

        <div class="app-card px-6 py-6 sm:px-7">
            <form method="POST" action="{{ route('servers.store') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="name" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Server name
                    </label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        class="app-input"
                        value="{{ old('name') }}"
                        placeholder="Example: SRV-APP-01"
                        required
                    >
                    @error('name')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="identifier" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Agent identifier
                    </label>
                    <input
                        type="text"
                        id="identifier"
                        name="identifier"
                        class="app-input"
                        value="{{ old('identifier') }}"
                        placeholder="Example: srv-app-01"
                        required
                    >
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Use only letters, numbers, dashes, and underscores. Your monitoring agent will send this identifier with every metric push.
                    </p>
                    @error('identifier')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="app-button-primary">
                        Create Server
                    </button>
                </div>
            </form>
        </div>
    </section>
</x-app-layout>
