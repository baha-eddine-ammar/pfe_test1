<?php

namespace Tests\Feature\Chat;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_chat_page_can_be_rendered_for_authenticated_users(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('chat.index'));

        $response
            ->assertOk()
            ->assertSeeText('Team Chat');
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
                'body' => 'Hello from chat test',
            ]);

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Message sent successfully.',
            ])
            ->assertJsonPath('message_data.body', 'Hello from chat test')
            ->assertJsonPath('message_data.is_mine', true)
            ->assertJsonPath('message_data.user.id', $user->id);

        $this->assertDatabaseHas('messages', [
            'user_id' => $user->id,
            'body' => 'Hello from chat test',
        ]);
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

    public function test_chat_messages_endpoint_returns_latest_messages_with_user_data(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Message::create([
            'user_id' => $user->id,
            'body' => 'My message',
        ]);

        Message::create([
            'user_id' => $otherUser->id,
            'body' => 'Other message',
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson(route('chat.messages'));

        $response
            ->assertOk()
            ->assertJsonCount(2, 'messages')
            ->assertJsonFragment([
                'body' => 'My message',
                'is_mine' => true,
                'name' => $user->name,
            ])
            ->assertJsonFragment([
                'body' => 'Other message',
                'is_mine' => false,
                'name' => $otherUser->name,
            ]);
    }
}
