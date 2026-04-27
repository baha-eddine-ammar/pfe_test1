<?php

/*
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| This service contains the main business logic for the Team Chat module.
| It prepares chat messages for the UI, computes presence, handles mentions,
| and sends notifications when users are tagged.
|
| Why this file exists:
| Chat involves more than simply storing rows. The feature also needs
| filtering, HTML-safe rendering, online/offline states, summary statistics,
| and mention detection. Keeping that logic here makes the controller simpler.
|
| When this file is used:
| - When the chat page is loaded
| - When the frontend syncs after WebSocket events or visibility changes
| - When a new message is stored
|
| FILES TO READ (IN ORDER):
| 1. app/Http/Controllers/ChatController.php
| 2. app/Services/ChatWorkspaceService.php
| 3. app/Services/NotificationService.php
| 4. app/Services/TelegramService.php
| 5. app/Models/Message.php and app/Models/User.php
| 6. resources/views/chat/*
| 7. resources/js/chat-workspace.js
|
| HOW TO UNDERSTAND THIS FEATURE:
| 1. The controller receives the request.
| 2. This service loads users and messages from the database.
| 3. It shapes them into arrays the UI can render directly.
| 4. It detects @mentions and notifies mentioned users.
| 5. The frontend calls the sync endpoint after realtime events.
*/

namespace App\Services;

