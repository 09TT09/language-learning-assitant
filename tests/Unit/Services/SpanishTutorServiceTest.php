<?php

namespace Tests\Unit\Services;

use App\Ai\Agents\SpanishTutor;
use App\Models\Conversation;
use App\Models\User;
use App\Services\SpanishTutorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpanishTutorServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_the_user_message_and_assistant_response(): void
    {
        $user = User::factory()->create();

        $conversation = Conversation::factory()
            ->for($user)
            ->create();

        SpanishTutor::fake(function () {
            return [
                'reply' => '¡Muy bien! ¿Qué hiciste ayer?',
                'corrected_sentence' => 'Ayer fui al restaurante.',
                'mistakes' => [],
            ];
        });

        $result = app(SpanishTutorService::class)->sendMessage(
            $conversation,
            'Ayer fui al restaurante.'
        );

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Ayer fui al restaurante.',
            'corrected_content' => 'Ayer fui al restaurante.',
        ]);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => '¡Muy bien! ¿Qué hiciste ayer?',
        ]);

        $this->assertCount(0, $result['mistakes']);

        SpanishTutor::assertPrompted(function ($prompt): bool {
            return str_contains(
                $prompt->prompt,
                'Ayer fui al restaurante.'
            );
        });
    }

    public function test_it_stores_detected_mistakes(): void
    {
        $user = User::factory()->create();
    
        $conversation = Conversation::factory()
            ->for($user)
            ->create();
    
        SpanishTutor::fake(function () {
            return [
                'reply' => '¡Muy bien! ¿Qué hiciste ayer?',
                'corrected_sentence' => 'Ayer fui al restaurante.',
                'mistakes' => [
                    [
                        'type' => 'grammar',
                        'subtype' => 'verb_conjugation',
                        'original_text' => 'Yo fue',
                        'corrected_text' => 'Yo fui',
                        'explanation' => 'The first person singular of ir in the preterite is fui.',
                        'severity' => 'high',
                    ],
                ],
            ];
        });
    
        app(SpanishTutorService::class)->sendMessage(
            $conversation,
            'Ayer yo fue al restaurante.'
        );
    
        $this->assertDatabaseHas('mistakes', [
            'conversation_id' => $conversation->id,
            'type' => 'grammar',
            'subtype' => 'verb_conjugation',
            'original_text' => 'Yo fue',
            'corrected_text' => 'Yo fui',
            'explanation' => 'The first person singular of ir in the preterite is fui.',
            'severity' => 'high',
        ]);
    }

    public function test_it_throws_a_friendly_exception_when_the_ai_request_fails(): void
    {
        $user = User::factory()->create();
    
        $conversation = Conversation::factory()
            ->for($user)
            ->create();
    
        SpanishTutor::fake(function () {
            throw new \Exception('Gemini API error');
        });
    
        $this->expectException(\RuntimeException::class);
    
        $this->expectExceptionMessage(
            'The Spanish tutor is temporarily unavailable. Please try again.'
        );
    
        app(SpanishTutorService::class)->sendMessage(
            $conversation,
            'Hola'
        );
    }

    public function test_it_rolls_back_the_user_message_when_the_ai_request_fails(): void
    {
        $user = User::factory()->create();
    
        $conversation = Conversation::factory()
            ->for($user)
            ->create();
    
        SpanishTutor::fake(function () {
            throw new \Exception('Gemini API error');
        });
    
        try {
            app(SpanishTutorService::class)->sendMessage(
                $conversation,
                'Hola'
            );
        } catch (\RuntimeException) {
            // Expected exception.
        }
    
        $this->assertDatabaseMissing('messages', [
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Hola',
        ]);
    
        $this->assertDatabaseCount('messages', 0);
    }

    public function test_it_includes_conversation_history_in_the_ai_prompt(): void
    {
        $user = User::factory()->create();
    
        $conversation = Conversation::factory()
            ->for($user)
            ->create();
    
        $conversation->messages()->create([
            'role' => 'user',
            'content' => 'Hola, ¿cómo estás?',
        ]);
    
        $conversation->messages()->create([
            'role' => 'assistant',
            'content' => 'Estoy muy bien. ¿Y tú?',
        ]);
    
        SpanishTutor::fake(function () {
            return [
                'reply' => 'Estoy bien también.',
                'corrected_sentence' => 'Estoy bien también.',
                'mistakes' => [],
            ];
        });
    
        app(SpanishTutorService::class)->sendMessage(
            $conversation,
            'Yo estoy muy bien.'
        );
    
        SpanishTutor::assertPrompted(function ($prompt): bool {
            return str_contains(
                $prompt->prompt,
                'Hola, ¿cómo estás?'
            )
            && str_contains(
                $prompt->prompt,
                'Estoy muy bien. ¿Y tú?'
            )
            && str_contains(
                $prompt->prompt,
                'Yo estoy muy bien.'
            );
        });
    }
}