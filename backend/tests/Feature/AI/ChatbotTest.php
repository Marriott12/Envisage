<?php

namespace Tests\Feature\AI;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ChatbotTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_send_message_to_chatbot()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/ai/chat/message', [
                'conversation_id' => 'test_conv_123',
                'message' => 'Hello, I need help finding a product'
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'response',
                    'conversation_id',
                    'suggested_actions'
                ]
            ]);
    }

    /** @test */
    public function it_can_get_conversation_history()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/ai/chat/conversations/test_conv_123');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'conversation_id',
                    'messages' => [
                        '*' => ['role', 'content', 'timestamp']
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_validates_message_content()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/ai/chat/message', [
                'conversation_id' => 'test_conv_123',
                'message' => ''
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    /** @test */
    public function it_requires_authentication_for_chat()
    {
        $response = $this->postJson('/api/ai/chat/message', [
            'conversation_id' => 'test_conv_123',
            'message' => 'Hello'
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_can_end_conversation()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/ai/chat/conversations/test_conv_123/end');

        $response->assertStatus(200);
    }
}
