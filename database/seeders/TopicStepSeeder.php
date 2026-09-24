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
                'narrator' => 'You arrive at a restaurant in Madrid. A waiter comes to welcome you and asks how many people need a table.',
                'objective' => 'Ask for a table and say how many people it is for.',
            ],
            [
                'position' => 2,
                'title' => 'Choosing your meal',
                'narrator' => 'You are already seated at your table and have the menu. The waiter comes back to take your order.',
                'objective' => 'Order your meal.',
            ],
            [
                'position' => 3,
                'title' => 'Ordering a drink',
                'narrator' => 'The waiter has written down your meal and continues taking your order by asking what you would like to drink.',
                'objective' => 'Order a drink.',
            ],
            [
                'position' => 4,
                'title' => 'Eating',
                'narrator' => 'You have finished your meal. The waiter comes back to check whether everything was good.',
                'objective' => 'Talk briefly about your meal.',
            ],
            [
                'position' => 5,
                'title' => 'Asking for the bill',
                'narrator' => 'You have finished your meal and are ready to leave. The waiter is nearby.',
                'objective' => 'Ask the waiter for the bill.',
            ],
            [
                'position' => 6,
                'title' => 'Paying',
                'narrator' => 'The waiter brings the bill to your table and waits for you to pay.',
                'objective' => 'Pay the bill and say goodbye.',
            ],
        ];
        
        $topicSteps = [];
        
        foreach ($steps as $step) {
            $topicSteps[$step['position']] = $topic->steps()->create($step);
        }

        $topicSteps[2]->dependencies()->attach($topicSteps[1]->id);

        $topicSteps[3]->dependencies()->attach($topicSteps[1]->id);
        
        $topicSteps[4]->dependencies()->attach([
            $topicSteps[2]->id,
            $topicSteps[3]->id,
        ]);
        
        $topicSteps[5]->dependencies()->attach([
            $topicSteps[2]->id,
            $topicSteps[3]->id,
        ]);
        
        $topicSteps[6]->dependencies()->attach($topicSteps[5]->id);
    }
}