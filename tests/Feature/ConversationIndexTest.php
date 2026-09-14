<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_list_conversations(): void
    {
        $this->getJson('/api/conversations')
            ->assertUnauthorized();
    }

    public function test_authenticated_user_can_list_their_conversations(): void
    {
        $user = User::factory()->create();

        $conversation1 = Conversation::factory()
            ->for($user)
            ->create();

        $conversation2 = Conversation::factory()
            ->for($user)
            ->create();

        $this->actingAs($user)
            ->getJson('/api/conversations')
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment([
                'id' => $conversation1->id,
            ])
            ->assertJsonFragment([
                'id' => $conversation2->id,
            ]);
    }

    public function test_user_only_receives_their_own_conversations(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownConversation = Conversation::factory()
            ->for($user)
            ->create();

        $otherConversation = Conversation::factory()
            ->for($otherUser)
            ->create();

        $this->actingAs($user)
            ->getJson('/api/conversations')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'id' => $ownConversation->id,
            ])
            ->assertJsonMissing([
                'id' => $otherConversation->id,
            ]);
    }

    public function test_conversations_are_returned_newest_first(): void
    {
        $user = User::factory()->create();

        $oldConversation = Conversation::factory()
            ->for($user)
            ->create([
                'created_at' => now()->subMinutes(10),
            ]);

        $newConversation = Conversation::factory()
            ->for($user)
            ->create([
                'created_at' => now(),
            ]);

        $response = $this->actingAs($user)
            ->getJson('/api/conversations')
            ->assertOk();

        $response->assertJsonPath('0.id', $newConversation->id);
        $response->assertJsonPath('1.id', $oldConversation->id);
    }

    public function test_user_with_no_conversations_receives_an_empty_list(): void
    {
        $user = User::factory()->create();
    
        $this->actingAs($user)
            ->getJson('/api/conversations')
            ->assertOk()
            ->assertJsonCount(0)
            ->assertExactJson([]);
    }
}