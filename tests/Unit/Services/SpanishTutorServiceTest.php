<?php

namespace Tests\Unit\Services;

use App\Ai\Agents\SpanishTutor;
use App\Enums\ConversationStepStatus;
use App\Models\Conversation;
use App\Models\Topic;
use App\Models\User;
use App\Services\SpanishTutorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Exceptions\ProviderConnectionException;
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
                'completed_step_positions' => [],
'scenario_state_updates' => [],
                'title' => 'Ayer en el restaurante',
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

        $this->assertDatabaseCount('mistakes', 0);

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
                'corrected_sentence' => 'Ayer yo fui al restaurante.',
                'mistakes' => [
                    [
                        'type' => 'grammar',
                        'subtype' => 'verb_conjugation',
                        'original_text' => 'Yo fue',
                        'corrected_text' => 'Yo fui',
                        'explanation' => 'The first person singular of ir in the preterite is fui.',
                        'severity' => 'high',
                        'start_position' => 999,
                        'end_position' => 999,
                    ],
                ],
                'completed_step_positions' => [],
'scenario_state_updates' => [],
                'title' => 'Ayer en el restaurante',
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
            'start_position' => 5,
            'end_position' => 11,
        ]);
    }

    public function test_it_calculates_positions_for_multiple_mistakes(): void
    {
        $user = User::factory()->create();
    
        $conversation = Conversation::factory()
            ->for($user)
            ->create();
    
        SpanishTutor::fake(function () {
            return [
                'reply' => '¡Muy bien!',
    
                'corrected_sentence' =>
                    'Hola hoy fui por la playa con mis amigos',
    
                'mistakes' => [
                    [
                        'type' => 'spelling',
                        'subtype' => 'typo',
                        'original_text' => 'hoi',
                        'corrected_text' => 'hoy',
                        'explanation' => 'The correct spelling is hoy.',
                        'severity' => 'medium',
                        'start_position' => 999,
                        'end_position' => 999,
                    ],
                    [
                        'type' => 'grammar',
                        'subtype' => 'verb_conjugation',
                        'original_text' => 'fue',
                        'corrected_text' => 'fui',
                        'explanation' => 'The first person singular of ir in the preterite is fui.',
                        'severity' => 'high',
                        'start_position' => 999,
                        'end_position' => 999,
                    ],
                    [
                        'type' => 'spelling',
                        'subtype' => 'typo',
                        'original_text' => 'conn',
                        'corrected_text' => 'con',
                        'explanation' => 'The correct spelling is con.',
                        'severity' => 'low',
                        'start_position' => 999,
                        'end_position' => 999,
                    ],
                    [
                        'type' => 'grammar',
                        'subtype' => 'number_agreement',
                        'original_text' => 'amigo',
                        'corrected_text' => 'amigos',
                        'explanation' => 'The noun must agree with the plural possessive.',
                        'severity' => 'medium',
                        'start_position' => 999,
                        'end_position' => 999,
                    ],
                ],
                'completed_step_positions' => [],
                'scenario_state_updates' => [],
                'title' => 'Un paseo por la playa',
            ];
        });
    
        app(SpanishTutorService::class)->sendMessage(
            $conversation,
            'Hola hoi fue por la playa conn mis amigo.'
        );
    
        $this->assertDatabaseHas('mistakes', [
            'conversation_id' => $conversation->id,
            'original_text' => 'hoi',
            'start_position' => 5,
            'end_position' => 8,
        ]);
    
        $this->assertDatabaseHas('mistakes', [
            'conversation_id' => $conversation->id,
            'original_text' => 'fue',
            'start_position' => 9,
            'end_position' => 12,
        ]);
    
        $this->assertDatabaseHas('mistakes', [
            'conversation_id' => $conversation->id,
            'original_text' => 'conn',
            'start_position' => 26,
            'end_position' => 30,
        ]);
    
        $this->assertDatabaseHas('mistakes', [
            'conversation_id' => $conversation->id,
            'original_text' => 'amigo',
            'start_position' => 35,
            'end_position' => 40,
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
                'completed_step_positions' => [],
                'scenario_state_updates' => [],
                'title' => '¿Cómo estás?',
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

    public function test_it_generates_and_saves_a_title_for_the_first_message(): void
    {
        $user = User::factory()->create();
    
        $conversation = Conversation::factory()
            ->for($user)
            ->create([
                'title' => null,
            ]);
    
        SpanishTutor::fake(function () {
            return [
                'reply' => '¡Muy bien! ¿Cómo estás?',
                'corrected_sentence' => 'Hola, ¿cómo estás?',
                'mistakes' => [],
                'completed_step_positions' => [],
                'scenario_state_updates' => [],
                'title' => '¿Cómo estás?',
            ];
        });
    
        app(SpanishTutorService::class)->sendMessage(
            $conversation,
            'Hola, ¿cómo estás?'
        );
    
        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'title' => '¿Cómo estás?',
        ]);
    }

    public function test_it_does_not_regenerate_the_title_on_subsequent_messages(): void
    {
        $user = User::factory()->create();
    
        $conversation = Conversation::factory()
            ->for($user)
            ->create([
                'title' => 'Mi viaje a España',
            ]);
    
        SpanishTutor::fake(function () {
            return [
                'reply' => '¡Qué interesante!',
                'corrected_sentence' => 'Mañana voy a Madrid.',
                'completed_step_positions' => [],
                'scenario_state_updates' => [],
                'mistakes' => [],
            ];
        });
    
        app(SpanishTutorService::class)->sendMessage(
            $conversation,
            'Mañana voy a Madrid.'
        );
    
        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'title' => 'Mi viaje a España',
        ]);
    }

    public function test_it_does_not_save_a_title_when_the_first_message_fails(): void
    {
        $user = User::factory()->create();
    
        $conversation = Conversation::factory()
            ->for($user)
            ->create([
                'title' => null,
            ]);
    
        SpanishTutor::fake(function () {
            throw new \Exception('Gemini API error');
        });
    
        try {
            app(SpanishTutorService::class)->sendMessage(
                $conversation,
                'Hola, ¿cómo estás?'
            );
        } catch (\RuntimeException) {
            // Expected exception.
        }
    
        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'title' => null,
        ]);
    }

    public function test_it_retries_when_the_ai_provider_is_overloaded(): void
    {
        $user = User::factory()->create();
    
        $conversation = Conversation::factory()
            ->for($user)
            ->create();
    
        $attempts = 0;
    
        SpanishTutor::fake(function () use (&$attempts) {
            $attempts++;
    
            if ($attempts < 2) {
                throw new ProviderOverloadedException();
            }
    
            return [
                'reply' => '¡Muy bien!',
                'corrected_sentence' => 'Hola, ¿cómo estás?',
                'mistakes' => [],
                'completed_step_positions' => [],
                'scenario_state_updates' => [],
                'title' => 'Saludos',
            ];
        });
    
        $result = app(SpanishTutorService::class)->sendMessage(
            $conversation,
            'Hola, ¿cómo estás?'
        );
    
        $this->assertSame(2, $attempts);
    
        $this->assertSame(
            '¡Muy bien!',
            $result['message']->content
        );
    }

    public function test_it_completes_an_active_step_and_unlocks_its_dependents(): void
    {
        $user = User::factory()->create();
    
        $conversation = Conversation::factory()
            ->for($user)
            ->create();
    
        $topic = Topic::factory()->create([
            'level' => 'A1',
        ]);
    
        $step1 = $topic->steps()->create([
            'position' => 1,
            'title' => 'Arriving',
            'narrator' => 'You arrive at a restaurant.',
            'objective' => 'Ask for a table.',
        ]);
    
        $step2 = $topic->steps()->create([
            'position' => 2,
            'title' => 'Ordering',
            'narrator' => 'You are seated at the table.',
            'objective' => 'Order your meal.',
        ]);
    
        $step3 = $topic->steps()->create([
            'position' => 3,
            'title' => 'Drinking',
            'narrator' => 'The waiter asks what you want to drink.',
            'objective' => 'Order a drink.',
        ]);
    
        $step2->dependencies()->attach($step1);
        $step3->dependencies()->attach($step1);
    
        $conversation->update([
            'topic_id' => $topic->id,
        ]);
    
        $conversation->steps()->create([
            'topic_step_id' => $step1->id,
            'status' => ConversationStepStatus::ACTIVE,
        ]);
    
        $conversation->steps()->create([
            'topic_step_id' => $step2->id,
            'status' => ConversationStepStatus::LOCKED,
        ]);
    
        $conversation->steps()->create([
            'topic_step_id' => $step3->id,
            'status' => ConversationStepStatus::LOCKED,
        ]);
    
        SpanishTutor::fake(function () {
            return [
                'reply' => 'Perfecto. ¿Qué desea pedir?',
                'corrected_sentence' => 'Una mesa para dos, por favor.',
                'mistakes' => [],
                'completed_step_positions' => [1],
                'scenario_state_updates' => [],
                'title' => 'En el restaurante',
            ];
        });
    
        $result = app(SpanishTutorService::class)->sendMessage(
            $conversation,
            'Una mesa para dos, por favor.'
        );
    
        $this->assertSame([1], $result['completed_steps']);
    
        $this->assertDatabaseHas('conversation_steps', [
            'conversation_id' => $conversation->id,
            'topic_step_id' => $step1->id,
            'status' => ConversationStepStatus::COMPLETED->value,
        ]);
    
        $this->assertDatabaseHas('conversation_steps', [
            'conversation_id' => $conversation->id,
            'topic_step_id' => $step2->id,
            'status' => ConversationStepStatus::ACTIVE->value,
        ]);
    
        $this->assertDatabaseHas('conversation_steps', [
            'conversation_id' => $conversation->id,
            'topic_step_id' => $step3->id,
            'status' => ConversationStepStatus::ACTIVE->value,
        ]);
    }

    public function test_it_completes_multiple_active_steps_and_unlocks_their_dependents(): void
    {
        $user = User::factory()->create();
    
        $conversation = Conversation::factory()
            ->for($user)
            ->create();
    
        $topic = Topic::factory()->create([
            'level' => 'A1',
        ]);
    
        $step1 = $topic->steps()->create([
            'position' => 1,
            'title' => 'Arriving',
            'narrator' => 'You arrive at a restaurant.',
            'objective' => 'Ask for a table.',
        ]);
    
        $step2 = $topic->steps()->create([
            'position' => 2,
            'title' => 'Choosing your meal',
            'narrator' => 'The waiter asks what you would like to eat.',
            'objective' => 'Order your meal.',
        ]);
    
        $step3 = $topic->steps()->create([
            'position' => 3,
            'title' => 'Ordering a drink',
            'narrator' => 'The waiter asks what you would like to drink.',
            'objective' => 'Order a drink.',
        ]);
    
        $step4 = $topic->steps()->create([
            'position' => 4,
            'title' => 'Eating',
            'narrator' => 'The waiter asks whether everything was good.',
            'objective' => 'Talk briefly about your meal.',
        ]);
    
        $step5 = $topic->steps()->create([
            'position' => 5,
            'title' => 'Asking for the bill',
            'narrator' => 'You are ready to leave the restaurant.',
            'objective' => 'Ask for the bill.',
        ]);
    
        $step2->dependencies()->attach($step1);
        $step3->dependencies()->attach($step1);
    
        $step4->dependencies()->attach([
            $step2->id,
            $step3->id,
        ]);
    
        $step5->dependencies()->attach([
            $step2->id,
            $step3->id,
        ]);
    
        $conversation->update([
            'topic_id' => $topic->id,
        ]);
    
        $conversation->steps()->create([
            'topic_step_id' => $step1->id,
            'status' => ConversationStepStatus::COMPLETED,
        ]);
    
        $conversation->steps()->create([
            'topic_step_id' => $step2->id,
            'status' => ConversationStepStatus::ACTIVE,
        ]);
    
        $conversation->steps()->create([
            'topic_step_id' => $step3->id,
            'status' => ConversationStepStatus::ACTIVE,
        ]);
    
        $conversation->steps()->create([
            'topic_step_id' => $step4->id,
            'status' => ConversationStepStatus::LOCKED,
        ]);
    
        $conversation->steps()->create([
            'topic_step_id' => $step5->id,
            'status' => ConversationStepStatus::LOCKED,
        ]);
    
        SpanishTutor::fake(function () {
            return [
                'reply' => 'Perfecto. ¿Todo estaba bien?',
                'corrected_sentence' => 'Quiero una paella y una cerveza, por favor.',
                'mistakes' => [],
                'completed_step_positions' => [2, 3],
                'scenario_state_updates' => [],
                'title' => 'En el restaurante',
            ];
        });
    
        $result = app(SpanishTutorService::class)->sendMessage(
            $conversation,
            'Quiero una paella y una cerveza, por favor.'
        );
    
        $this->assertSame([2, 3], $result['completed_steps']);
    
        $this->assertDatabaseHas('conversation_steps', [
            'conversation_id' => $conversation->id,
            'topic_step_id' => $step2->id,
            'status' => ConversationStepStatus::COMPLETED->value,
        ]);
    
        $this->assertDatabaseHas('conversation_steps', [
            'conversation_id' => $conversation->id,
            'topic_step_id' => $step3->id,
            'status' => ConversationStepStatus::COMPLETED->value,
        ]);
    
        $this->assertDatabaseHas('conversation_steps', [
            'conversation_id' => $conversation->id,
            'topic_step_id' => $step4->id,
            'status' => ConversationStepStatus::ACTIVE->value,
        ]);
    
        $this->assertDatabaseHas('conversation_steps', [
            'conversation_id' => $conversation->id,
            'topic_step_id' => $step5->id,
            'status' => ConversationStepStatus::ACTIVE->value,
        ]);
    }

    public function test_it_unlocks_the_final_step_after_all_dependencies_are_completed(): void
    {
        $user = User::factory()->create();
    
        $conversation = Conversation::factory()
            ->for($user)
            ->create();
    
        $topic = Topic::factory()->create([
            'level' => 'A1',
        ]);
    
        $step1 = $topic->steps()->create([
            'position' => 1,
            'title' => 'Arriving',
            'narrator' => 'You arrive at a restaurant.',
            'objective' => 'Ask for a table.',
        ]);
    
        $step2 = $topic->steps()->create([
            'position' => 2,
            'title' => 'Choosing your meal',
            'narrator' => 'The waiter asks what you would like to eat.',
            'objective' => 'Order your meal.',
        ]);
    
        $step3 = $topic->steps()->create([
            'position' => 3,
            'title' => 'Ordering a drink',
            'narrator' => 'The waiter asks what you would like to drink.',
            'objective' => 'Order a drink.',
        ]);
    
        $step4 = $topic->steps()->create([
            'position' => 4,
            'title' => 'Eating',
            'narrator' => 'The waiter asks whether everything was good.',
            'objective' => 'Talk briefly about your meal.',
        ]);
    
        $step5 = $topic->steps()->create([
            'position' => 5,
            'title' => 'Asking for the bill',
            'narrator' => 'You are ready to leave the restaurant.',
            'objective' => 'Ask for the bill.',
        ]);
    
        $step6 = $topic->steps()->create([
            'position' => 6,
            'title' => 'Paying',
            'narrator' => 'The waiter brings the bill.',
            'objective' => 'Pay the bill and say goodbye.',
        ]);
    
        $step2->dependencies()->attach($step1);
        $step3->dependencies()->attach($step1);
    
        $step4->dependencies()->attach([
            $step2->id,
            $step3->id,
        ]);
    
        $step5->dependencies()->attach([
            $step2->id,
            $step3->id,
        ]);
    
        $step6->dependencies()->attach($step5);
    
        $conversation->update([
            'topic_id' => $topic->id,
        ]);
    
        $conversation->steps()->createMany([
            [
                'topic_step_id' => $step1->id,
                'status' => ConversationStepStatus::COMPLETED,
            ],
            [
                'topic_step_id' => $step2->id,
                'status' => ConversationStepStatus::COMPLETED,
            ],
            [
                'topic_step_id' => $step3->id,
                'status' => ConversationStepStatus::COMPLETED,
            ],
            [
                'topic_step_id' => $step4->id,
                'status' => ConversationStepStatus::ACTIVE,
            ],
            [
                'topic_step_id' => $step5->id,
                'status' => ConversationStepStatus::ACTIVE,
            ],
            [
                'topic_step_id' => $step6->id,
                'status' => ConversationStepStatus::LOCKED,
            ],
        ]);
    
        SpanishTutor::fake(function () {
            return [
                'reply' => 'Perfecto. Aquí tiene la cuenta.',
                'corrected_sentence' => 'La comida estuvo muy buena. La cuenta, por favor.',
                'mistakes' => [],
                'completed_step_positions' => [4, 5],
                'scenario_state_updates' => [],
                'title' => 'En el restaurante',
            ];
        });
    
        $result = app(SpanishTutorService::class)->sendMessage(
            $conversation,
            'La comida estuvo muy buena. La cuenta, por favor.'
        );
    
        $this->assertSame([4, 5], $result['completed_steps']);
    
        $this->assertDatabaseHas('conversation_steps', [
            'conversation_id' => $conversation->id,
            'topic_step_id' => $step4->id,
            'status' => ConversationStepStatus::COMPLETED->value,
        ]);
    
        $this->assertDatabaseHas('conversation_steps', [
            'conversation_id' => $conversation->id,
            'topic_step_id' => $step5->id,
            'status' => ConversationStepStatus::COMPLETED->value,
        ]);
    
        $this->assertDatabaseHas('conversation_steps', [
            'conversation_id' => $conversation->id,
            'topic_step_id' => $step6->id,
            'status' => ConversationStepStatus::ACTIVE->value,
        ]);
    }

    public function test_it_ignores_locked_steps_reported_as_completed_by_the_ai(): void
    {
        $user = User::factory()->create();
    
        $conversation = Conversation::factory()
            ->for($user)
            ->create();
    
        $topic = Topic::factory()->create([
            'level' => 'A1',
        ]);
    
        $step1 = $topic->steps()->create([
            'position' => 1,
            'title' => 'Arriving',
            'narrator' => 'You arrive at a restaurant.',
            'objective' => 'Ask for a table.',
        ]);
    
        $step2 = $topic->steps()->create([
            'position' => 2,
            'title' => 'Ordering',
            'narrator' => 'The waiter asks what you want.',
            'objective' => 'Order your meal.',
        ]);
    
        $step6 = $topic->steps()->create([
            'position' => 6,
            'title' => 'Paying',
            'narrator' => 'The waiter brings the bill.',
            'objective' => 'Pay the bill and say goodbye.',
        ]);
    
        $step2->dependencies()->attach($step1);
        $step6->dependencies()->attach($step2);
    
        $conversation->update([
            'topic_id' => $topic->id,
        ]);
    
        $conversation->steps()->createMany([
            [
                'topic_step_id' => $step1->id,
                'status' => ConversationStepStatus::ACTIVE,
            ],
            [
                'topic_step_id' => $step2->id,
                'status' => ConversationStepStatus::LOCKED,
            ],
            [
                'topic_step_id' => $step6->id,
                'status' => ConversationStepStatus::LOCKED,
            ],
        ]);
    
        SpanishTutor::fake(function () {
            return [
                'reply' => '¡Perfecto!',
                'corrected_sentence' => 'Quiero pagar, por favor.',
                'mistakes' => [],
                'completed_step_positions' => [6],
                'scenario_state_updates' => [],
                'title' => 'En el restaurante',
            ];
        });
    
        $result = app(SpanishTutorService::class)->sendMessage(
            $conversation,
            'Quiero pagar, por favor.'
        );
    
        $this->assertSame([], $result['completed_steps']);
    
        $this->assertDatabaseHas('conversation_steps', [
            'conversation_id' => $conversation->id,
            'topic_step_id' => $step1->id,
            'status' => ConversationStepStatus::ACTIVE->value,
        ]);
    
        $this->assertDatabaseHas('conversation_steps', [
            'conversation_id' => $conversation->id,
            'topic_step_id' => $step2->id,
            'status' => ConversationStepStatus::LOCKED->value,
        ]);
    
        $this->assertDatabaseHas('conversation_steps', [
            'conversation_id' => $conversation->id,
            'topic_step_id' => $step6->id,
            'status' => ConversationStepStatus::LOCKED->value,
        ]);
    }
}