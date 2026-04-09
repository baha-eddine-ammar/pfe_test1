<?php

namespace Tests\Feature\AIChat;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AIChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_ai_chat_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('ai-chat.index'))
            ->assertOk()
            ->assertSeeText('AI Chat')
            ->assertSeeText('Your prompt')
            ->assertSeeText('Ask for quick suggestions about server room operations');
    }

    public function test_authenticated_user_can_send_message_and_receive_groq_response(): void
    {
        $user = User::factory()->create();

        config()->set('services.groq.api_key', 'test-key');
        config()->set('services.groq.base_url', 'https://api.groq.com/openai/v1');
        config()->set('services.groq.model', 'llama-test');

        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Groq says: start by reviewing the latest report and maintenance queue.',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('ai-chat.send'), [
                'body' => 'Give me a maintenance suggestion',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'AI suggestion generated successfully.')
            ->assertJsonPath('user_message.role', 'user')
            ->assertJsonPath('assistant_message.role', 'assistant')
            ->assertJsonPath('assistant_message.body', 'Groq says: start by reviewing the latest report and maintenance queue.')
            ->assertJsonPath('assistant_message.meta.provider', 'groq')
            ->assertJsonPath('assistant_message.meta.status', 'success');

        Http::assertSentCount(1);
    }

    public function test_ai_chat_falls_back_when_groq_is_not_configured(): void
    {
        $user = User::factory()->create();

        config()->set('services.groq.api_key', null);

        $response = $this->actingAs($user)
            ->postJson(route('ai-chat.send'), [
                'body' => 'Give me a maintenance suggestion',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('assistant_message.meta.provider', 'fallback')
            ->assertJsonPath('assistant_message.meta.status', 'fallback');

        $this->assertStringContainsString(
            'This is a suggestion based on your server room data',
            $response->json('assistant_message.body')
        );
    }
}
