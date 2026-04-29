{{--
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| This is the main Team Chat page.
| It combines the presence sidebar, message stream, and message composer.
|
| Why this file exists:
| Chat needs one workspace page where users can read messages, find teammates,
| use mentions, and send new messages without reloading the full page.
|
| When this file is used:
| After ChatController@index builds the initial chat payload.
|
| FILES TO READ (IN ORDER):
| 1. routes/web.php
| 2. app/Policies/MessagePolicy.php
| 3. app/Http/Controllers/ChatController.php
| 4. app/Services/ChatWorkspaceService.php
| 5. resources/views/chat/index.blade.php
| 6. resources/views/chat/partials/*
| 7. resources/views/components/chat/*
| 8. resources/js/chat-workspace.js
|
| HOW TO UNDERSTAND THIS FEATURE:
| 1. Controller sends messages, directory, mentions, and summary to this page.
| 2. This page renders the initial workspace.
| 3. chat-workspace.js handles realtime sync, filters, mentions, and sending.
| 4. Partial views are replaced during live updates.
--}}
<x-app-layout>
    {{--
        Root chat workspace.
        The Alpine component receives all data it needs from ChatController@index.
    --}}
    <section
        x-data="chatWorkspace({
            messagesUrl: @js(route('chat.messages')),
            storeUrl: @js(route('chat.store')),
            csrfToken: @js(csrf_token()),
            mentionableUsers: @js($mentionableUsers),
            summary: @js($summary),
            initialFilters: @js($filters),
            highlightMessageId: @js($highlightMessageId),
        })"
        x-init="init()"
        data-chat-workspace
        class="chat-shell relative isolate mx-auto max-w-[1700px] space-y-4 pb-4"
    >
        <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[36rem] bg-[radial-gradient(circle_at_top_left,_rgba(70,95,255,0.18),_transparent_28%),radial-gradient(circle_at_top_right,_rgba(16,185,129,0.14),_transparent_22%),linear-gradient(180deg,_rgba(255,255,255,0.96),_rgba(248,250,252,0))] blur-2xl dark:bg-[radial-gradient(circle_at_top_left,_rgba(70,95,255,0.24),_transparent_28%),radial-gradient(circle_at_top_right,_rgba(16,185,129,0.08),_transparent_20%),linear-gradient(180deg,_rgba(15,23,42,0.72),_rgba(2,6,23,0))]"></div>

        @if (session('success'))
            <div class="app-status-success">
                {{ session('success') }}
            </div>
        @endif

        <template x-if="successMessage">
            <div class="app-status-success" x-text="successMessage"></div>
        </template>

        <template x-if="sendError">
            <div class="app-status-danger" x-text="sendError"></div>
        </template>

        {{--
            Main chat layout:
            Left = team directory / presence
            Right = message stream and composer
        --}}
        <section class="grid gap-6 2xl:grid-cols-[340px_minmax(0,1fr)]">
            <aside class="chat-panel flex h-[82vh] min-h-[520px] flex-col overflow-hidden px-5 py-5 sm:px-6">
                {{--
                    Team directory:
                    Rendered from $userDirectory, which already includes presence labels and role labels.
                    The partial is refreshed during chat polling.
                --}}
                <div class="shrink-0 flex items-center justify-between gap-3 border-b border-slate-200/70 pb-5 dark:border-white/10">
                    <div>
                        <p class="app-section-title">Presence</p>
                        <h2 class="mt-2 font-display text-2xl font-semibold text-slate-950 dark:text-white">Team directory</h2>
                    </div>

                    <div class="chat-presence-summary">
                        <span class="chat-presence-dot bg-emerald-400 shadow-[0_0_18px_rgba(52,211,153,0.55)]"></span>
                        <span x-text="summary.online_count"></span>
                    </div>
                </div>

                <div class="mt-5 shrink-0 rounded-[24px] border border-slate-200/80 bg-slate-50/80 px-4 py-4 dark:border-white/10 dark:bg-white/[0.03]">
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-300">
                        Click any user to filter the room by sender. Presence is derived from active authenticated sessions, so it stays lightweight and deployment-safe.
                    </p>
                </div>

                <div x-ref="userDirectory" @click="handleDirectoryClick($event)" class="mt-5 min-h-0 flex-1 overflow-y-auto pr-1 custom-scrollbar">
                    @include('chat.partials.user-list', [
                        'users' => $userDirectory,
                        'currentUserId' => $currentUserId,
                        'selectedUserId' => $filters['sender_id'],
                    ])
                </div>
            </aside>

            <article class="chat-panel flex h-[82vh] min-h-[520px] flex-col overflow-hidden">
                {{--
                    Message stream:
                    Initial HTML comes from the controller.
                    Later refreshes replace or append HTML from chat.partials.message-list.
                --}}
                <div x-ref="messagesScroller" class="chat-message-scroller min-h-0 flex-1 overflow-y-auto px-4 py-4 sm:px-5 custom-scrollbar">
                    <div x-ref="messageStream" @click="handleMessageStreamClick($event)" class="space-y-3">
                        @include('chat.partials.message-list', ['messages' => $messages])
                    </div>
                </div>

                {{--
                    Composer:
                    Sends new messages through AJAX using chat-workspace.js.
                    Mention suggestions come from $mentionableUsers.
                --}}
                <div class="chat-composer-wrap shrink-0 border-t border-slate-200/70 px-4 py-3 dark:border-white/10 sm:px-5">
                    <form method="POST" action="{{ route('chat.store') }}" class="space-y-3" @submit.prevent="sendMessage()">
                        @csrf

                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <p class="app-section-title">Compose</p>
                                <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">
                                    Use <span class="font-semibold text-slate-700 dark:text-slate-300">@handle</span> mentions to notify teammates. Press Enter to send and Shift+Enter for a new line.
                                </p>
                            </div>

                            <span class="chat-meta-chip">
                                <span x-text="draft.length"></span>/1000
                            </span>
                        </div>

                        <div class="relative">
                            <textarea
                                x-ref="composer"
                                x-model="draft"
                                @input="onComposerInput()"
                                @keydown="onComposerKeydown($event)"
                                rows="2"
                                class="chat-composer-input"
                                placeholder="Write an update, ask for help, or mention a teammate..."
                                maxlength="1000"
                                :disabled="sending"
                            ></textarea>

                            <div
                                x-cloak
                                x-show="mentionMenuOpen && currentMentionMatches().length > 0"
                                x-transition.origin.bottom.left
                                class="chat-mention-menu"
                                style="display: none;"
                            >
                                <p class="px-4 pb-2 text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400 dark:text-slate-500">
                                    Mention a teammate
                                </p>

                                <div class="space-y-1">
                                    <template x-for="(user, index) in currentMentionMatches()" :key="user.id">
                                        <button
                                            type="button"
                                            class="chat-mention-option"
                                            :class="index === mentionIndex ? 'chat-mention-option--active' : ''"
                                            @mousedown.prevent="insertMention(user)"
                                        >
                                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 to-sky-400 text-sm font-semibold text-white">
                                                <span x-text="user.initials"></span>
                                            </div>

                                            <div class="min-w-0 flex-1 text-left">
                                                <p class="truncate text-sm font-semibold text-slate-950 dark:text-white" x-text="user.name"></p>
                                                <p class="mt-1 truncate text-xs text-slate-500 dark:text-slate-400" x-text="`@${user.handle} - ${user.role_label}`"></p>
                                            </div>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex flex-wrap items-center gap-2 text-xs text-slate-400 dark:text-slate-500">
                                <span class="chat-meta-chip">Escapes XSS by server rendering</span>
                                <span class="chat-meta-chip">Throttled against spam</span>
                            </div>

                            <button type="submit" class="app-button-primary min-w-[150px] px-4 py-2.5" :disabled="sending">
                                <span x-show="!sending">Send message</span>
                                <span x-show="sending" x-cloak>Sending...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </article>
        </section>
    </section>
</x-app-layout>
