<?php

namespace Tests\Feature\Api;

use App\Models\Conversation;
use App\Models\Mistake;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MistakeIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_list_mistakes(): void
    {
        $response = $this->getJson('/api/mistakes');

        $response->assertUnauthorized();
    }

    public function test_user_can_list_their_own_mistakes(): void
    {
        $user = User::factory()->create();

        $conversation = Conversation::factory()
            ->for($user)
            ->create();

        Mistake::factory()
            ->for($conversation)
            ->create();

        $response = $this
            ->actingAs($user)
            ->getJson('/api/mistakes');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_user_only_sees_their_own_mistakes(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
    
        $conversation = Conversation::factory()
            ->for($user)
            ->create();
    
        $otherConversation = Conversation::factory()
            ->for($otherUser)
            ->create();
    
        $mistake = Mistake::factory()
            ->for($conversation)
            ->create();
    
        Mistake::factory()
            ->for($otherConversation)
            ->create();
    
        $response = $this
            ->actingAs($user)
            ->getJson('/api/mistakes');
    
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mistake->id);
    }

    public function test_user_can_filter_mistakes_by_type(): void
    {
        $user = User::factory()->create();
    
        $conversation = Conversation::factory()
            ->for($user)
            ->create();
    
        Mistake::factory()
            ->for($conversation)
            ->create([
                'type' => 'grammar',
            ]);
    
        Mistake::factory()
            ->for($conversation)
            ->create([
                'type' => 'vocabulary',
            ]);
    
        $response = $this
            ->actingAs($user)
            ->getJson('/api/mistakes?type=grammar');
    
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'grammar');
    }

    public function test_user_can_filter_mistakes_by_severity(): void
    {
        $user = User::factory()->create();
    
        $conversation = Conversation::factory()
            ->for($user)
            ->create();
    
        Mistake::factory()
            ->for($conversation)
            ->create([
                'severity' => 'low',
            ]);
    
        Mistake::factory()
            ->for($conversation)
            ->create([
                'severity' => 'high',
            ]);
    
        $response = $this
            ->actingAs($user)
            ->getJson('/api/mistakes?severity=high');
    
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.severity', 'high');
    }

    public function test_user_can_filter_mistakes_by_subtype(): void
    {
        $user = User::factory()->create();
    
        $conversation = Conversation::factory()
            ->for($user)
            ->create();
    
        Mistake::factory()
            ->for($conversation)
            ->create([
                'subtype' => 'verb_conjugation',
            ]);
    
        Mistake::factory()
            ->for($conversation)
            ->create([
                'subtype' => 'article',
            ]);
    
        $response = $this
            ->actingAs($user)
            ->getJson('/api/mistakes?subtype=verb_conjugation');
    
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.subtype', 'verb_conjugation');
    }

    public function test_user_can_paginate_mistakes(): void
    {
        $user = User::factory()->create();
    
        $conversation = Conversation::factory()
            ->for($user)
            ->create();
    
        Mistake::factory()
            ->for($conversation)
            ->count(3)
            ->create();
    
        $response = $this
            ->actingAs($user)
            ->getJson('/api/mistakes?per_page=1');
    
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.total', 3);
    }

    public function test_invalid_type_filter_returns_validation_error(): void
    {
        $user = User::factory()->create();
    
        $response = $this
            ->actingAs($user)
            ->getJson('/api/mistakes?type=invalid');
    
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }

    public function test_invalid_severity_filter_returns_validation_error(): void
    {
        $user = User::factory()->create();
    
        $response = $this
            ->actingAs($user)
            ->getJson('/api/mistakes?severity=invalid');
    
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['severity']);
    }

    public function test_response_contains_mistake_details(): void
    {
        $user = User::factory()->create();
    
        $conversation = Conversation::factory()
            ->for($user)
            ->create();
    
        Mistake::factory()
            ->for($conversation)
            ->create([
                'type' => 'grammar',
                'subtype' => 'verb_conjugation',
                'original_text' => 'yo hablo ayer',
                'corrected_text' => 'yo hablé ayer',
                'explanation' => 'Use the past tense for a completed action.',
                'severity' => 'medium',
            ]);
    
        $response = $this
            ->actingAs($user)
            ->getJson('/api/mistakes');
    
        $response->assertOk()
            ->assertJsonPath('data.0.type', 'grammar')
            ->assertJsonPath('data.0.subtype', 'verb_conjugation')
            ->assertJsonPath('data.0.original_text', 'yo hablo ayer')
            ->assertJsonPath('data.0.corrected_text', 'yo hablé ayer')
            ->assertJsonPath(
                'data.0.explanation',
                'Use the past tense for a completed action.'
            )
            ->assertJsonPath('data.0.severity', 'medium');
    }

    public function test_response_contains_sentence_and_all_message_mistakes(): void
    {
        $user = User::factory()->create();
    
        $conversation = Conversation::factory()
            ->for($user)
            ->create();
    
        $message = $conversation->messages()->create([
            'role' => 'user',
            'content' => 'Hola hoi fue por la playa conn mis amigo.',
        ]);
    
        $firstMistake = Mistake::factory()
            ->for($conversation)
            ->for($message)
            ->create([
                'type' => 'spelling',
                'subtype' => 'typo',
                'original_text' => 'hoi',
                'corrected_text' => 'hoy',
                'start_position' => 5,
                'end_position' => 8,
                'explanation' => 'The correct spelling is hoy.',
                'severity' => 'medium',
            ]);
    
        Mistake::factory()
            ->for($conversation)
            ->for($message)
            ->create([
                'type' => 'grammar',
                'subtype' => 'verb_conjugation',
                'original_text' => 'fue',
                'corrected_text' => 'fui',
                'start_position' => 9,
                'end_position' => 12,
                'explanation' => 'Use fui with yo.',
                'severity' => 'high',
            ]);
    
        Mistake::factory()
            ->for($conversation)
            ->for($message)
            ->create([
                'type' => 'spelling',
                'subtype' => 'typo',
                'original_text' => 'conn',
                'corrected_text' => 'con',
                'start_position' => 26,
                'end_position' => 30,
                'explanation' => 'The correct spelling is con.',
                'severity' => 'low',
            ]);
    
        Mistake::factory()
            ->for($conversation)
            ->for($message)
            ->create([
                'type' => 'grammar',
                'subtype' => 'number_agreement',
                'original_text' => 'amigo',
                'corrected_text' => 'amigos',
                'start_position' => 35,
                'end_position' => 40,
                'explanation' => 'The noun must be plural.',
                'severity' => 'medium',
            ]);
    
        $response = $this
            ->actingAs($user)
            ->getJson('/api/mistakes');
    
        $response->assertOk()
            ->assertJsonPath(
                'data.0.sentence',
                'Hola hoi fue por la playa conn mis amigo.'
            )
            ->assertJsonCount(4, 'data.0.message_mistakes')
            ->assertJsonPath(
                'data.0.message_mistakes.0.id',
                $firstMistake->id
            )
            ->assertJsonPath(
                'data.0.message_mistakes.0.original_text',
                'hoi'
            )
            ->assertJsonPath(
                'data.0.message_mistakes.0.corrected_text',
                'hoy'
            )
            ->assertJsonPath(
                'data.0.message_mistakes.0.start_position',
                5
            )
            ->assertJsonPath(
                'data.0.message_mistakes.0.end_position',
                8
            )
            ->assertJsonPath(
                'data.0.message_mistakes.0.type',
                'spelling'
            )
            ->assertJsonPath(
                'data.0.message_mistakes.0.subtype',
                'typo'
            )
            ->assertJsonPath(
                'data.0.message_mistakes.0.severity',
                'medium'
            );
    }
}