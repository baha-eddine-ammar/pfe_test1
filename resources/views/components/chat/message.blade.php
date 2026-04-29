{{--
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| Reusable component for one rendered chat message bubble.
|
| Data source:
| The $message array is prepared by ChatWorkspaceService::presentMessages().
| That service already decides alignment flags, safe rendered HTML, and mention state.
|--------------------------------------------------------------------------
--}}
@props([
    'message',
])

@php
    // Alignment/styling depends on whether the message belongs to the current user.
    $rowAlignment = $message['is_mine'] ? 'justify-end' : 'justify-start';
    $stackAlignment = $message['is_mine'] ? 'flex-row-reverse' : '';
    $avatarClasses = $message['is_mine']
        ? 'bg-brand-500 text-white shadow-lg shadow-brand-500/20'
        : 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-200';
    $bubbleClasses = $message['is_mine']
        ? 'chat-message-bubble chat-message-bubble--mine'
        : 'chat-message-bubble chat-message-bubble--other';

    if ($message['is_mentioned_me']) {
        $bubbleClasses .= ' chat-message-bubble--mentioned';
    }
@endphp

<article data-message-id="{{ $message['id'] }}" class="chat-message-row flex {{ $rowAlignment }}">
    {{--
        Each message shows:
        - avatar initials
        - sender name and handle
        - timestamp
        - safely rendered body
        - badges such as role and "Mentioned you"
    --}}
    <div class="flex max-w-[82%] items-end gap-2 {{ $stackAlignment }}">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-xs font-semibold {{ $avatarClasses }}">
            {{ $message['author']['initials'] }}
        </div>

        <div class="{{ $bubbleClasses }}">
            <div class="flex flex-wrap items-center gap-2">
                <p class="text-sm font-semibold text-slate-950 dark:text-white">
                    {{ $message['author']['name'] }}
                </p>
                <span class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">
                    {{ '@'.$message['author']['handle'] }}
                </span>
                <span class="text-[11px] font-medium text-slate-400 dark:text-slate-500">
                    {{ $message['created_at_label'] }}
                </span>
                @if ($message['is_mine'])
                    <button
                        type="button"
                        data-chat-delete-message="{{ $message['id'] }}"
                        data-chat-delete-url="{{ url('/chat/messages/'.$message['id']) }}"
                        class="chat-delete-message-button"
                        aria-label="Delete message"
                        title="Delete message"
                    >
                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <path d="M7 5V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v1" stroke-linecap="round"></path>
                            <path d="M4 5h12" stroke-linecap="round"></path>
                            <path d="M6 8l.5 8h7L14 8" stroke-linecap="round" stroke-linejoin="round"></path>
                            <path d="M9 9.5v4M11 9.5v4" stroke-linecap="round"></path>
                        </svg>
                    </button>
                @endif
            </div>

            <div class="mt-2 break-words text-sm leading-6 text-slate-600 dark:text-slate-300">
                {!! $message['rendered_body'] !!}
            </div>

            <div class="mt-2 flex flex-wrap items-center gap-2">
                <span class="chat-meta-chip">
                    {{ $message['author']['role_label'] }}
                </span>

                @if ($message['is_mentioned_me'])
                    <span class="chat-meta-chip chat-meta-chip--mention">
                        Mentioned you
                    </span>
                @endif
            </div>
        </div>
    </div>
</article>
