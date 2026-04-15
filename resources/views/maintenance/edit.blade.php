<x-app-layout>
    <section class="mx-auto max-w-4xl">
        <div class="mb-6">
            <a href="{{ route('maintenance.show', $maintenanceTask) }}" class="app-link">
                Back to Task Details
            </a>

            <p class="app-section-title mt-4">Operations</p>
            <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
                Edit Maintenance Task
            </h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Update the schedule, assignment, priority, and status of this maintenance task.
            </p>
        </div>

        <div class="app-card px-6 py-6 sm:px-7">
            <form method="POST" action="{{ route('maintenance.update', $maintenanceTask) }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label for="server_room" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Server room
                    </label>
                    <input
                        type="text"
                        id="server_room"
                        name="server_room"
                        class="app-input"
                        value="{{ old('server_room', $maintenanceTask->server_room) }}"
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
                        value="{{ old('maintenance_date', $maintenanceTask->maintenance_date->format('Y-m-d\\TH:i')) }}"
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
                        required
                    >{{ old('fix_description', $maintenanceTask->fix_description) }}</textarea>
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
                            @foreach ($priorityOptions as $priority)
                                <option value="{{ $priority }}" @selected(old('priority', $maintenanceTask->priority) === $priority)>
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
                            @foreach ($statusOptions as $status)
                                <option value="{{ $status }}" @selected(old('status', $maintenanceTask->status) === $status)>
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
                            Assigned user
                        </label>
                        <select id="assigned_to_user_id" name="assigned_to_user_id" class="app-select" required>
                            @foreach ($itStaffUsers as $staffUser)
                                <option value="{{ $staffUser->id }}" @selected(old('assigned_to_user_id', $maintenanceTask->assigned_to_user_id) == $staffUser->id)>
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
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </section>
</x-app-layout>
