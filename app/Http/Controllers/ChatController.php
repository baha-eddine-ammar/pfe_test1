<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function index(Request $request): View
    {
        $messages = Message::with('user')
            ->latest()
            ->take(50)
            ->get()
            ->reverse()
            ->values();

        return view('chat.index', [
            'messages' => $messages,
            'currentUserId' => $request->user()->id,
        ]);
    }

    public function messages(Request $request): JsonResponse
    {
        $currentUserId = $request->user()->id;

        $messages = Message::with('user')
            ->latest()
            ->take(50)
            ->get()
            ->reverse()
            ->values()
            ->map(function (Message $message) use ($currentUserId) {
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
            });

        return response()->json([
            'messages' => $messages,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:1000'],
        ]);

        Message::create([
            'user_id' => $request->user()->id,
            'body' => trim($validated['body']),
        ]);

        return redirect()
            ->route('chat.index')
            ->with('success', 'Message sent successfully.');
    }
}
