<?php

namespace Tests\Feature\Api;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_delete_a_conversation(): void
    {
        $conversation = Conversation::factory()->create();

        $this->deleteJson("/api/conversations/{$conversation->id}")
            ->assertUnauthorized();
    }

    public function test_user_cannot_delete_another_users_conversation(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
    
        $conversation = Conversation::factory()
            ->for($otherUser)
            ->create();
    
        $this->actingAs($user)
            ->deleteJson("/api/conversations/{$conversation->id}")
            ->assertForbidden();
    
        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
        ]);
    }

    public function test_user_can_delete_their_own_conversation(): void
    {
        $user = User::factory()->create();
    
        $conversation = Conversation::factory()
            ->for($user)
            ->create();
    
        $this->actingAs($user)
            ->deleteJson("/api/conversations/{$conversation->id}")
            ->assertOk()
            ->assertJson([
                'message' => 'Conversation deleted successfully.',
            ]);
    
        $this->assertDatabaseMissing('conversations', [
            'id' => $conversation->id,
        ]);
    }

    public function test_deleting_a_conversation_also_deletes_its_messages_and_mistakes(): void
    {
        $user = User::factory()->create();
    
        $conversation = Conversation::factory()
            ->for($user)
            ->create();
    
        $message = $conversation->messages()->create([
            'role' => 'user',
            'content' => 'Hola, como estas?',
        ]);
    
        $mistake = $message->mistakes()->create([
            'conversation_id' => $conversation->id,
            'type' => 'grammar',
            'subtype' => 'verb_conjugation',
            'original_text' => 'como',
            'corrected_text' => 'cómo',
            'explanation' => 'The question requires an accent.',
            'severity' => 'low',
        ]);
    
        $this->actingAs($user)
            ->deleteJson("/api/conversations/{$conversation->id}")
            ->assertOk();
    
        $this->assertDatabaseMissing('conversations', [
            'id' => $conversation->id,
        ]);
    
        $this->assertDatabaseMissing('messages', [
            'id' => $message->id,
        ]);
    
        $this->assertDatabaseMissing('mistakes', [
            'id' => $mistake->id,
        ]);
    }
}