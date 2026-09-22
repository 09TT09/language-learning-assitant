```php
<?php

namespace Database\Factories;

use App\Enums\ConversationLevel;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Topic>
 */
class TopicFactory extends Factory
{
    protected $model = Topic::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->sentence(),
            'level' => ConversationLevel::A1,
            'scenario' => fake()->paragraph(),
            'vocabulary' => [
                fake()->word(),
                fake()->word(),
                fake()->word(),
            ],
            'is_active' => true,
        ];
    }
}
```
