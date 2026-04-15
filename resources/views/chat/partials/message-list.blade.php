@forelse ($messages as $message)
    <x-chat.message :message="$message" />
@empty
    <div class="chat-empty-state">
        <div class="flex h-16 w-16 items-center justify-center rounded-[28px] bg-brand-50 text-brand-500 dark:bg-brand-500/10 dark:text-brand-300">
            <svg class="h-8 w-8" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
                <path d="M5.5 13.5L3 16v-2.8A5.5 5.5 0 018.5 7.7h3A5.5 5.5 0 0117 13.2v.3" stroke-linejoin="round"></path>
            </svg>
        </div>
        <h2 class="mt-5 font-display text-2xl font-semibold text-slate-950 dark:text-white">
            No messages matched this view
        </h2>
        <p class="mt-3 max-w-lg text-center text-sm leading-7 text-slate-500 dark:text-slate-400">
            Start the conversation, widen the filters, or switch back to the full team room to see the latest discussion.
        </p>
    </div>
@endforelse
