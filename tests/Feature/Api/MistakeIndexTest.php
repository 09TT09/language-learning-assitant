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
}