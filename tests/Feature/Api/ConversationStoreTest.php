<?php

namespace Tests\Feature\Api;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_create_a_conversation(): void
    {
        $this->postJson('/api/conversations')
            ->assertUnauthorized();

        $this->assertDatabaseCount('conversations', 0);
    }

    public function test_it_creates_a_conversation_with_defaults(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/conversations');

        $response
            ->assertCreated()
            ->assertJsonPath('language', 'es')
            ->assertJsonPath('level', 'A1')
            ->assertJsonPath('user_id', $user->id)
            ->assertJsonStructure([
                'id',
                'user_id',
                'language',
                'level',
                'created_at',
                'updated_at',
            ]);

        $this->assertDatabaseHas('conversations', [
            'id' => $response->json('id'),
            'user_id' => $user->id,
            'language' => 'es',
            'level' => 'A1',
        ]);
    }

    public function test_it_creates_a_conversation_with_a_given_level(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/conversations', [
            'language' => 'es',
            'level' => 'B1',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('language', 'es')
            ->assertJsonPath('level', 'B1')
            ->assertJsonPath('user_id', $user->id);

        $this->assertDatabaseHas('conversations', [
            'user_id' => $user->id,
            'language' => 'es',
            'level' => 'B1',
        ]);
        $this->assertSame(1, Conversation::query()->count());
    }

    public function test_it_rejects_an_invalid_level(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/conversations', [
                'level' => 'beginner',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['level']);

        $this->assertDatabaseCount('conversations', 0);
    }

    public function test_it_rejects_an_unsupported_language(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/conversations', [
                'language' => 'fr',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['language']);

        $this->assertDatabaseCount('conversations', 0);
    }

    public function test_it_accepts_all_supported_levels(): void
    {
        $user = User::factory()->create();
    
        foreach (['A1', 'A2', 'B1', 'B2', 'C1', 'C2'] as $level) {
            $response = $this->actingAs($user)
                ->postJson('/api/conversations', [
                    'language' => 'es',
                    'level' => $level,
                ]);
    
            $response
                ->assertCreated()
                ->assertJsonPath('level', $level);
        }
    
        $this->assertSame(6, Conversation::query()->count());
    }

    public function test_it_rejects_an_empty_language(): void
    {
        $user = User::factory()->create();
    
        $this->actingAs($user)
            ->postJson('/api/conversations', [
                'language' => '',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['language']);
    
        $this->assertDatabaseCount('conversations', 0);
    }

    public function test_it_rejects_an_empty_level(): void
    {
        $user = User::factory()->create();
    
        $this->actingAs($user)
            ->postJson('/api/conversations', [
                'level' => '',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['level']);
    
        $this->assertDatabaseCount('conversations', 0);
    }

    public function test_it_rejects_a_non_string_language(): void
    {
        $user = User::factory()->create();
    
        $this->actingAs($user)
            ->postJson('/api/conversations', [
                'language' => 123,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['language']);
    
        $this->assertDatabaseCount('conversations', 0);
    }

    public function test_it_rejects_a_non_string_level(): void
    {
        $user = User::factory()->create();
    
        $this->actingAs($user)
            ->postJson('/api/conversations', [
                'level' => 123,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['level']);
    
        $this->assertDatabaseCount('conversations', 0);
    }

    public function test_user_cannot_assign_a_conversation_to_another_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
    
        $response = $this->actingAs($user)
            ->postJson('/api/conversations', [
                'language' => 'es',
                'level' => 'A1',
                'user_id' => $otherUser->id,
            ]);
    
        $response
            ->assertCreated()
            ->assertJsonPath('user_id', $user->id);
    
        $this->assertDatabaseHas('conversations', [
            'id' => $response->json('id'),
            'user_id' => $user->id,
        ]);
    
        $this->assertDatabaseMissing('conversations', [
            'id' => $response->json('id'),
            'user_id' => $otherUser->id,
        ]);
    }
}
