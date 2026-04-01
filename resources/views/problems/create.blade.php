<x-app-layout>
    <section class="mx-auto max-w-4xl">
        <div class="mb-6">
            <p class="app-section-title">Knowledge</p>
            <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
                Submit a Problem
            </h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Use this form to report a problem so other users can review it and suggest solutions.
            </p>
        </div>

        <div class="app-card px-6 py-6 sm:px-7">
            @if (session('success'))
                <div class="app-status-success mb-5">
                    {{ session('success') }}
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('problems.store') }}"
                enctype="multipart/form-data"
                class="space-y-5"
            >
                @csrf

                <div>
                    <label for="title" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Problem title
                    </label>
                    <input
                        type="text"
                        id="title"
                        name="title"
                        class="app-input"
                        value="{{ old('title') }}"
                        placeholder="Example: Server room access card is not working"
                        required
                    >

                    @error('title')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Problem description
                    </label>
                    <textarea
                        id="description"
                        name="description"
                        rows="8"
                        class="app-input min-h-[180px] resize-y"
                        placeholder="Describe the problem clearly. Explain what happened, when it happened, and any useful details."
                        required
                    >{{ old('description') }}</textarea>

                    @error('description')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="attachments" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Attach files (optional)
                    </label>
                    <input
                        type="file"
                        id="attachments"
                        name="attachments[]"
                        class="app-input py-3"
                        multiple
                        accept=".pdf,.doc,.docx,.txt,.png,.jpg,.jpeg,.zip"
                    >

                    @error('attachments')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror

                    @error('attachments.*')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror

                    <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">
                        Allowed file types: PDF, DOC, DOCX, TXT, PNG, JPG, JPEG, ZIP. Maximum file size: 10 MB each.
                    </p>
                </div>

                <div class="flex items-center justify-between gap-4">
                    <p class="text-xs text-gray-400 dark:text-gray-500">
                        Attachments will be stored with this problem so users can review them later.
                    </p>

                    <button type="submit" class="app-button-primary">
                        Submit Problem
                    </button>
                </div>
            </form>
        </div>
    </section>
</x-app-layout>
