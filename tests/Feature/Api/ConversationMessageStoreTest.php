<?php

namespace Tests\Feature\Api;

use App\Models\Conversation;
use App\Models\User;
use App\Services\SpanishTutorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationMessageStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_send_a_message(): void
    {
        $conversation = Conversation::factory()->create();

        $this->postJson("/api/conversations/{$conversation->id}/messages", [
            'content' => 'Hola',
        ])->assertUnauthorized();
    }

    public function test_users_cannot_send_a_message_to_someone_elses_conversation(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $conversation = Conversation::factory()->for($owner)->create();

        $this->actingAs($otherUser)
            ->postJson("/api/conversations/{$conversation->id}/messages", [
                'content' => 'Hola',
            ])
            ->assertForbidden();
    }

    public function test_the_owner_can_send_a_message(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->for($user)->create();

        $this->mock(SpanishTutorService::class, function ($mock) {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->andReturn([
                    'message' => [
                        'id' => 1,
                        'role' => 'assistant',
                        'content' => 'Hola, ¿cómo estás?',
                    ],
                    'mistakes' => [],
                ]);
        });

        $this->actingAs($user)
            ->postJson("/api/conversations/{$conversation->id}/messages", [
                'content' => 'Hola',
            ])
            ->assertOk()
            ->assertJsonPath('message.content', 'Hola, ¿cómo estás?');
    }
}
