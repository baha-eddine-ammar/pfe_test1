{{--
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| Reusable user row for the chat presence sidebar.
|
| Data source:
| Each $user array comes from ChatWorkspaceService::userDirectory().
| It already includes presence state, initials, role label, and last seen label.
|--------------------------------------------------------------------------
--}}
@props([
    'user',
    'selected' => false,
])

@php
    // Presence state controls the colored dot shown on the avatar.
    $presenceDotClass = match ($user['presence_state']) {
        'online' => 'bg-emerald-400 shadow-[0_0_18px_rgba(52,211,153,0.55)]',
        'recent' => 'bg-amber-400 shadow-[0_0_18px_rgba(251,191,36,0.45)]',
        default => 'bg-slate-300 dark:bg-slate-600',
    };

    $surfaceClasses = $selected
        ? 'chat-user-item chat-user-item--active'
        : 'chat-user-item';
@endphp

<button
    {{--
        Clicking this row does not open a private chat.
        It filters the shared room by this sender using JS event delegation.
    --}}
    type="button"
    data-chat-filter-user="{{ $user['id'] }}"
    class="{{ $surfaceClasses }}"
>
    <div class="flex items-center gap-3">
        <div class="relative">
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 to-sky-400 text-sm font-semibold text-white shadow-lg shadow-brand-500/20">
                {{ $user['initials'] }}
            </div>
            <span class="chat-user-presence {{ $presenceDotClass }}"></span>
        </div>

        <div class="min-w-0 flex-1 text-left">
            <div class="flex items-center gap-2">
                <p class="truncate text-sm font-semibold text-slate-950 dark:text-white">
                    {{ $user['name'] }}
                </p>
                @if ($user['is_current_user'])
                    <span class="chat-meta-chip">You</span>
                @endif
            </div>

            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                <span class="truncate font-medium">{{ '@'.$user['handle'] }}</span>
                <span>{{ $user['role_label'] }}</span>
            </div>

            <p class="mt-2 text-xs font-medium text-slate-400 dark:text-slate-500">
                {{ $user['last_seen_label'] }}
            </p>
        </div>
    </div>
</button>
