<?php

namespace Database\Seeders;

use App\Enums\ConversationLevel;
use App\Models\Topic;
use Illuminate\Database\Seeder;

class TopicSeeder extends Seeder
{
    public function run(): void
    {
        Topic::create([
            'title' => 'Introducing yourself',
            'slug' => 'introducing-yourself',
            'description' => 'Talk about your name, where you are from, and what you do.',
            'level' => ConversationLevel::A1,
            'scenario' => 'You are meeting someone for the first time. Introduce yourself and ask simple questions about the other person.',
            'vocabulary' => [
                'me llamo',
                'soy de',
                'vivo en',
                'trabajo',
                '¿Cómo te llamas?',
            ],
            'is_active' => true,
        ]);

        Topic::create([
            'title' => 'At the restaurant',
            'slug' => 'at-the-restaurant',
            'description' => 'Practice ordering food, asking questions, and asking for the bill.',
            'level' => ConversationLevel::A1,
            'scenario' => 'You are at a restaurant in Spain. Order a meal, ask about the food, and ask for the bill.',
            'vocabulary' => [
                'la carta',
                'quiero',
                'para mí',
                'agua',
                'la cuenta',
            ],
            'is_active' => true,
        ]);

        Topic::create([
            'title' => 'Daily routine',
            'slug' => 'daily-routine',
            'description' => 'Talk about what you do during a normal day.',
            'level' => ConversationLevel::A1,
            'scenario' => 'Talk with the tutor about your typical day, including when you wake up, work, eat, and go to bed.',
            'vocabulary' => [
                'me levanto',
                'desayuno',
                'trabajo',
                'almuerzo',
                'me acuesto',
            ],
            'is_active' => true,
        ]);

        Topic::create([
            'title' => 'Exploring a city',
            'slug' => 'exploring-a-city',
            'description' => 'Ask for directions and talk about places in a city.',
            'level' => ConversationLevel::A1,
            'scenario' => 'You are visiting a Spanish city. Ask for directions and talk about places you want to visit.',
            'vocabulary' => [
                '¿Dónde está?',
                'a la derecha',
                'a la izquierda',
                'cerca',
                'lejos',
            ],
            'is_active' => true,
        ]);

        Topic::create([
            'title' => 'Talking about your family',
            'slug' => 'talking-about-your-family',
            'description' => 'Talk about your family and the people close to you.',
            'level' => ConversationLevel::A1,
            'scenario' => 'Tell the tutor about your family and answer simple questions about the people in your life.',
            'vocabulary' => [
                'mi familia',
                'mi hermano',
                'mi hermana',
                'mis padres',
                'tengo',
            ],
            'is_active' => true,
        ]);
    }
}
