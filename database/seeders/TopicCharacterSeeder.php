<?php

namespace Database\Seeders;

use App\Models\Topic;
use App\Models\TopicCharacter;
use Illuminate\Database\Seeder;

class TopicCharacterSeeder extends Seeder
{
    public function run(): void
    {
        $topic = Topic::where('slug', 'at-the-restaurant')->firstOrFail();

        $character = TopicCharacter::create([
            'topic_id' => $topic->id,
            'name' => 'Carlos',
            'role' => 'Waiter',
            'description' => 'Friendly, patient and helpful waiter working in a restaurant in Madrid.',
        ]);

        $character->steps()->attach(
            $topic->steps()->pluck('id')
        );
    }
}