use App\Events\ChatMessageCreated;
use App\Models\Message;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChatWorkspaceService
{
    // SNAPSHOT_LIMIT controls how many older messages are loaded on full page load.
    protected const SNAPSHOT_LIMIT = 90;

    // APPEND_LIMIT controls how many new messages are appended during realtime sync.
    protected const APPEND_LIMIT = 24;

    // These cached properties avoid re-querying the full user list repeatedly
    // during one request.
    protected ?Collection $allUsers = null;

    protected ?array $mentionHandleMap = null;

    public function __construct(
        protected NotificationService $notificationService,
        protected TelegramService $telegramService
    ) {
    }

    /*
    |----------------------------------------------------------------------
    | Full workspace payload
    |----------------------------------------------------------------------
    | Used on the first page load.
    |
    | Important variables:
    | - $directory: all users with presence metadata for the left sidebar.
    | - $messages: message rows converted into presentation arrays.
    */
    public function workspace(User $viewer, array $filters): array
    {
        $directory = $this->userDirectory($viewer);
        $messages = $this->presentMessages(
            $this->snapshotMessages($viewer, $filters),
            $viewer
        );

        return [
            'messages' => $messages,
            'directory' => $directory,
            'mentionableUsers' => $directory
                ->map(fn (array $user) => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'handle' => $user['handle'],
                    'initials' => $user['initials'],
                    'role_label' => $user['role_label'],
                ])
                ->values(),
            'summary' => $this->summary($viewer, $filters, $directory, $messages),
        ];
    }

    // Used by realtime sync. If filters are still at their default values, the
    // frontend can append only new messages after the latest known ID.
    public function syncPayload(User $viewer, array $filters, ?int $afterId = null): array
    {
        $append = $afterId !== null && $afterId > 0 && $this->filtersAreDefault($filters);

        $messages = $append
            ? $this->incrementalMessages($afterId)
            : $this->snapshotMessages($viewer, $filters);

        $presentedMessages = $this->presentMessages($messages, $viewer);
        $directory = $this->userDirectory($viewer);
        $summary = $this->summary($viewer, $filters, $directory, $append ? null : $presentedMessages);

        return [
            'append' => $append,
            'messages' => $presentedMessages,
            'directory' => $directory,
            'summary' => $summary,
        ];
    }

    // Stores the message, then triggers mention notifications.
    public function storeMessage(User $sender, string $body): Message
    {
        $message = Message::query()->create([
            'user_id' => $sender->id,
            'body' => trim($body),
        ]);

        $message->load('user');

        $this->notifyMentionedUsers($message, $sender);
        ChatMessageCreated::dispatch($message);

        return $message;
    }

    // Small summary numbers shown in the UI header.
    // These are recalculated from the database/presence state.
    public function summary(User $viewer, array $filters, Collection $directory, ?Collection $presentedMessages = null): array
    {
        $messageCount = $this->queryMessages($viewer, $filters)->count();
        $mentionCount = $this->queryMessages($viewer, ['mentions' => 'me'])->count();
        $onlineCount = $directory->where('presence_state', 'online')->count();
        $recentCount = $directory->where('presence_state', 'recent')->count();

        $lastMessageId = $presentedMessages?->last()['id']
            ?? $this->queryMessages($viewer, $filters)->max('id')
            ?? 0;

        return [
            'message_count' => $messageCount,
            'mention_count' => $mentionCount,
            'online_count' => $onlineCount,
            'recent_count' => $recentCount,
            'last_message_id' => (int) $lastMessageId,
            'current_user_id' => $viewer->id,
            'synced_at' => now()->format('H:i:s'),
        ];
    }

    /*
    |----------------------------------------------------------------------
    | Presence directory
    |----------------------------------------------------------------------
    | Flow:
    | users table + sessions table -> presence state -> left sidebar list
    |
    | Important variables:
    | - $presenceMap: user_id => last_activity timestamp from database sessions.
    | - $lastSeenAt: converted Carbon time for human-friendly presence labels.
    */
    public function userDirectory(User $viewer): Collection
    {
        $presenceMap = $this->presenceMap();

        return $this->allUsers()
            ->map(function (User $user) use ($viewer, $presenceMap): array {
                $lastActivityTimestamp = $presenceMap[$user->id] ?? null;
                $lastSeenAt = $lastActivityTimestamp !== null
                    ? Carbon::createFromTimestamp((int) $lastActivityTimestamp)
                    : null;

                $presenceState = 'offline';
                $presenceLabel = 'Offline';
                $lastSeenLabel = 'No recent activity';

                if ($lastSeenAt !== null) {
                    if ($lastSeenAt->gte(now()->subMinutes(5))) {
                        $presenceState = 'online';
                        $presenceLabel = 'Online';
                        $lastSeenLabel = 'Active now';
                    } elseif ($lastSeenAt->gte(now()->subMinutes(20))) {
                        $presenceState = 'recent';
                        $presenceLabel = 'Recent';
                        $lastSeenLabel = 'Seen '.$lastSeenAt->diffForHumans();
                    } else {
                        $lastSeenLabel = 'Seen '.$lastSeenAt->diffForHumans();
                    }
                }

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'handle' => $user->chatHandle(),
                    'initials' => $user->initials(),
                    'role_label' => $user->roleLabel(),
                    'status_label' => Str::of($user->statusLabel())->replace('_', ' ')->title()->toString(),
                    'presence_state' => $presenceState,
                    'presence_label' => $presenceLabel,
                    'last_seen_label' => $lastSeenLabel,
                    'is_current_user' => $user->id === $viewer->id,
                ];
            })
            ->sort(function (array $left, array $right): int {
                $leftPresenceWeight = match ($left['presence_state']) {
                    'online' => 0,
                    'recent' => 1,
                    default => 2,
                };

                $rightPresenceWeight = match ($right['presence_state']) {
                    'online' => 0,
                    'recent' => 1,
                    default => 2,
                };

                return [$left['is_current_user'] ? 0 : 1, $leftPresenceWeight, Str::lower($left['name'])]
                    <=>
                    [$right['is_current_user'] ? 0 : 1, $rightPresenceWeight, Str::lower($right['name'])];
            })
            ->values();
    }

    // Converts Message models into UI-ready arrays.
    // rendered_body is HTML-safe and highlights known mentions.
    public function presentMessages(Collection $messages, User $viewer): Collection
    {
        return $messages
            ->values()
            ->map(function (Message $message) use ($viewer): array {
                $mentionedHandles = $this->extractMentionHandles($message->body);
                $viewerHandle = Str::lower($viewer->chatHandle());

                return [
                    'id' => $message->id,
                    'body' => $message->body,
                    'rendered_body' => $this->renderBodyHtml($message->body, $viewerHandle),
                    'created_at_label' => $message->created_at->format('H:i'),
                    'created_at_full' => $message->created_at->format('d M Y H:i'),
                    'is_mine' => $message->user_id === $viewer->id,
                    'is_mentioned_me' => in_array($viewerHandle, $mentionedHandles, true),
                    'author' => [
                        'id' => $message->user->id,
                        'name' => $message->user->name,
                        'handle' => $message->user->chatHandle(),
                        'initials' => $message->user->initials(),
                        'role_label' => $message->user->roleLabel(),
                    ],
                ];
            });
    }

    // Full snapshot for page load or when filters are active.
    protected function snapshotMessages(User $viewer, array $filters): Collection
    {
        return $this->queryMessages($viewer, $filters)
            ->latest('id')
            ->limit(self::SNAPSHOT_LIMIT)
            ->get()
            ->reverse()
            ->values();
    }

    // Incremental fetch for new messages only.
    protected function incrementalMessages(int $afterId): Collection
    {
        return Message::query()
            ->with('user')
            ->where('id', '>', $afterId)
            ->orderBy('id')
            ->limit(self::APPEND_LIMIT)
            ->get();
    }

    // Base query used by both the initial page load and the sync endpoint.
    // This keeps filtering logic consistent everywhere.
    protected function queryMessages(User $viewer, array $filters): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $senderId = $filters['sender_id'] ?? null;
        $mentions = $filters['mentions'] ?? '';

        return Message::query()
            ->with('user')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $messageQuery) use ($search): void {
                    $messageQuery
                        ->where('body', 'like', '%'.$search.'%')
                        ->orWhereHas('user', function (Builder $userQuery) use ($search): void {
                            $userQuery
                                ->where('name', 'like', '%'.$search.'%')
                                ->orWhere('email', 'like', '%'.$search.'%');
                        });
                });
            })
            ->when($senderId !== null && $senderId !== '', fn (Builder $query) => $query->where('user_id', $senderId))
            ->when($mentions === 'me', fn (Builder $query) => $query->where('body', 'like', '%@'.$viewer->chatHandle().'%'));
    }

    // Detects all mentioned users except the sender and notifies them.
    protected function notifyMentionedUsers(Message $message, User $sender): void
    {
        $mentionedUsers = $this->allUsers()
            ->filter(function (User $user) use ($sender, $message): bool {
                if ($user->id === $sender->id) {
                    return false;
                }

                return in_array(Str::lower($user->chatHandle()), $this->extractMentionHandles($message->body), true);
            })
            ->values();

        foreach ($mentionedUsers as $recipient) {
            $this->notificationService->notifyChatMention($recipient, $message, $sender);
            $this->telegramService->sendChatMention($recipient, $message, $sender);
        }
    }

    // XSS protection happens here:
    // message text is escaped with e(), and only known mention spans are added
    // back as safe HTML wrappers.
    protected function renderBodyHtml(string $body, string $viewerHandle): string
    {
        $knownHandles = $this->mentionHandleMap();

        if (! preg_match_all('/@([A-Za-z0-9._-]+)/', $body, $matches, PREG_OFFSET_CAPTURE)) {
            return nl2br(e($body));
        }

        $cursor = 0;
        $html = '';

        foreach ($matches[0] as [$mention, $offset]) {
            $html .= nl2br(e(substr($body, $cursor, $offset - $cursor)));

            $handle = Str::lower(ltrim($mention, '@'));

            if (isset($knownHandles[$handle])) {
                $classes = 'chat-mention';

                if ($handle === $viewerHandle) {
                    $classes .= ' chat-mention--me';
                }

                $html .= '<span class="'.$classes.'">'.e($mention).'</span>';
            } else {
                $html .= e($mention);
            }

            $cursor = $offset + strlen($mention);
        }

        $html .= nl2br(e(substr($body, $cursor)));

        return $html;
    }

    // Extracts plain mention handles like "john.doe" from text like "@john.doe".
    protected function extractMentionHandles(string $body): array
    {
        preg_match_all('/@([A-Za-z0-9._-]+)/', $body, $matches);

        return collect($matches[1] ?? [])
            ->map(fn (string $handle) => Str::lower($handle))
            ->unique()
            ->values()
            ->all();
    }

    // Map of known handles so the renderer can highlight only real users.
    protected function mentionHandleMap(): array
    {
        if ($this->mentionHandleMap !== null) {
            return $this->mentionHandleMap;
        }

        $this->mentionHandleMap = $this->allUsers()
            ->mapWithKeys(fn (User $user) => [Str::lower($user->chatHandle()) => $user->id])
            ->all();

        return $this->mentionHandleMap;
    }

    /*
    |----------------------------------------------------------------------
    | Presence from sessions
    |----------------------------------------------------------------------
    | This project uses database sessions. That allows chat to estimate
    | online/offline state by reading each user's latest session activity.
    */
    protected function presenceMap(): array
    {
        if (config('session.driver') !== 'database') {
            return [];
        }

        $table = config('session.table', 'sessions');

        if (! DB::getSchemaBuilder()->hasTable($table)) {
            return [];
        }

        return DB::table($table)
            ->selectRaw('user_id, MAX(last_activity) as last_activity')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->pluck('last_activity', 'user_id')
            ->map(fn ($value) => (int) $value)
            ->all();
    }

    // Cached user list shared across mention, presence, and directory logic.
    protected function allUsers(): Collection
    {
        if ($this->allUsers !== null) {
            return $this->allUsers;
        }

        $this->allUsers = User::query()
            ->approved()
            ->orderBy('name')
            ->get();

        return $this->allUsers;
    }

    // Incremental appending is safe only when no filters are altering the list.
    protected function filtersAreDefault(array $filters): bool
    {
        return trim((string) ($filters['search'] ?? '')) === ''
            && trim((string) ($filters['sender_id'] ?? '')) === ''
            && trim((string) ($filters['mentions'] ?? '')) === '';
    }
}
