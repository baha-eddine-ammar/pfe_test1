<x-app-layout>
    <section
        class="mx-auto max-w-5xl"
        x-data="aiChatBox({
            sendUrl: '{{ route('ai-chat.send') }}',
            csrfToken: '{{ csrf_token() }}',
            initialMessages: @js($messages),
        })"
        x-init="init()"
    >
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="app-section-title">Communication</p>
                <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
                    AI Chat
                </h1>
                <p class="mt-2 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                    Ask for quick suggestions about server room operations, reports, and maintenance. This page uses Groq when configured and falls back gracefully if the API is unavailable.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span class="app-pill {{ $isGroqConfigured ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300' }}">
                    {{ $isGroqConfigured ? 'Groq connected' : 'Fallback mode' }}
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
                <div class="space-y-4">
                    <template x-for="message in messages" :key="message.id">
                        <div
                            class="flex"
                            :class="message.role === 'user' ? 'justify-end' : 'justify-start'"
                        >
                            <div class="flex max-w-[78%] items-end gap-3" :class="message.role === 'user' ? 'flex-row-reverse' : ''">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl text-sm font-semibold"
                                    :class="message.role === 'user'
                                        ? 'bg-brand-100 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300'
                                        : 'bg-violet-100 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300'"
                                    x-text="message.role === 'user' ? 'Y' : 'AI'"
                                ></div>

                                <div
                                    class="rounded-3xl px-4 py-3 shadow-sm"
                                    :class="message.role === 'user'
                                        ? 'bg-brand-500 text-white'
                                        : 'border border-gray-200 bg-white text-gray-800 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200'"
                                >
                                    <div class="flex items-center gap-3">
                                        <p
                                            class="text-sm font-semibold"
                                            :class="message.role === 'user' ? 'text-white/90' : 'text-gray-900 dark:text-white'"
                                            x-text="message.role === 'user' ? 'You' : 'AI Assistant'"
                                        ></p>

                                        <p
                                            class="text-xs"
                                            :class="message.role === 'user' ? 'text-white/70' : 'text-gray-400 dark:text-gray-500'"
                                            x-text="message.created_at"
                                        ></p>
                                    </div>

                                    <p
                                        class="mt-2 text-sm leading-6"
                                        :class="message.role === 'user' ? 'text-white' : 'text-gray-600 dark:text-gray-300'"
                                        x-text="message.body"
                                    ></p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="rounded-3xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <form method="POST" action="{{ route('ai-chat.send') }}" class="space-y-4" @submit.prevent="sendMessage">
                    @csrf

                    <div>
                        <div class="mb-2 flex items-center justify-between gap-4">
                            <label for="body" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Your prompt
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
                            placeholder="Ask about reports, server issues, maintenance planning, or next actions..."
                            x-model="draft"
                            :disabled="sending"
                            maxlength="1000"
                            required
                        ></textarea>

                        <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">
                            @if ($isGroqConfigured)
                                Messages are sent to the configured Groq model and recent conversation context is included.
                            @else
                                Add <span class="font-mono">GROQ_API_KEY</span> to your environment file to enable live Groq responses.
                            @endif
                        </p>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <p class="text-xs text-gray-400 dark:text-gray-500">
                            Keep prompts short and practical for the best suggestions.
                        </p>

                        <button type="submit" class="app-button-primary min-w-[150px]" :disabled="sending">
                            <span x-show="!sending">Send Message</span>
                            <span x-show="sending" x-cloak>Thinking...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('aiChatBox', ({ sendUrl, csrfToken, initialMessages }) => ({
                messages: initialMessages,
                sendUrl,
                csrfToken,
                draft: '',
                sending: false,
                sendError: '',
                successMessage: '',
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

                async sendMessage() {
                    const body = this.draft.trim();

                    if (!body) {
                        this.sendError = 'Please write a message before sending.';
                        return;
                    }

                    this.sending = true;
                    this.sendError = '';

                    try {
                        const response = await fetch(this.sendUrl, {
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
                            this.sendError = 'Unable to generate a response right now.';
                            return;
                        }

                        const data = await response.json();

                        this.messages.push(data.user_message);
                        this.messages.push(data.assistant_message);
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
                },
            }));
        });
    </script>
</x-app-layout>
