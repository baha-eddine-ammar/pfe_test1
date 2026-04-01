@php
    $serializedMessages = $messages->map(function ($message) use ($currentUserId) {
        return [
            'id' => $message->id,
            'body' => $message->body,
            'created_at' => $message->created_at->format('d M Y H:i'),
            'is_mine' => $message->user_id === $currentUserId,
            'user' => [
                'id' => $message->user->id,
                'name' => $message->user->name,
            ],
        ];
    })->values();
@endphp

<x-app-layout>
    <section
        class="mx-auto max-w-5xl"
        x-data="chatBox({
            messagesUrl: '{{ route('chat.messages') }}',
            storeUrl: '{{ route('chat.store') }}',
            csrfToken: '{{ csrf_token() }}',
            currentUserId: {{ $currentUserId }}
        })"
        x-init="init()"
    >
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="app-section-title">Communication</p>
                <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
                    Team Chat
                </h1>
                <p class="mt-2 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                    Shared team room for quick communication. New messages appear automatically every 3 seconds.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span class="app-pill bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">
                    Live room
                </span>
                <span class="app-pill bg-gray-100 text-gray-600 dark:bg-white/[0.05] dark:text-gray-300">
                    <span x-text="messages.length"></span> messages
                </span>
            </div>
        </div>

        <div class="app-card overflow-hidden p-6">
            <template x-if="successMessage">
                <div class="app-status-success mb-4" x-text="successMessage"></div>
            </template>

            <template x-if="sendError">
                <div class="app-status-danger mb-4" x-text="sendError"></div>
            </template>

            <div
                x-ref="messagesContainer"
                class="mb-6 h-[430px] overflow-y-auto rounded-3xl bg-gray-50 p-4 dark:bg-white/[0.03]"
            >
                <template x-if="messages.length === 0">
                    <div class="flex h-full flex-col items-center justify-center rounded-2xl border border-dashed border-gray-300 px-6 text-center dark:border-gray-700">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-500 dark:bg-brand-500/10 dark:text-brand-300">
                            <svg class="h-7 w-7" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
                                <path d="M5.5 13.5L3 16v-2.8A5.5 5.5 0 018.5 7.7h3A5.5 5.5 0 0117 13.2v.3" stroke-linejoin="round"></path>
                            </svg>
                        </div>
                        <h2 class="mt-4 font-display text-xl font-semibold text-gray-900 dark:text-white">
                            No messages yet
                        </h2>
                        <p class="mt-2 max-w-md text-sm leading-6 text-gray-500 dark:text-gray-400">
                            This chat room is empty for now. Send the first message to start the conversation.
                        </p>
                    </div>
                </template>

                <div class="space-y-4">
                    <template x-for="message in messages" :key="message.id">
                        <div
                            class="flex"
                            :class="message.is_mine ? 'justify-end' : 'justify-start'"
                        >
                            <div class="flex max-w-[78%] items-end gap-3" :class="message.is_mine ? 'flex-row-reverse' : ''">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl text-sm font-semibold"
                                    :class="message.is_mine
                                        ? 'bg-brand-100 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300'
                                        : 'bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-300'"
                                    x-text="message.user.name.charAt(0).toUpperCase()"
                                ></div>

                                <div
                                    class="rounded-3xl px-4 py-3 shadow-sm"
                                    :class="message.is_mine
                                        ? 'bg-brand-500 text-white'
                                        : 'border border-gray-200 bg-white text-gray-800 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200'"
                                >
                                    <div class="flex items-center gap-3">
                                        <p
                                            class="text-sm font-semibold"
                                            :class="message.is_mine ? 'text-white/90' : 'text-gray-900 dark:text-white'"
                                            x-text="message.user.name"
                                        ></p>

                                        <p
                                            class="text-xs"
                                            :class="message.is_mine ? 'text-white/70' : 'text-gray-400 dark:text-gray-500'"
                                            x-text="message.created_at"
                                        ></p>
                                    </div>

                                    <p
                                        class="mt-2 text-sm leading-6"
                                        :class="message.is_mine ? 'text-white' : 'text-gray-600 dark:text-gray-300'"
                                        x-text="message.body"
                                    ></p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="rounded-3xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <form
                    method="POST"
                    action="{{ route('chat.store') }}"
                    class="space-y-4"
                    @submit.prevent="sendMessage"
                >
                    @csrf

                    <div>
                        <div class="mb-2 flex items-center justify-between gap-4">
                            <label for="body" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Your message
                            </label>
                            <span class="text-xs text-gray-400 dark:text-gray-500">
                                <span x-text="draft.length"></span>/1000
                            </span>
                        </div>

                        <textarea
                            id="body"
                            name="body"
                            rows="4"
                            class="app-input min-h-[120px] resize-none"
                            placeholder="Type your message here..."
                            x-model="draft"
                            :disabled="sending"
                            maxlength="1000"
                            required
                        ></textarea>

                        <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">
                            Messages are shared with all logged-in users in this room.
                        </p>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <p class="text-xs text-gray-400 dark:text-gray-500">
                            Auto-refresh checks for new messages every 3 seconds.
                        </p>

                        <button
                            type="submit"
                            class="app-button-primary min-w-[150px]"
                            :disabled="sending"
                        >
                            <span x-show="!sending">Send Message</span>
                            <span x-show="sending" x-cloak>Sending...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('chatBox', ({ messagesUrl, storeUrl, csrfToken, currentUserId }) => ({
                messages: @js($serializedMessages),
                messagesUrl,
                storeUrl,
                csrfToken,
                currentUserId,
                draft: '',
                sending: false,
                sendError: '',
                successMessage: '',
                intervalId: null,
                successTimeout: null,

                scrollToBottom() {
                    this.$nextTick(() => {
                        if (this.$refs.messagesContainer) {
                            this.$refs.messagesContainer.scrollTop = this.$refs.messagesContainer.scrollHeight;
                        }
                    });
                },

                showSuccess(message) {
                    this.successMessage = message;

                    if (this.successTimeout) {
                        clearTimeout(this.successTimeout);
                    }

                    this.successTimeout = setTimeout(() => {
                        this.successMessage = '';
                    }, 2500);
                },

                async fetchMessages() {
                    const response = await fetch(this.messagesUrl, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        return;
                    }

                    const data = await response.json();
                    this.messages = data.messages;
                    this.scrollToBottom();
                },

                async sendMessage() {
                    const body = this.draft.trim();

                    if (!body) {
                        this.sendError = 'Please write a message before sending.';
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
                            const data = await response.json();
                            this.sendError = data.errors?.body?.[0] ?? 'Validation failed.';
                            return;
                        }

                        if (!response.ok) {
                            this.sendError = 'Unable to send the message right now.';
                            return;
                        }

                        const data = await response.json();

                        this.messages.push(data.message_data);
                        this.draft = '';
                        this.showSuccess(data.message);
                        this.scrollToBottom();
                    } catch (error) {
                        this.sendError = 'A network error happened while sending the message.';
                    } finally {
                        this.sending = false;
                    }
                },

                init() {
                    this.scrollToBottom();

                    this.intervalId = setInterval(() => {
                        this.fetchMessages();
                    }, 3000);
                },
            }));
        });
    </script>
</x-app-layout>
