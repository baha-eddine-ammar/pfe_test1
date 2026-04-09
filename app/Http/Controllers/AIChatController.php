<?php

namespace App\Http\Controllers;

use App\Services\AIChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AIChatController extends Controller
{
    public function __construct(
        private readonly AIChatService $aiChatService,
    ) {
    }

    public function index(Request $request): View
    {
        return view('ai-chat.index', [
            'messages' => $request->session()->get('ai_chat_messages', $this->defaultMessages()),
            'isGroqConfigured' => $this->aiChatService->isConfigured(),
        ]);
    }

    public function send(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:1000'],
        ]);

        $messages = $request->session()->get('ai_chat_messages', $this->defaultMessages());
        $userMessage = $this->formatMessage('user', trim($validated['body']));
        $assistantReply = $this->aiChatService->reply($userMessage['body'], $messages);
        $assistantMessage = $this->formatMessage('assistant', $assistantReply['body'], [
            'provider' => $assistantReply['provider'],
            'status' => $assistantReply['status'],
            'model' => $assistantReply['model'],
            'error_message' => $assistantReply['error_message'],
        ]);

        $messages[] = $userMessage;
        $messages[] = $assistantMessage;

        $request->session()->put('ai_chat_messages', array_slice($messages, -20));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'AI suggestion generated successfully.',
                'user_message' => $userMessage,
                'assistant_message' => $assistantMessage,
            ]);
        }

        return redirect()
            ->route('ai-chat.index')
            ->with('success', 'AI suggestion generated successfully.');
    }

    private function defaultMessages(): array
    {
        return [
            $this->formatMessage('assistant', 'Hello. Ask me about server room operations, reports, maintenance, or general supervision tasks. Groq responses will be used when the API key is configured.'),
        ];
    }

    private function formatMessage(string $role, string $body, array $meta = []): array
    {
        return [
            'id' => sprintf('%s-%s', $role, now()->format('Uu')),
            'role' => $role,
            'body' => $body,
            'created_at' => now()->format('d M Y H:i'),
            'meta' => $meta,
        ];
    }
}
