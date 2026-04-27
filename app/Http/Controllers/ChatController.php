<?php

/*
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| This controller is the HTTP entry point for the Team Chat feature.
| It keeps the controller thin by validating requests, authorizing access, and
| delegating chat logic to ChatWorkspaceService.
|
| Why this file exists:
| Chat needs endpoints for:
| - loading the main chat page
| - syncing fresh messages/presence updates after realtime events
| - storing new messages
|
| When this file is used:
| - GET /chat
| - GET /chat/messages
| - POST /chat
|
| FILES TO READ (IN ORDER):
| 1. routes/web.php
| 2. app/Policies/MessagePolicy.php
| 3. app/Http/Requests/StoreChatMessageRequest.php
| 4. app/Http/Controllers/ChatController.php
| 5. app/Services/ChatWorkspaceService.php
| 6. app/Models/Message.php and app/Models/User.php
| 7. resources/views/chat/index.blade.php
| 8. resources/js/chat-workspace.js
|
| HOW TO UNDERSTAND THIS FEATURE:
| 1. Read the routes that expose the chat page and sync endpoint.
| 2. Read this controller to see request validation and returned payloads.
| 3. Read the service to understand message queries, presence, mentions, and notifications.
| 4. Read the models to understand who sends messages.
| 5. Read the Blade/JS files to see how the UI consumes the payload.
*/

namespace App\Http\Controllers;

use App\Http\Requests\StoreChatMessageRequest;
use App\Models\Message;
use App\Services\ChatWorkspaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ChatController extends Controller
{
    // The service centralizes chat-specific business logic so the controller
    // can stay easy to read and maintain.
    public function __construct(
        protected ChatWorkspaceService $workspaceService
    ) {
    }

    /*
    |----------------------------------------------------------------------
    | Main chat page
    |----------------------------------------------------------------------
    | Flow:
    | Request filters -> service workspace payload -> Blade view
    |
    | Important variables:
    | - $filters: validated search/filter settings from the query string.
    | - $workspace: combined payload containing messages, user directory,
    |   mention suggestions, and summary statistics.
    */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Message::class);

        $filters = $this->validatedFilters($request);
        $workspace = $this->workspaceService->workspace($request->user(), $filters);

        return view('chat.index', [
            'messages' => $workspace['messages'],
            'userDirectory' => $workspace['directory'],
            'mentionableUsers' => $workspace['mentionableUsers'],
            'summary' => $workspace['summary'],
            'filters' => $filters,
            'currentUserId' => $request->user()->id,
            'highlightMessageId' => (int) ($request->integer('highlight') ?: 0),
        ]);
    }

    // Lightweight sync endpoint used by the frontend to fetch new messages
    // and refreshed presence/sidebar data without reloading the full page.
    public function messages(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Message::class);

        $filters = $this->validatedFilters($request);
        $payload = $this->workspaceService->syncPayload(
            $request->user(),
            $filters,
            $request->integer('after_id') ?: null
        );

        return response()->json([
            'append' => $payload['append'],
            'messages_html' => view('chat.partials.message-list', [
                'messages' => $payload['messages'],
            ])->render(),
            'users_html' => view('chat.partials.user-list', [
                'users' => $payload['directory'],
                'currentUserId' => $request->user()->id,
                'selectedUserId' => $filters['sender_id'],
            ])->render(),
            'summary' => $payload['summary'],
        ]);
    }

    /*
    |----------------------------------------------------------------------
    | Store a new message
    |----------------------------------------------------------------------
    | Validation happens in StoreChatMessageRequest.
    | The message is created, mention notifications are sent, and then the
    | frontend receives either JSON (AJAX) or a redirect (classic form post).
    */
    public function store(StoreChatMessageRequest $request): JsonResponse|RedirectResponse
    {
        $message = $this->workspaceService->storeMessage(
            $request->user(),
            $request->validated('body')
        );

        if ($request->expectsJson()) {
            $workspace = $this->workspaceService->workspace($request->user(), [
                'search' => '',
                'sender_id' => '',
                'mentions' => '',
            ]);

            return response()->json([
                'message' => 'Message sent successfully.',
                'message_html' => view('chat.partials.message-list', [
                    'messages' => $this->workspaceService->presentMessages(collect([$message]), $request->user()),
                ])->render(),
                'users_html' => view('chat.partials.user-list', [
                    'users' => $workspace['directory'],
                    'currentUserId' => $request->user()->id,
                    'selectedUserId' => '',
                ])->render(),
                'summary' => $workspace['summary'],
            ]);
        }

        return redirect()
            ->route('chat.index')
            ->with('success', 'Message sent successfully.');
    }

    // Validates optional query-string filters used by the chat UI.
    // Keeping this in one method ensures both the page load and sync
    // endpoint interpret filters exactly the same way.
    protected function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'sender_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'mentions' => ['nullable', Rule::in(['', 'me'])],
            'highlight' => ['nullable', 'integer', 'min:1'],
        ]);

        return [
            'search' => trim((string) ($validated['search'] ?? '')),
            'sender_id' => (string) ($validated['sender_id'] ?? ''),
            'mentions' => (string) ($validated['mentions'] ?? ''),
            'highlight' => (int) ($validated['highlight'] ?? 0),
        ];
    }
}
