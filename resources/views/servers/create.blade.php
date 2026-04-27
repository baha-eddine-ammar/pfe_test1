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
                Register a monitoring node. Provide the machine details below and Laravel will generate an API token automatically if you leave it blank.
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
                        Hostname / Identifier
                    </label>
                    <input
                        type="text"
                        id="identifier"
                        name="identifier"
                        class="app-input"
                        value="{{ old('identifier') }}"
                        placeholder="Example: srv-app-01.example.local"
                        required
                    >
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Use letters, numbers, dots, dashes, or underscores. Your monitoring agent will send this identifier with every metric push.
                    </p>
                    @error('identifier')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Description
                    </label>
                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                        class="app-input"
                        placeholder="Optional notes about the server role, owner, or environment."
                    >{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label for="ip_address" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            IP address
                        </label>
                        <input
                            type="text"
                            id="ip_address"
                            name="ip_address"
                            class="app-input"
                            value="{{ old('ip_address') }}"
                            placeholder="Example: 192.168.1.24"
                        >
                        @error('ip_address')
                            <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="server_type" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Server type
                        </label>
                        <input
                            type="text"
                            id="server_type"
                            name="server_type"
                            class="app-input"
                            value="{{ old('server_type') }}"
                            placeholder="Example: Workstation, Database, Application"
                        >
                        @error('server_type')
                            <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="api_token" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        API token
                    </label>
                    <input
                        type="text"
                        id="api_token"
                        name="api_token"
                        class="app-input"
                        value="{{ old('api_token') }}"
                        placeholder="Leave blank to auto-generate a secure token"
                    >
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Optional. If you already manage agent secrets externally, you can provide a token now.
                    </p>
                    @error('api_token')
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
