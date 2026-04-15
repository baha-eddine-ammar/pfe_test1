<?php

namespace Tests\Feature\Chat;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_chat_page_can_be_rendered_for_authenticated_users(): void
    {
        $user = User::factory()->create();
        User::factory()->count(2)->create();

        $response = $this->actingAs($user)->get(route('chat.index'));

        $response
            ->assertOk()
            ->assertSeeText('Team chat workspace')
            ->assertSeeText('Team directory');
    }

    public function test_chat_directory_only_lists_approved_users(): void
    {
        $user = User::factory()->create([
            'name' => 'Approved User',
        ]);

        $pendingUser = User::factory()->create([
            'name' => 'Pending User',
            'status' => 'pending',
            'is_approved' => false,
        ]);

        $response = $this->actingAs($user)->get(route('chat.index'));

        $response
            ->assertOk()
            ->assertSeeText('Approved User')
            ->assertDontSeeText($pendingUser->name);
    }

    public function test_chat_store_returns_json_for_async_requests(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->postJson(route('chat.store'), [
                'body' => 'Hello from the upgraded chat test',
            ]);

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Message sent successfully.',
            ])
            ->assertJsonPath('summary.message_count', 1)
            ->assertJsonPath('summary.last_message_id', 1);

        $this->assertDatabaseHas('messages', [
            'user_id' => $user->id,
            'body' => 'Hello from the upgraded chat test',
        ]);

        $this->assertStringContainsString('Hello from the upgraded chat test', $response->json('message_html'));
    }

    public function test_chat_store_keeps_redirect_behavior_for_normal_form_posts(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('chat.store'), [
                'body' => 'Standard form message',
            ]);

        $response
            ->assertRedirect(route('chat.index'))
            ->assertSessionHas('success', 'Message sent successfully.');

        $this->assertDatabaseHas('messages', [
            'user_id' => $user->id,
            'body' => 'Standard form message',
        ]);
    }

    public function test_chat_messages_endpoint_returns_html_partials_and_summary(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Message::query()->create([
            'user_id' => $user->id,
            'body' => 'My message',
        ]);

        Message::query()->create([
            'user_id' => $otherUser->id,
            'body' => 'Other message',
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson(route('chat.messages'));

        $response
            ->assertOk()
            ->assertJsonPath('append', false)
            ->assertJsonPath('summary.message_count', 2);

        $this->assertStringContainsString('My message', $response->json('messages_html'));
        $this->assertStringContainsString('Other message', $response->json('messages_html'));
        $this->assertStringContainsString($otherUser->name, $response->json('users_html'));
    }

    public function test_tagged_users_receive_platform_and_telegram_notifications(): void
    {
        config()->set('services.telegram.bot_token', 'telegram-test-token');
        Http::fake();

        $sender = User::factory()->create([
            'email' => 'sender.user@draxmailer',
        ]);

        $recipient = User::factory()->create([
            'email' => 'ops.agent@draxmailer',
            'telegram_chat_id' => '456789123',
        ]);

        $this
            ->actingAs($sender)
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->postJson(route('chat.store'), [
                'body' => 'Please review the alert @ops.agent before the next shift.',
            ])
            ->assertOk();

        $message = Message::query()->latest('id')->first();

        $this->assertNotNull($message);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $recipient->id,
            'type' => 'chat.mentioned',
            'title' => 'You were mentioned in team chat',
        ]);

        Http::assertSent(function ($request) use ($recipient) {
            return $request->url() === 'https://api.telegram.org/bottelegram-test-token/sendMessage'
                && $request['chat_id'] === $recipient->telegram_chat_id
                && str_contains($request['text'], 'You were mentioned in team chat.')
                && str_contains($request['text'], 'By:');
        });

        $this->assertNotNull($message->id);
    }

    public function test_chat_messages_endpoint_can_filter_to_mentions_for_current_user(): void
    {
        $user = User::factory()->create([
            'email' => 'network.ops@draxmailer',
        ]);

        $otherUser = User::factory()->create([
            'email' => 'other.user@draxmailer',
        ]);

        Message::query()->create([
            'user_id' => $otherUser->id,
            'body' => 'Ping @network.ops when the backup completes.',
        ]);

        Message::query()->create([
            'user_id' => $otherUser->id,
            'body' => 'This message should not match.',
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson(route('chat.messages', ['mentions' => 'me']));

        $response->assertOk();

        $this->assertSame(1, $response->json('summary.message_count'));
        $this->assertStringContainsString('@network.ops', $response->json('messages_html'));
        $this->assertStringNotContainsString('This message should not match.', $response->json('messages_html'));
    }
}
