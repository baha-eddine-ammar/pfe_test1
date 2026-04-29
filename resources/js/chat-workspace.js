/*
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| This file defines the Alpine component used by the Team Chat page.
|
| What it does:
| - refreshes messages with lightweight JavaScript polling and visibility changes
| - syncs filters with the URL
| - manages the @mention dropdown
| - sends new messages with AJAX
| - keeps the message view scrolled intelligently
|
| FILES TO READ (IN ORDER):
| 1. resources/views/chat/index.blade.php
| 2. resources/js/chat-workspace.js
| 3. app/Http/Controllers/ChatController.php
| 4. app/Services/ChatWorkspaceService.php
*/
const syncTimeFormatter = new Intl.DateTimeFormat(undefined, {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
});

export default (config = {}) => ({
    // Values injected from Blade on first page load.
    messagesUrl: config.messagesUrl || '',
    storeUrl: config.storeUrl || '',
    csrfToken: config.csrfToken || '',
    mentionableUsers: Array.isArray(config.mentionableUsers) ? config.mentionableUsers : [],
    summary: config.summary || {
        message_count: 0,
        mention_count: 0,
        online_count: 0,
        recent_count: 0,
        last_message_id: 0,
        synced_at: '',
    },
    searchTerm: config.initialFilters?.search || '',
    senderFilter: String(config.initialFilters?.sender_id || ''),
    mentionsOnly: config.initialFilters?.mentions === 'me',
    highlightMessageId: Number(config.highlightMessageId || 0),
    draft: '',
    sending: false,
    sendError: '',
    successMessage: '',
    filterHandle: null,
    successHandle: null,
    pollingHandle: null,
    pollingDelay: 2500,
    syncInProgress: false,
    deletingMessages: {},
    visibilityHandler: null,
    mentionMenuOpen: false,
    mentionIndex: 0,
    mentionQuery: '',
    // Entry point called by x-init on the chat page.
    init() {
        this.scrollToBottom(true);
        this.refreshTimestamp();

        this.$watch('searchTerm', () => {
            this.queueFilterRefresh();
        });

        this.$watch('mentionsOnly', () => {
            this.refreshSnapshot(true);
        });

        this.$watch('senderFilter', () => {
            this.refreshSnapshot(true);
        });

        this.startVisibilitySync();
        this.startPolling();

        if (this.highlightMessageId > 0) {
            this.$nextTick(() => {
                this.scrollToMessage(this.highlightMessageId, true);
            });
        }
    },
    // Pauses polling while hidden and catches up as soon as the tab is visible.
    startVisibilitySync() {
        if (this.visibilityHandler) {
            return;
        }

        this.visibilityHandler = () => {
            if (document.visibilityState === 'visible') {
                this.refreshMessages(false, this.isNearBottom());
                this.startPolling();
                return;
            }

            this.stopPolling();
        };

        document.addEventListener('visibilitychange', this.visibilityHandler);
    },
    startPolling() {
        if (this.pollingHandle || !this.messagesUrl || document.visibilityState === 'hidden') {
            return;
        }

        this.pollingHandle = window.setInterval(() => {
            if (!this.canAppend()) {
                return;
            }

            this.refreshMessages(false, false);
        }, this.pollingDelay);
    },
    stopPolling() {
        if (!this.pollingHandle) {
            return;
        }

        window.clearInterval(this.pollingHandle);
        this.pollingHandle = null;
    },
    destroy() {
        this.stopPolling();

        if (this.visibilityHandler) {
            document.removeEventListener('visibilitychange', this.visibilityHandler);
        }
    },
    // Focuses the composer again after successful sends.
    restoreComposerFocus() {
        this.$nextTick(() => {
            this.$refs.composer?.focus();
        });
    },
    selectedSenderName() {
        const selected = this.mentionableUsers.find((user) => String(user.id) === String(this.senderFilter));

        return selected ? selected.name : '';
    },
    // Clicking a user in the presence sidebar toggles the sender filter.
    setSenderFilter(userId = '') {
        const nextValue = String(userId || '');

        this.senderFilter = this.senderFilter === nextValue ? '' : nextValue;
    },
    clearFilters() {
        this.searchTerm = '';
        this.senderFilter = '';
        this.mentionsOnly = false;
    },
    // Filter shortcut used by the "Mentions" chip.
    toggleMentionsOnly() {
        this.mentionsOnly = !this.mentionsOnly;
    },
    // Debounces search typing so the app does not refresh on every keystroke.
    queueFilterRefresh() {
        if (this.filterHandle) {
            clearTimeout(this.filterHandle);
        }

        this.filterHandle = window.setTimeout(() => {
            this.refreshSnapshot(true);
        }, 280);
    },
    // Updates the small "last sync" time displayed in the UI.
    refreshTimestamp() {
        this.summary.synced_at = syncTimeFormatter.format(new Date());
    },
    // Builds the query string sent to ChatController@messages.
    buildQuery(forceSnapshot = false) {
        const params = new URLSearchParams();

        if (this.searchTerm.trim() !== '') {
            params.set('search', this.searchTerm.trim());
        }

        if (this.senderFilter !== '') {
            params.set('sender_id', this.senderFilter);
        }

        if (this.mentionsOnly) {
            params.set('mentions', 'me');
        }

        if (!forceSnapshot && this.canAppend()) {
            params.set('after_id', String(this.latestRenderedMessageId()));
            params.set('limit', '20');
        }

        return params;
    },
    // Incremental append is safe only when no filters change the message set.
    canAppend() {
        return this.searchTerm.trim() === ''
            && this.senderFilter === ''
            && !this.mentionsOnly;
    },
    renderedMessageIds() {
        const messages = this.$refs.messageStream?.querySelectorAll('[data-message-id]') || [];

        return new Set(
            Array.from(messages)
                .map((message) => Number(message.dataset.messageId || 0))
                .filter((messageId) => messageId > 0)
        );
    },
    latestRenderedMessageId() {
        const renderedIds = Array.from(this.renderedMessageIds());

        if (renderedIds.length === 0) {
            return 0;
        }

        return Math.max(...renderedIds);
    },
    // Used so new messages do not yank the screen while the user is reading older content.
    isNearBottom() {
        const scroller = this.$refs.messagesScroller;

        if (!scroller) {
            return true;
        }

        return scroller.scrollHeight - scroller.scrollTop - scroller.clientHeight < 120;
    },
    // Scroll helper for initial load and successful sends.
    scrollToBottom(smooth = false) {
        this.$nextTick(() => {
            const scroller = this.$refs.messagesScroller;

            if (!scroller) {
                return;
            }

            scroller.scrollTo({
                top: scroller.scrollHeight,
                behavior: smooth ? 'smooth' : 'auto',
            });
        });
    },
    // Used by mention notifications so the UI can jump to a specific message.
    scrollToMessage(messageId, pulse = false) {
        const target = document.querySelector(`[data-message-id="${messageId}"]`);

        if (!target) {
            return;
        }

        target.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
        });

        if (pulse) {
            target.classList.remove('chat-message-flash');
            void target.offsetWidth;
            target.classList.add('chat-message-flash');
        }

        this.highlightMessageId = 0;
        this.removeHighlightFromQuery();
    },
    // Clears the highlight query parameter after it has been used once.
    removeHighlightFromQuery() {
        const url = new URL(window.location.href);

        url.searchParams.delete('highlight');
        window.history.replaceState({}, '', url);
    },
    // Keeps the browser URL aligned with the active filter state.
    pushCurrentFiltersToUrl() {
        const url = new URL(window.location.href);

        if (this.searchTerm.trim() !== '') {
            url.searchParams.set('search', this.searchTerm.trim());
        } else {
            url.searchParams.delete('search');
        }

        if (this.senderFilter !== '') {
            url.searchParams.set('sender_id', this.senderFilter);
        } else {
            url.searchParams.delete('sender_id');
        }

        if (this.mentionsOnly) {
            url.searchParams.set('mentions', 'me');
        } else {
            url.searchParams.delete('mentions');
        }

        window.history.replaceState({}, '', url);
    },
    // Partial DOM replacement for the left sidebar.
    replaceUserDirectory(html) {
        if (!this.$refs.userDirectory) {
            return;
        }

        this.$refs.userDirectory.innerHTML = html;
    },
    // Partial DOM replacement or append for the message list.
    replaceMessages(html, append = false, scrollAfter = false) {
        if (!this.$refs.messageStream) {
            return 0;
        }

        let insertedCount = 0;

        if (append && html.trim() !== '') {
            insertedCount = this.appendNewMessages(html);
        } else if (!append) {
            this.$refs.messageStream.innerHTML = html;
            insertedCount = this.$refs.messageStream.querySelectorAll('[data-message-id]').length;
        }

        if (scrollAfter) {
            this.scrollToBottom(true);
        }

        return insertedCount;
    },
    appendNewMessages(html) {
        const template = document.createElement('template');
        const knownIds = this.renderedMessageIds();
        let insertedCount = 0;

        template.innerHTML = html.trim();

        Array.from(template.content.children).forEach((node) => {
            const messageElement = node.matches?.('[data-message-id]')
                ? node
                : node.querySelector?.('[data-message-id]');
            const messageId = Number(messageElement?.dataset?.messageId || 0);

            if (messageId <= 0 || knownIds.has(messageId)) {
                return;
            }

            knownIds.add(messageId);
            messageElement.classList.add('chat-message-new');

            if (insertedCount === 0) {
                this.$refs.messageStream.querySelector('.chat-empty-state')?.remove();
            }

            this.$refs.messageStream.appendChild(node);
            insertedCount += 1;
        });

        return insertedCount;
    },
    // Applies the fresh summary payload returned by the backend.
    applySummary(summary) {
        this.summary = {
            ...this.summary,
            ...summary,
        };
    },
    /*
     * Main sync method.
     * It calls ChatController@messages and receives:
     * - rendered message HTML
     * - rendered user directory HTML
     * - summary stats
     */
    async refreshMessages(forceSnapshot = false, forceScroll = false) {
        if (document.visibilityState === 'hidden' && !forceSnapshot) {
            return;
        }

        if (this.syncInProgress && !forceSnapshot) {
            return;
        }

        this.syncInProgress = true;

        const wasNearBottom = this.isNearBottom();
        const query = this.buildQuery(forceSnapshot).toString();

        try {
            const response = await fetch(`${this.messagesUrl}${query ? `?${query}` : ''}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                return;
            }

            const payload = await response.json();
            const shouldScroll = forceScroll || (wasNearBottom && this.isNearBottom());

            if (!forceSnapshot && !this.canAppend()) {
                return;
            }

            this.replaceUserDirectory(payload.users_html);
            this.replaceMessages(payload.messages_html, payload.append && !forceSnapshot, shouldScroll || forceSnapshot);
            this.applySummary(payload.summary);
            this.pushCurrentFiltersToUrl();

            if (this.highlightMessageId > 0) {
                this.$nextTick(() => {
                    this.scrollToMessage(this.highlightMessageId, true);
                });
            }
        } catch (error) {
            console.error('Unable to refresh chat workspace', error);
        } finally {
            this.syncInProgress = false;
        }
    },
    // Helper to force a full snapshot instead of incremental append mode.
    refreshSnapshot(forceScroll = false) {
        return this.refreshMessages(true, forceScroll);
    },
    // Temporary success banner after sending a message.
    showSuccess(message) {
        this.successMessage = message;

        if (this.successHandle) {
            clearTimeout(this.successHandle);
        }

        this.successHandle = window.setTimeout(() => {
            this.successMessage = '';
        }, 2600);
    },
    // Returns the currently visible mention suggestions under the textarea.
    currentMentionMatches() {
        if (!this.mentionMenuOpen) {
            return [];
        }

        const query = this.mentionQuery.trim().toLowerCase();

        return this.mentionableUsers
            .filter((user) => {
                if (query === '') {
                    return true;
                }

                return [user.name, user.handle, user.role_label]
                    .filter(Boolean)
                    .some((value) => value.toLowerCase().includes(query));
            })
            .slice(0, 6);
    },
    // Detects whether the user is currently typing an @mention token.
    updateMentionState() {
        const textarea = this.$refs.composer;

        if (!textarea) {
            return;
        }

        const cursor = textarea.selectionStart ?? this.draft.length;
        const beforeCursor = this.draft.slice(0, cursor);
        const match = beforeCursor.match(/(?:^|\s)@([A-Za-z0-9._-]*)$/);

        if (!match) {
            this.mentionMenuOpen = false;
            this.mentionQuery = '';
            this.mentionIndex = 0;
            return;
        }

        this.mentionMenuOpen = true;
        this.mentionQuery = match[1] || '';
        this.mentionIndex = 0;
    },
    // Replaces the typed @fragment with a real selected handle.
    insertMention(user) {
        const textarea = this.$refs.composer;

        if (!textarea) {
            return;
        }

        const cursor = textarea.selectionStart ?? this.draft.length;
        const beforeCursor = this.draft.slice(0, cursor);
        const afterCursor = this.draft.slice(cursor);
        const match = beforeCursor.match(/(?:^|\s)@([A-Za-z0-9._-]*)$/);

        if (!match) {
            return;
        }

        const tokenStart = cursor - match[1].length - 1;
        const replacement = `@${user.handle} `;

        this.draft = `${this.draft.slice(0, tokenStart)}${replacement}${afterCursor}`;
        this.mentionMenuOpen = false;
        this.mentionQuery = '';

        this.$nextTick(() => {
            textarea.focus();
            const nextCursor = tokenStart + replacement.length;

            textarea.setSelectionRange(nextCursor, nextCursor);
        });
    },
    // Runs after each textarea input event.
    onComposerInput() {
        this.sendError = '';
        this.updateMentionState();
    },
    // Keyboard behavior:
    // - Enter sends
    // - Shift+Enter creates a newline
    // - arrow keys navigate the mention menu
    onComposerKeydown(event) {
        if (this.mentionMenuOpen && this.currentMentionMatches().length > 0) {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                this.mentionIndex = (this.mentionIndex + 1) % this.currentMentionMatches().length;
                return;
            }

            if (event.key === 'ArrowUp') {
                event.preventDefault();
                this.mentionIndex = (this.mentionIndex - 1 + this.currentMentionMatches().length) % this.currentMentionMatches().length;
                return;
            }

            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                this.insertMention(this.currentMentionMatches()[this.mentionIndex]);
                return;
            }

            if (event.key === 'Escape') {
                this.mentionMenuOpen = false;
                return;
            }
        }

        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            this.sendMessage();
        }
    },
    // Event delegation for clicking a user in the directory partial.
    handleDirectoryClick(event) {
        const target = event.target.closest('[data-chat-filter-user]');

        if (!target) {
            return;
        }

        this.setSenderFilter(target.dataset.chatFilterUser || '');
    },
    handleMessageStreamClick(event) {
        const button = event.target.closest('[data-chat-delete-message]');

        if (!button || !this.$refs.messageStream?.contains(button)) {
            return;
        }

        this.deleteMessage(
            Number(button.dataset.chatDeleteMessage || 0),
            button.dataset.chatDeleteUrl || '',
            button
        );
    },
    async deleteMessage(messageId, deleteUrl, button = null) {
        if (messageId <= 0 || deleteUrl === '' || this.deletingMessages[messageId]) {
            return;
        }

        this.deletingMessages = {
            ...this.deletingMessages,
            [messageId]: true,
        };
        button?.setAttribute('disabled', 'disabled');
        this.sendError = '';

        try {
            const response = await fetch(deleteUrl, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
            });

            if (response.status === 403) {
                this.sendError = 'You can only delete your own messages.';
                return;
            }

            if (response.status === 404) {
                this.removeMessageElement(messageId);
                return;
            }

            if (!response.ok) {
                this.sendError = 'Unable to delete the message right now.';
                return;
            }

            const payload = await response.json().catch(() => ({}));

            this.removeMessageElement(Number(payload.message_id || messageId));
            this.showSuccess(payload.message || 'Message deleted successfully.');
        } catch (error) {
            this.sendError = 'A network error happened while deleting the message.';
        } finally {
            const nextDeletingMessages = { ...this.deletingMessages };
            delete nextDeletingMessages[messageId];
            this.deletingMessages = nextDeletingMessages;
            button?.removeAttribute('disabled');
        }
    },
    removeMessageElement(messageId) {
        const message = this.$refs.messageStream?.querySelector(`[data-message-id="${messageId}"]`);

        if (!message) {
            return;
        }

        message.classList.add('chat-message-removing');

        window.setTimeout(() => {
            message.remove();
        }, 180);
    },
    /*
     * Sends a message safely with AJAX.
     * The server still validates the message again, so frontend checks here are
     * only for fast UX feedback.
     */
    async sendMessage() {
        const body = this.draft.trim();

        if (body === '') {
            this.sendError = 'Please write a message before sending.';
            return;
        }

        if (body.length > 1000) {
            this.sendError = 'Messages must stay under 1000 characters.';
            return;
        }

        this.sending = true;
        this.sendError = '';

        try {
            const response = await fetch(this.storeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify({ body }),
            });

            if (response.status === 422) {
                const payload = await response.json();
                this.sendError = payload.errors?.body?.[0] || 'Validation failed.';
                return;
            }

            if (response.status === 429) {
                this.sendError = 'You are sending messages too quickly. Please wait a moment.';
                return;
            }

            if (!response.ok) {
                this.sendError = 'Unable to send the message right now.';
                return;
            }

            const payload = await response.json();

            if (this.canAppend() && payload.message_html) {
                this.replaceMessages(payload.message_html, true, true);
            } else {
                await this.refreshSnapshot(true);
            }

            if (payload.users_html) {
                this.replaceUserDirectory(payload.users_html);
            }

            if (payload.summary) {
                this.applySummary(payload.summary);
            }

            this.draft = '';
            this.mentionMenuOpen = false;
            this.mentionQuery = '';
            this.showSuccess(payload.message || 'Message sent successfully.');
            this.restoreComposerFocus();
        } catch (error) {
            this.sendError = 'A network error happened while sending the message.';
        } finally {
            this.sending = false;
        }
    },
});
