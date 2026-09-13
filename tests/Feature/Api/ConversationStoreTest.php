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
}
