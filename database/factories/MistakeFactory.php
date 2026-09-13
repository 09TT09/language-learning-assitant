<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Mistake;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mistake>
 */
class MistakeFactory extends Factory
{
    protected $model = Mistake::class;

    public function definition(): array
    {
        $conversation = Conversation::factory();

        $message = Message::factory()->for($conversation);

        return [
            'conversation_id' => $conversation,
            'message_id' => $message,

            'type' => fake()->randomElement([
                'grammar',
                'vocabulary',
                'spelling',
                'word_order',
            ]),

            'original_text' => fake()->sentence(),
            'corrected_text' => fake()->sentence(),
            'explanation' => fake()->sentence(),

            'severity' => fake()->randomElement([
                'low',
                'medium',
                'high',
            ]),
        ];
    }
}