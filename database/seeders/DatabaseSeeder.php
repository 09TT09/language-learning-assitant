<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\TopicSeeder;
use Database\Seeders\TopicStepSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call([
            TopicSeeder::class,
            TopicStepSeeder::class,
            TopicCharacterSeeder::class,
        ]);
    }
}