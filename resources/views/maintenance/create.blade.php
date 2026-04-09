<x-app-layout>
    <section class="mx-auto max-w-4xl">
        <div class="mb-6">
            <a href="{{ route('maintenance.index') }}" class="app-link">
                Back to Maintenance
            </a>

            <p class="app-section-title mt-4">Operations</p>
            <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
                Create Maintenance Task
            </h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Schedule a maintenance task and assign it to an approved IT staff member.
            </p>
        </div>

        <div class="app-card px-6 py-6 sm:px-7">
            <form method="POST" action="{{ route('maintenance.store') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="server_room" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Server room
                    </label>
                    <input
                        type="text"
                        id="server_room"
                        name="server_room"
                        class="app-input"
                        value="{{ old('server_room') }}"
                        placeholder="Example: Server Room A"
                        required
                    >
                    @error('server_room')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="maintenance_date" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Maintenance date
                    </label>
                    <input
                        type="datetime-local"
                        id="maintenance_date"
                        name="maintenance_date"
                        class="app-input"
                        value="{{ old('maintenance_date') }}"
                        required
                    >
                    @error('maintenance_date')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="fix_description" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Fix description
                    </label>
                    <textarea
                        id="fix_description"
                        name="fix_description"
                        rows="7"
                        class="app-input min-h-[180px] resize-y"
                        placeholder="Describe the maintenance work clearly."
                        required
                    >{{ old('fix_description') }}</textarea>
                    @error('fix_description')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-5 md:grid-cols-3">
                    <div>
                        <label for="priority" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Priority
                        </label>
                        <select id="priority" name="priority" class="app-select" required>
                            <option value="">Select priority</option>
                            @foreach ($priorityOptions as $priority)
                                <option value="{{ $priority }}" @selected(old('priority') === $priority)>
                                    {{ ucfirst($priority) }}
                                </option>
                            @endforeach
                        </select>
                        @error('priority')
                            <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="status" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Status
                        </label>
                        <select id="status" name="status" class="app-select" required>
                            <option value="">Select status</option>
                            @foreach ($statusOptions as $status)
                                <option value="{{ $status }}" @selected(old('status') === $status)>
                                    {{ str_replace('_', ' ', ucfirst($status)) }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
                            <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="assigned_to_user_id" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Assigned IT staff
                        </label>
                        <select id="assigned_to_user_id" name="assigned_to_user_id" class="app-select" required>
                            <option value="">Select IT staff</option>
                            @foreach ($itStaffUsers as $staffUser)
                                <option value="{{ $staffUser->id }}" @selected(old('assigned_to_user_id') == $staffUser->id)>
                                    {{ $staffUser->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('assigned_to_user_id')
                            <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="app-button-primary">
                        Create Task
                    </button>
                </div>
            </form>
        </div>
    </section>
</x-app-layout>
