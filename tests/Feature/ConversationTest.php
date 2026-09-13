<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Mistake;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationTest extends TestCase
{
    use RefreshDatabase;

    public function test_conversation_belongs_to_a_user(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->for($user)->create();

        $this->assertTrue($conversation->user->is($user));
        $this->assertTrue($user->conversations->contains($conversation));
    }

    public function test_conversation_has_many_messages(): void
    {
        $conversation = Conversation::factory()
            ->has(Message::factory()->count(3))
            ->create();

        $this->assertCount(3, $conversation->messages);
        $this->assertInstanceOf(Message::class, $conversation->messages->first());
    }

    public function test_conversation_has_many_mistakes(): void
    {
        $conversation = Conversation::factory()->create();

        $message = Message::factory()->create([
            'conversation_id' => $conversation->id,
        ]);

        Mistake::factory()->create([
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
        ]);

        $this->assertCount(1, $conversation->mistakes);
        $this->assertInstanceOf(Mistake::class, $conversation->mistakes->first());
    }

    public function test_message_belongs_to_conversation(): void
    {
        $conversation = Conversation::factory()->create();

        $message = Message::factory()->create([
            'conversation_id' => $conversation->id,
        ]);

        $this->assertTrue(
            $message->conversation->is($conversation)
        );
    }

    public function test_message_has_many_mistakes(): void
    {
        $conversation = Conversation::factory()->create();

        $message = Message::factory()->create([
            'conversation_id' => $conversation->id,
        ]);

        Mistake::factory()
            ->count(3)
            ->create([
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
            ]);

        $this->assertCount(3, $message->mistakes);
    }

    public function test_mistake_belongs_to_conversation(): void
    {
        $conversation = Conversation::factory()->create();

        $message = Message::factory()->create([
            'conversation_id' => $conversation->id,
        ]);

        $mistake = Mistake::factory()->create([
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
        ]);

        $this->assertTrue(
            $mistake->conversation->is($conversation)
        );
    }

    public function test_mistake_belongs_to_message(): void
    {
        $conversation = Conversation::factory()->create();

        $message = Message::factory()->create([
            'conversation_id' => $conversation->id,
        ]);

        $mistake = Mistake::factory()->create([
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
        ]);

        $this->assertTrue(
            $mistake->message->is($message)
        );
    }

    public function test_deleting_conversation_deletes_messages_and_mistakes(): void
    {
        $conversation = Conversation::factory()->create();

        $message = Message::factory()->create([
            'conversation_id' => $conversation->id,
        ]);

        $mistake = Mistake::factory()->create([
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
        ]);

        $conversation->delete();

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
