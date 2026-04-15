<?php

namespace App\Http\Controllers;

use App\Services\AIChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AIChatController extends Controller
{


// function we use to handle AI chat and "I need AIChatService to work"
// AIchatService A class that handles AI logic (calls Groq API, generates replies) inside app/Services/AIChatService.php
    public function __construct(
        //only this controller can use AI service
        private readonly AIChatService $aiChatService,
    ) {
    }





        //This function loads the AI chat page.
    public function index(Request $request): View
    {
        return view('ai-chat.index', [

           //This creates a variable for Blade called: $messages
           //session = small memory for this user

            'messages' => $request->session()->get('ai_chat_messages', $this->defaultMessages()),

            // $this = mean this controller ( AIChatController)

            'isGroqConfigured' => $this->aiChatService->isConfigured(),
        ]);
    }

    public function send(Request $request): JsonResponse|RedirectResponse
    {
        //Get user message and clean it (remove spaces)
        //Replace old value with cleaned one
        $request->merge([
            'body' => trim((string) $request->input('body', '')),
        ]);

        //Make sure message is valid

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:1000'],
        ]);


        //Get previous chat history
        $messages = $request->session()->get('ai_chat_messages', $this->defaultMessages());

        // Convert user text into structured message
        $userMessage = $this->formatMessage('user', $validated['body']);
        //CALL AI , Send message to AI → get reply , Controller → AIChatService → Groq API → response
        $assistantReply = $this->aiChatService->reply($userMessage['body'], $messages);

        //Convert AI reply into message format
        $assistantMessage = $this->formatMessage('assistant', $assistantReply['body'], [
            'provider' => $assistantReply['provider'],
            'status' => $assistantReply['status'],
            'model' => $assistantReply['model'],
            'error_message' => $assistantReply['error_message'],
        ]);


        // Add new messages to conversation

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
