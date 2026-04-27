{{--
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| This is the main Team Chat page.
| It combines the presence sidebar, message stream, filters, and message composer.
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
        class="chat-shell relative isolate mx-auto max-w-[1700px] space-y-6 pb-10"
    >
        <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[36rem] bg-[radial-gradient(circle_at_top_left,_rgba(70,95,255,0.18),_transparent_28%),radial-gradient(circle_at_top_right,_rgba(16,185,129,0.14),_transparent_22%),linear-gradient(180deg,_rgba(255,255,255,0.96),_rgba(248,250,252,0))] blur-2xl dark:bg-[radial-gradient(circle_at_top_left,_rgba(70,95,255,0.24),_transparent_28%),radial-gradient(circle_at_top_right,_rgba(16,185,129,0.08),_transparent_20%),linear-gradient(180deg,_rgba(15,23,42,0.72),_rgba(2,6,23,0))]"></div>

        {{--
            Top summary area:
            Left card explains the feature.
            Right card shows room statistics coming from ChatWorkspaceService::summary().
        --}}
        <section class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_360px]">
            <article class="chat-panel px-6 py-6 sm:px-7">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                    <div class="max-w-3xl">
                        <p class="app-section-title">Communication</p>
                        <h1 class="mt-2 font-display text-3xl font-semibold tracking-[-0.04em] text-slate-950 dark:text-white sm:text-4xl">
                            Team chat workspace
                        </h1>
                        <p class="mt-3 text-sm leading-7 text-slate-500 dark:text-slate-400">
                            A shared, production-ready conversation hub with live presence, searchable history, mention notifications, and optimized partial updates instead of page reloads.
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:w-[320px]">
                        <div class="chat-mini-card">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400 dark:text-slate-500">Live sync</p>
                            <p class="mt-2 text-sm font-semibold text-slate-950 dark:text-white">
                                Instant via WebSocket
                            </p>
                        </div>

                        <div class="chat-mini-card">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400 dark:text-slate-500">Last sync</p>
                            <p class="mt-2 text-sm font-semibold text-slate-950 dark:text-white" x-text="summary.synced_at"></p>
                        </div>
                    </div>
                </div>
            </article>

            <article class="chat-panel px-6 py-6 sm:px-7">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="app-section-title">Channel</p>
                        <h2 class="mt-2 font-display text-2xl font-semibold text-slate-950 dark:text-white">Ops room</h2>
                    </div>

                    <span class="dashboard-live-badge">
                        <span class="dashboard-live-dot"></span>
                        Team online
                    </span>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-3">
                    <div class="chat-mini-stat">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400 dark:text-slate-500">Visible messages</p>
                        <p class="mt-2 font-display text-3xl font-semibold text-slate-950 dark:text-white" x-text="summary.message_count"></p>
                    </div>
                    <div class="chat-mini-stat">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400 dark:text-slate-500">Online now</p>
                        <p class="mt-2 font-display text-3xl font-semibold text-slate-950 dark:text-white" x-text="summary.online_count"></p>
                    </div>
                    <div class="chat-mini-stat">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400 dark:text-slate-500">My mentions</p>
                        <p class="mt-2 font-display text-3xl font-semibold text-slate-950 dark:text-white" x-text="summary.mention_count"></p>
                    </div>
                </div>
            </article>
        </section>

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
            Right = filters, message stream, and composer
        --}}
        <section class="grid gap-6 2xl:grid-cols-[340px_minmax(0,1fr)]">
            <aside class="chat-panel flex min-h-[78vh] flex-col px-5 py-5 sm:px-6">
                {{--
                    Team directory:
                    Rendered from $userDirectory, which already includes presence labels and role labels.
                    The partial is refreshed after realtime events.
                --}}
                <div class="flex items-center justify-between gap-3 border-b border-slate-200/70 pb-5 dark:border-white/10">
                    <div>
                        <p class="app-section-title">Presence</p>
                        <h2 class="mt-2 font-display text-2xl font-semibold text-slate-950 dark:text-white">Team directory</h2>
                    </div>

                    <div class="chat-presence-summary">
                        <span class="chat-presence-dot bg-emerald-400 shadow-[0_0_18px_rgba(52,211,153,0.55)]"></span>
                        <span x-text="summary.online_count"></span>
                    </div>
                </div>

                <div class="mt-5 rounded-[24px] border border-slate-200/80 bg-slate-50/80 px-4 py-4 dark:border-white/10 dark:bg-white/[0.03]">
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-300">
                        Click any user to filter the room by sender. Presence is derived from active authenticated sessions, so it stays lightweight and deployment-safe.
                    </p>
                </div>

                <div x-ref="userDirectory" @click="handleDirectoryClick($event)" class="mt-5 flex-1 overflow-y-auto pr-1 custom-scrollbar">
                    @include('chat.partials.user-list', [
                        'users' => $userDirectory,
                        'currentUserId' => $currentUserId,
                        'selectedUserId' => $filters['sender_id'],
                    ])
                </div>
            </aside>

            <article class="chat-panel flex min-h-[78vh] flex-col overflow-hidden">
                {{--
                    Filter bar:
                    Lets the user search messages, isolate one sender, or show only mentions.
                    These filters are read by ChatController and ChatWorkspaceService.
                --}}
                <div class="border-b border-slate-200/70 px-6 py-5 dark:border-white/10 sm:px-7">
                    <div class="flex flex-col gap-4 2xl:flex-row 2xl:items-end 2xl:justify-between">
                        <div>
                            <p class="app-section-title">Filters</p>
                            <h2 class="mt-2 font-display text-2xl font-semibold text-slate-950 dark:text-white">Conversation stream</h2>
                            <p class="mt-3 text-sm leading-7 text-slate-500 dark:text-slate-400">
                                Search messages, isolate a sender, or jump into messages that mention you directly.
                            </p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto_auto] 2xl:w-[760px]">
                            <div class="relative">
                                <input
                                    type="text"
                                    x-model="searchTerm"
                                    class="app-search"
                                    placeholder="Search messages, names, or email"
                                >
                                <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
                                    <circle cx="9" cy="9" r="5.5"></circle>
                                    <path d="M13.5 13.5L17 17" stroke-linecap="round"></path>
                                </svg>
                            </div>

                            <button
                                type="button"
                                @click="toggleMentionsOnly()"
                                class="chat-filter-chip"
                                :class="mentionsOnly ? 'chat-filter-chip--active' : ''"
                            >
                                Mentions
                            </button>

                            <button
                                type="button"
                                @click="clearFilters()"
                                class="app-button-secondary px-4 py-3"
                            >
                                Reset
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <template x-if="senderFilter">
                            <span class="chat-filter-chip chat-filter-chip--active">
                                Sender: <span class="font-semibold" x-text="selectedSenderName()"></span>
                            </span>
                        </template>

                        <template x-if="mentionsOnly">
                            <span class="chat-filter-chip chat-filter-chip--active">
                                Showing messages that mention you
                            </span>
                        </template>
                    </div>
                </div>

                {{--
                    Message stream:
                    Initial HTML comes from the controller.
                    Later refreshes replace or append HTML from chat.partials.message-list.
                --}}
                <div x-ref="messagesScroller" class="chat-message-scroller flex-1 overflow-y-auto px-6 py-6 sm:px-7 custom-scrollbar">
                    <div x-ref="messageStream" class="space-y-4">
                        @include('chat.partials.message-list', ['messages' => $messages])
                    </div>
                </div>

                {{--
                    Composer:
                    Sends new messages through AJAX using chat-workspace.js.
                    Mention suggestions come from $mentionableUsers.
                --}}
                <div class="chat-composer-wrap border-t border-slate-200/70 px-6 py-5 dark:border-white/10 sm:px-7">
                    <form method="POST" action="{{ route('chat.store') }}" class="space-y-4" @submit.prevent="sendMessage()">
                        @csrf

                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="app-section-title">Compose</p>
                                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
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
                                rows="4"
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

                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex flex-wrap items-center gap-2 text-xs text-slate-400 dark:text-slate-500">
                                <span class="chat-meta-chip">Escapes XSS by server rendering</span>
                                <span class="chat-meta-chip">Throttled against spam</span>
                            </div>

                            <button type="submit" class="app-button-primary min-w-[170px]" :disabled="sending">
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
