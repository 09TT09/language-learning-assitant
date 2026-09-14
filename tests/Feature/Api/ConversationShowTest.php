<?php

namespace Tests\Feature\Api;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Message;

class ConversationShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_a_conversation(): void
    {
        $conversation = Conversation::factory()->create();

        $this->getJson("/api/conversations/{$conversation->id}")
            ->assertUnauthorized();
    }

    public function test_user_cannot_view_another_users_conversation(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
    
        $conversation = Conversation::factory()
            ->for($otherUser)
            ->create();
    
        $this->actingAs($user)
            ->getJson("/api/conversations/{$conversation->id}")
            ->assertForbidden();
    }

    public function test_user_can_view_their_own_conversation(): void
    {
        $user = User::factory()->create();
    
        $conversation = Conversation::factory()
            ->for($user)
            ->create();
    
        $this->actingAs($user)
            ->getJson("/api/conversations/{$conversation->id}")
            ->assertOk()
            ->assertJsonPath('id', $conversation->id)
            ->assertJsonPath('user_id', $user->id)
            ->assertJsonPath('language', $conversation->language)
            ->assertJsonPath('level', $conversation->level);
    }

    public function test_user_can_view_their_conversation_with_messages(): void
    {
        $user = User::factory()->create();
    
        $conversation = Conversation::factory()
            ->for($user)
            ->create();
    
        $message1 = Message::factory()
            ->for($conversation)
            ->create();
    
        $message2 = Message::factory()
            ->for($conversation)
            ->create();
    
        $this->actingAs($user)
            ->getJson("/api/conversations/{$conversation->id}")
            ->assertOk()
            ->assertJsonFragment([
                'id' => $message1->id,
            ])
            ->assertJsonFragment([
                'id' => $message2->id,
            ]);
    }

    public function test_it_returns_not_found_for_a_non_existent_conversation(): void
    {
        $user = User::factory()->create();
    
        $this->actingAs($user)
            ->getJson('/api/conversations/999999')
            ->assertNotFound();
    }
}