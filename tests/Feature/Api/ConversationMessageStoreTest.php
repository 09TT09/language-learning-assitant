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

    public function test_the_owner_can_send_a_message_with_correction_and_mistakes(): void
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
                        'content' => '¡Muy bien! ¿Qué hiciste ayer?',
                    ],
                    'corrected_sentence' => 'Ayer yo fui al restaurante.',
                    'mistakes' => [
                        [
                            'id' => 1,
                            'type' => 'grammar',
                            'subtype' => 'verb_conjugation',
                            'original_text' => 'Yo fue',
                            'corrected_text' => 'Yo fui',
                            'explanation' => 'The first person singular of ir in the preterite is fui.',
                            'severity' => 'major',
                        ],
                    ],
                ]);
        });
    
        $this->actingAs($user)
            ->postJson("/api/conversations/{$conversation->id}/messages", [
                'content' => 'Ayer yo fue al restaurante.',
            ])
            ->assertOk()
            ->assertJsonPath(
                'message.content',
                '¡Muy bien! ¿Qué hiciste ayer?'
            )
            ->assertJsonPath(
                'corrected_sentence',
                'Ayer yo fui al restaurante.'
            )
            ->assertJsonPath(
                'mistakes.0.type',
                'grammar'
            )
            ->assertJsonPath(
                'mistakes.0.subtype',
                'verb_conjugation'
            )
            ->assertJsonPath(
                'mistakes.0.original_text',
                'Yo fue'
            )
            ->assertJsonPath(
                'mistakes.0.corrected_text',
                'Yo fui'
            )
            ->assertJsonPath(
                'mistakes.0.severity',
                'major'
            );
    }

    public function test_content_is_required(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->for($user)->create();
    
        $this->actingAs($user)
            ->postJson("/api/conversations/{$conversation->id}/messages", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['content']);
    }
    
    public function test_content_must_be_a_string(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->for($user)->create();
    
        $this->actingAs($user)
            ->postJson("/api/conversations/{$conversation->id}/messages", [
                'content' => ['Hola'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['content']);
    }
    
    public function test_content_cannot_exceed_5000_characters(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->for($user)->create();
    
        $this->actingAs($user)
            ->postJson("/api/conversations/{$conversation->id}/messages", [
                'content' => str_repeat('a', 5001),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['content']);
    }

    public function test_it_returns_503_when_the_spanish_tutor_is_unavailable(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->for($user)->create();
    
        $this->mock(SpanishTutorService::class, function ($mock) {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->andThrow(
                    new \RuntimeException(
                        'The Spanish tutor is temporarily unavailable. Please try again.'
                    )
                );
        });
    
        $this->actingAs($user)
            ->postJson("/api/conversations/{$conversation->id}/messages", [
                'content' => 'Hola',
            ])
            ->assertServiceUnavailable()
            ->assertJson([
                'message' => 'The Spanish tutor is temporarily unavailable. Please try again.',
            ]);
    }

    public function test_a_user_can_send_at_most_10_messages_per_minute(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->for($user)->create();
    
        $this->mock(SpanishTutorService::class, function ($mock) {
            $mock->shouldReceive('sendMessage')
                ->times(10)
                ->andReturn([
                    'message' => [
                        'id' => 1,
                        'role' => 'assistant',
                        'content' => '¡Hola!',
                    ],
                    'corrected_sentence' => 'Hola.',
                    'mistakes' => [],
                ]);
        });
    
        $payload = [
            'content' => 'Hola',
        ];
    
        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($user)
                ->postJson(
                    "/api/conversations/{$conversation->id}/messages",
                    $payload
                )
                ->assertOk();
        }
    
        $response = $this->actingAs($user)
        ->postJson(
            "/api/conversations/{$conversation->id}/messages",
            $payload
        );
    
        $response
            ->assertTooManyRequests()
            ->assertJson([
                'message' => 'Too Many Attempts.',
            ]);
    }
}