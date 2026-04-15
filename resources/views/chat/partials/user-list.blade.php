<div class="space-y-3">
    <button
        type="button"
        data-chat-filter-user=""
        class="{{ $selectedUserId === '' ? 'chat-user-item chat-user-item--active' : 'chat-user-item' }}"
    >
        <div class="flex items-center gap-3">
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-950 text-sm font-semibold text-white dark:bg-white dark:text-slate-950">
                ALL
            </div>

            <div class="text-left">
                <p class="text-sm font-semibold text-slate-950 dark:text-white">All team members</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Clear the sender filter and watch the full room.</p>
            </div>
        </div>
    </button>

    @foreach ($users as $user)
        <x-chat.user-item
            :user="$user"
            :selected="$selectedUserId !== '' && (string) $selectedUserId === (string) $user['id']"
        />
    @endforeach
</div>
