<?php

namespace Database\Seeders;

use App\Models\Topic;
use App\Models\TopicStep;
use Illuminate\Database\Seeder;

class TopicStepSeeder extends Seeder
{
    public function run(): void
    {
        $topic = Topic::where('slug', 'at-the-restaurant')->firstOrFail();

        $steps = [
            [
                'position' => 1,
                'title' => 'Arriving',
                'narrator' => 'You arrive at a restaurant in Madrid. A waiter comes to welcome you.',
                'objective' => 'Respond to the waiter and ask for a table.',
            ],
            [
                'position' => 2,
                'title' => 'Choosing your meal',
                'narrator' => 'You have been looking at the menu for two minutes. The waiter comes back to your table.',
                'objective' => 'Order your meal.',
            ],
            [
                'position' => 3,
                'title' => 'Ordering a drink',
                'narrator' => 'The waiter writes down your meal and asks what you would like to drink.',
                'objective' => 'Order a drink.',
            ],
            [
                'position' => 4,
                'title' => 'Eating',
                'narrator' => 'You have finished your meal. The waiter comes back to check if everything was good.',
                'objective' => 'Respond to the waiter and talk briefly about your meal.',
            ],
            [
                'position' => 5,
                'title' => 'Asking for the bill',
                'narrator' => 'You have finished your meal and are ready to leave.',
                'objective' => 'Ask the waiter for the bill.',
            ],
            [
                'position' => 6,
                'title' => 'Paying',
                'narrator' => 'The waiter brings the bill to your table.',
                'objective' => 'Pay the bill and say goodbye.',
            ],
        ];

        foreach ($steps as $step) {
            TopicStep::create([
                'topic_id' => $topic->id,
                ...$step,
            ]);
        }
    }
}